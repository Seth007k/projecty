<?php
//Erlaubt CORS-Zugriffe von localhost
header("Access-Control-Allow-Origin: http://localhost:8082");
//Erlaubt mitsenden von cookies, brauch ich für SESSION-IDS
header("Access-Control-Allow-Credentials: true");
//Welche HTTP Methoden sind erlaubt?
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
//Erlaubt Header den Content-type zb application/json
header("Access-Control-Allow-Headers: Content-Type");
//Jede Antwort ist JSON Format, brauch ich für frontend
header('Content-Type: application/json');

//Abfangen der OPTIONS REQUEST, diese prüft ob CORS erlaubt sind und wird oft vor POST etc geschickt, antwort ok -> 200 dann wird script beendet
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//Hier binde ich externe Dateien ein um functionen verwenden zu können
require_once __DIR__ . '/src/services/Database.php';
require_once __DIR__ . '/src/middleware/AuthMiddleWare.php';
require_once __DIR__ . '/src/services/CharakterService.php';
require_once __DIR__ . '/src/services/SpielService.php';

//Hier wird geprüft ob noch KEINE SESSION aktiv ist, wenn sie nicht aktiv ist dann starte session, brauch ich für SESSION Benutzer ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//aus der middleware, prüft ob SESSION benutzerID hat benötig für login, falls nicht session benutzer id gesetzt ist 401 und json antwort mit erfolg false und hinweisausgabe
requireAuth();

//speichert buntzerid von session in spieler_id und prüft ob spieler_id gesetzt wurde, wenn nicht dann fehlercode 401 mit json antwort erfolg false und fehler nicht eingeloggt
$spieler_id = $_SESSION['benutzer_id'] ?? null;
if (!$spieler_id) {
    http_response_code(401);
    echo json_encode(['erfolg' => false, 'fehler' => 'nicht eingeloggt!']);
    exit;
}



//REQUEST_METHOD wird in methode gespeichert
$methode = $_SERVER['REQUEST_METHOD'];


//liest den JSON Body eines POST Requests und wandelt ihn in ein php array um, falls leer dann {}
$eingabedaten = json_decode(file_get_contents('php://input') ?? '{}', true);

//Meine Antwort Arrays zur wiederverwendung
$antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte alle benötigten Daten ausfüllen!'];
$antwortMethodenFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];
$antwortSpielNichtGefunden = ['erfolg' => false, 'fehler' => 'Spiel wurden nicht gefunden oder existiert nicht!'];
$antwortCharakterNichtGefunden = ['erfolg' => false, 'fehler' => 'Der charakter wurde nicht gefunden oder existiert nicht!'];
$antwortKeineCharakterId = ['erfolg' => false, 'fehler' => 'Es wurde keine Charakter ID angegeben!'];
$antwortServerFehler = ['erfolg' => false, 'fehler' => 'Serverfehler!'];
$antwortBasis = ['erfolg' => true];

