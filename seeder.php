<?php
// seeder.php

$host = 'localhost';
$port = '5432';
$username = 'postgres';
$password = '12345678';

echo "Iniciando Seeder del Sistema...\n";
echo "===============================\n\n";

try {
    // ----------------------------------------------------
    // 1. Sembrado de la Base de Datos de Roles
    // ----------------------------------------------------
    $dbRol = 'service_rol_db';
    $pdoRol = new PDO("pgsql:host=$host;port=$port;dbname=$dbRol", $username, $password);
    $pdoRol->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si existe el rol Admin
    $stmt = $pdoRol->query("SELECT id FROM rol WHERE name = 'Administrador'");
    $rol = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rol) {
        $stmtInsert = $pdoRol->prepare("INSERT INTO rol (name, description) VALUES ('Administrador', 'Rol con acceso total al sistema') RETURNING id");
        $stmtInsert->execute();
        $row = $stmtInsert->fetch(PDO::FETCH_ASSOC);
        $rolId = $row['id'];
        echo "[+] Rol 'Administrador' creado con ID: $rolId\n";
    } else {
        $rolId = $rol['id'];
        echo "[*] El Rol 'Administrador' ya existia en el microservicio.\n";
    }

    // Verificar si existe el rol Técnico
    $stmtTec = $pdoRol->query("SELECT id FROM rol WHERE name = 'Técnico'");
    $rolTec = $stmtTec->fetch(PDO::FETCH_ASSOC);

    if (!$rolTec) {
        $stmtInsertTec = $pdoRol->prepare("INSERT INTO rol (name, description) VALUES ('tecnico', 'Rol para técnicos que resuelven incidencias') RETURNING id");
        $stmtInsertTec->execute();
        $rowTec = $stmtInsertTec->fetch(PDO::FETCH_ASSOC);
        echo "[+] Rol 'Técnico' creado con ID: " . $rowTec['id'] . "\n";
    } else {
        echo "[*] El Rol 'Técnico' ya existia en el microservicio.\n";
    }

    // ----------------------------------------------------
    // 2. Sembrado de la Base de Datos de Usuarios
    // ----------------------------------------------------
    $dbUser = 'service_user_db';
    $pdoUser = new PDO("pgsql:host=$host;port=$port;dbname=$dbUser", $username, $password);
    $pdoUser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si existe el administrador nativo
    $stmtUser = $pdoUser->query("SELECT id FROM users WHERE email = 'admin@admin.com'");

    if (!$stmtUser->fetch()) {
        $passPlano = 'admin123';
        $hashedPassword = password_hash($passPlano, PASSWORD_DEFAULT);

        $stmtInsertUser = $pdoUser->prepare("INSERT INTO users (user_name, password, email, rol_id) VALUES ('Admin', :pwd, 'admin@admin.com', :rol_id)");
        $stmtInsertUser->execute([
            'pwd' => $hashedPassword,
            'rol_id' => $rolId
        ]);

        echo "[+] Usuario Administrador creado exitosamente.\n";
        echo "    -> Correo: admin@admin.com\n";
        echo "    -> Contrasena: $passPlano\n";
    } else {
        echo "[*] El usuario 'admin@admin.com' ya existia en el sistema.\n";
    }

    echo "\n===============================\n";
    echo "¡Seeder completado! Inicia sesion en el frontend.\n";

} catch (PDOException $e) {
    die("\n[ERROR CRÍTICO] Ocurrió un error ejecutando el seeder: \n" . $e->getMessage() . "\n(Verifica que PostgreSQL este encendido y que hayas ejecutado los migrate de los microservicios)");
}
?>