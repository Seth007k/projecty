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
    if (!spiel || gameOver) return;

    try {
      const angriffDaten = await spielerAngriff(spiel.id, charakterId);

      setGegnerListe(angriffDaten.gegner || []);
      setCharakter(angriffDaten.spieler || charakter);
      setSpiel({
        ...spiel,
        aktuelle_runde: angriffDaten.spiel.aktuelle_runde,
        punkte: angriffDaten.spiel.punkte,
      });

      setAusgabe(angriffDaten.ausgabe || "");

      const bossBesiegt =
        angriffDaten.gegner.every((g) => g.leben === 0) &&
        angriffDaten.spiel.aktuelle_runde === 4;

      if (bossBesiegt) {
        setGewonnen(true);
      }
      if (angriffDaten.charakter.leben <= 0) {
        setGameOver(true);
      }
    } catch (e) {
      console.error("Fehler beim Angriff:", e);
    }
  };

  const handleNochmalSpielen = async () => {
    if (!spiel) return;

    try {
      const nochmalSpielen = await nochmalSpielenService(
        spielerId,
        charakterId,
        spiel.id
      );

      setGegnerListe(nochmalSpielen.gegner);
      setGameOver(false);
      setSpiel({
        ...spiel,
        schwierigkeit: nochmalSpielen.schwierigkeit,
        aktuelle_runde: 1,
      });
    } catch (e) {
      console.error("Fehler beim neustarten des Spiels:", e);
    }
  };

  if (loading) return <div className="loading">Lade Spiel...</div>;

  if (!charakter || !spiel) {
    return <div className="loading"> Charakter oder Spiel nicht gefunden!</div>;
  }

  if (gameOver) {
    return (
      <div className="spiel_container">
        <h2> Verloren!</h2>
        <button onClick={() => weiterleitung("/charakterauswahl")}>
          Beenden
        </button>
      </div>
    );
  }

  if (gewonnen) {
    return (
      <div className="spiel_container">
        <h2>Gewonnen!</h2>
        <button onClick={handleNochmalSpielen}>Nochmal spielen!</button>
        <button onClick={() => weiterleitung("/charakterauswahl")}>
          Beenden
        </button>
      </div>
    );
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
          {gegnerListe.map((gegner, index) => (
            <div key={index} className="gegner">
              <img
                src={`/assets/${gegner.bild || "gegner.png"}`}
                alt={gegner.name}
                className="gegner_bild"
              />
              <div className="info">
                <h3>{gegner.name}</h3>
                <p>Leben: {gegner.leben}</p>
                <p>Angriff: {gegner.angriff}</p>
                <p>Verteidigung: {gegner.verteidigung}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
      <div className="action_bar">
        {!gameOver && !gewonnen && (
          <>
            <button className="angriff_button" onClick={handleAngriff}>
              Angriff
            </button>
            <button
              className="beenden_button"
              onClick={() => weiterleitung("/charakterauswahl")}
            >
              Beenden
            </button>
          </>
        )}

        {gameOver && (
          <div>
            <p>Du wurdes besiegt!</p>
            <button onClick={() => weiterleitung("/charakterauswahl")}>
              Beenden
            </button>
            "<button onClick={handleNochmalSpielen}>Nochmal spielen</button>
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
