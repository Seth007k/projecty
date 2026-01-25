<?php
//einbinden database
require_once __DIR__ . '/Database.php';


//spielerID aus SESSIon holen
function getSpielerId()
{
    return $_SESSION['benutzer_id'] ?? null;
}
//prüft mit der datenbankverbindung und dem benutzername ob benutzer bereits existiert
function existiertBenutzer($datenbank, $benutzername){

    //prepared statement : sucht eine id aus der tabelle spieler wo benutzername gleich platzhalter für benutzername (Schutz vor SQL INjection)
    $sqlAnweisungExistiertBenutzer = $datenbank->prepare('SELECT id FROM spieler WHERE benutzername = ?');
    //übergibt den tatsächlichen wert für benutzername s für string
    $sqlAnweisungExistiertBenutzer->bind_param("s", $benutzername);
    //fürhrt SQL abfrage aus
    $sqlAnweisungExistiertBenutzer->execute();
    //speichert ergebnis im speicher damit num_rows angewendet werden kann
    $sqlAnweisungExistiertBenutzer->store_result();

    //gibt true zurück wenn mindestens ein benutzer gefunden wurde und false wenn keiner existiert
    return $sqlAnweisungExistiertBenutzer->num_rows > 0;
}

//registrierungsfunktion eingabeDaten ist ein array 
function registriereBenutzer($datenbank, $eingabeDaten)
{
   //holt benutzernamen aus array und speichert in benutzername das gleiche bei passwort
    $benutzername = $eingabeDaten['benutzername'];
    $passwort = $eingabeDaten['passwort'];

    //prüfung ob benutzername und passwort existiert, wenn nein fehler werfen
    if (!$benutzername || !$passwort) {
        throw new Exception('Benutzername und Passwort erforderlich', 400);
    }

    //prüft ob benutzer in der datenbank existiert, wenn nein fgehler werfen
    if (existiertBenutzer($datenbank, $benutzername)){
        throw new Exception('Benutzername existiert bereits! Neuen Namen wählen', 409);
    } 

    //sicherheitsmaßnahme: passwort wird gehasht
    $gehashtesPw = password_hash($passwort, PASSWORD_DEFAULT);

    //prepared statement zum einfügen neuen benutzers in die DB
    $sqlAnweisungUserAnlegen = $datenbank->prepare('INSERT INTO spieler (benutzername, passwort) VALUES (?,?)');
    $sqlAnweisungUserAnlegen->bind_param("ss", $benutzername, $gehashtesPw);
    $sqlAnweisungUserAnlegen->execute();

    //holt automatisch die generierte ID des neuen datensatzes und speichert in benutzer_id welche dann zurückgegeben wird
    $benutzer_id = $datenbank->insert_id;

    return $benutzer_id;
}

//benutzer laden für login mit name und datenbank
function ladeBenutzer($datenbank, $benutzername) {

//prepared statement um benutzer aus DB zu holen und in geladenerBenutzer zu speichern als objekt(get_result()) und wird dann in ein assoziatives array umgewandelt (fetch_assoc)
    $sqlAnweisungLadeBenutzer = $datenbank->prepare('SELECT id, passwort FROM spieler WHERE benutzername = ?');
    $sqlAnweisungLadeBenutzer->bind_param("s", $benutzername);
    $sqlAnweisungLadeBenutzer->execute();
    
    $ergebnisGeladenenerBenutzer = $sqlAnweisungLadeBenutzer->get_result();
    $ergebnisGeladenenerBenutzer = $ergebnisGeladenenerBenutzer->fetch_assoc();

    //rückgabewert: array
    return $ergebnisGeladenenerBenutzer;
}

//passwort überprüfung: entweder benutzer gibt es nicht oder gehashtes passwort stimmt nicht überein dann fehlerwerfen, aber nicht sagen warum (Shcuztmaßnahme)
function pruefePasswort($ergebnisGeladenenerBenutzer, $passwort)  {

    if(!$ergebnisGeladenenerBenutzer || !password_verify($passwort, $ergebnisGeladenenerBenutzer['passwort'])) {
        throw new Exception('Login fehlgeschlagen!');
    }
}

//pürüft ob leere werte in logindaten vorhanden sind
function loginDatenVorhanden($benutzername, $passwort)
{
    if (empty($benutzername) || empty($passwort)) {
        throw new Exception('Es wird ein benutzername und Passwort benötigt!');
    }
}
//rückgabetyp: integer ( function darfd nur int zurückgeben sonst fatal error, wird während laufzeit von php geprüft), prüft erst die eingaben ob vorhanden und erstellt dann den benutzer welche in benutzer_id gespeichert wird da rückgabewert aus registriereBenutzer ID ist. benutzerId wird in session benutzerID gespeichert -> eingeloggt und dann wird benutzerId zurückgegeben 
function registriereUndEinloggen($datenbank, $eingabeDaten):int {
    loginDatenVorhanden($eingabeDaten['benutzername'], $eingabeDaten['passwort']);

    $benutzer_id = registriereBenutzer($datenbank, $eingabeDaten);

    $_SESSION['benutzer_id'] = $benutzer_id;

    return $benutzer_id;
}

//auch rückgabewert: integer, gibt benutzerid zurück, holt erst benutzername aus eingabedaten und speichert in benutzername selbe mit pw dann wird geprüft ob daten vorhanden sind dann ob benutzer existiert und dann wird der aktuelle benutzer aus DB geladen und in aktuellerUser gespeichert welche ich brauche um 
//pwdie passwort eingabe zu validieren. Dann wird der aktuelleUser in SESSION benutzerID gespeichert und die ID zurückgegeben
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