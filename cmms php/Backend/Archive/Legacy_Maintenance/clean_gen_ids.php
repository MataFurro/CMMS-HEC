<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

$db = \Backend\Core\DatabaseService::getInstance();

try {
    $updated = $db->exec("UPDATE assets SET inventory_id = 'S/N' WHERE inventory_id LIKE 'GEN-%'");
    echo "Successfully updated $updated assets, removing the GEN- codes and restoring them to S/N.\n";
} catch (PDOException $e) {
    echo "Error updating assets: " . $e->getMessage() . "\n";
}
