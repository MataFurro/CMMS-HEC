<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();

    $tables = ['assets', 'work_orders', 'checklist_results', 'work_order_logs', 'ot_attachments'];

    echo "--- Database Cleanup Check ---\n";
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Table '$table': $count records\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
