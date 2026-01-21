import React, { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import {
  ladeSpielUndCharakter,
  spielerAngriff,
  nochmalSpielen as nochmalSpielenService,
} from "../services/spielService";
import "../styles/spiel.css";

export default function Spiel() {
  const { spielerId, charakterId } = useParams();
  const [loading, setLoading] = useState(true);
  const [spiel, setSpiel] = useState(null);
  const [charakter, setCharakter] = useState(null);
  const [gegnerListe, setGegnerListe] = useState([]);
  const [gameOver, setGameOver] = useState(false);
  const weiterleitung = useNavigate();
  const [ausgabe, setAusgabe] = useState("");
  const [gewonnen, setGewonnen] = useState(false);

  useEffect(() => {
    console.log(spielerId, charakterId);
    if (!spielerId || !charakterId) {
      weiterleitung("/charakterauswahl");
      return;
    }

    let abgebrochen = false;

    const ladeSpiel = async () => {
      setLoading(true);
      try {
        const spielDaten = await ladeSpielUndCharakter(charakterId);
        if (!spielDaten.erfolg) {
          console.error(spielDaten.fehler);
          return;
        }

        if (!abgebrochen) {
          setSpiel(spielDaten.spiel);
          setCharakter(spielDaten.charakter);
          const gegnerListe = spielDaten.spiel.gegner_status
            ? JSON.parse(spielDaten.spiel.gegner_status)
            : [];
          setGegnerListe(gegnerListe);
        }
      } catch (e) {
        console.error("Fehler beim Laden des Spiels:", e);
      } finally {
        setLoading(false);
      }
    };

    ladeSpiel();
    return () => {
      abgebrochen = true;
    };
  }, [charakterId, spielerId]);

  const handleAngriff = async () => {
    if (!spiel || gameOver || !charakter) return;

    try {
      const angriffDaten = await spielerAngriff(spiel.id, charakterId);

      if (angriffDaten.gegner) {
        setGegnerListe(angriffDaten.gegner);
      }

      let neueAusgabe = angriffDaten.ausgabe || "";

      if (angriffDaten.spieler) {
        setCharakter(angriffDaten.spieler);
        if (angriffDaten.spieler.leben <= 0) {
          setGameOver(true);
          setAusgabe((angriffDaten.ausgabe || "") + "\nDu wurdest besiegt!");
        }
      } else {
        setGameOver(true);
        neueAusgabe = "Dein Charakter wurde besiegt! Erstelle einen neuen und versuche deinen Highscore zu knacken!";
      }
      
      setAusgabe(neueAusgabe);

      if (angriffDaten.spiel) {
        setSpiel({
          ...spiel,
          aktuelle_runde: angriffDaten.spiel.aktuelle_runde,
          punkte: angriffDaten.spiel.punkte,
        });
      }

      if (angriffDaten.spiel && angriffDaten.gegner) {
        const alleGegnerTot = angriffDaten.gegner.every((g) => g.leben <= 0);
        if (alleGegnerTot && angriffDaten.spiel.aktuelle_runde === 4) {
          setGewonnen(true);
        }
      }
    } catch (e) {
      console.error("Fehler beim Angriff:", e);
    }
  };

  const handleNochmalSpielen = async () => {
    if (!spiel) return;

    try {
      const nochmalSpielenDaten = await nochmalSpielenService(
        spielerId,
        charakterId,
        spiel.id,
      );

      setCharakter(nochmalSpielenDaten.charakter);
      setGegnerListe(nochmalSpielenDaten.gegner || []);
      setSpiel({
        ...spiel,
        schwierigkeit: nochmalSpielenDaten.schwierigkeit,
        aktuelle_runde: nochmalSpielenDaten.aktuelle_runde,
      });
      setGameOver(false);
      setGewonnen(false);
      setAusgabe(nochmalSpielenDaten.hinweis);
    } catch (e) {
      console.error("Fehler beim neustarten des Spiels:", e);
    }
  };

  if (loading) return <div className="loading">Lade Spiel...</div>;

  if (!charakter || !spiel) {
    return <div className="loading"> Charakter oder Spiel nicht gefunden!</div>;
  }

  return (
    <div className="spiel_container">
      <div className="kampffeld">
        <div className="charakter">
          <img
            src={`/assets/${charakter.bild || "charakter.png"}`}
            alt={charakter.name}
            className="charakter_bild"
          />
          <div className="info">
            <h3>{charakter.name}</h3>
            <p>Leben: {charakter.leben}</p>
            <p>Angriff: {charakter.angriff}</p>
            <p>Verteidigung: {charakter.verteidigung}</p>
            <p>Level: {charakter.level}</p>
          </div>
        </div>

        <div className="gegner_liste">
          {gegnerListe.map((gegner, index) => {
            const istBoss = gegner.name.toLowerCase().includes("boss");
            const bild = istBoss
              ? "/assets/boss.png"
              : `/assets/${gegner.bild || "gegner.png"}`;

            return (
              <div key={index} className="gegner">
                <img
                  src={bild}
                  alt={gegner.name}
                  className={`gegner_bild ${istBoss ? "boss_bild" : ""}`}
                />
                <div className="info">
                  <h3>{gegner.name}</h3>
                  <p>Leben: {gegner.leben}</p>
                  <p>Angriff: {gegner.angriff}</p>
                  <p>Verteidigung: {gegner.verteidigung}</p>
                </div>
              </div>
            );
          })}
          ;
        </div>
      </div>

      <div className="action_bar">
        {!gameOver && !gewonnen && (
          <div className="action_buttons">
            <button className="angriff_button" onClick={handleAngriff}>
              Angriff
            </button>
            <button
              className="beenden_button"
              onClick={() => weiterleitung("/charakterauswahl")}
            >
              Beenden
            </button>
          </div>
        )}

        {gameOver && (
          <div className="game_over_container">
            <p>Du wurdest besiegt!</p>
            <button onClick={() => weiterleitung("/charakterauswahl")}>
              Zur Charakterauswahl
            </button>
            <button onClick={handleNochmalSpielen}>Nochmal spielen</button>
          </div>
        )}

        {gewonnen && (
          <div>
            <p>Gklückwunsch! Du hast den Boss besiegt!</p>
            <button onClick={handleNochmalSpielen}>Nochmal spielen</button>
            <button onClick={() => weiterleitung("/charakterauswahl")}>
              Beenden
            </button>
          </div>
        )}

        

        {ausgabe && <div className="ausgabe">{ausgabe}</div>}

        
      </div>
    </div>
  );
}
