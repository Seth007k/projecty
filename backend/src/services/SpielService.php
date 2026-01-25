<?php
require_once __DIR__ . '/CharakterService.php';

//einstiegsfunktion im spiel: bekommt DB, SpüielerID, CharakterID um ein spiel zu laden
function ladeSpielUndCharakter($datenbank, $spieler_id, $charakter_id)
{
    //erst alten speilstand laden, wenn keiner vorhanden bzw funktin fehler zurückgibt dann lege neues spiel an, falls speil erstellen fehlgeschlagen ist: fehlerantwort an frontend
    $aktuellesSpiel = ladeAltenSpielstand($datenbank, $spieler_id, $charakter_id);

    if (isset($aktuellesSpiel['error'])) {
        $aktuellesSpiel = erstelleNeuesSpiel($datenbank, $spieler_id, $charakter_id);
        if (!$aktuellesSpiel) {
            return ['error' => 'Spiel konnte nicht erstellt werden!'];
        }
    }
    //lädt den charakter aus der DB prüft ob charakterListe leer ist wenn ja error
    $charakterListe = charakterLaden($datenbank, $spieler_id, $charakter_id);
    if (empty($charakterListe)) {
        return ['error' => 'Charakter nicht gefunden!'];
    }

    //rückgabe json aktuellerspielstand und erster charakter aus der liste
    return [
        'spiel' => $aktuellesSpiel,
        'charakter' => $charakterListe[0]
    ];
}

//lädt bestehenden spielstand aus db
function ladeAltenSpielstand($datenbank, $spieler_id, $charakter_id)
{
    //sucht spiel mit diesem charakter und diesem spieler aus DB
    $sqlAnweisungSpielLaden = $datenbank->prepare("SELECT * FROM spiele WHERE charakter_id=? AND spieler_id=? LIMIT 1");
    $sqlAnweisungSpielLaden->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungSpielLaden->execute();
    $aktuellesSpiel = $sqlAnweisungSpielLaden->get_result(); // holt ergebnis
    $ergebnisAktuellesSpiel = $aktuellesSpiel->fetch_assoc();// macht array draus

    //prüfung ob spielstand gefunden wurde
    if (!$ergebnisAktuellesSpiel) {
        return ['error' => 'Es wurde kein früherer Spielstand gefunden!'];
    }

    //initialisiert gegnerliste
    $gegnerListe = [];
    //prüft ob gegner in spiel vorhanden sind
    if (!empty($ergebnisAktuellesSpiel['gegner_status'])) {
        $gegnerListe = json_decode($ergebnisAktuellesSpiel['gegner_status'], true) ?? []; //?? [] fallback
    }

    //Wenn keine gegner existieren dann neue gegner generieren, gegnerliste in json umwandeln, und gegner im spiel speichern
    if (empty($gegnerListe)) {
        $gegnerListe = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
        $gegnerAusJson = json_encode($gegnerListe);

        $sqlAnweisungAktualisiereGegner = $datenbank->prepare("UPDATE spiele SET gegner_status = ?  WHERE id = ?");
        $sqlAnweisungAktualisiereGegner->bind_param("si", $gegnerAusJson, $ergebnisAktuellesSpiel['id']);
        $sqlAnweisungAktualisiereGegner->execute();
    }
    //stellt sicher dass ich immer den aktuellen stand der gegner   im aktuellem spiel habe, dann return
    $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);
    return $ergebnisAktuellesSpiel;
}

