<?php

/**
 * scripts/test_retirement_flow.php
 */

require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

use Backend\Core\DatabaseService;

$db = DatabaseService::getInstance();

// 1. Tomar el equipo demo (ID 3113)
$assetId = 3113;
echo "--- PROBANDO FLUJO DE BAJA PARA ACTIVO #$assetId ---\n";

$asset = getAssetById($assetId);
echo "Estado Inicial: " . ($asset['status'] ?? 'UNKNOWN') . " (en_uso: " . ($asset['en_uso'] ?? '?') . ")\n";

// 2. Ejecutar Baja (Soft Delete)
echo "Ejecutando softDeleteAsset()...\n";
softDeleteAsset($assetId);

$asset = getAssetById($assetId);
echo "Estado Post-Baja: " . ($asset['status'] ?? 'UNKNOWN') . " (en_uso: " . ($asset['en_uso'] ?? '?') . ")\n";

if ($asset['status'] === 'RETIRED' && $asset['en_uso'] == 0) {
    echo "[OK] El equipo se marcó como RETIRED correctamente.\n";
} else {
    echo "[FAIL] El equipo NO tiene el estado correcto post-baja.\n";
}

// 3. Ejecutar Restauración
echo "Ejecutando restoreAsset()...\n";
restoreAsset($assetId);

$asset = getAssetById($assetId);
echo "Estado Post-Restauración: " . ($asset['status'] ?? 'UNKNOWN') . " (en_uso: " . ($asset['en_uso'] ?? '?') . ")\n";

if ($asset['status'] === 'OPERATIVE' && $asset['en_uso'] == 1) {
    echo "[OK] El equipo se restauró a OPERATIVE correctamente.\n";
} else {
    echo "[FAIL] Error en la restauración.\n";
}

echo "--- PRUEBA FINALIZADA ---\n";
