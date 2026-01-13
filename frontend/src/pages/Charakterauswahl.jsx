import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import "../styles/charakterauswahl.css";
import {
  ladeCharaktere,
  erstelleCharakter,
  loescheCharakter,
} from "../services/charakterService";

export default function Charakterauswahl() {
  const weiterleitung = useNavigate();

  const [charaktere, setCharaktere] = useState(null);
  const [charakter, setCharakter] = useState(null);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState("");
  const spielerId = localStorage.getItem("benutzer_id");

  const ladeCharakter = async () => {
    try {
      const charakterDaten = await ladeCharaktere();
      if (charakterDaten.erfolg) {
        setCharaktere(charakterDaten.charakterauswahl);
        if (charakterDaten.charakterauswahl.length > 0) {
          setCharakter(charakterDaten.charakterauswahl[0]);
        }
      }
    } catch (e) {
      console.error("Fehler beim Laden der Charaktere:", e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    ladeCharakter();
  }, []);

  const handleCharakterErstellen = async () => {
    if (name === "") {
      alert("Bitte gib deinem Charakter einen Namen: ");
      return;
    }

    try {
      const neuerCharakter = await erstelleCharakter(name);
      if (neuerCharakter.erfolg) {
        setCharaktere((prev) => [...prev, neuerCharakter.charakter]);
        setCharakter(neuerCharakter.charakter);
        setName("");
      } else {
        alert(neuerCharakter.fehler);
      }
    } catch (e) {
      console.error("Fehler beim Erstellen:", e);
    }
  };

  const handleWeiterspielen = () => {
    if (!charakter || !charakter.id) {
      alert("Bitte wähle zuerst einen Charakter aus!");
      return;
    }
    const spielerId = localStorage.getItem('benutzer_id');
    if(!spielerId) {
      console.log("Kein Spieler eingeloggt");
      return;
    }
    
    weiterleitung(`/Spiel/${spielerId}/${charakter.id}`);
  };

  const handleCharakterLoeschen = async () => {
    if (!charakter) return;
    const bestaetigung = window.confirm(
      `Willst du den Charakter wirklich löschen?`
    );

    if (!bestaetigung) return;

    const antwort = await loescheCharakter(charakter.id);
    if (antwort.erfolg) {
      setCharakter(null);
      setCharaktere((prev) => prev.filter((c) => c.id !== charakter.id));
    } else {
      alert(antwort.fehler || "charakter konnte nicht gelöscht werden");
    }
  };

  const handleCharakterWahlen= (id) => {
    if(!id) {
      console.error("Kein Char ausgeweählt");
      return;
    }
    weiterleitung(`/Spiel/${spielerId}/${id}`);
  }

  if (loading) return <p>Lade Charakter..</p>;

  return (
    <main className="charakterauswahl_hintergrund_container">
      <section className="charakterauswahl_section">
        <h1>Charakterauswahl</h1>

        {charaktere.length > 0 ? (
          <>
            <p>
              Aktueller Charakter: <strong>{charakter?.name}</strong>
            </p>

            <ul className="charakter_liste">
              {charaktere.map((c) => (
                <li key={c.id} className={`charakter_status ${charakter?.id === c.id ? "aktiv" : ""}`} onClick={() => setCharakter(c)}>
                  <div className="charakter_bild_wrapper">
                  <img
                    src={c.bild}
                    alt={c.name}
                    className="charakter_bild"
                  />
                  </div>

                  <div className="charakter_info">
                    <h3>{c.name}</h3>
                    <p>Level: {c.level}</p>
                    <p>Leben: {c.leben}</p>
                    <p>Angriff: {c.angriff}</p>
                    <p>Verteidigung: {c.verteidigung}</p>
                  </div>
                </li>
              ))}
            </ul>

            <div>
              <button
                className="charakter_button"
                onClick={handleWeiterspielen}
                
              >
                Weiterspielen
              </button>
              <button
                className="charakter_button"
                onClick={handleCharakterLoeschen}
                style={{ marginLeft: "10px" }}
              >
                Charakter löschen
              </button>
            </div>
          </>
        ) : (
          <p>Noch kein Charakter vorhanden</p>
        )}

        <div>
          <input name="charName_input"
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Name eingeben"
          />
          <button
            className="charakter_button"
            onClick={handleCharakterErstellen}
          >
            Charakter erstellen
          </button>
        </div>
      </section>
    </main>
  );
}
