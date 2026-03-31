<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $db->exec("USE biocmms");
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "--- All Tables in biocmms ---\n";
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "Table: $table -> Count: $count\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
