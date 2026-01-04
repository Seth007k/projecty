import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { login, registrieren } from "../services/authService.js";
import "../styles/menue.css";

export default function Menue() {
  const weiterleitung = useNavigate();

  const [benutzername, setBenutzername] = useState('');
  const [passwort, setPasswort] = useState('');

  //Login
  const handleLogin = async (e) => {
    e.preventDefault();
    const loginDaten = await login(benutzername, passwort);

    if (loginDaten.erfolg) {
      weiterleitung("/charakterauswahl");
    } else {
      alert(loginDaten.fehler);
    }
  };

  //Registrieren
  const handleRegister = async () => {
    const registerDaten = await registrieren(benutzername, passwort);

    if (registerDaten.erfolg) {
      weiterleitung("/charakterauswahl");
    } else {
      alert(registerDaten.fehler);
    }
  };

  return (
    <main className="menue_hintergrund_container">
      <section className="menue_main" aria-label="Hauptmenü">
        <header>
          <h1 className="menue_title">Hauptmenü</h1>
          <p>
            Bitte Registrieren Sie sich, oder loggen sich ein, um das Spiel zu
            starten
          </p>
        </header>
        <form className="benutzerdaten">
          <label htmlFor="username">
            <b>Benutzername</b>
          </label>
          <input
            type="text"
            placeholder="Benutzername eingeben"
            id="username"
            value={benutzername}
            onChange={(e) => setBenutzername(e.target.value)}
            required
          ></input>

          <label htmlFor="password">
            <b>Passwort</b>
          </label>
          <input
            type="password"
            placeholder="Passwort eingeben"
            id="password"
            value={passwort}
            onChange={(e) => setPasswort(e.target.value)}
            required
          ></input>
          <button type="submit" className="menue_button" onClick={handleLogin}>
            Einloggen
          </button>
          <button
            type="button"
            className="menue_button"
            onClick={handleRegister}
          >
            Registrieren
          </button>
        </form>
      </section>
    </main>
  );
}
