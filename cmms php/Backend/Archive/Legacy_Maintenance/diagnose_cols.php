<?php
ini_set('memory_limit', '512M');
// Check how many rows have fewer than N columns in the XLSX output

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
$headerRaw = array_shift($rows);
$headerCount = count($headerRaw);
echo "Total rows: " . count($rows) . " | Headers: $headerCount\n\n";

// Count distribution of column counts per row
$colDist = [];
$shortRows = []; // rows with fewer than 5 columns filled (name is at col4)

foreach ($rows as $idx => $row) {
    $n = count($row);
    $colDist[$n] = ($colDist[$n] ?? 0) + 1;
    if ($n < 5) {
        $shortRows[] = ['row' => $idx + 2, 'cols' => $n, 'data' => implode(' | ', $row)];
    }
}

ksort($colDist);
echo "Column count distribution:\n";
foreach ($colDist as $n => $c) {
    $bar = str_repeat('■', min(50, $c));
    echo sprintf("  %3d cols → %4d rows  %s\n", $n, $c, $bar);
}

echo "\nRows with < 5 cols (name would be missing): " . count($shortRows) . "\n";
if (!empty($shortRows)) {
    foreach (array_slice($shortRows, 0, 20) as $r) {
        echo "  Row {$r['row']} ({$r['cols']} cols): {$r['data']}\n";
    }
}

// Count how many rows from ExcelProvider's perspective would fail
// ExcelProvider does: $row = array_combine(array_slice($headers,0,count($data)), array_slice($data,0,count($headers)))
// If count($data) < name column index (4), name won't be in $row
$headers = array_fill(0, $headerCount, 'x');
$headers[0] = 'location';
$headers[1] = 'sub_location';
$headers[2] = 'risk_class';
$headers[3] = 'subclase';
$headers[4] = 'name';
$headers[5] = 'brand';
$headers[6] = 'model';
$headers[7] = 'serial_number';
$headers[8] = 'id';

$missingName = 0;
foreach ($rows as $data) {
    if (empty(array_filter($data))) continue;
    $row = array_combine(array_slice($headers, 0, count($data)), array_slice($data, 0, count($headers)));
    if (empty(trim($row['name'] ?? ''))) $missingName++;
}
echo "\nRows where 'name' is empty after array_combine: $missingName\n";

$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
echo "Current DB assets: " . $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn() . "\n";
echo "\nIf max-column rows = 3109 - $missingName = " . (3109 - $missingName) . " expected in DB\n";
