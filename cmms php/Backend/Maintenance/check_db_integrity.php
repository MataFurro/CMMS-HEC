<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();

    echo "--- CHECKING WORK_ORDER IDs ---\n";
    $stmt = $db->query("SELECT id, asset_id FROM work_orders LIMIT 50");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: [{$row['id']}], asset_id: [{$row['asset_id']}]\n";
    }

    echo "\n--- CHECKING FOR NON-OT PREFIXED IDs IN WORK_ORDERS ---\n";
    $stmt = $db->query("SELECT COUNT(*) FROM work_orders WHERE id NOT LIKE 'OT-%'");
    echo "Non-OT count: " . $stmt->fetchColumn() . "\n";
    if ($stmt->fetchColumn() > 0) {
        $stmt = $db->query("SELECT id FROM work_orders WHERE id NOT LIKE 'OT-%' LIMIT 10");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Non-OT ID: [{$row['id']}]\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
