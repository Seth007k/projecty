<?php 

//funktion bekommt 3 parameter, es wird geprüft ob charakterid gesetzt ist, wenn ja wird der charakter geladen aus der Datenbank mit bind param sql inkection schutz
// wenn charakter_id nicht gesetzt ist alle charakter des eingeloggten spielers laden. execute führt sql aus und getResult gibt mir ergebnis welches ich in ergebnischarakter speichere
// dann erstelle ich ein array in charaktere und iteriere mit eine while schleife durch das ergebnis von getResult()um jede zeile durchzugehen und alle benötigten datensätze abzufragen - PHP Array; return liste von charaktern
function charakterLaden($datenbank, $spieler_id, $charakter_id)
{
    if ($charakter_id) {
        $sqlAnweisungCharakteranzeigen = $datenbank->prepare("SELECT * FROM charakter WHERE spieler_id =? AND id = ?");
        $sqlAnweisungCharakteranzeigen->bind_param("ii", $spieler_id, $charakter_id);
    } else {
        $sqlAnweisungCharakteranzeigen = $datenbank->prepare("SELECT * FROM charakter WHERE spieler_id =?");
        $sqlAnweisungCharakteranzeigen->bind_param("i", $spieler_id);
    }

    $sqlAnweisungCharakteranzeigen->execute();
    $ergebnisCharakter = $sqlAnweisungCharakteranzeigen->get_result();

    $charaktere = [];
    while ($row = $ergebnisCharakter->fetch_assoc()) {
        $charaktere[] = $row;
    }

    return $charaktere;
}

//function für charakter löschen: parameter + sql anweisung mit charakter_id + schutz sql injection, löschungserfolg gibt true oder false zurück je nachdem obs geklappt hat
function charakterLöschen($datenbank, $spieler_id, $charakter_id)
{
    $sqlAnweisungSpieleDesCharaktersLöschen = $datenbank->prepare("DELETE FROM spiele WHERE charakter_id = ?");
    $sqlAnweisungSpieleDesCharaktersLöschen->bind_param("i", $charakter_id);
    $sqlAnweisungSpieleDesCharaktersLöschen->execute();

    $sqlAnweisungCharakterLöschen = $datenbank->prepare("DELETE FROM charakter WHERE id=? AND spieler_id=?");
    $sqlAnweisungCharakterLöschen->bind_param("ii", $charakter_id, $spieler_id);
    $sqlAnweisungCharakterLöschen->execute();

    $löschungErfolg = $sqlAnweisungCharakterLöschen->affected_rows > 0;

    return $löschungErfolg;
}

// charaktererstellung mit status und bild, id wird zurückgegeben
function charakterErstellen($datenbank, $spieler_id, $eingabeDaten)
{
    $level = 1;
    $leben = 1000;
    $angriff = 250;
    $verteidigung = 5;
    $bild = "charakter.png";

    $sqlAnweisungCharakterErstellen = $datenbank->prepare("INSERT INTO charakter (spieler_id, name, level, leben, angriff, verteidigung, bild) VALUES (?,?,?,?,?,?,?)");
    $sqlAnweisungCharakterErstellen->bind_param("isiiiis", $spieler_id, $eingabeDaten['name'], $level, $leben, $angriff, $verteidigung, $bild);
    $sqlAnweisungCharakterErstellen->execute();

    return $sqlAnweisungCharakterErstellen->insert_id;
}

//name ist prgramm
function pruefeCharakterName($eingabeDaten)
{
    return !empty($eingabeDaten['name']);
}
