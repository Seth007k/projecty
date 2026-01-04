import React from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Startbildschirm from "./pages/Startbildschirm";
import Menue from "./pages/Menue";
import Charakterauswahl from "./pages/Charakterauswahl";

function Login() {
  return (
    <div style={{ color: "white", textAlign: "center", marginTop: "40vh" }}>
      <h1>Login-Seite</h1>
    </div>
  );
}

function Registrieren() {
  return (
    <div style={{ color: "white", textAlign: "center", marginTop: "40vh" }}>
      <h1> Registrier-Seite</h1>
    </div>
  );
}
function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Startbildschirm />} />
        <Route path="/menue" element={<Menue />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Registrieren />} />
        <Route path="/charakterauswahl" element={<Charakterauswahl/>} />
        {/* Hier kommt nachher die login router rein */}
      </Routes>
    </Router>
  );
}

export default App;
