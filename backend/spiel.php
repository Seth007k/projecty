<?php
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

//Abfangen der OPTIONS REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/src/services/CharakterService.php';
require_once __DIR__ . '/src/services/SpielService.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//aus der middleware, prüft ob SESSION benutzerID hat benötig für login
requireAuth();

//speichert buntzerid von session in spieler_id und prüft ob spieler_id gesetzt wurde
$spieler_id = $_SESSION['benutzer_id'] ?? null;
if (!$spieler_id) {
    http_response_code(401);
    echo json_encode(['erfolg' => false, 'fehler' => 'nicht eingeloggt!']);
    exit;
}

header('Content-Type: application/json');


$methode = $_SERVER['REQUEST_METHOD'];
$spieler_id = $_SESSION['benutzer_id'];
$eingabedaten = json_decode(file_get_contents('php://input') ?? '{}', true);

$antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte alle benötigten Daten ausfüllen!'];
$antwortMethodenFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];
$antwortSpielNichtGefunden = ['erfolg' => false, 'fehler' => 'Spiel wurden nicht gefunden oder existiert nicht!'];
$antwortCharakterNichtGefunden = ['erfolg' => false, 'fehler' => 'Der charakter wurde nicht gefunden oder existiert nicht!'];
$antwortKeineCharakterId = ['erfolg' => false, 'fehler' => 'Es wurde keine Charakter ID angegeben!'];
$antwortServerFehler = ['erfolg' => false, 'fehler' => 'Serverfehler!'];
$antwortErfolg = ['erfolg' => true];


