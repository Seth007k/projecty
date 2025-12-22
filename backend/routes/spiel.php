<?php
require_once __DIR__ . '/../src/services/Database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleWare.php';

header('Content-Type: application/json');
requireAuth();

$methode = $_SERVER['REQUEST_METHOD'];
$spieler_id = $_SESSION['benutzer_id'];

$eingabedaten = json_decode(file_get_contents('php://input'), true);
$antwortDatenFehler = ['erfolg' => false, 'fehler' => 'Bitte alle benötigten Daten ausfüllen!'];
$antwortMethodenFehler = ['erfolg' => false, 'fehler' => 'Methode nicht erlaubt!'];
$antwortSpielNichtGefunden = ['erfolg' => false, 'fehler' => 'Spiel wurden nicht gefunden oder existiert nicht!'];
$antwortCharakterNichtGefunden = ['erfolg' => false, 'fehler' => 'Der charakter wurde nicht gefunden oder existiert nicht!'];
$antwortKeineCharakterId = ['erfolg' => false, 'fehler' => 'Es wurde keine Charakter ID angegeben!'];
$antwortServerFehler = ['erfolg' => false, 'fehler' => 'Serverfehler!'];
$antwortErfolg = ['erfolg' => true];


function erstelleGegner($runde, $schwierigkeit)
{
    $gegnerListe = [];

    $gegnerProRunde = [1 => 1, 2 => 2, 3 => 3, 4 => 1];
    $anzahlGegnerProRunde = $gegnerProRunde[$runde] ?? 1;

    for ($i = 0; $i < $anzahlGegnerProRunde; $i++) {
        if ($runde == 4) {
            $gegnerListe[] = [
                'name' => 'Boss - Faulheit',
                'leben' => 1000 * $schwierigkeit,
                'angriff' => 15 * $schwierigkeit,
                'verteidigung' => 5 * $schwierigkeit
            ];
        } else {
            $gegnerListe[] = [
                'name' => 'Goblin der Pflichten',
                'leben' => 200 * $schwierigkeit,
                'angriff' => 25 * $schwierigkeit,
                'verteidigung' => 10 * $schwierigkeit
            ];
        }
    }
    return $gegnerListe;
}



