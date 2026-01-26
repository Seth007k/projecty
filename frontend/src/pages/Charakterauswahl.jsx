import React, { useEffect, useState } from "react"; //  importiert React und 2 hooks useState für komponenten states und useEffect für seiteneffekte wie datan laden
import { useNavigate } from "react-router-dom"; // brauch ich für weiterleitung  bzw nevigation zu anderen routen
import "../styles/charakterauswahl.css"; // für style
import {
  ladeCharaktere,
  erstelleCharakter,
  loescheCharakter,
} from "../services/charakterService"; // functionen die ich hier brauche

export default function Charakterauswahl() {
  //benötigte konstanten
  const weiterleitung = useNavigate();
  const [charaktere, setCharaktere] = useState(null);
  const [charakter, setCharakter] = useState(null);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState("");
  const spielerId = localStorage.getItem("benutzer_id"); // holt eingeloggten user aus localStorge für navigation; unsafe prüfen

  const ladeCharakter = async () => { // asynchrone funktion zum laden des charakters 
    try {
      const charakterDaten = await ladeCharaktere(); // API aufruf ladeCharaktere, await = warte auf ergebnis
      if (charakterDaten.erfolg) { //prüft ob API call erfolgreich war
        setCharaktere(charakterDaten.charakterauswahl);//wenn ja setze state auf alle chars
        if (charakterDaten.charakterauswahl.length > 0) { // wenn charaktrere existieren wähle den ersten als aktiv aus
          setCharakter(charakterDaten.charakterauswahl[0]);
        }
      }
    } catch (e) {
      console.error("Fehler beim Laden der Charaktere:", e); // fehlerausgabe 
    } finally { // ladezustand = false
      setLoading(false);
    }
  };

  useEffect(() => { // hook der einmal nach der initialisierung ausgeführt wird, lädt charaktere beim ersten rendern
    ladeCharakter();
  }, []);

  const handleCharakterErstellen = async () => { // prüft ob name eingegeben wurde wenn leer dann abbruch und alert
    if (name === "") {
      alert("Bitte gib deinem Charakter einen Namen: ");
      return;
    }

    try {
      const neuerCharakter = await erstelleCharakter(name);//ApI call neue charakter wird erstellt
      if (neuerCharakter.erfolg) {//wenn erfolgreich erstellt dann füge neuen charakter der liste zu setze ihn als aktiv, resette den input name, ansonsten fehleralert
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

  //prüft ob charakter aktiv ausgewählt wurde sonst alert 
  const handleWeiterspielen = () => {
    if (!charakter || !charakter.id) {
      alert("Bitte wähle zuerst einen Charakter aus!");
      return;
    }
    //prüft ob spieler eingeloggt ist, sonst keine weiterleitung
    const spielerId = localStorage.getItem('benutzer_id');
    if(!spielerId) {
      console.log("Kein Spieler eingeloggt");
      return;
    }
    //navigiert zu spielroute mit dopieler und charakterid
    weiterleitung(`/Spiel/${spielerId}/${charakter.id}`);
  };
  //pürft ob ein charakter ausgerwählt wurde, wenn ja bist du sicher? abfrage
  const handleCharakterLoeschen = async () => {
    if (!charakter) return;
    const bestaetigung = window.confirm( // besser eigenes popup
      `Willst du den Charakter wirklich löschen?`
    );
    if (!bestaetigung) return;

    //API call - charakter löschen, wenn erfolgreich dann state updaten andernfalls alertfehler
    const antwort = await loescheCharakter(charakter.id);
    if (antwort.erfolg) {
      setCharakter(null);
      setCharaktere((prev) => prev.filter((c) => c.id !== charakter.id));
    } else {
      alert(antwort.fehler || "charakter konnte nicht gelöscht werden");
    }
  };

  //während loading = true kommt die einfache anzeige:
  if (loading) return <p>Lade Charakter..</p>;

  //html return 
  return (
    <main className="charakterauswahl_hintergrund_container">
      <section className="charakterauswahl_section">
        <h1>Charakterauswahl</h1>
        {/* wenn charaktere existieren zeige aktiven charakter*/}
        {charaktere.length > 0 ? (
          <>
            <p>
              Aktueller Charakter: <strong>{charakter?.name}</strong>
            </p>
            {/* liste aller charakter, klick aktiver charakter, aktiv = css klasse / dann kommt das Bild des charakters mit alt text für barrierereiheit darunter dann die charakter stats dann kommen die buttons*/}
            <ul className="charakter_liste">
              {charaktere.map((c) => (
                <li key={c.id} className={`charakter_status ${charakter?.id === c.id ? "aktiv" : ""}`} onClick={() => setCharakter(c)}> {/* ist dieser charakter der ausgewählöte charakter? + arrow funktion wird erst beim klick ausgeführt daher onClick={() => setCharakter(c)}*/}
                  <div className="charakter_bild_wrapper"> {/* jeder li bekommt eigene id (key) bein redner, und beim klciken: setCharrakter = setzt aktiven charakter, charakter id ändert sich , react rendert neu, nur dieses li bekommt aktiv, UI kann aktiven Char anzeigen*/}
                  <img
                    src={`/assets/${c.bild || "charakter.png"}`}
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

            <div className="charakter_button_container">
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
        )} {/*Falls keine charakter existieren fallback ausgabe */}

        <div className="charakter_erstellen_container">

          {charaktere.length > 0 && <p>Du kannst weitere Charaktere erstellen</p>}

          <input name="charName_input"
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Name eingeben"
          /> {/*Input und button für charakter erstellen */}
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
