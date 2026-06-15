<?php
// assignment-service/config.php

$host = 'localhost';
$port = '5432';
$dbname = 'service_assignment_db';
$username = 'postgres';
$password = '12345678';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => "Connection failed: " . $e->getMessage()]));
}
?>
