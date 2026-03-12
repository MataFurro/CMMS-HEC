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

    $isGeneric = ($inv === '' || $inv === 'S/I' || $inv === 'NO APLICA' || $inv === '-' || $inv === 'S/N');

    if (!$isGeneric) {
        // Query to see if this exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE inventory_id = :inv AND serial_number = :serial AND en_uso=1");
        $stmt->execute(['inv' => $inv, 'serial' => $serial]);
        $count = $stmt->fetchColumn();

        // Also check if matches missing serial logic
        if ($count == 0 && empty($serial)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE inventory_id = :inv AND (serial_number IS NULL OR serial_number = '') AND en_uso=1");
            $stmt->execute(['inv' => $inv]);
            $count = $stmt->fetchColumn();
        }

        if ($count == 0) {
            echo "Missing: Row " . ($i + 2) . " | Inv: $inv | Serial: $serial | Name: $name\n";
            $missingCount++;
        }
    } else {
        // hard to match generic perfectly unless by name and location
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE name = :name AND location = :loc AND en_uso=1");
        $stmt->execute(['name' => $name, 'loc' => $loc]);
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            echo "Missing Generic: Row " . ($i + 2) . " | Inv: $inv | Name: $name\n";
            $missingCount++;
        }
    }
}
echo "Total missing suspected: $missingCount\n";
