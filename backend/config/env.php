<?php
$envDaten = __DIR__ . '/../../.env';

if (file_exists($envDaten)){//prüfen ob die env exisitert, liest alle zeilen in der datei aus, fil ignore new lines = entfernt zeilenumbrüche und empty lines = ignoriert leere zeilen
    $zeilen = file($envDaten, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($zeilen as $zeile) {//für jede zeile in zeilen führe aus: 
        $zeile = trim($zeile);//entfernt / und leerzeichen

        if($zeile === '' || $zeile[0] === '#') { //wenn zeile leer dann überspringen oder zeilen die mit # beginnen überspringen
            continue;
        }

        [$key, $value] = explode('=', $zeile,2);//trennt zeile bei = in schlüssel und wert (key=value) 2 steht für nur einmal teilen damit werte = enthalten können
        putenv("$key=$value");// environment variable für php setzen
        $_ENV[$key] = $value;//speichert in ENV array
    }
}
//rückagbearray für database
return [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASSWORD'),
    'DB_ROOT_PASSWORD' => getenv('DB_ROOT_PASSWORD')
];
