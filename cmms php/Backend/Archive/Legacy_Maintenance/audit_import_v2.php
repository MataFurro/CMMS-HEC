<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    // 1. Contar totales
    $stmt = $db->query("SELECT COUNT(*) as total FROM assets");
    $total = $stmt->fetchColumn();

    // 2. Ver una muestra de datos crudos para ver qué se guardó
    $stmt = $db->query("SELECT id, name, inventory_id, serial_number, hec_id, criticality FROM assets LIMIT 10");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- DIAGNÓSTICO DE BASE DE DATOS ---\n";
    echo "Total de registros encontrados: $total\n\n";
    echo "Muestra de registros:\n";
    print_r($samples);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
