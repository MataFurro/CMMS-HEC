<?php

/**
 * Script de Demostración de Ciclo de Vida: MONITOR DE SIGNOS VITALES
 */
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';

echo "--- 1. CREANDO NUEO EQUIPO: MONITOR DE SIGNOS VITALES ---\n";
$assetData = [
    'name' => 'MONITOR DE SIGNOS VITALES',
    'inventory_id' => 'INV-DEMO-001',
    'serial_number' => 'SN-DEMO-2026',
    'brand' => 'MINDRAY',
    'model' => 'UMEC 12',
    'location' => 'URGENCIAS',
    'riesgo_ge' => 'APOYO DIAGNÓSTICO',
    'criticality' => 'RELEVANTE',
    'status' => 'OPERATIVE',
    'subclase' => 'BAJO COSTO'
];

// Generar HEC ID automáticamente usando nuestra nueva lógica
$hecId = generateAssetHecId($assetData);
$assetData['hec_id'] = $hecId;

$repo = new \Backend\Repositories\AssetRepository();
$success = $repo->create($assetData);

if ($success) {
    echo "✅ Equipo creado exitosamente.\n";
    echo "   - HEC ID Asignado: $hecId (Basado en Apoyo Diagnóstico -> APD)\n";
    echo "   - ID Inventario: INV-DEMO-001\n";
} else {
    echo "❌ Error al crear el equipo.\n";
    exit;
}

// Obtener el ID interno recién creado
$db = \Backend\Core\DatabaseService::getInstance();
$assetInternalId = $db->lastInsertId();

echo "\n--- 2. GENERANDO ORDEN DE TRABAJO (OT) CORRECTIVA ---\n";
$otData = [
    'asset_id' => $assetInternalId,
    'type' => 'Correctiva',
    'priority' => 'Alta',
    'observations' => 'Pantalla no enciende correctamente después de caída.',
    'status' => 'Pendiente'
];

$otId = createWorkOrder($otData);
if ($otId) {
    echo "✅ Orden de Trabajo creada: $otId\n";
} else {
    echo "❌ Error al crear la OT.\n";
}

echo "\n--- 3. COMPLETANDO OT Y ACTUALIZANDO ESTADO ---\n";
$executionData = [
    'final_asset_status' => 'OPERATIVE',
    'observations' => 'Se reemplaza fusible de fuente de poder. Equipo queda operativo.',
    'duration_hours' => 2.5
];

$otSuccess = completeWorkOrder($otId, $executionData);
if ($otSuccess) {
    echo "✅ OTizada finalizada con éxito.\n";
}

echo "\n--- 4. DADO DE BAJA EL EQUIPO (FIN DEL CICLO) ---\n";
$decommissionSuccess = updateAssetInfo($assetInternalId, ['status' => 'RETIRED']);
if ($decommissionSuccess) {
    echo "✅ Equipo dado DE BAJA satisfactoriamente.\n";
}

echo "\n--- RESUMEN FINAL ---\n";
$finalAsset = getAssetById($assetInternalId);
echo "Equipo: " . $finalAsset['name'] . "\n";
echo "Estado Final: " . $finalAsset['status'] . "\n";
echo "HEC ID: " . $finalAsset['hec_id'] . "\n";
echo "---------------------------------------------------\n";
