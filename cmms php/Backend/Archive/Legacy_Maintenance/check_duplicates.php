<?php
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

$db = DatabaseService::getInstance();

// Check for duplicates in DB based on inventory_id + serial_number
$stmt = $db->query("
    SELECT inventory_id, serial_number, name, COUNT(*) as count 
    FROM assets 
    GROUP BY inventory_id, serial_number, name 
    HAVING count > 1
");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total duplicate groups found: " . count($duplicates) . "\n";
foreach ($duplicates as $d) {
    echo "Duplicate: {$d['name']} | {$d['inventory_id']} | {$d['serial_number']} ({$d['count']} times)\n";
}

// Check how many assets are missing an ID
$stmt = $db->query("SELECT COUNT(*) FROM assets WHERE id IS NULL OR id = ''");
$missingId = $stmt->fetchColumn();
echo "Assets missing ID: $missingId\n";

// Show a sample of assets
$stmt = $db->query("SELECT id, inventory_id, serial_number, name FROM assets LIMIT 10");
$sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Sample Assets:\n";
print_r($sample);
