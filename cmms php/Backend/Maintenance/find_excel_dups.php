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
echo "Total data rows: $totalRows\n";

$identicalCount = 0;
foreach ($rows as $i => $r) {
    if (count($r) < 10) continue;
    $inv = trim((string)$r[8]);
    $serial = trim((string)$r[7]);

    if ($inv === '' || $inv === 'S/I' || $inv === 'NO APLICA' || $inv === '-' || $inv === 'S/N') continue;

    $key = "$inv|$serial";
    if (isset($seen[$key])) {
        echo "Duplicate: Inv=$inv, Serial=$serial\n";
        echo "  Row {$seen[$key]['row']}: Loc={$seen[$key]['loc']}\n";
        echo "  Row " . ($i + 2) . ": Loc={$r[0]}\n";
        $identicalCount++;
    } else {
        $seen[$key] = ['row' => $i + 2, 'loc' => $r[0]];
    }
}
echo "Total duplicates with exact same Inventory ID and Serial Number: $identicalCount\n";
