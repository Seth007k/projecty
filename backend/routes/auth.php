<?php
require_once __DIR__ . '/../src/services/Database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleWare.php';

header('Content-Type: application/json');

$methode = $_SERVER['REQUEST_METHOD'];
$anwortUserDaten =  file_get_contents('php://input');
$eingabeDaten = json_decode($anwortUserDaten, true);
$datenbank = getDB();

function loginDatenVorhanden($eingabeDaten)
{
    if (empty($eingabeDaten['benutzername']) || empty($eingabeDaten['passwort'])) {
        http_response_code(406);
        $antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte benutzername und passwort mit angeben!'];
        echo json_encode($antwortDatenFehler);
        exit;
    }
}

function ladeBenutzer($datenbank, $eingabeDaten)
{
    $benutzername = $eingabeDaten['benutzername'];

    $sqlAnweisung = $datenbank->prepare("SELECT id, passwort FROM spieler WHERE benutzername = ?");
    $sqlAnweisung->bind_param("s", $benutzername);
    $sqlAnweisung->execute();
    $ergebnisUser = $sqlAnweisung->get_result();
    $aktuellerUser = $ergebnisUser->fetch_assoc();

    return $aktuellerUser;
}

function pruefePasswort($aktuellerUser, $eingabeDaten)
{
    $antwortFehler = ['erfolg' => false, 'fehler' => 'Login fehlgeschlagen!'];
    $benutzerpasswort = $eingabeDaten['passwort'];
    if (!$aktuellerUser) {
        http_response_code(401);
        echo json_encode($antwortFehler);
        exit;
    } elseif (!password_verify($benutzerpasswort, $aktuellerUser['passwort'])) {
        http_response_code(401);
        echo json_encode($antwortFehler);
        exit;
    }
}

try {
    switch ($methode) {
        case 'POST':
            loginDatenVorhanden($eingabeDaten);
            $aktuellerUser = ladeBenutzer($datenbank, $eingabeDaten);
            pruefePasswort($aktuellerUser, $eingabeDaten);

            $_SESSION['benutzer_id'] = $aktuellerUser['id'];
            $antwortOk = ['erfolg' => true, 'hinweis' => 'User erfoglreich eingeloggt!'];
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
    echo json_encode(['erfolg' => false, 'fehler' => 'Die Verbindung zur Datenbank ist fehlgeschlagen!', 'hinweis' => $e->getMessage()]);
}
