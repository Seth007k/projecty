<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';

$eingabeDaten = json_decode(file_get_contents('php://input'), true);

$antwort = ['erfolg'=> false];


$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

try {
    switch($uri) {
        case 'auth':
            require_once __DIR__ . '/routes/auth.php';
            break;
        case 'spiel':
            require_once __DIR__ . '/routes/spiel.php';
            break;
        case 'charakter':
            require_once __DIR__ . '/routes/charakter.php';
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