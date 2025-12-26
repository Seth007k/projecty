import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Startbildschirm from './pages/Startbildschirm';

function App() {
    return ( 
        <Router>
            <Routes>
                <Route path="/" element={<Startbildschirm />} />
                {/* Hier kommt nachher die login router rein */}
            </Routes>
        </Router>
    )
}

export default App;