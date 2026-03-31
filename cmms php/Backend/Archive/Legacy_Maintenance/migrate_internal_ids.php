<?php

/**
 * migrate_internal_ids.php
 * Script de migración crítica para cambiar el sistema de IDs de Activos.
 * 1. Mueve 'id' actual a 'inventory_id'
 * 2. Crea nueva PK entera auto-incremental 'id'
 * 3. Actualiza todas las FKs en tablas relacionadas
 */

require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Core/LoggerService.php';

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;

$db = DatabaseService::getInstance();

try {
    $db->beginTransaction();

    echo "Iniciando migración de IDs...\n";

    // 1. Eliminando restricciones de Foreign Key...
    $dependentTables = [
        'work_orders' => 'asset_id',
        'checklist_results' => 'asset_id',
        'ot_attachments' => 'asset_id',
        'service_requests' => 'asset_id',
        'audit_trail' => 'asset_id'
    ];

    foreach ($dependentTables as $table => $column) {
        try {
            $fkQuery = "SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column' 
                        AND REFERENCED_TABLE_NAME = 'assets' AND TABLE_SCHEMA = DATABASE()";
            $fks = $db->query($fkQuery)->fetchAll(PDO::FETCH_COLUMN);
            foreach ($fks as $fkName) {
                echo "   - Eliminando FK $fkName de $table...\n";
                $db->exec("ALTER TABLE $table DROP FOREIGN KEY $fkName");
            }
        } catch (Exception $e) {
            echo "   - Aviso: Error al buscar/eliminar FK en $table: " . $e->getMessage() . "\n";
        }
    }

    echo "2. Reestructurando tabla 'assets'...\n";

    // Cambiar nombre de columna actual 'id' a 'inventory_id'
    $db->exec("ALTER TABLE assets MODIFY id VARCHAR(50) NOT NULL");
    $db->exec("ALTER TABLE assets DROP PRIMARY KEY");
    $db->exec("ALTER TABLE assets CHANGE id inventory_id VARCHAR(50) NOT NULL");

    // Crear la nueva columna 'id' (INT PK AI)
    $db->exec("ALTER TABLE assets ADD id INT AUTO_INCREMENT PRIMARY KEY FIRST");

    echo "   - Nueva estructura de 'assets' creada.\n";

    // 3. Mapear antiguos IDs a nuevos IDs
    $mapping = $db->query("SELECT id, inventory_id FROM assets")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "   - " . count($mapping) . " activos mapeados.\n";

    // 4. Actualizar tablas relacionadas
    foreach ($dependentTables as $table => $column) {
        echo "4. Actualizando tabla '$table' ($column)...\n";

        $allowNull = ($table === 'audit_trail') ? "NULL" : "NOT NULL";

        // Cambiar columna a INT
        $db->exec("ALTER TABLE $table MODIFY $column INT $allowNull");

        // Actualizar valores usando un temporal para evitar colisiones de tipos si fuera necesario
        // Pero como ya limpiamos las FKs y cambiamos a INT, podemos mapear directo
        // Optimización: Update masivo con JOIN
        $db->exec("UPDATE $table t JOIN assets a ON t.$column = a.inventory_id SET t.$column = a.id");

        // Re-crear FK
        try {
            $db->exec("ALTER TABLE $table ADD CONSTRAINT fk_{$table}_{$column} FOREIGN KEY ($column) REFERENCES assets(id) ON DELETE RESTRICT");
            echo "   - FK re-establecida en $table.\n";
        } catch (Exception $e) {
            echo "   - Error al re-crear FK en $table: " . $e->getMessage() . "\n";
        }
    }

    $db->commit();
    echo "\nMigración finalizada exitosamente.\n";
    LoggerService::info("Migración de IDs de Activos completada.");
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "\nERROR CRÍTICO: " . $e->getMessage() . "\n";
    LoggerService::error("Error en migración de IDs", ['error' => $e->getMessage()]);
}
