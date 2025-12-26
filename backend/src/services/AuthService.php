<?php
require_once __DIR__ . '/Database.php';



function getSpielerId()
{
    return $_SESSION['benutzer_id'] ?? null;
}

function registriereBenutzer($datenbank, $eingabeDaten)
{
   
    $benutzername = $eingabeDaten['benutzername'];
    $passwort = $eingabeDaten['passwort'];

    if (!$benutzername || !$passwort) {
        throw new Exception('Benutzername und Passwort erforderlich', 400);
    }

    $sqlAnweisungExistiertBenutzer = $datenbank->prepare('SELECT id FROM spieler WHERE benutzername = ?');
    $sqlAnweisungExistiertBenutzer->bind_param("s", $benutzername);
    $sqlAnweisungExistiertBenutzer->execute();
    $sqlAnweisungExistiertBenutzer->store_result();

    if ($sqlAnweisungExistiertBenutzer->num_rows > 0) {
        throw new Exception('Benutzername existiert bereits', 409);
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