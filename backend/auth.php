<?php
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { // preflieght ist kein echter request sondern nur eine anfrage vonw egen: darf ich? dann antwort.
    http_response_code(200);
    exit();
}

if(session_status() == PHP_SESSION_NONE){
    session_start();
}


require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/services/AuthService.php';

header('Content-Type: application/json'); //obere header sind zugriffsregeln dieser header  sagt dem client das was jetzt kommt ist JSON

$methode = $_SERVER['REQUEST_METHOD'];
$anwortUserDaten =  file_get_contents('php://input');
$eingabeDaten = json_decode($anwortUserDaten, true);

if(!$eingabeDaten){
    http_response_code(400);
    echo json_encode(['erfolg'=>false,'fehler' => 'Fehelr beim lesen der json datei']);
    exit;
}
$datenbank = getDB();

//Bei post wiurd action in action gespeichert und geprüft ob action == registrieren wenn ja dann registrere und logge ein und wenn nein dann loginbenutzer in benutzer_id speichern und erfolg meldung mit json ausgeben
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

            //bei DELETE wird erst die aktuelle session geprüft dann mit session unset werden alle sessino variablen gelöscht und dann wird die session gekillt + meldung logout erfolgreich
        case 'DELETE': // omg ich hab kein logout button :( muss noch hinzugefügt werden)
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
    //catch für fehler
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $e->getMessage();
    $error_trc = $e->getTraceAsString();
    echo json_encode(['erfolg' => false, 'fehler' => $error_msg, 'trace' => $error_trc]);
}
