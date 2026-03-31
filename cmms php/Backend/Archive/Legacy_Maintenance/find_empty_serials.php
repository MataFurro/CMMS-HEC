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
$seenInv = [];
$emptySerialDups = 0;

foreach ($rows as $i => $r) {
    if (count($r) < 10) continue;

    $inv = trim((string)$r[8]);
    $serial = trim((string)$r[7]);

    $isGeneric = ($inv === '' || $inv === 'S/I' || $inv === 'NO APLICA' || $inv === '-' || $inv === 'S/N');
    if ($isGeneric) continue;

    if (!isset($seenInv[$inv])) {
        $seenInv[$inv] = [];
    }

    $seenInv[$inv][] = [
        'row' => $i + 2,
        'serial' => $serial,
        'loc' => $r[0]
    ];
}

foreach ($seenInv as $inv => $items) {
    if (count($items) > 1) {
        $serials = array_column($items, 'serial');
        $hasEmpty = false;
        foreach ($serials as $s) {
            if (empty($s) || $s === 'S/I' || $s === 'NO APLICA' || $s === 'S/N' || $s === '-') {
                $hasEmpty = true;
            }
        }

        if ($hasEmpty) {
            $emptySerialDups += (count($items) - 1);
            echo "Inventory $inv has multiple items, some with empty serials:\n";
            foreach ($items as $it) {
                echo "  Row {$it['row']} - Serial: '{$it['serial']}' - Loc: {$it['loc']}\n";
            }
            echo "--------------------------\n";
        }
    }
}

echo "Total potential false merges due to empty serials: $emptySerialDups\n";
