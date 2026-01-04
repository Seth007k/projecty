import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import  "../styles/charakterauswahl.css";

export default function Charakterauswahl() {
  const weiterleitung = useNavigate();
  const [charakter, setCharakter] = useState(null);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState("");

  useEffect(() => {
    fetch("http://localhost:8080/charakter.php", {
      method: "GET",
      credentials: "include",
    })
      .then((response) => response.json())
      .then((charakterDaten) => {
        if (
          charakterDaten.erfolg &&
          charakterDaten.charakterauswahl.length > 0
        ) {
          setCharakter(charakterDaten.charakterauswahl[0]); // erster Char
        }
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  const handleCharakterErstellen = async () => {
    if (name === "") {
      alert("Bitte gib deinem Charakter einen Namen: ");
      return;
    }

    fetch("http://localhost:8080/charakter.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ name }),
    })
      .then((response) => response.json())
      .then((charakterDaten) => {
        if (charakterDaten.erfolg) {
          setCharakter({ id: charakterDaten.id, name });
        } else {
          alert(
            charakterDaten.fehler || "Fehler beim Erstellen des Charakters!"
          );
        }
      });
  };

  const handleWeiterspielen = () => {
    weiterleitung("/spiel");
  };

  if (loading) return <p>Lade Charakter..</p>;

  return (
    <main className="charakterauswahl_hintergrund_container">

        <section className="charakterauswahl_section">
            {charakter ? (
                <>
                <h1>Charakterauswahl</h1>
                <p>Dein Charakter: <strong>{charakter.name}</strong></p>
                <button className="charakter_button" onClick={handleWeiterspielen}>
                     Weiterspielen
                 </button>
                </>
            ) : (
                <>
                <h1>Charakterauswahl</h1>
                <input type="text" value={name} onChange={(e) => setName(e.target.value)} placeholder="Name eingeben"/>
                <button className="charakter_button" onClick={handleCharakterErstellen}>Charakter erstellen</button>
                </>
            )}
        </section>
    </main>
  );
}