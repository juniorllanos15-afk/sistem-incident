<?php
// solution-service/migrate.php

$host = 'localhost';
$port = '5432';
$username = 'postgres';
$pass = '12345678';
$dbname = 'service_solution_db';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port", $username, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbname'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE DATABASE $dbname");
        echo "Database $dbname created successfully.\n";
    }

    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/database.sql');
    $pdo->exec($sql);
    echo "Tables created successfully in $dbname.\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
