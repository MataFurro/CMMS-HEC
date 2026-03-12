<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

$db = \Backend\Core\DatabaseService::getInstance();

// Delete test assets
$deleted = $db->exec("DELETE FROM assets WHERE name LIKE '%Prueba%' OR serial_number = 'N12-TEST-777'");
echo "Test assets deleted: " . $deleted . "\n";
