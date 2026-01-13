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

      if (angriffDaten.game_over) {
        setGameOver(true);
        setCharakter(angriffDaten);
      } else {
        setGegnerListe(
          angriffDaten.gegner_status
            ? JSON.parse(angriffDaten.gegner_status)
            : []
        );
        setCharakter(angriffDaten.charakter || charakter);
        setSpiel({
          ...spiel,
          üpunkte: angriffDaten.punkte,
          aktuelle_runde: angriffDaten.aktuelle_runde,
        });
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

  if (gameOver)
    return (
      <div className="game_over">
        <h2>Game Over!</h2>
        <button onClick={() => weiterleitung("/Menue")}>
          zurück zum Menü!
        </button>
      </div>
    );

  return (
    <div className="spiel_container">
      <div className="spielfeld">
        <div className="charakter_info">
          <img
            src={`/assets/${charakter.bild}`}
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

        <div className="gegner_info">
          {gegnerListe.map((gegner, index) => (
            <div key={index} className="gegner">
              <img
                src={`/assets/${gegner.bild || "gegner.png"}`}
                alt={gegner.name}
                className="gegner_bild"
              />
              <div className="gegner_info">
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
    </div>
  );
}
