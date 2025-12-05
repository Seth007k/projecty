<?php 
// Hier werden die .env daten für das backend ausgelesen
$DB_HOST = getenv('DB_HOST') ?: 'localhost'; // ich arbeite hier mit fallback werten falls ich lokale tests machen möchte später
$DB_NAME = getenv('DB_NAME') ?: 'name';
$DB_USER = getenv('DB_USER') ?: 'user';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: 'password';
$DB_ROOT_PASSWORD = getenv('DEB_ROOT_PASSWORD') ?: 'root';
