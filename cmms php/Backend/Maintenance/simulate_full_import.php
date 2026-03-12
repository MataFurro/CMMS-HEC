<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Models/AssetEntity.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';
require_once __DIR__ . '/Backend/Providers/ExcelProvider.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\AssetRepository;
use function Backend\Providers\importAssetsFromFile;

$db = DatabaseService::getInstance();

$db->exec("SET FOREIGN_KEY_CHECKS = 0;");
$db->exec("TRUNCATE TABLE assets");
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");

$repo = new AssetRepository($db);
$file = __DIR__ . '/Prueba 2.xlsx';
if (!file_exists($file)) {
    $file = 'C:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.xlsx';
}

echo "Running full import as if uploaded via UI...\n";

// Need to pass a file array structure
$fileInfo = [
    'name'     => 'Prueba 2.xlsx',
    'tmp_name' => $file,
    'size'     => filesize($file)
];

$stats = importAssetsFromFile($fileInfo, $repo);

echo "\n--- RESULTS ---\n";
echo "Total Rows: " . $stats['total'] . "\n";
echo "Created: " . $stats['created'] . "\n";
echo "Updated: " . $stats['updated'] . "\n";
echo "Merged: " . $stats['merged'] . "\n";
echo "Errors: " . $stats['errors'] . "\n";
echo "\n--- ERROR DETAILS ---\n";
foreach ($stats['details'] as $det) {
    if (strpos($det, '❌') !== false) {
        echo "$det\n";
    }
}
