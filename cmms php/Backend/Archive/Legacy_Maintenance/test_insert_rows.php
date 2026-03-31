<?php

/**
 * Row-by-row INSERT test: finds exactly which rows fail and why.
 * Tests the real ExcelProvider mapping logic and DB insert.
 */
ini_set('memory_limit', '512M');

$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$count = $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
echo "Current assets in DB: $count\n\n";

// XLSX parser
function xlsxParse($fp)
{
    $zip = new ZipArchive();
    $zip->open($fp);
    $ss = [];
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = new SimpleXMLElement($ssData);
        foreach ($xml->si as $si) {
            if (isset($si->r)) {
                $p = '';
                foreach ($si->r as $r) $p .= (string)($r->t ?? '');
                $ss[] = $p;
            } else $ss[] = (string)($si->t ?? '');
        }
    }
    $rows = [];
    $sd = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sd) {
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

$rows = xlsxParse('C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx');
$header = array_shift($rows); // col 0=location,1=sub,4=name,7=serial,8=inv_id,etc.

// Column indices (from diagnostic: location=0,sub=1,name=4,serial=7,inv_id=8)
$C = [
    'location' => 0,
    'sub_location' => 1,
    'risk_class' => 2,
    'name' => 4,
    'brand' => 5,
    'model' => 6,
    'serial_number' => 7,
    'id' => 8,
    'purchased_year' => 9,
    'total_useful_life' => 10,
    'years_remaining' => 11,
    'ownership' => 12,
    'status' => 13,
    'criticality' => 14,
    'acquisition_cost' => 22,
];

$normOwnership = function ($v) {
    $v = strtoupper(trim($v));
    if ($v === 'ARRIENDO') return 'Arriendo';
    if ($v === 'COMODATO') return 'Comodato';
    return 'Propio'; // default
};

$normStatus = function ($v) {
    $v = strtoupper(trim($v));
    $m = ['BUENO' => 'OPERATIVE', 'REGULAR' => 'OPERATIVE_WITH_OBS', 'MALO' => 'NO_OPERATIVE', 'BAJA' => 'NO_OPERATIVE', 'OPERATIVO' => 'OPERATIVE'];
    return $m[$v] ?? 'OPERATIVE';
};

$normCriticality = function ($v) {
    $c = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($v, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'])));
    $m = ['critico' => 'CRITICAL', 'emc' => 'CRITICAL', 'alta' => 'CRITICAL', 'relevant' => 'RELEVANT', 'emr' => 'RELEVANT', 'media' => 'RELEVANT', 'low' => 'LOW', 'baja' => 'LOW', 'noaplica' => 'LOW', 'na' => 'LOW', 'im12' => 'RELEVANT'];
    return $m[$c] ?? 'LOW';
};

$cleanCost = function ($v) {
    $v = preg_replace('/[^0-9.,]/', '', $v);
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : 0.0;
};

$cleanYear = function ($v) {
    $v = preg_replace('/[^0-9]/', '', $v);
    $v = (int)$v;
    if ($v >= 1900 && $v <= 2100) return $v;
    return null;
};

$cleanInt = function ($v) {
    $v = preg_replace('/[^0-9]/', '', $v);
    return strlen($v) > 0 ? (int)$v : null;
};

// Prepared insert
$stmt = $pdo->prepare("INSERT INTO assets 
    (inventory_id, name, serial_number, brand, model, location, sub_location,
     ownership, criticality, status, riesgo_ge, purchased_year, acquisition_cost,
     total_useful_life, useful_life_pct, years_remaining, en_uso, annual_maint_cost,
     under_maintenance_plan)
    VALUES 
    (:inv, :name, :serial, :brand, :model, :loc, :sub,
     :own, :crit, :stat, :rge, :year, :cost,
     :tul, :ulp, :yr, 1, 0, 0)");

$genericValues = ['S/S', 'S/I', 'N/A', 'SIN SERIE', 'COMODATO', 'COMPRA', '0', '-', 'DESC', 'POR DEFINIR', 'MANTENCION'];

$created = 0;
$skipped = 0;
$errors = 0;
$errorLog = [];

foreach ($rows as $i => $row) {
    $rowNum = $i + 2;
    if (count(array_filter($row, fn($v) => trim($v) !== '')) < 3) {
        $skipped++;
        continue;
    }

    $rawId = trim($row[$C['id']] ?? '');
    $isGeneric = empty($rawId) || in_array(strtoupper($rawId), $genericValues);

    if ($isGeneric) {
        $name = trim($row[$C['name']] ?? '');
        $serial = trim($row[$C['serial_number']] ?? '');
        $loc = trim($row[$C['location']] ?? '');
        $sub = trim($row[$C['sub_location']] ?? '');
        $invId = 'GEN-' . substr(md5(mb_strtolower($name . $serial . $loc . $sub)), 0, 12);
    } else {
        $invId = $rawId;
    }

    $purchased_year = $cleanYear($row[$C['purchased_year']] ?? '');
    $tul = $cleanInt($row[$C['total_useful_life']] ?? '');
    $yr  = $cleanInt($row[$C['years_remaining']] ?? '');
    $ulp = ($tul && $yr !== null) ? min(100, max(0, round(($yr / $tul) * 100))) : 100;

    $params = [
        ':inv'   => $invId,
        ':name'  => trim($row[$C['name']] ?? ''),
        ':serial' => trim($row[$C['serial_number']] ?? '') ?: null,
        ':brand' => trim($row[$C['brand']] ?? '') ?: null,
        ':model' => trim($row[$C['model']] ?? '') ?: null,
        ':loc'   => trim($row[$C['location']] ?? '') ?: null,
        ':sub'   => trim($row[$C['sub_location']] ?? '') ?: null,
        ':own'   => $normOwnership($row[$C['ownership']] ?? ''),
        ':crit'  => $normCriticality($row[$C['criticality']] ?? ''),
        ':stat'  => $normStatus($row[$C['status']] ?? ''),
        ':rge'   => trim($row[$C['risk_class']] ?? '') ?: null,
        ':year'  => $purchased_year,
        ':cost'  => $cleanCost($row[$C['acquisition_cost']] ?? ''),
        ':tul'   => $tul,
        ':ulp'   => $ulp,
        ':yr'    => $yr,
    ];

    if (empty($params[':name'])) {
        $skipped++;
        continue;
    }

    try {
        $stmt->execute($params);
        $created++;
    } catch (PDOException $e) {
        $errors++;
        $errorLog[] = "Row $rowNum: " . $e->getMessage() . " | inv=$invId name={$params[':name']}";
        if ($errors <= 20) {
            echo "ROW $rowNum FAIL: " . $e->getMessage() . "\n";
            echo "  inv=$invId | year={$params[':year']} | cost={$params[':cost']} | own={$params[':own']} | crit={$params[':crit']}\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Created: $created\nSkipped: $skipped\nErrors:  $errors\n";
$final = $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
echo "DB count: $final\n";
echo "Missing: " . (count($rows) + 1 - $created - $skipped - $errors) . "\n";
if (!empty($errorLog)) {
    file_put_contents(__DIR__ . '/import_errors.txt', implode("\n", $errorLog));
    echo "\nAll errors written to import_errors.txt\n";
}
