<?php
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';

echo "--- PRUEBA DE FILTRADO POR CRITICIDAD ---\n";

// 1. Buscar todos (RELEVANT debería aparecer)
$all = searchAssets('', 'ALL', 10, 0, ['criticality' => 'ALL']);
echo "Total con filtro ALL: " . count($all) . "\n";

// 2. Buscar por RELEVANT (Debería encontrar al menos el del demo)
$relevant = searchAssets('', 'ALL', 10, 0, ['criticality' => 'RELEVANT']);
echo "Total con filtro RELEVANT: " . count($relevant) . "\n";
foreach ($relevant as $a) {
    echo " - Encontrado: " . $a['name'] . " (HEC: " . $a['hec_id'] . ")\n";
}

// 3. Buscar por CRITICAL (Debería ser 0 para el demo)
$critical = searchAssets('', 'ALL', 10, 0, ['criticality' => 'CRITICAL']);
echo "Total con filtro CRITICAL: " . count($critical) . "\n";

echo "----------------------------------------\n";
