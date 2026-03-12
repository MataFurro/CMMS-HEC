<?php
ini_set('memory_limit', '512M');
// Find exactly how many rows ExcelProvider skips due to empty 'name' field

function parseXlsx($fp)
{
    $zip = new ZipArchive();
    $zip->open($fp);
    $ss = [];
    if ($sd = $zip->getFromName('xl/sharedStrings.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->si as $si) {
            if (isset($si->r)) {
                $p = '';
                foreach ($si->r as $r) $p .= (string)($r->t ?? '');
                $ss[] = $p;
            } else $ss[] = (string)($si->t ?? '');
        }
    }
    $rows = [];
    if ($sd = $zip->getFromName('xl/worksheets/sheet1.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->sheetData->row as $row) {
            $cur = [];
            foreach ($row->c as $cell) {
                $v = (string)$cell->v;
                if ((string)$cell['t'] === 's') $v = $ss[(int)$v] ?? '';
                $ref = (string)$cell['r'];
                $ci = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) $ci = $ci * 26 + (ord($ref[$i]) - 64);
                    else break;
                }
                $cur[$ci - 1] = $v;
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

$rows = parseXlsx('C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx');
$headersRaw = array_shift($rows);
echo "Total data rows: " . count($rows) . "\n";
echo "Header count: " . count($headersRaw) . "\n";

$cleaner = function ($str) {
    if (!$str) return "";
    if (!mb_check_encoding($str, 'UTF-8')) $str = @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    $str = mb_strtolower(trim($str), 'UTF-8');
    $n = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', 'Ü' => 'u'];
    return preg_replace('/[^a-z0-9]/', '', strtr($str, $n));
};

$synonyms = [
    'id'               => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario'],
    'name'             => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo'],
    'serial_number'    => ['serie', 'n de serie', 'n° de serie', 'serial', 's/n', 'numero de serie'],
    'criticality'      => ['criticidad', 'criticality', 'prioridad', 'clasificacion', 'criticorelevanteim12noaplica'],
    'location'         => ['ubicacion', 'servicio', 'area', 'unidad', 'departamento', 'servicio clínico', 'servicio clinico'],
    'sub_location'     => ['sub-ubicacion', 'sububicacion', 'recinto', 'piso', 'sala', 'oficina', 'nivel'],
    'status'           => ['estado', 'status', 'situacion', 'estadobuenoregularmalobaja'],
    'purchased_year'   => ['año compra', 'fecha compra', 'año', 'adquisicion', 'año de adquisición', 'adquisición'],
    'total_useful_life' => ['vida útil', 'vida util (total)', 'vida util', 'vida util completa', 'vida util total'],
    'years_remaining'  => ['vida útil residual', 'vida util residual', 'años restantes', 'años residuales', 'vida residual'],
    'acquisition_cost' => ['costo', 'valor', 'precio', 'costo de adquisición', 'costo adquisicion', 'valor de adquisicion', 'acquisition cost', 'valor comercial', 'costo anual de mantenimiento segun convenio  precio de referencia mantenimiento anual'],
    'risk_class'       => ['clase de riesgo', 'riesgo', 'riesgo ge', 'clase riesgo', 'clase_riesgo', 'clase']
];

// Map headers as ExcelProvider does
$headers = [];
$mappedCount = 0;
$usedKeys = [];
foreach ($headersRaw as $h) {
    $hClean = $cleaner($h);
    $mapped = null;
    foreach ($synonyms as $key => $list) {
        if (in_array($key, $usedKeys)) continue;
        foreach ($list as $s) {
            if ($hClean === $cleaner($s)) {
                $mapped = $key;
                $usedKeys[] = $key;
                $mappedCount++;
                break 2;
            }
        }
    }
    if (!$mapped) {
        foreach ($synonyms as $key => $list) {
            if (in_array($key, $usedKeys)) continue;
            foreach ($list as $s) {
                $sC = $cleaner($s);
                if (strlen($sC) > 4 && strpos($hClean, $sC) !== false) {
                    $mapped = $key;
                    $usedKeys[] = $key;
                    $mappedCount++;
                    break 2;
                }
            }
        }
    }
    $headers[] = $mapped ?? $hClean;
}

echo "\nMapped " . $mappedCount . " headers\n";
echo "Final header order:\n";
foreach ($headers as $i => $h) echo "  col$i = $h\n";

// Check what 'name' maps to and where
$nameIdx = array_search('name', $headers);
echo "\n'name' is at header index: $nameIdx\n";
echo "'name' header raw: " . ($headersRaw[$nameIdx] ?? 'N/A') . "\n";

// Count how many rows produce an empty name after combine
$emptyName = 0;
$emptyRow = 0;
$ok = 0;
$firstFewEmptyName = [];

foreach ($rows as $idx => $data) {
    $rowNum = $idx + 2;
    if (empty(array_filter($data))) {
        $emptyRow++;
        continue;
    }
    $row = array_combine(array_slice($headers, 0, count($data)), array_slice($data, 0, count($headers)));
    $n = trim($row['name'] ?? '');
    if (empty($n)) {
        $emptyName++;
        if (count($firstFewEmptyName) < 10) {
            $firstFewEmptyName[] = "Row $rowNum: " . json_encode(array_slice($row, 0, 8));
        }
    } else {
        $ok++;
    }
}

echo "\nCompletely empty rows (skipped): $emptyRow\n";
echo "Rows with empty 'name' after mapping: $emptyName\n";
echo "Rows with valid 'name': $ok\n";
echo "\nExpected DB records from ExcelProvider (ok - dedup): ~$ok (minus collisions)\n";

if ($emptyName > 0) {
    echo "\nFirst few empty-name rows:\n";
    foreach ($firstFewEmptyName as $r) echo "  $r\n";
}
