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
            $sqlAnweisungCharakteranzeigen = $datenbank->prepare("SELECT id, name, level, leben, angriff, verteidigung FROM charakter WHERE spieler_id =?");
            $sqlAnweisungCharakteranzeigen->bind_param("i", $spieler_id);
            $sqlAnweisungCharakteranzeigen->execute();
            $ergebnisCharakter = $sqlAnweisungCharakteranzeigen->get_result();

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
            $leben = 2050;
            $angriff = 250;
            $verteidigung = 5;

            $sqlAnweisungCharakterErstellen = $datenbank->prepare("INSERT INTO charakter (spieler_id, name, level, leben, angriff, verteidigung) VALUES (?,?,?,?,?,?)");
            $sqlAnweisungCharakterErstellen->bind_param("isiiii", $spieler_id, $eingabeDaten['name'], $level, $leben, $angriff, $verteidigung);
            $sqlAnweisungCharakterErstellen->execute();

            $neueCharakterId = $sqlAnweisungCharakterErstellen->insert_id;
            $antwortCharakterErstellt = ['erfolg' => true, 'hinweis' => 'Der Charakter wurde erfolgreich erstellt!', 'id' => $neueCharakterId];
            echo json_encode($antwortCharakterErstellt);
            break;
        case 'PUT':
            $charakter_id = $eingabeDaten['id'] ?? null;
            if (!isset($eingabeDaten['id'], $eingabeDaten['name'])) {
                http_response_code(400);
                echo json_encode($antwortDatenFehler);
                exit;
            }

            $sqlAnweisungCharakterAktualisieren = $datenbank->prepare("UPDATE charakter SET name = ?, level = ?, leben = ?, angriff = ?, verteidigung = ? WHERE id = ? AND spieler_id = ?");
            $sqlAnweisungCharakterAktualisieren->bind_param("siiiiii", $eingabeDaten['name'], $eingabeDaten['level'], $eingabeDaten['leben'], $eingabeDaten['angriff'], $eingabeDaten['verteidigung'], $charakter_id, $spieler_id);
            $sqlAnweisungCharakterAktualisieren->execute();

            $antwortcharakterAktualisiert = ['erfolg' => $sqlAnweisungCharakterAktualisieren->affected_rows > 0, 'hinweis:' => $sqlAnweisungCharakterAktualisieren->affected_rows > 0 ? 'Charakter wurde erfolgreich aktualsisiert und gespeichert' : 'Keine Änderungen durchgeführt, da keine Änderung gefunden'];
            echo json_encode($antwortcharakterAktualisiert);
            break;
        case 'DELETE':
            $charakter_id = $eingabeDaten['id'] ?? null;
            if (!$charakter_id) {
                http_response_code(400);
                echo json_encode($antwortDatenFehler);
                exit;
            }

            $sqlAnweisungSpieleDesCharaktersLöschen = $datenbank->prepare("DELETE FROM spiele WHERE charakter_id = ?");
            $sqlAnweisungSpieleDesCharaktersLöschen->bind_param("i", $charakter_id);
            $sqlAnweisungSpieleDesCharaktersLöschen->execute();

            $sqlAnweisungCharakterLöschen = $datenbank->prepare("DELETE FROM charakter WHERE id=? AND spieler_id=?");
            $sqlAnweisungCharakterLöschen->bind_param("ii", $charakter_id, $spieler_id);
            $sqlAnweisungCharakterLöschen->execute();

            $löschungErfolg = $sqlAnweisungCharakterLöschen->affected_rows > 0;
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
