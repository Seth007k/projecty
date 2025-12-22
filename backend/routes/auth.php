<?php
require_once __DIR__ . '/../src/services/Database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleWare.php';

header('Content-Type: application/json');

$methode = $_SERVER['REQUEST_METHOD'];
$anwortUserDaten =  file_get_contents('php://input');
$eingabeDaten = json_decode($anwortUserDaten, true);
$datenbank = getDB();
$antwortOk = ['erfolg' => true, 'hinweis' => 'User erfoglreich eingeloggt!'];
$antwortFehler = ['erfolg' => false, 'fehler' => 'Login fehlgeschlagen!'];
$antwortFormFehler = ['erfolg' => false, 'fehler' => 'Nur JSON format erlaubt'];
$antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte benutzername und passwort mit angeben!'];
$antwortMethodeFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt! nur POST oder DELETE erleaubt'];
$antwortLogout = ['erfolg' => true, 'erfolg' => 'User wude erfolgreich ausgeloggt! Bis zum nächsten mal'];

try{
    switch ($methode) {
    case 'POST':
        if (!is_array($eingabeDaten)) {
            http_response_code(400);
            echo json_encode($antwortFormFehler);
            exit;
        }
        if (empty($eingabeDaten['benutzername']) || empty($eingabeDaten['passwort'])) {
            http_response_code(406);
            echo json_encode($antwortDatenFehler);
            exit;
        }

        $benutzername = $eingabeDaten['benutzername'];
        $benutzerpasswort = $eingabeDaten['passwort'];

        $sqlAnweisung = $datenbank->prepare("SELECT id, passwort FROM spieler WHERE benutzername = ?");
        $sqlAnweisung->bind_param("s", $benutzername);
        $sqlAnweisung->execute();
        $ergebnisUser = $sqlAnweisung->get_result();
        $aktuellerUser = $ergebnisUser->fetch_assoc();

        if (!$aktuellerUser) {
            http_response_code(401);
            echo json_encode($antwortFehler);
            exit;
        } elseif (!password_verify($benutzerpasswort, $aktuellerUser['passwort'])) {
            http_response_code(401);
            echo json_encode($antwortFehler);
            exit;
        }

        $_SESSION['benutzer_id'] = $aktuellerUser['id'];

        echo json_encode($antwortOk);
        break;
    case 'DELETE':
        requireAuth();
        session_unset();
        session_destroy();

        echo json_encode($antwortLogout);
        break;
    default:
        http_response_code(405);
        echo json_encode($antwortMethodeFehler);
        exit;
}

}catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erfolg' => false, 'fehler' => 'Die Verbindung zur Datenbank ist fehlgeschlagen!', 'hinweis' => $e->getMessage()]);
}


