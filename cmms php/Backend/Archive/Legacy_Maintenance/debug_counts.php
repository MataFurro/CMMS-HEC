<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    echo "--- ASSET STATUS COUNTS ---\n";
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['status']}: {$row['count']}\n";
    }

    echo "\n--- ASSET CRITICALITY COUNTS ---\n";
    $stmt = $db->query("SELECT criticality, COUNT(*) as count FROM assets GROUP BY criticality");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "'{$row['criticality']}': {$row['count']}\n";
    }

    echo "\n--- WORK ORDER TYPE COUNTS ---\n";
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM work_orders GROUP BY type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['type']}: {$row['count']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
