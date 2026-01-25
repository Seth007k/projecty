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
          setGegnerListe(spielDaten.gegner || []);
          setAusgabe(spielDaten.ausgabe || "");
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
  }, [charakterId, spielerId, weiterleitung]);

  const handleAngriff = async () => {
    if (!spiel || gameOver || !charakter) return;

    try {
      const angriffDaten = await spielerAngriff(spiel.id, charakterId);

      setCharakter(angriffDaten.spieler || charakter);
      setSpiel({
          ...spiel,
          aktuelle_runde: angriffDaten.spiel?.aktuelle_runde || spiel.aktuelle_runde,
          punkte: angriffDaten.spiel?.punkte || spiel.punkte,
          schwierigkeit: angriffDaten.spiel?.schwierigkeit || spiel.schwierigkeit,
        });
        setGegnerListe(angriffDaten.gegner || []);
        setAusgabe(angriffDaten.ausgabe || "");

        if(angriffDaten.spieler?.leben <= 0) {
          setGameOver(true);
        }

        if(angriffDaten.gegner && angriffDaten.gegner.every((g) => g.leben <= 0) && angriffDaten.spiel?.aktuelle_runde === 4) {
          setGewonnen(true);
        }
    } catch(e) {
      console.error("Feheler beim Angriff:".e);
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
      setAusgabe(nochmalSpielenDaten.ausgabe || "");
      setGameOver(false);
      setGewonnen(false);
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
      <div className="punkte_bar">
        <p>Highscore: {spiel.punkte}</p>
      </div>
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
            <button className="button" onClick={handleAngriff}>
              Angriff
            </button>
            <button
              className="button"
              onClick={() => weiterleitung("/charakterauswahl")}
            >
              Beenden
            </button>
          </div>
        )}

        {gameOver && (
          <div className="game_over_container">
            <p>{ausgabe}</p>
            <button className="button" onClick={() => weiterleitung("/charakterauswahl")}>
              Zur Charakterauswahl
            </button>
            <button className="button" onClick={handleNochmalSpielen}>Nochmal spielen</button>
          </div>
        )}

        {gewonnen && (
          <div className="gewonnen_container">
            <p>{ausgabe}</p>
            <button className="button" onClick={handleNochmalSpielen}>Nochmal spielen</button>
            <button className="button" onClick={() => weiterleitung("/charakterauswahl")}>
              Beenden
            </button>
          </div>
        )}

        {!gameOver && !gewonnen && ausgabe && <div className="ausgabe">{ausgabe}</div>}
      </div>
    </div>
  );
}
