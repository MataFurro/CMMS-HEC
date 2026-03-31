<?php

/**
 * CLI diagnostic: reads Prueba 2.xlsx and identifies problematic rows.
 */

$filePath = 'C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx';

if (!file_exists($filePath)) {
    echo "ERROR: File not found at $filePath\n";
    exit(1);
}

function parseXlsx(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        echo "ERROR: Cannot open ZIP/XLSX\n";
        return [];
    }

    $sharedStrings = [];
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = new SimpleXMLElement($ssData);
        foreach ($xml->si as $si) {
            if (isset($si->r)) {
                $parts = '';
                foreach ($si->r as $r) $parts .= (string)($r->t ?? '');
                $sharedStrings[] = $parts;
            } else {
                $sharedStrings[] = (string)($si->t ?? '');
            }
        }
    }

    $rows = [];
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetData) {
        $xml = new SimpleXMLElement($sheetData);
        foreach ($xml->sheetData->row as $row) {
            $currentRow = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                if ((string)$cell['t'] === 's') $val = $sharedStrings[(int)$val] ?? '';
                $ref = (string)$cell['r'];
                $colIndex = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) $colIndex = $colIndex * 26 + (ord($ref[$i]) - 64);
                    else break;
                }
                $currentRow[$colIndex - 1] = $val;
            }
            if (!empty($currentRow)) {
                $maxCol = max(array_keys($currentRow));
                for ($i = 0; $i <= $maxCol; $i++) if (!isset($currentRow[$i])) $currentRow[$i] = '';
                ksort($currentRow);
                $rows[] = array_values($currentRow);
            }
        }
    }
    $zip->close();
    return $rows;
}

$allRows = parseXlsx($filePath);
$headerRaw = array_shift($allRows);
$totalDataRows = count($allRows);
$colCount = count($headerRaw);

echo "=== EXCEL DIAGNOSTIC: Prueba 2.xlsx ===\n";
echo "Total data rows (excl. header): $totalDataRows\n";
echo "Expected: 3111 | Imported: 3108 | Missing: 3\n";
echo "Columns: $colCount\n";
echo "Headers: " . implode(' | ', $headerRaw) . "\n\n";

$issues = [];
$fingerprints = [];
$genericSerials = ['s/s', 's/i', 'n/a', 'sin serie', '0', '-', 'comodato', '', 'por definir', 'desc'];

foreach ($allRows as $idx => $row) {
    $rowNum = $idx + 2;
    $filled = array_filter($row, fn($v) => trim($v) !== '');
    $filledCount = count($filled);

    if ($filledCount === 0) {
        $issues[] = ['row' => $rowNum, 'type' => 'EMPTY', 'detail' => 'Row completely empty'];
        continue;
    }

    if ($filledCount < 3) {
        $issues[] = ['row' => $rowNum, 'type' => 'SPARSE', 'detail' => "Only $filledCount field(s): " . implode(' | ', array_filter($row, fn($v) => trim($v) !== ''))];
        continue;
    }

    // Check duplicate fingerprint
    $serial = strtolower(trim($row[7] ?? ''));
    if (empty($serial)) $serial = strtolower(trim($row[6] ?? ''));
    $name   = '';
    for ($c = 0; $c < min(8, count($row)); $c++) {
        if (!empty(trim($row[$c]))) {
            $name = strtolower(trim($row[$c]));
            break;
        }
    }
    $loc    = strtolower(trim($row[1] ?? $row[0] ?? ''));

    $hasValidSerial = !in_array($serial, $genericSerials) && strlen($serial) > 2;
    if ($hasValidSerial) {
        if (isset($fingerprints[$serial])) {
            $issues[] = ['row' => $rowNum, 'type' => 'DUPLICATE_SERIAL', 'detail' => "Same serie='$serial' as row {$fingerprints[$serial]}"];
        } else {
            $fingerprints[$serial] = $rowNum;
        }
    }

    // Encoding check
    $rowText = implode('', $row);
    if (!mb_check_encoding($rowText, 'UTF-8')) {
        $issues[] = ['row' => $rowNum, 'type' => 'BAD_ENCODING', 'detail' => 'Non-UTF-8 characters in row'];
    }
}

if (empty($issues)) {
    echo "No issues found in the Excel file structure.\n";
    echo "The missing rows may have failed silently on DB INSERT (e.g. duplicate inventory_id constraint).\n";
} else {
    echo count($issues) . " issue(s) found:\n\n";
    printf("%-6s %-20s %s\n", "ROW#", "TYPE", "DETAIL");
    echo str_repeat('-', 80) . "\n";
    foreach ($issues as $issue) {
        printf("%-6s %-20s %s\n", $issue['row'], $issue['type'], substr($issue['detail'], 0, 100));
    }
}

// Check the debug import log
$logFile = __DIR__ . '/Backend/Providers/debug_import.txt';
echo "\n=== Last Import Log ===\n";
if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "(no debug log found)\n";
}

echo "\n=== DONE ===\n";
