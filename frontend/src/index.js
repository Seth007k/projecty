import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './styles/index.css';

const root = ReactDOM.createRoot(document.getElementById('root')); // sucht im "echten" html nach <div id="root"></div> in der index.html. createRoot erstellt react root // root ist die verbindung zwischen react & dom
root.render( // startet rendern ab hier übernimmt react kontrolle über root
    <React.StrictMode> {/* hilft fehler zu erkennen(für mich als DEV) und rendert dann die App komponente Merke: html liefert hülle react leifert inhalt*/}
        <App></App>
    </React.StrictMode>
)
