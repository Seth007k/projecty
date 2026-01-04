<?php
require_once __DIR__ . '/Database.php';



function getSpielerId()
{
    return $_SESSION['benutzer_id'] ?? null;
}

function existiertBenutzer($datenbank, $benutzername){

    $sqlAnweisungExistiertBenutzer = $datenbank->prepare('SELECT id FROM spieler WHERE benutzername = ?');
    $sqlAnweisungExistiertBenutzer->bind_param("s", $benutzername);
    $sqlAnweisungExistiertBenutzer->execute();
    $sqlAnweisungExistiertBenutzer->store_result();

    return $sqlAnweisungExistiertBenutzer->num_rows > 0;
}

function registriereBenutzer($datenbank, $eingabeDaten)
{
   
    $benutzername = $eingabeDaten['benutzername'];
    $passwort = $eingabeDaten['passwort'];

    if (!$benutzername || !$passwort) {
        throw new Exception('Benutzername und Passwort erforderlich', 400);
    }

    if (existiertBenutzer($datenbank, $benutzername)){
        throw new Exception('Benutzername existiert bereits! Neuen Namen wählen', 409);
    } 

    $gehashtesPw = password_hash($passwort, PASSWORD_DEFAULT);

    $sqlAnweisungUserAnlegen = $datenbank->prepare('INSERT INTO spieler (benutzername, passwort) VALUES (?,?)');
    $sqlAnweisungUserAnlegen->bind_param("ss", $benutzername, $gehashtesPw);
    $sqlAnweisungUserAnlegen->execute();

    $benutzer_id = $datenbank->insert_id;

    return $benutzer_id;
}

function ladeBenutzer($datenbank, $benutzername) {

    $sqlAnweisungLadeBenutzer = $datenbank->prepare('SELECT id, passwort FROM spieler WHERE benutzername = ?');
    $sqlAnweisungLadeBenutzer->bind_param("s", $benutzername);
    $sqlAnweisungLadeBenutzer->execute();
    
    $ergebnisGeladenenerBenutzer = $sqlAnweisungLadeBenutzer->get_result();
    $ergebnisGeladenenerBenutzer = $ergebnisGeladenenerBenutzer->fetch_assoc();

    return $ergebnisGeladenenerBenutzer;
}

function pruefePasswort($ergebnisGeladenenerBenutzer, $passwort)  {

    if(!$ergebnisGeladenenerBenutzer || !password_verify($passwort, $ergebnisGeladenenerBenutzer['passwort'])) {
        throw new Exception('Login fehlgeschlagen!');
    }
}

function loginDatenVorhanden($benutzername, $passwort)
{
    if (empty($benutzername) || empty($passwort)) {
        throw new Exception('Es wird ein benutzername und Passwort benötigt!');
    }
}

function registriereUndEinloggen($datenbank, $eingabeDaten):int {
    loginDatenVorhanden($eingabeDaten['benutzername'], $eingabeDaten['passwort']);

    $benutzer_id = registriereBenutzer($datenbank, $eingabeDaten);

    $_SESSION['benutzer_id'] = $benutzer_id;

    return $benutzer_id;
}

function loginBenutzer($datenbank, $eingabeDaten):int {
    $benutzername = $eingabeDaten['benutzername'] ?? '';
    $passwort = $eingabeDaten['passwort'] ?? '';

    loginDatenVorhanden($benutzername, $passwort);
    if(!existiertBenutzer($datenbank,$benutzername)){
        throw new Exception('Benutzer existiert nicht', 404);
    };


    $aktuellerUser = ladeBenutzer($datenbank, $eingabeDaten['benutzername']);
    pruefePasswort($aktuellerUser, $eingabeDaten['passwort']);

    $_SESSION['benutzer_id'] = $aktuellerUser['id'];
    return $aktuellerUser['id'];
}