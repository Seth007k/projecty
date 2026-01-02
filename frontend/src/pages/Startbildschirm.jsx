import React from "react";
import { useNavigate } from "react-router-dom";
import "../styles/start.css";
import startbildschirm from "../images/Startbildschirm.png";

export default function Startbildschirm() {
  const weiterleitung = useNavigate();

  const handleStart = () => {
    weiterleitung("/menue");
  };

  return (
    <div
      className="Startbildschirm"
      style={{
        backgroundImage: `url(${startbildschirm})`,
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
      }}
    >
      <div className="startbildschirmcontainer">
        <button className="start_button" onClick={handleStart}>
          Start
        </button>
      </div>
    </div>
  );
}
