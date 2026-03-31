<?php
require_once __DIR__ . '/Backend/Providers/ExcelProvider.php';

use function Backend\Providers\parseXlsxToArray;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');

$file = __DIR__ . '/Prueba 2.xlsx';
if (!file_exists($file)) {
    $file = 'C:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.xlsx';
}

$rows = parseXlsxToArray($file);
$headerRow = array_shift($rows);

$missingCount = 0;
foreach ($rows as $i => $r) {
    if (count($r) < 10) continue;
    $inv = trim((string)$r[8]);
    $serial = trim((string)$r[7]);
    $name = trim((string)$r[4]);
    $loc = trim((string)$r[0]);

    if ($name === '') $name = 'SIN NOMBRE';

    // Simple brute-force check: Does any asset match this name, location, and serial?
    // We use LIKE or exact match
    $stmt = $pdo->prepare("SELECT id, inventory_id FROM assets WHERE name = :name AND location = :loc AND serial_number = :serial AND en_uso=1");
    $stmt->execute(['name' => $name, 'loc' => $loc, 'serial' => $serial]);
    $found = $stmt->fetchAll();

    if (count($found) === 0) {
        echo "Missing completely: Row " . ($i + 2) . " | Inv: $inv | Serial: $serial | Name: $name\n";
        $missingCount++;
    }
}
echo "Total missing: $missingCount\n";
