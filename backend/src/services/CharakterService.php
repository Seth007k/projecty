<?php 


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

function charakterAktualisieren($datenbank, $spieler_id, $ergebnisAktuellerCharakter)
{
    $sqlAnweisungCharakterAktualisieren = $datenbank->prepare("UPDATE charakter SET name = ?, level = ?, leben = ?, angriff = ?, verteidigung = ? WHERE id = ? AND spieler_id = ?");
    $sqlAnweisungCharakterAktualisieren->bind_param("siiiiii", $ergebnisAktuellerCharakter['name'], $ergebnisAktuellerCharakter['level'], $ergebnisAktuellerCharakter['leben'], $ergebnisAktuellerCharakter['angriff'], $ergebnisAktuellerCharakter['verteidigung'], $ergebnisAktuellerCharakter['id'], $spieler_id);
    $sqlAnweisungCharakterAktualisieren->execute();
}

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

function charakterErstellen($datenbank, $spieler_id, $eingabeDaten)
{
    $level = 1;
    $leben = 1000;
    $angriff = 250;
    $verteidigung = 5;

    $sqlAnweisungCharakterErstellen = $datenbank->prepare("INSERT INTO charakter (spieler_id, name, level, leben, angriff, verteidigung) VALUES (?,?,?,?,?,?)");
    $sqlAnweisungCharakterErstellen->bind_param("isiiii", $spieler_id, $eingabeDaten['name'], $level, $leben, $angriff, $verteidigung);
    $sqlAnweisungCharakterErstellen->execute();

    return $sqlAnweisungCharakterErstellen->insert_id;
}

function pruefeCharakterName($eingabeDaten)
{
    return !empty($eingabeDaten['name']);
}

function exisitiertCharakter($eingabeDaten)
{
    return $eingabeDaten['id'] ?? null;
}