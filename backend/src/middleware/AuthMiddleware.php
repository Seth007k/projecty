<?php

function requireAuth(){
    if (!isset($_SESSION['benutzer_id'])) {// wenn benutzer existiert -> eingeloggt wenn nicht  nicht eingeloggt
        http_response_code(401); //fehlerfall, frontend weiss login nötig
        echo json_encode([ 
            'erfolg' => false,
            'hinweis' => 'Nicht eingeloggt'
        ]);
        exit;
    }
}
