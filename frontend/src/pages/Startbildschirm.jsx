import React from "react";
import { useNavigate } from "react-router-dom";
import "../styles/start.css";


export default function Startbildschirm() {
  const weiterleitung = useNavigate();

  const handleStart = () => {
    weiterleitung("/menue");
  };

  return (
    <div
      className="Startbildschirm"
    >
      <div className="start_button_container">
        <button className="start_button" onClick={handleStart}>
          Start
        </button>
      </div>
    </div>
  );
}
