<?php
/**
 * bio_cmms_verifier.php
 * Script para verificar los flujos de trabajo programáticamente.
 */

require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';

echo "--- INICIANDO VERIFICACIÓN DE FLUJOS BIOCMMS ---\n\n";

// 1. Verificar Escenario 3: Equipo Observado (Fix Mapeo)
echo "Escenario 3: Verificando mapeo de estado 'Degrado' a 'OPERATIVE_WITH_OBS'...\n";
$assetId = 1; // Usar primer activo para prueba
$oldAsset = getAssetById($assetId);

// Crear OT Correctiva
$otId = createWorkOrder([
    'asset_id' => $assetId,
    'type' => 'Correctiva',
    'priority' => 'Media',
    'observations' => 'Prueba de verificación de flujo 3'
]);

echo "  - OT Creada: $otId\n";

// Cerrar OT con estado final DEGRADED
$success = completeWorkOrder($otId, [
    'final_asset_status' => 'DEGRADED',
    'duration_hours' => 1,
    'observations' => 'Cierre de prueba con degradación'
]);

$newAsset = getAssetById($assetId);
if ($newAsset['status'] === 'OPERATIVE_WITH_OBS') {
    echo "  [OK] Estado mapeado correctamente a OPERATIVE_WITH_OBS.\n";
} else {
    echo "  [ERROR] El estado es " . $newAsset['status'] . " (se esperaba OPERATIVE_WITH_OBS).\n";
}

// 2. Verificar Escenario 2: Preventivo (+6 meses)
echo "\nEscenario 2: Verificando actualización de fecha de próximo preventivo...\n";
$currentNextDate = $newAsset['next_maintenance_date'] ?? date('Y-m-d');

$otPreId = createWorkOrder([
    'asset_id' => $assetId,
    'type' => 'Preventiva',
    'priority' => 'Baja',
    'observations' => 'Prueba de verificación de flujo 2'
]);

echo "  - OT Preventiva Creada: $otPreId\n";

completeWorkOrder($otPreId, [
    'final_asset_status' => 'OPERATIVE',
    'duration_hours' => 2
]);

$finalAsset = getAssetById($assetId);
$expectedDate = date('Y-m-d', strtotime("+6 months"));

if ($finalAsset['next_maintenance_date'] === $expectedDate) {
    echo "  [OK] Fecha de próximo mantenimiento actualizada a: " . $finalAsset['next_maintenance_date'] . "\n";
} else {
    echo "  [ERROR] Fecha calculada: " . $finalAsset['next_maintenance_date'] . " | Esperada: $expectedDate\n";
}

echo "\n--- VERIFICACIÓN FINALIZADA ---\n";
