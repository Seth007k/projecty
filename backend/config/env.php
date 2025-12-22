<?php
$envDaten = __DIR__ . '/../../../.env';

if (file_exists($envDaten)){
    $zeilen = file($envdaten, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($zeilen as $zeile) {
        $zeile = trim($zeile);

        if($zeile === '' || $zeile[0] === '#') {
            continue;
        }

        [$key, $value] = explode('=', $zeile,2);
        putenv("$key=$value");
        $_ENV['key'] = $value;
    }
}

return [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASSWORD'),
    'DB_ROOT_PASSWORD' => getenv('DEB_ROOT_PASSWORD')
];
