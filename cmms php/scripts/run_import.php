<?php

require_once __DIR__ . '/../backend/Helpers/ImportInventory.php';

use Backend\Helpers\ImportInventory;

$importer = new ImportInventory();
$csvPath = 'C:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.csv';

echo "Iniciando importación masiva...\n";
$results = $importer->run($csvPath);

if (isset($results['error'])) {
    echo "ERROR: " . $results['error'] . "\n";
} else {
    echo "Importación completada con éxito.\n";
    echo "- Activos creados: " . ($results['assets_created'] ?? 0) . "\n";
    echo "- Activos actualizados: " . ($results['assets_updated'] ?? 0) . "\n";
    echo "- Activos omitidos: " . ($results['assets_skipped'] ?? 0) . "\n";
    echo "- OTs Preventivas generadas: " . $results['ots_created'] . "\n";

    if (!empty($results['errors'])) {
        echo "\nErrores detectados:\n";
        foreach (array_slice($results['errors'], 0, 10) as $err) {
            echo "  - $err\n";
        }
    }
}
