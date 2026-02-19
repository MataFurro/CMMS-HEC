<?php

/**
 * migrate_v4.2.php
 * Migración para añadir campos de ejecución extendida a la tabla work_orders.
 */

require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

echo "<h1>BioCMMS - Ejecutando Migraciones v4.2</h1>";

try {
    $db = DatabaseService::getInstance();

    // 1. Añadir campos a la tabla work_orders
    $sql = "ALTER TABLE work_orders 
            ADD COLUMN failure_code VARCHAR(100) NULL AFTER duration_hours,
            ADD COLUMN service_warranty_date DATE NULL AFTER failure_code,
            ADD COLUMN final_asset_status VARCHAR(50) NULL AFTER service_warranty_date";

    $db->exec($sql);
    echo "✅ Tabla <code>work_orders</code> actualizada con campos de ejecución.<br>";

    // 2. Opcional: Asegurar que los datos iniciales de técnicos estén actualizados si es necesario
    // ...

    echo "<br><b>Migración completada con éxito.</b>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "ℹ️ Las columnas ya existen. Saltando migración.<br>";
    } else {
        echo "<br><b style='color: red;'>ERROR: </b>" . $e->getMessage();
    }
}

echo "<br><br><a href='index.php'>Volver al sistema</a>";
