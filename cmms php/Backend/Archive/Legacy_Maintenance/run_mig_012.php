<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    $sql = file_get_contents(__DIR__ . '/Backend/Database/migrations/012_communication_bottlenecks.sql');

    // Ejecutar multi-query
    $db->exec($sql);

    echo "Migración 012 (Comunicación y Coordinación) ejecutada con éxito.\n";
} catch (Exception $e) {
    echo "Error ejecutando migración: " . $e->getMessage() . "\n";
}
