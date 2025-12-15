<?php
require_once __DIR__ . '/../services/Database.php'; // Hier lade ich die database.php

session_start(); //startet php session, wird später mit $_SESSION['benutzer_id'] gespeichert

header('Content-Type: application/json'); //frontend weiss: json kommt

$method = $_SERVER['REQUEST_METHOD']; //holt http methode get, post

if ($method === 'POST') { // auth nur per POST
    $input = json_decode(file_get_contents('php://input'), true); // liest json aus request body, true als array

    if (!isset($input['action'])) { //aktion check
        http_response_code(400);
        echo json_encode(['error' => 'Aktion fehlt']);
        exit;
    }

    if ($input['action'] === 'register') { // mini rputer ohne framework
        register($input);
    }

    if ($input['action'] == 'login') {
        login($input);
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unbekannte Aktion']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => ' Nur POST erlaubt']);
exit;

function register(array $data) //register funktion, $data kommt aus dem json-body
{
    if (!isset($data['benutzername'], $data['passwort'])) { //validierung: eingaben werden auf richtigkeit geprüft
        http_response_code(400);
        echo json_encode(['error' => 'Username oder Passwort fehlt']);
        exit;
    }

    $db = getDB(); //db verbindung holen

    $stmt = $db->prepare( //Benuter existiert? prepare statement schütz vor sql injection
        "SELECT id FROM spieler WHERE benutzername =?"
    );

    $stmt->bind_param("s", $data['benutzername']); // s= string , bindet ? an username

    $stmt->execute(); //query ausführen
    $stmt->store_result(); // ergebins zwischenspeichern

    if ($stmt->num_rows > 0) { // wenn es bereits einen eintrag gibt: benutzer existiert -> registrierung abbrechen
        http_response_code(409);
        echo json_encode(['error' => 'Benutzer existiert bereits']);
        exit;
    }

    $hash = password_hash($data['passwort'], PASSWORD_DEFAULT); // passwort hashen (sicherheit)

    $stmt = $db->prepare( //user speichern 
        "INSERT INTO spieler (benutzername, passwort) VALUES (?,?)"
    );
    $stmt->bind_param("ss", $data['benutzername'], $hash); //ss= weis trings; speichert benutzer und hash
    $stmt->execute();

    echo json_encode(['Erfolg' => true]); // FRONTEND WEIß REGISTRIERUNG OK
    exit;
}

function login(array $data) { // leogin eines bestehenden benuters
    if(!isset($data['benutzername'], $data['passwort'])) { //prüfung auf eingabe
        http_response_code(400);
        echo json_encode(['error' => 'Benutzername oder passwort fehlt']);
        exit;
    }

    $db = getDB(); // datenbank holen

    $stmt = $db->prepare( //user aus datenbank holen
        "SELECT id, passwort FROM spieler WHERE benutzername = ?"
    );
    $stmt->bind_param("s", $data['benutzername']);
    $stmt->execute();

    $result = $stmt->get_result();
    $benutzer = $result->fetch_assoc(); // in $benutzer wird ein array aus id, password, etc gepseichert  

    if (!$benutzer || !password_verify($data['passwort'], $benutzer['passwort'])) { //fehlerfall: benutzer existiert nicht oder passwort falsch; password_verify prüft ob pw korrekt
        http_response_code(401);
        echo json_encode(['error' => 'Login fehlgeschlagen']);
        exit;
    }

    $_SESSION['benutzer_id'] = $benutzer['id']; // session wird gesetzt user eingelogtt, alle weiteren request erkennen den nutzer

    echo json_encode([ // frontend bekommt json
        'erfolg' => true,
        'benutzer_id' => $benutzer['id']
    ]);
    exit;
}


