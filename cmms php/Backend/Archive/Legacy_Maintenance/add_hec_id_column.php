<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $db->exec("ALTER TABLE assets ADD COLUMN hec_id VARCHAR(50) UNIQUE DEFAULT NULL AFTER id;");
    echo "SUCCESS: Column 'hec_id' added successfully.\\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\\n";
}
