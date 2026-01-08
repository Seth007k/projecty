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
      alert("Fehler beim Estellen des Charakters!");
    }
  };

  const handleWeiterspielen = (id) => {
    if (!charakter) {
      alert("Bitte wähle zuerst einen Charakter aus!");
      return;
    }
    weiterleitung("/spiel");
  };

  const handleCharakterLoeschen = () => {
    if (!charakter) return;
    const bestaetigung = window.confirm(
      `Willst du den Charakter wirklich löschen?`
    );

    if (!bestaetigung) return;

    const antwort = loescheCharakter(charakter.id);
    if (antwort.erfolg) {
      setCharakter(null);
      setCharaktere((prev) => prev.filter((c) => c.id !== charakter_id));
    } else {
      alert(antwort.fehler || "charakter konnte nicht gelöscht werden");
    }
  };

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
                <li key={c.id} className="charakter_status">
                  <img
                    src={c.bild}
                    alt={c.name}
                    className="charakter_bild"
                  />
                  <button onClick={() => setCharakter(c)}>{c.name}</button>
                </li>
              ))}
            </ul>

            <div style={{ marginTop: "10px" }}>
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

        <div style={{ marginTop: "20px" }}>
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