try {

    $datenbank = getDB();
    if (!$datenbank) {
        echo ('Datenbankverbindung fehlgeschlagen');
    }

    switch ($methode) {

        case 'GET':
            $charakterId = $_GET['charakter_id'] ?? null;

            if (!$charakterId) {
                http_response_code(400);
                $antwort = $antwortKeineCharakterId;
                break;
            }

            $sqlAnweisung = $datenbank->prepare("SELECT * FROM spiele WHERE charakter_id=? AND spieler_id=? LIMIT 1");
            $sqlAnweisung->bind_param("ii", $charakterId, $spieler_id);
            $sqlAnweisung->execute();
            $aktuellesSpiel = $sqlAnweisung->get_result();
            $ergebnisAktuellesSpiel = $aktuellesSpiel->fetch_assoc();

            if (!$ergebnisAktuellesSpiel) {
                $aktuelleRunde = 1;
                $punkte = 0;
                $schwierigkeit = 1;

                $gegnerListe = erstelleGegner($aktuelleRunde, $schwierigkeit);
                $gegnerAusJson = json_encode($gegnerListe);

                $sqlAnweisungSpielErstellen = $datenbank->prepare("INSERT INTO spiele (spieler_id, charakter_id, aktuelle_runde, punkte, schwierigkeit, gespeichert_am, gegner_status) VALUES (?,?,?,?,?,NOW(),?)");
                $sqlAnweisungSpielErstellen->bind_param("iiiiis", $spieler_id, $charakterId, $aktuelleRunde, $punkte, $schwierigkeit, $gegnerAusJson);
                $sqlAnweisungSpielErstellen->execute();

                $ergebnisAktuellesSpiel = [
                    'id' => $sqlAnweisungSpielErstellen->insert_id,
                    'spieler_id' => $spieler_id,
                    'charakter_id' => $charakterId,
                    'aktuelle_runde' => $aktuelleRunde,
                    'punkte' => $punkte,
                    'schwierigkeit' => $schwierigkeit,
                    'gespeichert_am' => date("Y-m-d H:i:s"),
                    'gegner_status' => $gegnerAusJson
                ];
            } else {
                $gegnerListe = $ergebnisAktuellesSpiel['gegner_status'] ? json_decode($ergebnisAktuellesSpiel['gegner_status'], true) : [];

                if (empty($gegnerListe)) {
                    $gegnerListe = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
                    $gegnerAusJson = json_encode($gegnerListe);

                    $sqlAnweisungAktualisiereGegner = $datenbank->prepare("UPDATE spiele SET gegner_status = ?  WHERE id = ?");
                    $sqlAnweisungAktualisiereGegner->bind_param("si", $gegnerAusJson, $ergebnisAktuellesSpiel['id']);
                    $sqlAnweisungAktualisiereGegner->execute();

                    $ergebnisAktuellesSpiel['gegner_status'] = $gegnerAusJson;
                }
            }
            $antwort = $antwortErfolg;
            $antwort['spiel'] = $ergebnisAktuellesSpiel;
            $antwort['gegner'] = $gegnerListe;
            break;


        case 'POST':
            $charakter_id = $eingabedaten['charakter_id'] ?? null;
            $benutzerAktion = $eingabedaten['aktion'] ?? null;

            if (!$charakter_id || !$benutzerAktion) {
                http_response_code(400);
                $antwort = $antwortDatenFehler;
                break;
            }

            //Spielstand laden
            $sqlAnweisungSpielLaden = $datenbank->prepare("SELECT * FROM spiele WHERE charakter_id=? AND spieler_id=?");
            $sqlAnweisungSpielLaden->bind_param("ii", $charakter_id, $spieler_id);
            $sqlAnweisungSpielLaden->execute();
            $aktuellesSpiel = $sqlAnweisungSpielLaden->get_result();
            $ergebnisAktuellesSpiel = $aktuellesSpiel->fetch_assoc();


            if (!$ergebnisAktuellesSpiel) {
                http_response_code(404);
                $antwort = $antwortSpielNichtGefunden;
                $antwort['debug'] = [
                        'charakter_id' => $charakter_id,
                        'spieler_id' => $spieler_id,
                        'ergebnisAktuellesSpiel' => $ergebnisAktuellesSpiel
                    ];
                break;
            }

            $gegnerListe = json_decode($ergebnisAktuellesSpiel['gegner_status'], true);

            //Charakter laden
            $sqlAnweisungCharakterLaden = $datenbank->prepare("SELECT * FROM charakter WHERE id=? AND spieler_id=?");
            $sqlAnweisungCharakterLaden->bind_param("ii", $charakter_id, $spieler_id);
            $sqlAnweisungCharakterLaden->execute();
            $aktuellerCharakter = $sqlAnweisungCharakterLaden->get_result();
            $ergebnisAktuellerCharakter = $aktuellerCharakter->fetch_assoc();

            if (!$ergebnisAktuellerCharakter) {
                http_response_code(404);
                $antwort = $antwortCharakterNichtGefunden;
                break;
            }

            switch ($benutzerAktion) {
                case 'angriff':
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

                    //Spieler angriff
                    $schadenNormal = $ergebnisAktuellerCharakter['angriff'] - ($ergebnisAktuelleGegner['verteidigung'] * 0.5);
                    $schadenNormal = max(1, (int) round($schadenNormal));

                    $spielerSchaden = $schadenNormal + rand(-2, 2);
                    $spielerSchaden = max(1, $spielerSchaden);


                    $ergebnisAktuelleGegner['leben'] = $ergebnisAktuelleGegner['leben'] - $spielerSchaden;
                    if ($ergebnisAktuelleGegner['leben'] < 0) {
                        $ergebnisAktuelleGegner['leben'] = 0;
                    }
                    $ausgabeNachAngriff = "Du hast {$spielerSchaden} Schaden an {$ergebnisAktuelleGegner['name']} verursacht!";

                    if ($ergebnisAktuelleGegner['leben'] === 0) {
                        $ergebnisAktuellesSpiel['punkte'] = $ergebnisAktuellesSpiel['punkte'] + 100;
                        $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} wurde besiegt! Du erhälst 100 Punkte!";
                    }

                    //Gegner angriff
                    if ($ergebnisAktuelleGegner['leben'] > 0) {
                        if (rand(0, 1) === 1) {
                            $gegnerSchaden = $ergebnisAktuelleGegner['angriff'] - $ergebnisAktuellerCharakter['verteidigung'];
                            $gegnerSchaden = max(1, $gegnerSchaden);
                            $ergebnisAktuellerCharakter['leben'] = $ergebnisAktuellerCharakter['leben'] - $gegnerSchaden;
                            $ergebnisAktuellerCharakter['leben'] = max(0, $ergebnisAktuellerCharakter['leben']);
                            $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} schlägt zurück und verursacht: {$gegnerSchaden} Schaden!";
                        } else {
                            $ausgabeNachAngriff .= " {$ergebnisAktuelleGegner['name']} hat dich nicht getroffen!";
                        }
                    }

                    if ($ergebnisAktuellerCharakter['leben'] <= 0) {

                        $ausgabeNachAngriff .= " Du wurdest besiegt! Game over!";

                        $sqlAnweisungSpieleDesCharaktersLöschen = $datenbank->prepare("DELETE FROM spiele WHERE charakter_id = ?");
                        $sqlAnweisungSpieleDesCharaktersLöschen->bind_param("i", $charakter_id);
                        $sqlAnweisungSpieleDesCharaktersLöschen->execute();

                        $sqlAnweisungCharakterLöschen = $datenbank->prepare("DELETE FROM charakter WHERE id=? AND spieler_id=?");
                        $sqlAnweisungCharakterLöschen->bind_param("ii", $charakter_id, $spieler_id);
                        $sqlAnweisungCharakterLöschen->execute();

                        $antwort = $antwortErfolg;
                        $antwort['ausgabe'] = $ausgabeNachAngriff;
                        $antwort['spieler'] = [
                            'name' => $ergebnisAktuellerCharakter['name'],
                            'leben' => $ergebnisAktuellerCharakter['leben'],
                            'angriff' => $ergebnisAktuellerCharakter['angriff'],
                            'verteidigung' => $ergebnisAktuellerCharakter['verteidigung'],
                            'level' => $ergebnisAktuellerCharakter['level'],
                            'punkte' => $ergebnisAktuellesSpiel['punkte']
                        ];
                        $antwort['game_over'] = true;
                        break;
                    }

                    $sqlAnweisungCharakterAktualisieren = $datenbank->prepare("UPDATE charakter SET name = ?, level = ?, leben = ?, angriff = ?, verteidigung = ? WHERE id = ? AND spieler_id = ?");
                    $sqlAnweisungCharakterAktualisieren->bind_param("siiiiii", $ergebnisAktuellerCharakter['name'], $ergebnisAktuellerCharakter['level'], $ergebnisAktuellerCharakter['leben'], $ergebnisAktuellerCharakter['angriff'], $ergebnisAktuellerCharakter['verteidigung'], $charakter_id, $spieler_id);
                    $sqlAnweisungCharakterAktualisieren->execute();

                    $gegnerBesiegt = true;
                    foreach ($gegnerListe as $gegner) {
                        if ($gegner['leben'] > 0) {
                            $gegnerBesiegt = false;
                            break;
                        }
                    }

                    if ($gegnerBesiegt) {

                        $sqlAnweisungSpielSpeichern = $datenbank->prepare("UPDATE spiele SET  punkte = ?, gespeichert_am = NOW() WHERE id = ?");
                        $sqlAnweisungSpielSpeichern->bind_param("ii", $ergebnisAktuellesSpiel['punkte'], $ergebnisAktuellesSpiel['id']);
                        $sqlAnweisungSpielSpeichern->execute();

                        if ($ergebnisAktuellesSpiel['aktuelle_runde'] >= 4) {
                            $ausgabeNachAngriff .= "Du hast die Faulheit besiegt! Glückwunsch! Punkte: {$ergebnisAktuellesSpiel['punkte']} ! Drücke 'nochmal_spielen' um aufzuleveln und die nächste Runde zu starten!";
                        } else {
                            $ergebnisAktuellesSpiel['aktuelle_runde']++;


                            $neueGegner = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
                            $gegnerListe = $neueGegner;

                            $ausgabeNachAngriff .= "Neue Gegner erscheinen...";
                        }
                    }


                    //Spielstand speichern 

                    $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);

                    $sqlAnweisungSpielSpeichern = $datenbank->prepare("UPDATE spiele SET aktuelle_runde = ?, punkte = ?, schwierigkeit = ?, gegner_status = ?, gespeichert_am = NOW() WHERE id = ?");
                    $sqlAnweisungSpielSpeichern->bind_param("iiisi", $ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['punkte'], $ergebnisAktuellesSpiel['schwierigkeit'], $ergebnisAktuellesSpiel['gegner_status'], $ergebnisAktuellesSpiel['id']);
                    $sqlAnweisungSpielSpeichern->execute();

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
                    $sqlAnweisungLevelUp = $datenbank->prepare("UPDATE charakter SET level = level + 1, angriff = angriff + 5, leben = leben + 10, verteidigung = verteidigung + 5 WHERE id = ? AND spieler_id = ?");
                    $sqlAnweisungLevelUp->bind_param("ii", $charakter_id, $spieler_id);
                    $sqlAnweisungLevelUp->execute();

                    $neueSchwierigkeit = $ergebnisAktuellesSpiel['schwierigkeit'] + 1;

                    $gegnerListe = erstelleGegner(1, $neueSchwierigkeit);
                    $gegnerAusJson = json_encode($gegnerListe);

                    //Spielstand zurücksetzen
                    $sqlAnweisungSpielZuruecksetzen = $datenbank->prepare("UPDATE spiele SET aktuelle_runde = 1, schwierigkeit = ?, gespeichert_am = NOW(), gegner_status = ?, punkte = ? WHERE charakter_id = ? AND spieler_id = ? ");
                    $sqlAnweisungSpielZuruecksetzen->bind_param("isiii", $neueSchwierigkeit, $gegnerAusJson, $ergebnisAktuellesSpiel['punkte'], $charakter_id, $spieler_id);
                    $sqlAnweisungSpielZuruecksetzen->execute();

                    $antwort = $antwortErfolg;
                    $antwort['hinweis'] = 'Level Up! Neues Spiel wurde gestartet - viel Erfolg!';
                    break;


                default:
                    http_response_code(400);
                    $antwort = $antwortMethodenFehler;
                    break;
            }
    }
} catch (Exception $e) {
    http_response_code(500);
    $antwort = $antwortServerFehler;
    $antwort['debug'] = $e->getMessage();
}
echo json_encode($antwort);
