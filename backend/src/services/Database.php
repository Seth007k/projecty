<?php

function getDB()
{ //getDB kann aufgerufen werden um eine verbindung zur datenbank zu bekommen
    static $verbindung;

    if (isset($verbindung)) { // existiert vie Verbindung bereits? wenn ja - reutrn, sonst
        return $verbindung;
    }

    $config = require __DIR__ . '/../../config/env.php'; // lädt env.php und speichert in $env ;

    try {
        $verbindung = new mysqli( //new msyqli(..) = verbindung php mit mysql 
            $config['DB_HOST'],
            $config['DB_USER'],
            $config['DB_PASS'],
            $config['DB_NAME'],
        );
        $verbindung->set_charset('utf8mb4');
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['erfolg' => false, 'fehler' => 'Die Verbindung zur Datenbank ist fehlgeschlagen!', 'hinweis' => $e->getMessage()]);
        exit;
    }

    return $verbindung;
}
