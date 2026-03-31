<?php
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';

echo "--- PRUEBA DE FILTRADO 'LOW' ---\n";

// Buscar por LOW
$low = searchAssets('', 'ALL', 10, 0, ['criticality' => 'LOW']);
echo "Total con filtro LOW (primeros 10): " . count($low) . "\n";

// Contar total con filtro LOW
$countLow = countAssets('', 'ALL', ['criticality' => 'LOW']);
echo "Conteo Total LOW: " . $countLow . "\n";

echo "---------------------------------\n";
