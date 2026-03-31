<?php

/**
 * Deep diagnostic: counts GEN-hash collisions and DB unique key conflicts.
 */

$filePath = 'C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx';
$genericValues = ['S/S', 'S/I', 'N/A', 'SIN SERIE', 'COMODATO', 'COMPRA', '0', '-', 'DESC', 'POR DEFINIR', 'MANTENCION', ''];

function parseXlsx(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        echo "ERROR opening XLSX\n";
        return [];
    }
    $sharedStrings = [];
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = new SimpleXMLElement($ssData);
        foreach ($xml->si as $si) {
            if (isset($si->r)) {
                $p = '';
                foreach ($si->r as $r) $p .= (string)($r->t ?? '');
                $sharedStrings[] = $p;
            } else $sharedStrings[] = (string)($si->t ?? '');
        }
    }
    $rows = [];
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetData) {
        $xml = new SimpleXMLElement($sheetData);
        foreach ($xml->sheetData->row as $row) {
            $cur = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                if ((string)$cell['t'] === 's') $val = $sharedStrings[(int)$val] ?? '';
                $ref = (string)$cell['r'];
                $ci = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) $ci = $ci * 26 + (ord($ref[$i]) - 64);
                    else break;
                }
                $cur[$ci - 1] = $val;
            }
            if (!empty($cur)) {
                $mx = max(array_keys($cur));
                for ($i = 0; $i <= $mx; $i++) if (!isset($cur[$i])) $cur[$i] = '';
                ksort($cur);
                $rows[] = array_values($cur);
            }
        }
    }
    $zip->close();
    return $rows;
}

$allRows = parseXlsx($filePath);
$headerRaw = array_shift($allRows);

// Map headers (simplified)
$synonyms = [
    'serial' => ['serie', 'n de serie', 'serial', 's/n'],
    'inv_id' => ['n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario', 'id inventario', 'n inventario'],
    'name'   => ['nombre', 'equipo', 'nombre equipo', 'nombre del equipo'],
    'location' => ['ubicacion', 'servicio', 'servicio clinico', 'servicio clínico'],
    'sub'    => ['recinto', 'piso', 'sala', 'sub-ubicacion'],
];

$cleaner = function ($s) {
    if (!$s) return "";
    $n = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', '°' => ''];
    $s = mb_strtolower(trim($s), 'UTF-8');
    return preg_replace('/[^a-z0-9]/', '', strtr($s, $n));
};

$colMap = [];
foreach ($headerRaw as $i => $h) {
    $hc = $cleaner($h);
    foreach ($synonyms as $key => $list) {
        foreach ($list as $s) {
            if ($hc === $cleaner($s) || (strlen($cleaner($s)) > 4 && strpos($hc, $cleaner($s)) !== false)) {
                if (!isset($colMap[$key])) $colMap[$key] = $i;
                break 2;
            }
        }
    }
}

echo "=== DEEP DIAGNOSTIC: Import Collision Analysis ===\n";
echo "Column map: " . json_encode($colMap) . "\n\n";

$genHashCounts = [];  // hash => [rowNums]
$validInvIds   = [];  // inventory_id => [rowNums] — detect duplicate inv IDs in Excel
$totalGeneric  = 0;
$totalWithInvId = 0;
$totalWithSerial = 0;

foreach ($allRows as $idx => $row) {
    $rowNum = $idx + 2;
    $filled = array_filter($row, fn($v) => trim($v) !== '');
    if (count($filled) < 3) continue;

    $serial  = trim($row[$colMap['serial'] ?? 99] ?? '');
    $invId   = trim($row[$colMap['inv_id'] ?? 98] ?? '');
    $name    = trim($row[$colMap['name']   ?? 4] ?? '');
    $loc     = trim($row[$colMap['location'] ?? 0] ?? '');
    $sub     = trim($row[$colMap['sub']    ?? 1] ?? '');

    $isGenericInvId = empty($invId) || in_array(strtoupper($invId), ['S/S', 'S/I', 'N/A', 'SIN SERIE', 'COMODATO', 'COMPRA', '0', '-', 'DESC', 'POR DEFINIR', 'MANTENCION']);
    $isGenericSerial = empty($serial) || in_array(strtoupper($serial), ['S/S', 'S/I', 'N/A', 'SIN SERIE', 'COMODATO', 'COMPRA', '0', '-', 'DESC', 'POR DEFINIR', 'MANTENCION']);

    if ($isGenericInvId) {
        $totalGeneric++;
        // Compute GEN-... hash the same way ExcelProvider does
        $contentKey = mb_strtolower($name) . mb_strtolower($serial) . mb_strtolower($loc) . mb_strtolower($sub);
        $hash = "GEN-" . substr(md5($contentKey), 0, 12);
        $genHashCounts[$hash][] = $rowNum;
    } else {
        $totalWithInvId++;
        $validInvIds[$invId][] = $rowNum;
    }

    if (!$isGenericSerial) $totalWithSerial++;
}

$genCollisions = array_filter($genHashCounts, fn($rows) => count($rows) > 1);
$invIdDups     = array_filter($validInvIds, fn($rows) => count($rows) > 1);

echo "Total data rows                : " . count($allRows) . "\n";
echo "Rows WITH valid N° Inventario  : $totalWithInvId\n";
echo "Rows WITHOUT valid N° Inventario (GEN): $totalGeneric\n";
echo "Rows WITH valid serial          : $totalWithSerial\n\n";

echo "=== GEN-HASH COLLISIONS (rows that get MERGED instead of CREATED) ===\n";
echo "Distinct colliding hashes: " . count($genCollisions) . "\n";
$lostFromCollisions = 0;
foreach ($genCollisions as $hash => $rows) {
    $lostFromCollisions += count($rows) - 1;
    echo "  $hash → rows " . implode(', ', $rows) . "\n";
}
echo "Total rows LOST to GEN-hash collision: $lostFromCollisions\n\n";

echo "=== DUPLICATE N° INVENTARIO IN EXCEL (would also merge) ===\n";
echo "Distinct duplicate inv_ids: " . count($invIdDups) . "\n";
$lostFromInvDups = 0;
foreach ($invIdDups as $id => $rows) {
    $lostFromInvDups += count($rows) - 1;
    echo "  '$id' → rows " . implode(', ', $rows) . "\n";
}
echo "Total rows LOST to duplicate inv_id: $lostFromInvDups\n\n";

$totalLost = $lostFromCollisions + $lostFromInvDups;
echo "SUMMARY: Expected 3110, predict " . (3110 - $totalLost) . " if collisions cause losses.\n";
echo "Actual imported: 2665 → unaccounted: " . (3110 - 2665 - $totalLost) . "\n";

// Check DB for how many have valid inventory_ids
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
    $actual = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    echo "\nDB currently has: $actual assets\n";
} catch (Exception $e) {
    echo "\nDB check failed: " . $e->getMessage() . "\n";
}
echo "\nDONE\n";
