<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=biocmms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if ($table !== 'users') { // Don't truncate users
            $db->exec("TRUNCATE TABLE `$table`");
            echo "Truncated table: $table\n";
        }
    }

    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Database successfully cleaned! Users kept intact.\n";
} catch (Exception $e) {
    echo "Error cleaning database: " . $e->getMessage() . "\n";
}
