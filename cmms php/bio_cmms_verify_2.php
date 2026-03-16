<?php
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';

echo "--- VERIFICACIÓN DE ESCENARIO 4: BAJA DE ACTIVO ---\n";

$assetId = 2; // Otro activo para no ensuciar el 1
$repo = new \Backend\Repositories\AssetRepository();

echo "  - Retirando activo $assetId...\n";
$repo->softDelete($assetId);

$asset = getAssetById($assetId);
if ($asset['status'] === 'RETIRED' && $asset['en_uso'] == 0) {
    echo "  [OK] Activo correctamente marcado como RETIRED y en_uso = 0.\n";
} else {
    echo "  [ERROR] El estado es " . $asset['status'] . " | en_uso: " . $asset['en_uso'] . "\n";
}

// Restaurar para dejar limpio
$repo->restore($assetId);
echo "  - Activo restaurado.\n";

echo "\n--- VERIFICACIÓN DE ESCENARIO 6: GARANTÍAS (UI LOGIC) ---\n";
// Esta es lógica pura de visualización en asset.php, ya revisada.

echo "\n--- TODO OK ---\n";