//try catch fehlerabfangen
try {
    //Baut datenbankverbindung auf: wenn keine verbindung gesetzt dann wirf fehler und gehe in catch block. Hab hier zum zweiten mal die prüfung da in db schon geprüft wird
    $datenbank = getDB();
    if (!$datenbank) {
        throw new Exception('Datenbankverbidnung fehlgeschlagen');
    }

    //switch case um die methoden zu sortieren bzw darauf zu reagieren
    switch ($methode) {

    //Falls GET: Spielstatus wird abgerufen charakter ID wird aus URL geholt
        case 'GET':
            $charakterId = $_GET['charakter_id'] ?? null;

            //charakter ID muss gesetzt sein sonst fehler
            if (!$charakterId) {
                http_response_code(400);
                $antwort = $antwortKeineCharakterId;
                break;
            }
            //Holt spiel und charakter aus der datenbank, dann wird geprüft ob daten vorhanden
            $geladeneDaten = ladeSpielUndCharakter($datenbank, $spieler_id, $charakterId);
            if (!$geladeneDaten) {
                http_response_code(404);
                $antwort = $antwortSpielNichtGefunden;
                break;
            }
            //wenn "spiel" daten geladen wurden werden sie in aktuellesSpiel gespeichert
            $aktuellesSpiel = $geladeneDaten['spiel'];
            //gegnerdaten werden in gegnerListe gespeichert, es wird geprüft ob gegner_status true ist und ob gegnerstatus nicht null ist, wenn true dann baue ein array aus den json daten, danach kommt die antwort als json
            $gegnerListe = isset($aktuellesSpiel['gegner_status']) && $aktuellesSpiel['gegner_status'] !== null ? json_decode($aktuellesSpiel['gegner_status'], true) : [];
            $antwort = $antwortErfolg;
            $antwort['spiel'] = $aktuellesSpiel;
            $antwort['gegner'] = $gegnerListe;
            break;
            //Fall POST: post wird für aktionen benutzt. Dabei muss die charakter id und die jeweilige action gespeichert werden und auch wirklich gesetzt sein (if prüfung) ansonsten fehler.
        case 'POST':
            $charakter_id = $eingabedaten['charakter_id'] ?? null;
            $benutzerAktion = $eingabedaten['action'] ?? null;

            if (!$charakter_id || !$benutzerAktion) {
                http_response_code(400);
                $antwort = $antwortDatenFehler;
                break;
            }
            //ich brauche den aktuellen spielstand um kamfpaktionen durchzuführen, wird in geladeneDaten egspeichert, prüft vorher ob aktuelle aktion NICHT ladeSpiel ist. ladeSpiel lädt nur Daten welche ich für zb spielerAngriff brauche(speilstand muss vorhanden sein)
            if ($benutzerAktion !== 'ladeSpiel') {
                $geladeneDaten = ladeSpielUndCharakter($datenbank, $spieler_id, $charakter_id);
                //prüft ob meine funktion error geworden hat wenn ja, dann fehlercode 400, inhaltlich falsch - syntaktisch korrkt
                if (isset($geladeneDaten['error'])) {
                    http_response_code(400);
                    $antwort = ['erfolg' => false, 'fehler' => $geladeneDaten['error']];
                    break;
                }
                //sepichert das geladene spiel in ergebnisaktuellesspiel - enthält runde punkte gegner etc und danach wird charakterdaten in ergebnisaktuellercharakter geladen - enthält charakter infos angriff verteidigung etc
                $ergebnisAktuellesSpiel = $geladeneDaten['spiel'];
                $ergebnisAktuellerCharakter = $geladeneDaten['charakter'];
                //es wird geprüft ob gegner_status gesetzt is und wenn ja baue daraus ein php array wenn nein dann leeres array []
                $gegnerListe = isset($ergebnisAktuellesSpiel['gegner_status']) ? json_decode($ergebnisAktuellesSpiel['gegner_status'], true) : [];
                //Sicherheitsprüfung, falls json_ddecode kein array geliefert hat: gegnerliste auf leer setzen um spätere fehle in foreach zu vermeiden
                if (!is_array($gegnerListe)) $gegnerListe = [];
            }
            //die eigentliche benutzeraktion wird hier selektiert
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
                    //Fall angriff: brauche platzhalter für aktiven gegner daher = null, wenn gegnerlsite kein array oder leer ist setze in antwort erfolg auf true mit ausgabe für frontend mit spiel  und gegner
                case 'spielerAngriff':
                    $ergebnisAktuelleGegner = null;

                    if (!is_array($gegnerListe) || count($gegnerListe) === 0) {
                        $antwort = $antwortBasis;
                        $antwort['ausgabe'] = "Keine Gegner vorhanden oder alle besiegt";
                        $antwort['spiel'] = $ergebnisAktuellesSpiel;
                        $antwort['gegner'] = $gegnerListe;
                        echo json_encode($antwort);
                        exit;
                    }

                    //hier wird mit foreach durch die gegnerliste iteriert und dim platzhalöter gespeichert. & -> referenz = änderung direkt im array
                    foreach ($gegnerListe as &$gegner) {
                        //prüft ob gegner noch lebt
                        if ($gegner['leben'] > 0) {
                            //sepichert referenz auf aktuellegegner
                            $ergebnisAktuelleGegner = &$gegner;
                            //stoppt schleife beim ersten lebenden gegner
                            break;
                        }
                    }
                    //entfernt referenz, sonst bleiben spätere variablen verlinkt
                    unset($gegner);

                    //wenn kein lebender gegner gefunden, dann antowrt alle gegner besiergt und ausgabe
                    if (!$ergebnisAktuelleGegner) {
                        $ausgabeNachAngriff = "Alle gegner wurden bereits besiegt!";
                        $antwort = $antwortBasis;
                        $antwort['ausgabe'] = $ausgabeNachAngriff;
                        $antwort['spiel'] = $ergebnisAktuellesSpiel;
                        $antwort['gegner'] = $gegnerListe;
                        break;
                    }

                    //prüft ob charakter existiert
                    if (!$ergebnisAktuellerCharakter) {
                        throw new Exception("Charakter konnte nicht geladen werden");
                    }

                    //spielerAngriff: berechnet spielerschaden und zieht vom aktuellen gegner leben ab. max(0,..) -> keine negativen werte, danach ausgabe mit schaden summe 
                    $spielerSchaden = berechneSpielerSchaden($ergebnisAktuellerCharakter, $ergebnisAktuelleGegner);
                    $ergebnisAktuelleGegner['leben'] = max(0, $ergebnisAktuelleGegner['leben'] - $spielerSchaden);
                    $ausgabeNachAngriff = "Du hast {$spielerSchaden} Schaden an {$ergebnisAktuelleGegner['name']} verursacht!";

                    //es wird geprüft ob gegner besiegt wurde, dann werden punkte erhöht und die ausgabe geworfen. ausgabe wird mit .= erweitert, zusammengebaut
                    if ($ergebnisAktuelleGegner['leben'] === 0) {
                        $ergebnisAktuellesSpiel['punkte'] = $ergebnisAktuellesSpiel['punkte'] + 100;
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} wurde besiegt! Du erhälst 100 Punkte!";
                    }

                    //gegnerAngriff: wird direkt im selbn zug wie speilerangriff ausgeführt und berechnet gegnerschaden zieht vom charakter leben ab wieder mit max(0,..) und gibt die ausgabe aus, wenn gegner 0 schaden verursacht ausgabe: nciht getroffen
                    $gegnerSchaden = berechneGegnerSchaden($ergebnisAktuelleGegner, $ergebnisAktuellerCharakter);
                    if ($gegnerSchaden > 0) {
                        $ergebnisAktuellerCharakter['leben'] = max(0, $ergebnisAktuellerCharakter['leben'] - $gegnerSchaden);
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} schlägt zurück und verursacht: {$gegnerSchaden} Schaden!";
                    } else {
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} hat dich nicht getroffen!";
                    }

                    //es wird geprüft ob der charakter noch leben hat wenn 0 oder weniger dann funktion spielerbesiegt und ausgabe
                    if ($ergebnisAktuellerCharakter['leben'] <= 0) {
                        $antwort = spielerBesiegt($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellerCharakter, $ergebnisAktuellesSpiel);
                        $antwort['ausgabe'] = $ausgabeNachAngriff;
                        break;
                    }

                    //der zustand des charakter wird gespeichert
                    charakterAktualisieren($datenbank, $spieler_id, $ergebnisAktuellerCharakter);

                    //gegner wieder als json in ergebnisaktuellesspiel speichern , und dann mit funktion speicheraktuellesspiel in der DB abspeichern
                    $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);
                    speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel);

                    //variable plathhalter für prüfung auf allegegner tot
                    $gegnerBesiegt = true;
                    //schleife durch gegnerliste, prüfung ob gegner noch am leben wenn ja dann gegnerBesiegt false
                    foreach ($gegnerListe as $gegner) {
                        if ($gegner['leben'] > 0) {
                            $gegnerBesiegt = false;
                            break;
                        }
                    }

                    //wenn alle gegner besiegt wurden, dann wird gerpfüt ob runde 4 erreicht wurde und wenn das der fall ist, dann kommt ausgabe mit boss besiegt und nochmal spielen
                    if ($gegnerBesiegt) {
                        if ($ergebnisAktuellesSpiel['aktuelle_runde'] >= 4) {
                            $ausgabeNachAngriff .= "Du hast die Faulheit besiegt! Glückwunsch! Punkte: {$ergebnisAktuellesSpiel['punkte']} ! Drücke 'nochmal_spielen' um aufzuleveln und die nächste Runde zu starten!";
                        } else {
                            //wenn runde 4 noch nicht erreicht dann zähle runde hoch speicher das aktuelle spiel, erstelle für neue runde neue gegner nach runden anzahl, speichere gegner in generliste und gib ausgabe aus
                            $ergebnisAktuellesSpiel['aktuelle_runde']++;
                            speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel);
                            $neueGegner = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
                            $gegnerListe = $neueGegner;
                            $ausgabeNachAngriff .= "Neue Gegner erscheinen...";
                        }
                    }

                    //gegner status wird gespeichert in DB
                    gegnerStatusSpeichern($datenbank, $ergebnisAktuellesSpiel['id'], $gegnerListe);

                    //antwort ausgabe
                    $antwort = $antwortBasis;
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
        //fall nochmal spielen: kommt wenn boss besiegt wurde, aktuellespiel wird überschrieben mit funktion nochmal spielen welche spiel zurücksetzt schwierigkeit erhöht etc
        case 'nochmal_spielen':
                    $ergebnisNochmalSpielen = nochmalSpielen($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellesSpiel);
                    //lädt den charakter frishc aus der DB zwecks lvl up bzw veränderungen nach gewonnener runde, falls array leer ensteht fehler der danach mit if behandlet wird
                    $neuerCharakter = charakterLaden($datenbank, $spieler_id, $charakter_id)[0];
                    //wenn kein charakter geladen wurde , ergebnis leer oder null dann wirf exception und springt sofort in catch block da ohne charakter kein spiel
                    if (empty($neuerCharakter)) {
                        throw new Exception('Charakter konnte nicht geladen werden...');
                    }

                    //antwort ausgabe
                    $antwort = [
                        'schwierigkeit' => $ergebnisNochmalSpielen['schwierigkeit'],
                        'gegner' => $ergebnisNochmalSpielen['gegner'],
                        'runde' => $ergebnisAktuellesSpiel['aktuelle_runde'],
                        'charakter' => $neuerCharakter,
                        'hinweis' => $ergebnisNochmalSpielen['hinweis']
                    ];
                    //header setzen
                   header('Content-Type: application/json');
                   echo json_encode($antwort);
                   exit;       
                default: // wenn action kein bekannter fall dann fehler
                    http_response_code(400);
                    $antwort = $antwortMethodenFehler;
                    break;
            }
            break;
    }
} catch (Exception $e) { // fehler werde abgefangen und hier mit fehlerausgabe geworfen
    http_response_code(500);
    $antwort = [
        'erfolg' => false,
        'fehler' => $e->getMessage(),
        'trace' => $e->getTraceAsString() // debugging ich sehe wann welche funktion crasht
    ];
}
echo json_encode($antwort); // wenn kein exit vorher kam wird hier ausgegeben
