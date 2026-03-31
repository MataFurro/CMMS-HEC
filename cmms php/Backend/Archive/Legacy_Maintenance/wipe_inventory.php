<?php
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';

// SECURITY: Only Chief Engineer can clear the system
if (!isChiefEngineer()) {
    die("ERROR: Unauthorized. Only Chief Engineer can perform this action.\n");
}

require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("TRUNCATE TABLE work_orders;");
    $db->exec("TRUNCATE TABLE assets;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "SUCCESS: All inventory and OT history has been wiped.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
