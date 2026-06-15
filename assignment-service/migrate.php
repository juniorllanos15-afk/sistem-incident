<?php
// assignment-service/migrate.php

// 1. Create connection to PostgreSQL (without specifying DB first)
$host = 'localhost';
$port = '5432';
$username = 'postgres';
$pass = '12345678';
$dbname = 'service_assignment_db';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port", $username, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the database if it doesn't exist
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbname'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE DATABASE $dbname");
        echo "Database $dbname created successfully.\n";
    } else {
        echo "Database $dbname already exists.\n";
    }

    // 3. Connect to the new database
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Execute the SQL script
    $sql = file_get_contents(__DIR__ . '/database.sql');
    if ($sql === false) {
        throw new Exception("Could not read database.sql");
    }

    $pdo->exec($sql);
    echo "Tables created successfully in $dbname.\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
