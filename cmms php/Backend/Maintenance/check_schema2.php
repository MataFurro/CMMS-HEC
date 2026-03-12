<?php
// Check EXACT live schema and try a test insert to find column issues
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== ACTUAL LIVE TABLE: assets ===\n";
$cols = $pdo->query('SHOW FULL COLUMNS FROM assets')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    printf(
        "%-30s %-30s %-5s %-5s %s\n",
        $c['Field'],
        $c['Type'],
        $c['Key'],
        $c['Null'],
        $c['Default'] ?? 'NULL'
    );
}

$count = $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
echo "\nCurrent row count: $count\n";

echo "\n=== CHECKING FOR INVENTORY_ID COLUMN ===\n";
$hasInvId = false;
foreach ($cols as $c) {
    if ($c['Field'] === 'inventory_id') {
        $hasInvId = true;
        echo "inventory_id EXISTS: type={$c['Type']}\n";
    }
}
if (!$hasInvId) echo "inventory_id DOES NOT EXIST in live table!\n";

echo "\n=== SAMPLE ROWS ===\n";
$rows = $pdo->query('SELECT * FROM assets LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode(array_intersect_key($r, array_flip(['id', 'inventory_id', 'name', 'serial_number', 'location']))) . "\n";
}

// Check what the create() method actually has vs the live schema
echo "\n=== STATUS CHECK ===\n";
$cols2 = $pdo->query('SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA="biocmms" AND TABLE_NAME="assets"')->fetchColumn();
echo "DB columns: $cols2\n";

// Try a test insert with a dummy record
echo "\n=== TEST INSERT (will be rolled back) ===\n";
try {
    $pdo->beginTransaction();
    $sql = "INSERT INTO assets (inventory_id, name, serial_number, brand, model, location, sub_location, vendor, contract_id, ownership, criticality, status, riesgo_ge, fecha_instalacion, purchased_year, acquisition_cost, total_useful_life, useful_life_pct, years_remaining, under_maintenance_plan, en_uso, image_url, observations, annual_maint_cost)
    VALUES ('TEST-001', 'Test Equipment', 'SN-TEST', 'TestBrand', 'TestModel', 'Test Location', 'Sub', null, null, 'PROPIO', 'LOW', 'OPERATIVE', null, null, 2020, 0, 10, 100, 10, 0, 1, null, null, 0)";
    $pdo->exec($sql);
    $pdo->rollBack();
    echo "INSERT OK - column names match!\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "INSERT FAILED: " . $e->getMessage() . "\n";
}

// Test what happens with a 'status' value from Excel like 'BUENO'
echo "\n=== TEST STATUS ENUM ===\n";
try {
    $pdo->beginTransaction();
    $sql = "INSERT INTO assets (inventory_id, name, ownership, criticality, status, en_uso, useful_life_pct, under_maintenance_plan, total_useful_life, years_remaining, acquisition_cost, annual_maint_cost)
    VALUES ('TEST-002', 'Test2', 'PROPIO', 'LOW', 'BUENO', 1, 100, 0, 10, 10, 0, 0)";
    $pdo->exec($sql);
    $pdo->rollBack();
    echo "BUENO status INSERT OK\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "BUENO status INSERT FAILED: " . $e->getMessage() . "\n";
}
