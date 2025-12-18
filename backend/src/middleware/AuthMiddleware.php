<?php
 if(session_status() === PHP_SESSION_NONE) { // prfung ob es bereits eine session gibt
    session_start();
 }

function requireAuth(){
    if (!isset($_SESSION['benutzer_id'])) {// wenn benutzer existiert -> eingeloggt wenn nicht  nicht eingeloggt
        http_response_code(401); //fehlerfall, frontend weiss login nötig
        echo json_encode([ //antwort als json
            'error' => 'Nicht eingeloggt'
        ]);
        exit;
    }
}
