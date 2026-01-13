import React from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Startbildschirm from "./pages/Startbildschirm";
import Menue from "./pages/Menue";
import Charakterauswahl from "./pages/Charakterauswahl";
import Spiel from "./pages/Spiel";


function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Startbildschirm />} />
        <Route path="/menue" element={<Menue />} />
        <Route path="/charakterauswahl" element={<Charakterauswahl/>} />
        <Route path="/spiel/:spielerId/:charakterId" element={<Spiel/>} />
        
      </Routes>
    </Router>
  );
}

export default App;