//erstellt neuen spielstand
function erstelleNeuesSpiel($datenbank, $spieler_id, $charakter_id) 
{
    //erst werden charaktere geladen aus DB
    $aktuelleCharaktere = charakterLaden($datenbank, $spieler_id, $charakter_id);
    //wenn aktuelleCharakterre leer sind dann error
    if (empty($aktuelleCharaktere)) {
        return ['error' => 'Charakter nicht gefunden!'];
    }

    //der erste charakter aus aktuelleCharakter wird in charakter gespeichert, dann bekommt spiel startwerte
    $charakter = $aktuelleCharaktere[0];
    $aktuelleRunde = 1;
    $punkte = 0;
    $schwierigkeit = 1;

    //hier werden gegner generiert und als json in gegnerAusJson gespeichert
    $gegnerListe = erstelleGegner($aktuelleRunde, $schwierigkeit);
    $gegnerAusJson = json_encode($gegnerListe);

    //hier wird das spiel in der DB erstellt, und danach wird das ergebnis aus akteullen spieldaten ausgegeben mit ergebnisaktuellesspiel return
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

//gegner erstellen
function erstelleGegner($runde, $schwierigkeit)
{
    //jeder runde + 1 gegner bei runde 4 1 gegner
    $gegnerProRunde = [1 => 1, 2 => 2, 3 => 3, 4 => 1];
    //fallback 1, falls runde nicht existiert
    $anzahlGegnerProRunde = $gegnerProRunde[$runde] ?? 1;

    //leerey array für gegner in dieser runde
    $gegnerListe = [];
    
    //schleife läfut so oft gegner erstellt werden sollen
    for ($i = 0; $i < $anzahlGegnerProRunde; $i++) {
        //wenn runde 4 erreicht speicher json boss in gegnerliste[], in jeder anderen runde erstelle goblins
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
    return $gegnerListe; // rückagbe liste
}

//erst wird der schaden berechnet nach abzug der halben verteidgung das gegners mit dem angriff des spielers, dann wird schaden gerundet und mindestens auf 1 gesetzt,rand für zufallsschaden damit nicht immer derselbe dmg gemacht wird(varriert bissle) aber auch nach zufalls mindestens 1
function berechneSpielerSchaden($ergebnisAktuellerCharakter, $ergebnisAktuelleGegner)
{
    $schadenNormal = $ergebnisAktuellerCharakter['angriff'] - ($ergebnisAktuelleGegner['verteidigung'] * 0.5);
    $schadenNormal = max(1, (int) round($schadenNormal));

    $spielerSchaden = $schadenNormal + rand(-2, 2);
    $spielerSchaden = max(1, $spielerSchaden);
    return $spielerSchaden;
}

//gegnerschaden: erstmal hat gegner eine 50 50 chance zu treffen daher erste if, wenn trifft dann angriff gegner minus verteidigung spieler (kann man noch dran drehen für mehr spannung ingame) macht mind. 1 schaden 
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

//Updated den charakter bzw speichert aktuelle werte in der DB, rückgabewert gibt zurück ob etwas geändert wurde   !!! MUSS eigentlich bei charakterservice rein!!!!
function charakterAktualisieren($datenbank, $spieler_id, $charakter)
{
    $sqlAnweisungAktualisiereCharakter = $datenbank->prepare("UPDATE charakter SET leben =?, angriff = ?, verteidigung = ?, level = ? WHERE id = ? AND spieler_id =?");

    $leben = $charakter['leben'];
    $angriff = $charakter['angriff'];
    $verteidigung = $charakter['verteidigung'];
    $level = $charakter['level'];
    $charakter_id = $charakter['id'];

    $sqlAnweisungAktualisiereCharakter->bind_param("iiiiii", $leben, $angriff, $verteidigung, $level, $charakter_id, $spieler_id);
    $sqlAnweisungAktualisiereCharakter->execute();
    //statement schliessen
    $sqlAnweisungAktualisiereCharakter->close();

    return $datenbank->affected_rows > 0;
}

//gegner -> json, speichert aktuelle gegner in der DB und gibt zurück ob was geändert wurde
function gegnerStatusSpeichern($datenbank, $spiel_id, $gegnerListe)
{
    $gegnerAusJson = json_encode($gegnerListe);
    $sqlAnweisungAktualisiereGegner = $datenbank->prepare("UPDATE spiele SET gegner_status = ?, gespeichert_am = NOW() WHERE id = ?");
    $sqlAnweisungAktualisiereGegner->bind_param("si", $gegnerAusJson, $spiel_id);
    $sqlAnweisungAktualisiereGegner->execute();

    return $sqlAnweisungAktualisiereGegner->affected_rows > 0;
}

//wird aufgerufen wenn spieler stirbt, löscht alle spiele des charakters löscht den charakter selbst udn gibt antwort aus
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

//speichert runde und punkte
function speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel)
{
    $sqlAnweisungSpielSpeichern = $datenbank->prepare("UPDATE spiele SET  aktuelle_runde = ?, punkte = ?, gespeichert_am = NOW() WHERE id = ?");
    $sqlAnweisungSpielSpeichern->bind_param("iii", $ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['punkte'], $ergebnisAktuellesSpiel['id']);
    $sqlAnweisungSpielSpeichern->execute();
}

//lvl up, neues spiel erstellen charakter wird stärker  in der db, schwieirgkeit wird erhöt dadurch werden werte multipliziert  von gegner, neue runde wird gesetzt neue gegner erstellt etc. dann return ausgabe neues game
function nochmalSpielen($datenbank, $spieler_id, $charakter_id, $ergebnisAktuellesSpiel)
{
    $sqlAnweisungLevelUp = $datenbank->prepare("UPDATE charakter SET level = level + 1, angriff = angriff + 5, leben = leben + 10, verteidigung = verteidigung + 5 WHERE id = ? AND spieler_id = ?");
    $sqlAnweisungLevelUp->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungLevelUp->execute();

    $neueSchwierigkeit = $ergebnisAktuellesSpiel['schwierigkeit'] + 1;
    $neueRunde = 1;
 

    $gegnerListe = erstelleGegner($neueRunde, $neueSchwierigkeit);
    $gegnerAusJson = json_encode($gegnerListe);

    $sqlAnweisungSpielZuruecksetzen = $datenbank->prepare("UPDATE spiele SET aktuelle_runde = ?, schwierigkeit = ?, gespeichert_am = NOW(), gegner_status = ?, punkte = ? WHERE charakter_id = ? AND spieler_id = ? ");
    $sqlAnweisungSpielZuruecksetzen->bind_param("isiiii", $neueRunde,$neueSchwierigkeit, $gegnerAusJson, $ergebnisAktuellesSpiel['punkte'], $charakter_id, $spieler_id);
    $sqlAnweisungSpielZuruecksetzen->execute();

    $sqlAnweisungLadeCharakter = $datenbank->prepare("SELECT * FROM charakter WHERE id =? AND spieler_id =?");
    $sqlAnweisungLadeCharakter->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungLadeCharakter->execute();
    $ergebnisLadecharakter = $sqlAnweisungLadeCharakter->get_result();
    $ergebnisLadeCharakter = $ergebnisLadecharakter->fetch_assoc();
    return ['hinweis' => 'Level Up! Neues Spiel wurde gestartet - viel Erfolg!', 'gegner' => $gegnerListe, 'schwierigkeit' => $neueSchwierigkeit, 'charakter' => $ergebnisLadeCharakter, 'aktuelle_runde' => $neueRunde];
}

//
function spielerAngriff($datenbank, $ergebnisAktuellesSpiel, $ergebnisAktuellerCharakter)
{
    //Gegnerliste wird aus spielstand geladen
    $gegnerListe = json_decode($ergebnisAktuellesSpiel['gegner_status'], true) ?? [];

    //wenn gegnerliste leer dann erstelle welche
    if (empty($gegnerListe)) {
        $gegnerListe = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
    }
    
    //Spieler greift an
    $gegner = &$gegnerListe[0];
    $schadenSpieler = berechneSpielerSchaden($ergebnisAktuellerCharakter, $gegner);
    $gegner['leben'] = max(0, $gegner['leben'] - $schadenSpieler);

    //Gegner greift an falls am leben
    if ($gegner['leben'] > 0) {
        $schadenGegner = berechneGegnerSchaden($gegner, $ergebnisAktuellerCharakter);
        $ergebnisAktuellerCharakter['leben'] = max(0, $ergebnisAktuellerCharakter['leben'] - $schadenGegner);
    }

    //Gegnerstatus speichern
    gegnerStatusSpeichern($datenbank, $ergebnisAktuellesSpiel['id'], $gegnerListe);

    //Wurde der Spieler besiegt? 
    if ($ergebnisAktuellerCharakter['leben'] <= 0) {
        return spielerBesiegt($datenbank, $ergebnisAktuellerCharakter['spieler_id'] ?? $ergebnisAktuellesSpiel['spieler_id'], $ergebnisAktuellerCharakter['id'], $ergebnisAktuellerCharakter, $ergebnisAktuellesSpiel);
    }

    //punkte erhöhen, runde updaten, neue gegner erstellen
    if ($gegner['leben'] === 0) {
        $ergebnisAktuellesSpiel['punkte'] += 10;
        $ergebnisAktuellesSpiel['aktuelle_runde'] += 1;

        $gegnerListe = erstelleGegner($ergebnisAktuellesSpiel['aktuelle_runde'], $ergebnisAktuellesSpiel['schwierigkeit']);
        $ergebnisAktuellesSpiel['gegner_status'] = json_encode($gegnerListe);
        gegnerStatusSpeichern($datenbank, $ergebnisAktuellesSpiel['id'], $gegnerListe);
    }

    //Spielstand speichern
    speicherAktuellesSpiel($datenbank, $ergebnisAktuellesSpiel);

    //Ergebnis zurückgeben
    return [
        'charakter' => $ergebnisAktuellerCharakter,
        'gegner_status' => json_encode($gegnerListe),
        'gegner_liste' => $gegnerListe,
        'punkte' => $ergebnisAktuellesSpiel['punkte'],
        'aktuelle_runde' => $ergebnisAktuellesSpiel['aktuelle_runde'],
        'game_over' => false
    ];
}
