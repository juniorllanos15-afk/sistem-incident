<?php
/**
 * Script de "Migración" manual para PostgreSQL.
 * Este archivo crea la base de datos y ejecuta el archivo database.sql
 */

// 1. VARIABLES DE CONFIGURACIÓN DE POSTGRESQL
$host = 'localhost';
$port = '5432';
$user = 'postgres'; // El usuario por defecto de pgAdmin/PostgreSQL
$password = '12345678'; // ¡CUIDADO! Pon la misma contraseña que pusiste al instalar PostgreSQL
$dbname = 'service_incident_db';

try {
    echo "----------------------------------------\n";
    echo "Iniciando proceso de migración...\n";
    echo "----------------------------------------\n";

    // 2. CONECTARSE AL SERVIDOR POSTGRESQL (Base de datos por defecto 'postgres')
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=postgres", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Saber si la base de datos ya existe
    $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
    $stmt->execute(['dbname' => $dbname]);

    if (!$stmt->fetch()) {
        echo "[+] Creando base de datos '$dbname'...\n";
        // En Postgres, CREATE DATABASE no puede ir dentro de una transacción preparada, se usa exec general
        $pdo->exec("CREATE DATABASE $dbname");
        echo "[✓] Base de datos creada exitosamente.\n";
    } else {
        echo "[i] La base de datos '$dbname' ya existe. Omitiendo creación.\n";
    }

    // 3. CONECTARSE A LA BASE DE DATOS RECIÉN CREADA ('incidencias_db')
    $pdoDb = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdoDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[+] Evaluando el archivo 'database.sql'...\n";

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo database.sql en esta ruta: " . $sqlFile);
    }

    // Leer el archivo .sql
    $sqlContent = file_get_contents($sqlFile);

    // Ejecutar las instrucciones para crear Tablas, Triggers, etc.
    $pdoDb->exec($sqlContent);

    echo "[✓] Tablas, Funciones y Triggers creados/actualizados exitosamente.\n";
    echo "----------------------------------------\n";
    echo "¡Migración completada con éxito!\n";
    echo "----------------------------------------\n";

} catch (PDOException $e) {
    echo "[X] Error de Base de Datos: \n" . $e->getMessage() . "\n";
    echo "¿Pusiste la contraseña correcta en la variable \$password de migrate.php?\n";
    echo "Password = " . $password;
} catch (Exception $e) {
    echo "[X] Error: " . $e->getMessage() . "\n";
}
