<?php 

function getSpielerId() {
    return $_SESSION['benutzer_id'] ?? null;
}