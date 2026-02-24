<?php

/**
 * migrate_v4.4_progressive_ot.php
 * Actualiza la base de datos para soportar guardado parcial de OTs.
 */

require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    echo "Conexión exitosa a la base de datos.\n";

    // 1. Agregar columna checklist_data a work_orders si no existe
    $db->exec("ALTER TABLE work_orders ADD COLUMN IF NOT EXISTS checklist_data JSON DEFAULT NULL AFTER checklist_template");
    echo "Columna 'checklist_data' verificada/agregada en 'work_orders'.\n";

    // 2. Crear tabla asset_recalls si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS asset_recalls (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        asset_id    VARCHAR(50) NOT NULL,
        recall_date DATE NOT NULL,
        reason      TEXT,
        resolved    TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla 'asset_recalls' verificada/creada.\n";

    echo "Migración completada exitosamente.\n";
} catch (Exception $e) {
    echo "ERROR en migración: " . $e->getMessage() . "\n";
    exit(1);
}
