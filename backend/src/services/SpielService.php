<?php 
require_once __DIR__ . '/CharakterService.php';


function ladeSpielUndCharakter($datenbank, $spieler_id, $charakter_id)
{
    $aktuellesSpiel = ladeAltenSpielstand($datenbank, $spieler_id, $charakter_id);

    if (isset($aktuellesSpiel['error'])) {
        $aktuellesSpiel = erstelleNeuesSpiel($datenbank, $spieler_id, $charakter_id);
        if (!$aktuellesSpiel) {
            return ['error' => 'Spiel konnte nicht erstellt werden!'];
        }
    }

    $charakterListe = charakterLaden($datenbank, $spieler_id, $charakter_id);
    if (empty($charakterListe)) {
            return ['error' => 'Charakter nicht gefunden!'];
    }

    return [
        'spiel' => $aktuellesSpiel,
        'charakter' => $charakterListe[0]
    ];
}

function ladeAltenSpielstand($datenbank, $spieler_id, $charakter_id)
{
    $sqlAnweisungSpielLaden = $datenbank->prepare("SELECT * FROM spiele WHERE charakter_id=? AND spieler_id=? LIMIT 1");
    $sqlAnweisungSpielLaden->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungSpielLaden->execute();
    $aktuellesSpiel = $sqlAnweisungSpielLaden->get_result();
    $ergebnisAktuellesSpiel = $aktuellesSpiel->fetch_assoc();

    if (!$ergebnisAktuellesSpiel) {
        return ['error' => 'Es wurde kein früherer Spielstand gefunden!'];
    }

    $gegnerListe = [];
    if (!empty($ergebnisAktuellesSpiel['gegner_status'])) {
        $gegnerListe = json_decode($ergebnisAktuellesSpiel['gegner_status'], true) ?? [];
    }

    if (empty($gegnerListe)) {
        $gegnerListe = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
        $gegnerAusJson = json_encode($gegnerListe);

        $sqlAnweisungAktualisiereGegner = $datenbank->prepare("UPDATE spiele SET gegner_status = ?  WHERE id = ?");
        $sqlAnweisungAktualisiereGegner->bind_param("si", $gegnerAusJson, $ergebnisAktuellesSpiel['id']);
        $sqlAnweisungAktualisiereGegner->execute();
    }

    $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);
    return $ergebnisAktuellesSpiel;
}

function erstelleNeuesSpiel($datenbank, $spieler_id, $charakter_id) // hier
{
    $aktuelleCharaktere = charakterLaden($datenbank, $spieler_id, $charakter_id);
    if (empty($aktuelleCharaktere)) {
        return ['error' => 'Charakter nicht gefunden!'];
    }

    $charakter = $aktuelleCharaktere[0];
    $aktuelleRunde = 1;
    $punkte = 0;
    $schwierigkeit = 1;

    $gegnerListe = erstelleGegner($aktuelleRunde, $schwierigkeit);
    $gegnerAusJson = json_encode($gegnerListe);

    $sqlAnweisungSpielErstellen = $datenbank->prepare("INSERT INTO spiele (spieler_id, charakter_id, aktuelle_runde, punkte, schwierigkeit, gespeichert_am, gegner_status) VALUES (?,?,?,?,?,NOW(),?)");
    $sqlAnweisungSpielErstellen->bind_param("iiiiis", $spieler_id, $charakter_id, $aktuelleRunde, $punkte, $schwierigkeit, $gegnerAusJson);
    $sqlAnweisungSpielErstellen->execute();

    $ergebnisAktuellesSpiel = [
        'id' => $sqlAnweisungSpielErstellen->insert_id,
        'spieler_id' => $spieler_id,
        'charakter_id' => $charakter_id,
        'aktuelle_runde' => $aktuelleRunde,
        'punkte' => $punkte,
        'schwierigkeit' => $schwierigkeit,
        'gespeichert_am' => date("Y-m-d H:i:s"),
        'gegner_status' => $gegnerAusJson,
        'charakter' => $charakter
    ];

    return $ergebnisAktuellesSpiel;
}

