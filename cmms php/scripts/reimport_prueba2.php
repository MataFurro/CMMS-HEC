<?php
/**
 * scripts/reimport_prueba2.php
 * Script for emergency re-import with fixed specialty mapping.
 */

// 1. Setup Environment
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Providers/ExcelProvider.php';
require_once __DIR__ . '/../Backend/Repositories/AssetRepository.php';

use Backend\Providers\ExcelProvider;

echo "--- BioCMMS Emergency Re-import (Fixed Specialties) ---\n";

$csvPath = 'c:\Users\star_\OneDrive\Escritorio\Prueba 2.csv';

if (!file_exists($csvPath)) {
    die("ERROR: No se encuentra el archivo en $csvPath\n");
}

echo "Leyendo archivo: $csvPath\n";

// Emulamos la estructura de $_FILES
$fileData = [
    'tmp_name' => $csvPath,
    'name' => 'Prueba 2.csv'
];

echo "Iniciando importación...\n";
$stats = \Backend\Providers\importAssetsFromFile($fileData);

echo "Resultado de Importación:\n";
echo "- Total procesados: " . $stats['total'] . "\n";
echo "- Éxito: " . $stats['success'] . "\n";
echo "- Skipped/Merged: " . $stats['merged'] . "\n";
echo "- Errores: " . $stats['errors'] . "\n";

if (!empty($stats['details'])) {
    echo "\nDetalles de errores (primeros 5):\n";
    print_r(array_slice($stats['details'], 0, 5));
}

echo "\n--- Generando Historico de OTs para Dashboards ---\n";
// Re-utilizamos la lógica del poblador v3 para tener datos reales para los gráficos
require_once __DIR__ . '/../scripts/populate_dashboard_v4.php'; // We might need to create this or update v3

echo "Población completada.\n";
echo "Finalizado.\n";
