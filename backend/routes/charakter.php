<?php
require_once __DIR__ . '/../src/services/Database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';


header('Content-Type: application/json');

requireAuth();

$methode = $_SERVER['REQUEST_METHOD'];
$spieler_id = $_SESSION['benutzer_id'];

$datenbank = getDB();
$antwortUserDaten = file_get_contents('php://input');
$eingabeDaten = json_decode($antwortUserDaten, true);

$antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bite alle benötigten Daten ausfüllen!'];
$antwortMethodeFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];


try {
    switch ($methode) {
        case 'GET':
            $sqlAnweisung = $datenbank->prepare("SELECT id, name, level, leben, angriff, verteidigung FROM charakter WHERE spieler_id =?");
            $sqlAnweisung->bind_param("i", $spieler_id);
            $sqlAnweisung->execute();
            $ergebnisCharakter = $sqlAnweisung->get_result();

            $charaktere = [];
            while ($row = $ergebnisCharakter->fetch_assoc()) {
                $charaktere[] = $row;
            }

            $antwortZeigecharakter = ['erfolg' => true, 'charakterauswahl' => $charaktere];
            echo json_encode($antwortZeigecharakter);
            break;
        case 'POST':
            if (empty($eingabeDaten['name'])) {
                http_response_code(400);
                echo json_encode($antwortDatenFehler);
                exit;
            }

            $level = 1;
            $leben = 100;
            $angriff = 15;
            $verteidigung = 5;

            $sqlAnweisung = $datenbank->prepare("INSERT INTO charakter (spieler_id, name, level, leben, angriff, verteidigung) VALUES (?,?,?,?,?,?)");
            $sqlAnweisung->bind_param("isiiii", $spieler_id, $eingabeDaten['name'], $level, $leben, $angriff, $verteidigung);
            $sqlAnweisung->execute();

            $neueCharakterId = $sqlAnweisung->insert_id;
            $antwortCharakterErstellt = ['erfolg' => true, 'hinweis' => 'Der Charakter wurde erfolgreich erstellt!', 'id' => $neueCharakterId];
            echo json_encode($antwortCharakterErstellt);
            break;
        case 'PUT':
            if (!isset($eingabeDaten['id'], $eingabeDaten['name'])) {
                http_response_code(400);
                echo json_encode($antwortDatenFehler);
                exit;
            }

            $sqlAnweisung = $datenbank->prepare("UPDATE charakter SET name = ?, level = ?, leben = ?, angriff = ?, verteidigung = ? WHERE id = ? AND spieler_id = ?");
            $sqlAnweisung->bind_param("siiiiii", $eingabeDaten['name'], $eingabeDaten['level'], $eingabeDaten['leben'], $eingabeDaten['angriff'], $eingabeDaten['verteidigung'], $eingabeDaten['id'], $spieler_id);
            $sqlAnweisung->execute();

            $antwortcharakterAktualisiert = ['erfolg' => $sqlAnweisung->affected_rows > 0, 'hinweis:' => $sqlAnweisung->affected_rows > 0 ? 'Charakter wurde erfolgreich aktualsisiert und gespeichert' : 'Keine Änderungen durchgeführt, da keine Änderung gefunden'];
            echo json_encode($antwortcharakterAktualisiert);
            break;
        case 'DELETE':
            if (empty($eingabeDaten['id'])) {
                http_response_code(400);
                echo json_encode($antwortDatenFehler);
                exit;
            }

            $sqlAnweisung = $datenbank->prepare("DELETE FROM charakter WHERE id=? AND spieler_id=?");
            $sqlAnweisung->bind_param("ii", $eingabeDaten['id'], $spieler_id);
            $sqlAnweisung->execute();

            $löschungErfolg = $sqlAnweisung->affected_rows > 0;
            $antwortCharakterGelöscht = ['erfolg' => $löschungErfolg, 'hinweis' => $löschungErfolg ? 'Charakter wurde erfolgreich gelöscht' : 'Charakter wurde nicht gelöscht, da er nicht mehr existiert'];
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