function erstelleGegner($runde, $schwierigkeit)
{
    $gegnerProRunde = [1 => 1, 2 => 2, 3 => 3, 4 => 1];
    $anzahlGegnerProRunde = $gegnerProRunde[$runde] ?? 1;

    $gegnerListe = [];
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

function berechneSpielerSchaden($ergebnisAktuellerCharakter, $ergebnisAktuelleGegner)
{
    $schadenNormal = $ergebnisAktuellerCharakter['angriff'] - ($ergebnisAktuelleGegner['verteidigung'] * 0.5);
    $schadenNormal = max(1, (int) round($schadenNormal));

    $spielerSchaden = $schadenNormal + rand(-2, 2);
    $spielerSchaden = max(1, $spielerSchaden);
    return $spielerSchaden;
}

function berechneGegnerSchaden($ergebnisAktuelleGegner, $ergebnisAktuellerCharakter)
{
    if (rand(0, 1) === 1) {
        return 0;
    } else {
        $gegnerSchaden = $ergebnisAktuelleGegner['angriff'] - $ergebnisAktuellerCharakter['verteidigung'];
        $gegnerSchaden = max(1, $gegnerSchaden);
        return $gegnerSchaden;
    }
}

function gegnerStatusSpeichern($datenbank, $spiel_id, $gegnerListe)
{
    $gegnerAusJson = json_encode($gegnerListe);
    $sqlAnweisungAktualisiereGegner = $datenbank->prepare("UPDATE spiele SET gegner_status = ?, gespeichert_am = NOW() WHERE id = ?");
    $sqlAnweisungAktualisiereGegner->bind_param("si", $gegnerAusJson, $spiel_id);
    $sqlAnweisungAktualisiereGegner->execute();

    return $sqlAnweisungAktualisiereGegner->affected_rows > 0;
}

function spielerBesiegt($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellerCharakter, $ergebnisAktuellesSpiel)
{
    $sqlAnweisungSpieleDesCharaktersLöschen = $datenbank->prepare("DELETE FROM spiele WHERE charakter_id = ? AND spieler_id = ?");
    $sqlAnweisungSpieleDesCharaktersLöschen->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungSpieleDesCharaktersLöschen->execute();

    $sqlAnweisungCharakterLöschen = $datenbank->prepare("DELETE FROM charakter WHERE id=? AND spieler_id=?");
    $sqlAnweisungCharakterLöschen->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungCharakterLöschen->execute();

    $antwort = [
        'name' => $ergebnisAktuellerCharakter['name'],
        'leben' => $ergebnisAktuellerCharakter['leben'],
        'angriff' => $ergebnisAktuellerCharakter['angriff'],
        'verteidigung' => $ergebnisAktuellerCharakter['verteidigung'],
        'level' => $ergebnisAktuellerCharakter['level'],
        'punkte' => $ergebnisAktuellesSpiel['punkte'],
        'game_over' => true
    ];
    return $antwort;
}

function speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel)
{
    $sqlAnweisungSpielSpeichern = $datenbank->prepare("UPDATE spiele SET  aktuelle_runde = ?, punkte = ?, gespeichert_am = NOW() WHERE id = ?");
    $sqlAnweisungSpielSpeichern->bind_param("iii", $ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['punkte'], $ergebnisAktuellesSpiel['id']);
    $sqlAnweisungSpielSpeichern->execute();
}

function nochmalSpielen($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellesSpiel)
{
    $sqlAnweisungLevelUp = $datenbank->prepare("UPDATE charakter SET level = level + 1, angriff = angriff + 5, leben = leben + 10, verteidigung = verteidigung + 5 WHERE id = ? AND spieler_id = ?");
    $sqlAnweisungLevelUp->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungLevelUp->execute();

    $neueSchwierigkeit = $ergebnisAktuellesSpiel['schwierigkeit'] + 1;

    $gegnerListe = erstelleGegner(1, $neueSchwierigkeit);
    $gegnerAusJson = json_encode($gegnerListe);

    $sqlAnweisungSpielZuruecksetzen = $datenbank->prepare("UPDATE spiele SET aktuelle_runde = 1, schwierigkeit = ?, gespeichert_am = NOW(), gegner_status = ?, punkte = ? WHERE charakter_id = ? AND spieler_id = ? ");
    $sqlAnweisungSpielZuruecksetzen->bind_param("isiii", $neueSchwierigkeit, $gegnerAusJson, $ergebnisAktuellesSpiel['punkte'], $charakter_id, $spieler_id);
    $sqlAnweisungSpielZuruecksetzen->execute();
    return ['hinweis' => 'Level Up! Neues Spiel wurde gestartet - viel Erfolg!', 'gegner' => $gegnerListe, 'schwierigkeit' => $neueSchwierigkeit];
}