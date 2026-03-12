<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $dbs = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dbs as $dbname) {
        if (in_array($dbname, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin'])) continue;

        echo "Database: $dbname\n";
        try {
            $db->exec("USE `$dbname` ");
            $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                if (stripos($table, 'asset') !== false || stripos($table, 'equipo') !== false) {
                    $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    echo "  - Table '$table' found: $count records\n";
                }
            }
        } catch (Exception $e) {
            echo "  - Error: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
