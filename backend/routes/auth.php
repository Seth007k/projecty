<?php
require_once __DIR__ . '/../src/services/Database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/../src/services/AuthService.php';

header('Content-Type: application/json');

$methode = $_SERVER['REQUEST_METHOD'];
$anwortUserDaten =  file_get_contents('php://input');
$eingabeDaten = json_decode($anwortUserDaten, true);
$datenbank = getDB();


try {
    switch ($methode) {
        case 'POST':
            $action = $eingabeDaten['action'] ?? 'login';
            if ($action === 'registrieren') {
                $benutzer_id = registriereUndEinloggen($datenbank, $eingabeDaten);
                http_response_code(201);
                echo json_encode(['erfolg' => true, 'hinweis' => 'Registrierung erfolgreich abgeschlossen! Willkommen', 'benutzer_id' => $benutzer_id]);
                break;
            }

            $benutzer_id = loginBenutzer($datenbank, $eingabeDaten);
       
            http_response_code(200);
            $antwortOk = ['erfolg' => true, 'hinweis' => 'User erfoglreich eingeloggt!', 'benutzer_id' => $benutzer_id];
            echo json_encode($antwortOk);
            break;
        case 'DELETE':
            requireAuth();
            session_unset();
            session_destroy();
            $antwortLogout = ['erfolg' => true, 'hinweis' => 'User wude erfolgreich ausgeloggt! Bis zum nächsten mal'];
            echo json_encode($antwortLogout);
            break;
        default:
            http_response_code(405);
            $antwortMethodeFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt! nur POST oder DELETE erleaubt'];
            echo json_encode($antwortMethodeFehler);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erfolg' => false, 'fehler' => $e->getMessage()]);
}
