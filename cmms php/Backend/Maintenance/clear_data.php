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

    // Desactivar temporalmente las llaves foráneas
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Orden de limpieza (de más dependiente a menos dependiente)
    $tables = [
        'checklist_results',
        'ot_attachments',
        'service_requests',
        'audit_trail',
        'work_orders',
        'assets',
        'messenger_reports'
    ];

    foreach ($tables as $table) {
        try {
            // Intentar TRUNCATE para resetear IDs, si falla (por corrupción), intentar DELETE
            $db->exec("TRUNCATE TABLE $table");
            echo "✅ Tabla vaciada (Truncate): <code>$table</code><br>";
        } catch (Exception $e) {
            try {
                $db->exec("DELETE FROM $table");
                echo "⚠️ Datos eliminados (Delete fallback): <code>$table</code><br>";
            } catch (Exception $e2) {
                echo "❌ Fallo al limpiar tabla: <code>$table</code> - " . $e2->getMessage() . "<br>";
            }
        }
    }

    // Reactivar llaves foráneas
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<br><b>Limpieza completada con éxito.</b> El sistema está ahora listo para nuevos registros.";
} catch (Exception $e) {
    echo "<br><b style='color: red;'>ERROR CRÍTICO: </b>" . $e->getMessage();
}

echo "<br><br><a href='index.php'>Volver al sistema</a>";
