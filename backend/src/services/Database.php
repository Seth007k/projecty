<?php 

function getDB() { //getDB kann aufgerufen werden um eine verbindung zur datenbank zu bekommen
    static $mysqli;

    if (isset($mysqli)) { // existiert vie Verbindung bereits? wenn ja - reutrn; wen nein - erstelle neu
        return $mysqli;
    }

    $env = require __DIR__ . '/../config/env.php'; // lädt env.php und speichert in $env ; __DIR__ ist der ordner von database.php 

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); //Fehleraktivierung zum debuggen

    $mysqli = new mysqli( //new msyqli(..) = verbindung php mit mysql 
        $config['DB_HOST'],
        $config['DB_USER'],
        $config['DB_PASS'],
        $config['DB_NAME'],
    );

    $mysqli->set_charset('utf8mb4'); //benötige korrekten zeichensatz

    return $mysqli; // gibt die fertige db verbdinung zuruück

}