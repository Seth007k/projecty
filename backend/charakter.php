<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Header für CORS policy
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

//Abfangen der OPTIONS REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//imports von database etc
require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/src/services/CharakterService.php';

//es wird der session status geprüft ob schon session_start, wenn nicht session_start
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

//aus der middleware, prüft ob SESSION benutzerID hat benötig für login
requireAuth();

//speichert buntzerid von session in spieler_id und prüft ob spieler_id gesetzt wurde
$spieler_id = $_SESSION['benutzer_id'] ?? null;
if(!$spieler_id){
    http_response_code(401);
    echo json_encode(['erfolg' => false, 'fehler' => 'nicht eingeloggt!']);
    exit;
}
//Antwort header
header('Content-Type: application/json');


//Variablen für die methode, datenbank, und den eingegebenen userdaten inklusive antwort falls methodenfehler
$methode = $_SERVER['REQUEST_METHOD'];
$datenbank = getDB();
$antwortUserDaten = file_get_contents('php://input');
$eingabeDaten = json_decode($antwortUserDaten, true);
$antwortMethodeFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];

//try - catch für Fehler abfangen
try {

    //switch case um action der jeweiligen request auszuführen
    switch ($methode) {
        //Bei GET wird die ID erst geprüft ob gesetzt, das ist die bedingung um bei true: GET['id'] in charakter_id zu speichern und bei false: null wert wenn id nicht existiert zu speichern
        //parameter datenbank, spieler_id und charakter_id werden an charakterladen übergeben und das ergebnis in charaktere gespeichert, dann wird die antwortvariable vorbereitet und mit echo json_encode ausgegeben. break bricht switch case ab
        case 'GET':
            $charakter_id = isset($_GET['id']) ? $_GET['id'] : null;
            $charaktere = charakterLaden($datenbank, $spieler_id, $charakter_id);
            $antwortCharakterAuswahl = ['erfolg' => true, 'charakterauswahl' => $charaktere];
            echo json_encode($antwortCharakterAuswahl);
            break;
        //Bei POST wird mit der if geprüft ob in den eingegebenen Daten des users auch der name des charakters angegeben wurde, wenn nein dann fehlercode + ausgabe. exit bricht hier ab wird aber nur im fehler ausgelöst
        //wenn charakter einen namen hat, werden parameter datenbank, spieler_id und eingabDaten in funktion charakterErstellen übergeben um einen charakter zu erstellen und in neuecharakterid zu speichern, wobei nur die id des charakters zurückgegeben wird 
        //zudem laden wir bereits vorhandene charaktere mit funktion charakterladen und übergabe werten, und speichern in charakjterlisteneu. Danach die antwortvariable und ausgabe + break
        case 'POST':
            if(!pruefeCharakterName($eingabeDaten)) {
                http_response_code(400);
                echo json_encode((['erfolg' => false, 'fehler' => 'Bitte gib deinem Charakter ienen Namen!']));
                exit;
            }
            $neueCharakterId = charakterErstellen($datenbank, $spieler_id, $eingabeDaten);
            $charakterListeNeu = charakterLaden($datenbank, $spieler_id, $neueCharakterId)[0];

            $antwortCharakterErstellt = ['erfolg' => true, 'hinweis' => 'Der Charakter wurde erfolgreich erstellt!', 'charakter' => $charakterListeNeu];
            echo json_encode($antwortCharakterErstellt);
            break;
        //Bei DELETE wir zuerst geprüft die iD der GEt Anfrage und speichert diese in charakter_id, dann wird geprüft ob die ID gesetzt wurde wenn ja, dann werden wieder parameter an charakterlöschen funktion übergeben und das ergebnis in charaktergelöscht gespeichert.
        //danach wird die antwortvariable vorbereitet und ausgegeben als json
        case 'DELETE':
            $charakter_id = holeCharakterId($eingabeDaten);
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
        //Falls nicht POST, GET oder DELETE als action greifen kommt ein status fehler
        default:
            http_response_code(405);
            echo json_encode($antwortMethodeFehler);
            break;
    }
    //catcht fehler und gibt fehlermeldung aus 
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erfolg' => false, 'fehler' => 'Datenbankfehler', 'fehlerausgabe' => $e->getMessage()]);
}
