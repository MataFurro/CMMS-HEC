<?php

/**
 * seed_real_assets.php
 * Poblamiento masivo de la base de datos con activos reales del catastro 2026.
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\AssetRepository;

$csvPath = __DIR__ . '/temp_excel/output_2026/sheet_1_catastro_y_planificaci_n_mp.csv';

if (!file_exists($csvPath)) {
    die("❌ Error: No se encuentra el archivo CSV en $csvPath\n");
}

echo "🌱 Iniciando poblamiento masivo de activos (Catastro 2026)...\n\n";

try {
    $db = DatabaseService::getInstance();
    $repo = new AssetRepository();

    // Opcional: Limpiar tabla para evitar duplicados en la prueba
    $clear = isset($_GET['clear']) || (isset($argv) && in_array('clear', $argv));
    if ($clear) {
        $db->exec("DELETE FROM assets");
        echo " 🧹 Tabla 'assets' limpiada.\n";
    }

    $file = fopen($csvPath, 'r');
    $count = 0;
    $inserted = 0;

    // Saltar cabeceras (líneas 1-15 según el análisis previo)
    for ($i = 0; $i < 15; $i++) {
        fgetcsv($file);
    }

    while (($row = fgetcsv($file)) !== FALSE) {
        $count++;
        if (empty($row[4])) continue; // Nombre de equipo vacío

        // Mapeo de columnas
        $data = [
            'id' => $row[8] ?: ($row[7] ?: 'AST-' . str_pad($count, 5, '0', STR_PAD_LEFT)),
            'location' => $row[0] ?: 'CENTRAL',
            'sub_location' => $row[1] ?: 'S/R',
            'name' => $row[4],
            'brand' => $row[5],
            'model' => $row[6],
            'serial_number' => $row[7],
            'purchased_year' => (int)$row[9] ?: 2020,
            'total_useful_life' => (int)$row[10] ?: 8,
            'years_remaining' => (int)$row[11] ?: 0,
            'ownership' => $row[12] ?: 'PROPIO',
            'status' => (stripos($row[13], 'BUENO') !== false) ? 'OPERATIVE' : 'NO_OPERATIVE',
            'criticality' => (stripos($row[14], 'Crítico') !== false) ? 'CRITICAL' : 'RELEVANT',
            'under_maintenance_plan' => (stripos($row[17], 'SI') !== false) ? 1 : 0,
            'acquisition_cost' => (float)str_replace(['$', ',', ' '], '', $row[11]) ?: 1000.0,
            'useful_life_pct' => 100 // Por defecto
        ];

        // Calcular porcentaje de vida útil
        if ($data['total_useful_life'] > 0) {
            $data['useful_life_pct'] = round(($data['years_remaining'] / $data['total_useful_life']) * 100);
        }

        if ($repo->create($data)) {
            $inserted++;
            if ($inserted % 50 === 0) echo " 🔄 Procesados $inserted activos...\n";
        }
    }

    fclose($file);

    echo "\n✅ Finalizado: Se insertaron $inserted activos de un total de $count leídos.\n";
    echo "\n💡 Siguiente paso sugerido: Ejecute auto_classify_assets.php para asignar las nuevas familias tecnológicas.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
