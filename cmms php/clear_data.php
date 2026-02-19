<?php

/**
 * clear_data.php
 * Script de limpieza para eliminar todos los equipos (assets) y órdenes de trabajo (work_orders).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

echo "<h1>BioCMMS - Limpieza de Datos</h1>";

try {
    $db = DatabaseService::getInstance();

    // Desactivar temporalmente las llaves foráneas para una limpieza total y segura
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Limpiar tablas dependientes primero
    $tables = [
        'checklist_results',
        'ot_attachments',
        'service_requests',
        'audit_trail',
        'asset_recalls',
        'work_orders',
        'assets'
    ];

    foreach ($tables as $table) {
        $db->exec("DELETE FROM $table");
        echo "✅ Datos eliminados de la tabla: <code>$table</code><br>";
    }

    // Reactivar llaves foráneas
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<br><b>Limpieza completada con éxito.</b> El sistema está ahora listo para nuevos registros.";
} catch (Exception $e) {
    echo "<br><b style='color: red;'>ERROR: </b>" . $e->getMessage();
}

echo "<br><br><a href='index.php'>Volver al sistema</a>";
