<?php
/**
 * scripts/test_csv_full_sync.php
 * Prueba integral de importación/exportación con Frecuencia e IDs.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Providers/ExcelProvider.php';
require_once __DIR__ . '/../Backend/Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

// 1. Setup - Crear un activo base si no existe
$repo = new AssetRepository();
$testAsset = [
    'name' => 'EQUIPO TEST SINCRONIZACION',
    'brand' => 'TEST BRAND',
    'location' => 'PISO 1',
    'frecuencia_mp_meses' => 6,
    'inventory_id' => 'INV-SYNC-001',
    'serial_number' => 'SER-SYNC-001'
];

$id = $repo->create($testAsset);
$asset = $repo->findById($id);
$hecId = $asset->hecId;

echo "ACTIVO CREADO CON HEC_ID: $hecId\n";

// 2. Simular IMPORTACIÓN con cambio de nombre y frecuencia manual
$tmpCsv = __DIR__ . '/test_import.csv';
$csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM
$csvContent .= "CÓDIGO SISTEMA (ID);NOMBRE EQUIPO;FRECUENCIA ANUAL DE MANTENIMIENTO\n";
$csvContent .= "$hecId;EQUIPO TEST ACTUALIZADO;4\n";

file_put_contents($tmpCsv, $csvContent);

// Mocking $_FILES
$fileData = [
    'tmp_name' => $tmpCsv,
    'name' => 'test_import.csv'
];

echo "IMPORTANDO CAMBIOS DESDE CSV...\n";
$stats = \Backend\Providers\importAssetsFromFile($fileData);

echo "RESULTADO IMPORTADOR:\n";
print_r($stats);

// 3. Verificar en DB
$updatedAsset = $repo->findById($hecId);
$data = $updatedAsset->toArray();
echo "\n--- VERIFICACION EN BASE DE DATOS ---\n";
echo "NOMBRE ESPERADO: EQUIPO TEST ACTUALIZADO | REAL: " . $data['name'] . "\n";
echo "FRECUENCIA ESPERADA (MESES): 3 | REAL: " . $data['frecuencia_mp_meses'] . "\n";

if ($data['name'] === 'EQUIPO TEST ACTUALIZADO' && $data['frecuencia_mp_meses'] == 3) {
    echo "✅ PRUEBA DE IMPORTACIÓN EXITOSA.\n";
} else {
    echo "❌ PRUEBA DE IMPORTACIÓN FALLIDA.\n";
}

// 4. Limpieza
@unlink($tmpCsv);
$repo->delete($id);
echo "PRUEBAS FINALIZADAS.\n";
