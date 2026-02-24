<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    $sql = file_get_contents(__DIR__ . '/backend/database/migrations/003_add_asset_categories.sql');

    // El script tiene múltiples sentencias, PDO exec solo corre una usualmente si no se configura diferente.
    // Usaremos un split simple o ejecutaremos todo si el driver lo permite.
    $db->exec($sql);

    echo "Migración de categorías completada con éxito.\n";
} catch (Exception $e) {
    echo "Error ejecutando migración: " . $e->getMessage() . "\n";
}
