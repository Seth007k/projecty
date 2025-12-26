import React from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/Start.css';

export default function Startbildschirm() {
    const navigate = useNavigate();

    const handleStart = () => {
        navigate('/login');
    };

    return ( <div className="Startbildschirm">
        <div className="startbildschirmcontainer"> 
            <h1 className="titel">Tells from the Homeoffice</h1>
            <button className="start_button" onClick={handleStart}>Start</button>
        </div>
    </div>)
}