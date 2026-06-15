<?php
// category-service/config.php

$host = 'localhost';
$port = '5432';
$dbname = 'service_incident_db';
$username = 'postgres';
$password = '12345678';

try {
    // Cambio a 'pgsql:'
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => "Connection failed: " . $e->getMessage()]));
}

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'TU_CORREO@gmail.com');
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');
define('SMTP_FROM', 'TU_CORREO@gmail.com');
define('SMTP_FROM_NAME', 'Sistema de Incidencias');
define('USER_SERVICE_URL', 'http://localhost:8003/index.php');
?>