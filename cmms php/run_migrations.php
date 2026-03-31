<?php
require_once __DIR__ . '/config.php';

$host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$dbName = defined('DB_NAME') ? DB_NAME : 'biocmms';

echo "[*] Iniciando proceso de migración...\n";
echo "[*] Conectando a MySQL en $host...\n";

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[OK] Conectado a MySQL.\n";
    
    echo "[*] Creando Base de Datos `$dbName` si no existe...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    
    // Ejecutar statements evitando problemas con múltiples consultas juntas en PDO simple
    function executeSqlScript($pdo, $filepath) {
        $sql = file_get_contents($filepath);
        if (trim($sql) === '') return;
        try {
            // Emulando PDO exec para queries múltiples (requerido a veces)
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->exec($sql);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            echo "[OK] Script ejecutado exitosamente.\n";
        } catch (\PDOException $e) {
            echo "[!] Advertencia/Error ejecutando script: " . $e->getMessage() . "\n";
        }
    }

    $schemaFile = __DIR__ . '/Backend/Database/schema.sql';
    if(file_exists($schemaFile)) {
        echo "[*] Ejecutando schema base (schema.sql)...\n";
        executeSqlScript($pdo, $schemaFile);
    } else {
        echo "[!] No se encontró el schema base en Backend/Database/schema.sql\n";
    }
    
    $migrationsDir = __DIR__ . '/Backend/Database/migrations/';
    if(is_dir($migrationsDir)) {
        $files = glob($migrationsDir . '*.sql');
        sort($files);
        foreach($files as $file) {
            echo "[*] Ejecutando migración: " . basename($file) . "...\n";
            executeSqlScript($pdo, $file);
        }
    }
    
    echo "\n[v] ¡MIGRACIÓN COMPLETADA CON ÉXITO! La base de datos está lista.\n";
} catch (\PDOException $e) {
    echo "\n[ERROR CRÍTICO] " . $e->getMessage() . "\n\n";
    echo "==> POR FAVOR, ASEGÚRATE DE QUE MYSQL EN XAMPP ESTÁ INICIADO (Boton START activado).\n";
}
