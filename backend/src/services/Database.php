<?php

function getDB()
{ //getDB kann aufgerufen werden um eine verbindung zur datenbank zu bekommen
    static $verbindung;

    if (isset($verbindung)) { // existiert vie Verbindung bereits? wenn ja - reutrn verbidung, sonst
        return $verbindung;
    }

    $config = require __DIR__ . '/../../config/env.php'; // lädt env.php und speichert in $config ;


    $verbindung = new mysqli( //new msyqli(..) = verbindung php mit mysql DB_HOST etc aus env
        $config['DB_HOST'],
        $config['DB_USER'],
        $config['DB_PASS'],
        $config['DB_NAME']
    );

    $verbindung->set_charset('utf8mb4'); // Zeichencode gesetzt 

    if ($verbindung->connect_error) { 
        http_response_code(500);
        echo json_encode(['erfolg' => false, 'fehler' => 'Die Verbindung zur Datenbank ist fehlgeschlagen!', 'hinweis' => $verbindung->connect_error]);
        exit;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    return $verbindung;
}
