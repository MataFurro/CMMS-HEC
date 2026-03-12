<?php
require_once __DIR__ . '/Backend/Providers/ExcelProvider.php';

use function Backend\Providers\parseXlsxToArray;

$file = __DIR__ . '/Prueba 2.xlsx';
if (!file_exists($file)) {
    $file = 'C:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.xlsx';
}

$rows = parseXlsxToArray($file);
$headerRow = array_shift($rows);

// Find indices: inv 8, serial 7, loc 0
$seen = [];
$totalRows = count($rows);
$created = 0;
$merged = 0;
$skipped = 0;

foreach ($rows as $i => $r) {
    if (count($r) < 10) continue;

    $inv = trim((string)$r[8]);
    $serial = trim((string)$r[7]);
    $name = trim((string)$r[4]);

    if (empty($name)) {
        $name = 'SIN NOMBRE';
    }

    $isGeneric = ($inv === '' || $inv === 'S/I' || $inv === 'NO APLICA' || $inv === '-' || $inv === 'S/N');

    if (!$isGeneric) {
        $fileDupKey = $inv . '|' . $serial;

        if (isset($seen[$fileDupKey])) {
            $merged++;
            echo "MERGED (Same file): Inv=$inv, Serial=$serial, Row=" . ($i + 2) . "\n";
        } else {
            $seen[$fileDupKey] = 1;
            // For simplicity in this diagnostic script, we assume DB is empty to start.
            $created++;
        }
    } else {
        $created++;
    }
}
echo "Total Rows Processed: $totalRows\n";
echo "Simulated Created: $created\n";
echo "Simulated Merged: $merged\n";
echo "Simulated Skipped: $skipped\n";
