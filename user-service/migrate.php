<?php
$host = 'localhost';
$port = '5432';
$dbname = 'service_user_db';
$username = 'postgres';
$password = '12345678';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if DB exists
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname='$dbname'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE DATABASE $dbname");
        echo "Database $dbname created successfully.\n";
    }

    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('database.sql');
    $pdo->exec($sql);
    echo "Tables mapped successfully.\n";

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>
