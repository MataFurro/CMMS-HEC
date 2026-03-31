<?php
// Test exactly what values the Excel rows have vs what the live schema accepts
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Check the ownership ENUM constraint in strict mode
echo "=== SQL_MODE ===\n";
echo $pdo->query("SELECT @@SESSION.sql_mode")->fetchColumn() . "\n\n";

// 2. Test 'PROPIO' (uppercase) vs 'Propio'
$tests = [
    ['ownership' => 'PROPIO',    'expected' => 'fail or coerce'],
    ['ownership' => 'Propio',    'expected' => 'ok'],
    ['ownership' => 'propio',    'expected' => 'ok (case insensitive)'],
    ['ownership' => 'COMODATO',  'expected' => 'fail or coerce'],
    ['ownership' => 'Comodato',  'expected' => 'ok'],
    ['ownership' => 'ARRIENDO',  'expected' => 'fail or coerce'],
    ['ownership' => 'Arriendo',  'expected' => 'ok'],
    ['ownership' => '',          'expected' => 'fail - empty'],
    ['ownership' => null,        'expected' => 'fail - null'],
];

echo "=== OWNERSHIP ENUM TESTS ===\n";
foreach ($tests as $t) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO assets (inventory_id, name, ownership, criticality, status, en_uso, useful_life_pct, under_maintenance_plan, total_useful_life, years_remaining, acquisition_cost, annual_maint_cost)
        VALUES (:inv, 'Test', :own, 'LOW', 'OPERATIVE', 1, 100, 0, 10, 10, 0, 0)");
        $stmt->execute([':inv' => 'TEST-' . md5($t['ownership'] . 'x'), ':own' => $t['ownership']]);
        $pdo->rollBack();
        echo "✅ OK    ownership='" . $t['ownership'] . "'\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "❌ FAIL  ownership='" . $t['ownership'] . "' → " . $e->getMessage() . "\n";
    }
}

// 3. Check how ExcelProvider maps the ownership column
echo "\n=== WHAT DOES THE EXCEL HAVE IN OWNERSHIP COL? ===\n";

// Quick XLSX parse to check what values are in the ownership-like column
function parseXlsxQuick($fp)
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
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $rows = [];
    if ($sheetData) {
        $xml = new SimpleXMLElement($sheetData);
        foreach ($xml->sheetData->row as $row) {
            $cur = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                if ((string)$cell['t'] === 's') $val = $ss[(int)$val] ?? '';
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

$fp = 'C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx';
$rows = parseXlsxQuick($fp);
$header = array_shift($rows);

// Col 12 is "PROPIO / ARRIENDO / COMODATO" based on diagnostic
$ownershipCol = 12;
echo "Ownership column header: " . ($header[$ownershipCol] ?? 'N/A') . "\n";
$ownershipValues = [];
foreach ($rows as $r) {
    $v = trim($r[$ownershipCol] ?? '');
    $ownershipValues[$v] = ($ownershipValues[$v] ?? 0) + 1;
}
arsort($ownershipValues);
echo "Distinct values and counts:\n";
foreach ($ownershipValues as $v => $c) {
    echo "  '$v' → $c rows\n";
}

// Now test with the actual values from the Excel
echo "\n=== TESTING ACTUAL EXCEL VALUES ===\n";
foreach (array_keys($ownershipValues) as $v) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO assets (inventory_id, name, ownership, criticality, status, en_uso, useful_life_pct, under_maintenance_plan, total_useful_life, years_remaining, acquisition_cost, annual_maint_cost)
        VALUES (:inv, 'Test', :own, 'LOW', 'OPERATIVE', 1, 100, 0, 10, 10, 0, 0)");
        $stmt->execute([':inv' => 'TEST-' . md5($v), ':own' => $v]);
        $pdo->rollBack();
        echo "✅ '$v' → ACCEPTED\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "❌ '$v' → REJECTED: " . $e->getMessage() . "\n";
    }
}
