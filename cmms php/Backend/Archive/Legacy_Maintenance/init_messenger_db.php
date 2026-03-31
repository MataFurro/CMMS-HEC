<?php
// init_messenger_db.php - Script para inicializar la base de datos de reportes (SQLite)

$dbPath = __DIR__ . '/API Mail/database/messenger.db';
$dbDir = dirname($dbPath);

if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tabla de reportes
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT,
        serie TEXT,
        equipo TEXT,
        servicio TEXT,
        texto TEXT,
        imagen_path TEXT,
        status TEXT DEFAULT 'Pendiente',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "Base de datos SQLite inicializada correctamente en: " . $dbPath . "\n";
} catch (Exception $e) {
    echo "Error al inicializar la base de datos: " . $e->getMessage() . "\n";
}