try {

    $datenbank = getDB();
    if (!$datenbank) {
        throw new Exception('Datenbankverbidnung fehlgeschlagen');
    }

    switch ($methode) {

        case 'GET':
            $charakterId = $_GET['charakter_id'] ?? null;

            if (!$charakterId) {
                http_response_code(400);
                $antwort = $antwortKeineCharakterId;
                break;
            }

            $geladeneDaten = ladeSpielUndCharakter($datenbank, $spieler_id, $charakterId);
            if (!$geladeneDaten) {
                http_response_code(404);
                $antwort = $antwortSpielNichtGefunden;
                break;
            }
            $aktuellesSpiel = $geladeneDaten['spiel'];

            $gegnerListe = isset($aktuellesSpiel['gegner_status']) && $aktuellesSpiel['gegner_status'] !== null ? json_decode($aktuellesSpiel['gegner_status'], true) : [];
            $antwort = $antwortErfolg;
            $antwort['spiel'] = $aktuellesSpiel;

            $antwort['gegner'] = $gegnerListe;

            break;
        case 'POST':
            $charakter_id = $eingabedaten['charakter_id'] ?? null;
            $benutzerAktion = $eingabedaten['action'] ?? null;

            if (!$charakter_id || !$benutzerAktion) {
                http_response_code(400);
                $antwort = $antwortDatenFehler;
                break;
            }

            if ($benutzerAktion !== 'ladeSpiel') {
                $geladeneDaten = ladeSpielUndCharakter($datenbank, $spieler_id, $charakter_id);
                if (isset($geladeneDaten['error'])) {
                    http_response_code(400);
                    $antwort = ['erfolg' => false, 'fehler' => $geladeneDaten['error']];
                    break;
                }
                $ergebnisAktuellesSpiel = $geladeneDaten['spiel'];
                $ergebnisAktuellerCharakter = $geladeneDaten['charakter'];
                $gegnerListe = isset($ergebnisAktuellesSpiel['gegner_status']) ? json_decode($ergebnisAktuellesSpiel['gegner_status'], true) : [];
            }

            switch ($benutzerAktion) {
                case 'ladeSpiel':
                    $geladeneDaten = ladeSpielUndCharakter($datenbank, $spieler_id, $charakter_id);
                    if (isset($geladeneDaten['error'])) {
                        $antwort = $geladeneDaten['error'];
                    } else {
                        $antwort = ['erfolg' => true, 'spiel' => $geladeneDaten['spiel'], 'charakter' => $geladeneDaten['charakter']];
                    }
                    echo json_encode($antwort);
                    exit;
                case 'spielerAngriff':
                    $ergebnisAktuelleGegner = null;

                    foreach ($gegnerListe as &$gegner) {
                        if ($gegner['leben'] > 0) {
                            $ergebnisAktuelleGegner = &$gegner;
                            break;
                        }
                    }

                    unset($gegner);

                    if (!$ergebnisAktuelleGegner) {
                        $ausgabeNachAngriff = "Alle gegner wurden bereits besiegt!";
                        $antwort = $antwortErfolg;
                        $antwort['ausgabe'] = $ausgabeNachAngriff;
                        $antwort['spiel'] = $ergebnisAktuellesSpiel;
                        $antwort['gegner'] = $gegnerListe;
                        break;
                    }


                    $spielerSchaden = berechneSpielerSchaden($ergebnisAktuellerCharakter, $ergebnisAktuelleGegner);
                    $ergebnisAktuelleGegner['leben'] = max(0, $ergebnisAktuelleGegner['leben'] - $spielerSchaden);
                    $ausgabeNachAngriff = "Du hast {$spielerSchaden} Schaden an {$ergebnisAktuelleGegner['name']} verursacht!";

                    if ($ergebnisAktuelleGegner['leben'] === 0) {
                        $ergebnisAktuellesSpiel['punkte'] = $ergebnisAktuellesSpiel['punkte'] + 100;
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} wurde besiegt! Du erhälst 100 Punkte!";
                    }


                    $gegnerSchaden = berechneGegnerSchaden($ergebnisAktuelleGegner, $ergebnisAktuellerCharakter);
                    if ($gegnerSchaden > 0) {
                        $ergebnisAktuellerCharakter['leben'] = max(0, $ergebnisAktuellerCharakter['leben'] - $gegnerSchaden);
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} schlägt zurück und verursacht: {$gegnerSchaden} Schaden!";
                    } else {
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} hat dich nicht getroffen!";
                    }


                    if ($ergebnisAktuellerCharakter['leben'] <= 0) {
                        $antwort = spielerBesiegt($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellerCharakter, $ergebnisAktuellesSpiel);
                        $antwort['ausgabe'] = $ausgabeNachAngriff;
                        break;
                    }

                    charakterAktualisieren($datenbank, $spieler_id, $ergebnisAktuellerCharakter);

                    $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);
                    speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel);


                    $gegnerBesiegt = true;
                    foreach ($gegnerListe as $gegner) {
                        if ($gegner['leben'] > 0) {
                            $gegnerBesiegt = false;
                            break;
                        }
                    }

                    if ($gegnerBesiegt) {
                        if ($ergebnisAktuellesSpiel['aktuelle_runde'] >= 4) {
                            $ausgabeNachAngriff .= "Du hast die Faulheit besiegt! Glückwunsch! Punkte: {$ergebnisAktuellesSpiel['punkte']} ! Drücke 'nochmal_spielen' um aufzuleveln und die nächste Runde zu starten!";
                        } else {
                            $ergebnisAktuellesSpiel['aktuelle_runde']++;
                            speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel);
                            $neueGegner = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
                            $gegnerListe = $neueGegner;
                            $ausgabeNachAngriff .= "Neue Gegner erscheinen...";
                        }
                    }

                    gegnerStatusSpeichern($datenbank, $ergebnisAktuellesSpiel['id'], $gegnerListe);

                    $antwort = $antwortErfolg;
                    $antwort['ausgabe'] = $ausgabeNachAngriff;
                    $antwort['spiel'] = $ergebnisAktuellesSpiel;
                    $antwort['spieler'] = [
                        'name' => $ergebnisAktuellerCharakter['name'],
                        'leben' => $ergebnisAktuellerCharakter['leben'],
                        'angriff' => $ergebnisAktuellerCharakter['angriff'],
                        'verteidigung' => $ergebnisAktuellerCharakter['verteidigung'],
                        'level' => $ergebnisAktuellerCharakter['level'],
                        'punkte' => $ergebnisAktuellesSpiel['punkte']
                    ];
                    $antwort['gegner'] = $gegnerListe;
                    break;
                case 'nochmal_spielen':
                    $ergebnisNochmalSpielen = nochmalSpielen($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellesSpiel);
                    $neuerCharakter = charakterLaden($datenbank, $spieler_id, $charakter_id)[0];
                    if (empty($neuerCharakter)) {
                        throw new Exception('Charakter konnte nicht geladen werden...');
                    }

                    $antwort = [
                        'schwierigkeit' => $ergebnisNochmalSpielen['schwierigkeit'],
                        'gegner' => $ergebnisNochmalSpielen['gegner'],
                        'runde' => 1,
                        'hinweis' => $ergebnisNochmalSpielen['hinweis']
                    ];
                    break;
                default:
                    http_response_code(400);
                    $antwort = $antwortMethodenFehler;
                    break;
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $antwort = $antwortServerFehler;
}
echo json_encode($antwort);
