<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Core/LoggerService.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

$repo = new AssetRepository();

$testData = [
    'inventory_id' => 'TEST-' . time(),
    'name' => 'Bomba de Aspiración Test',
    'brand' => 'Yuwell',
    'model' => 'Yuwell 7E-C/G',
    'serial_number' => 'SN-TEST-' . time(),
    'location' => 'TEST AREA',
    'sub_location' => 'TEST ROOM',
    'status' => 'OPERATIVE',
    'criticality' => 'NA',
    'riesgo_ge' => 'APOYO TERAPÉUTICO',
    'purchased_year' => 2021,
    'total_useful_life' => 5,
    'years_remaining' => 0,
    'under_maintenance_plan' => 0,
    'annual_maint_cost' => 0,
    'vendor' => 'TEST VENDOR',
    'contract_id' => 'TEST-CONTRACT',
    'ownership' => 'PROPIO',
    'en_uso' => 1
];

echo "Intentando crear activo de prueba...\n";
try {
    $id = $repo->create($testData);

    if ($id) {
        echo "¡Éxito! Activo creado con ID interno: $id\n";
        // Limpiar prueba
        $repo->delete($id);
        echo "Activo de prueba eliminado.\n";
    } else {
        echo "Error: No se pudo crear el activo. Verifica los logs y la consola.\n";
    }
} catch (\Throwable $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
