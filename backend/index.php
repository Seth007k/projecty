<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/charakter.php';

$eingabeDaten = json_decode(file_get_contents('php://input'), true) ?? [];

//hier wird die URL geholt komplett
$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH); // entfernt query parameter
$uri = str_replace('/index.php', '', $uri); // entfernt index aus der URL
$uri = trim($uri, '/'); // entfernt /

//try catch für exceptions
try {
    //entscheidt anhand der URL welche Datei geladen wird
    switch($uri) {
        case 'auth':
            require_once __DIR__ . '/auth.php';
            break;
        case 'spiel':
            require_once __DIR__ . '/spiel.php';
            break;
        case 'charakter':
            require_once __DIR__ . '/charakter.php';
            break;
        default:
        http_response_code(404);
        $antwort = ['erfolg' => false, 'fehler' => 'Route nicht gefunden!'];
        break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $antwort = ['erfolg' => false, 'fehler' => 'Serverfehler!', 'debug' => $e->getMessage()];
}
?>