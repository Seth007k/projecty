<?php
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/src/services/CharakterService.php';

if(session_status() == PHP_SESSION_NONE){
    session_start();
}
header('Content-Type: application/json');

requireAuth();

$methode = $_SERVER['REQUEST_METHOD'];
$spieler_id = $_SESSION['benutzer_id'];

$datenbank = getDB();
$antwortUserDaten = file_get_contents('php://input');
$eingabeDaten = json_decode($antwortUserDaten, true);


$antwortMethodeFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];


try {
    switch ($methode) {
        case 'GET':
            $charakter_id = isset($_GET['id']) ? $_GET['id'] : null;
            $charaktere = charakterLaden($datenbank, $spieler_id, $charakter_id);
            $antwortCharakterAuswahl = ['erfolg' => true, 'charakterauswahl' => $charaktere];
            echo json_encode($antwortCharakterAuswahl);
            break;
        case 'POST':
            if(!pruefeCharakterName($eingabeDaten)) {
                http_response_code(400);
                echo json_encode((['erfolg' => false, 'fehler' => 'Bitte gib deinem Charakter ienen Namen!']));
                exit;
            }
            $neueCharakterId = charakterErstellen($datenbank, $spieler_id, $eingabeDaten);
            $antwortCharakterErstellt = ['erfolg' => true, 'hinweis' => 'Der Charakter wurde erfolgreich erstellt!', 'id' => $neueCharakterId];
            echo json_encode($antwortCharakterErstellt);
            break;
        case 'DELETE':
            $charakter_id = exisitiertCharakter($eingabeDaten);
            if (!$charakter_id) {
                http_response_code(400);
                $antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte name und id ausfüllen'];
                echo json_encode($antwortDatenFehler);
                exit;
            }
            $charakterGelöscht = charakterLöschen($datenbank, $spieler_id, $charakter_id);
            $antwortCharakterGelöscht = ['erfolg' => $charakterGelöscht, 'hinweis' => $charakterGelöscht ? 'Charakter wurde erfolgreich gelöscht' : 'Charakter wurde nicht gelöscht, da er nicht mehr existiert'];
            echo json_encode($antwortCharakterGelöscht);
            break;
        default:
            http_response_code(405);
            echo json_encode($antwortMethodeFehler);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erfolg' => false, 'fehler' => 'Datenbankfehler', 'fehlerausgabe' => $e->getMessage()]);
}
