import React from 'react'; // brauch für damit <main> zb verstanden wird
import {useNavigate} from 'react-router-dom'; // hook
import '../styles/menue.css';
import Menuebildschirm from '../images/Menuebildschirm.png'; //bildimport

export default function Menue() {
    const weiterleitung = useNavigate(); // weiterleitung

    //aufbau der seite menü mit bild und login bzw registrierungs buttons
    return (
        <main className='login_menue_hintergrund_container'style={{
                backgroundImage: `url(${Menuebildschirm})`,
                backgroundSize: "cover",
                backgroundPosition: "center",
                backgroundRepeat: "no-repeat",
                height: "100vh",
                width: "100vw",
                display: "flex",
                flexDirection: 'column',
                justifyContent: "center",
                alignItems: "center",
                margin: 0,
                padding: 0
              }}>
            <section className='login_menue_main' aria-label="Hauptmenü">
                <header>
                    <h1 className='login_menue_title'>Willkommen</h1>
                    <p>Bitte Registrieren Sie sich, oder loggen sich ein, um das Spiel zu starten</p>
                </header>
                <nav>
                    <button className='login_menue_button' onClick={() => weiterleitung('/login')} aria-label="Einloggen">
                        Einloggen
                    </button>
                    <button className='login_menue_button' onClick={() => weiterleitung('/register')} aria-label="Registrieren">
                        Registrieren
                    </button>
                </nav>
            </section>
        </main>
    )
}