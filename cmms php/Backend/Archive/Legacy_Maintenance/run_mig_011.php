<?php
// run_mig_011.php
require 'config.php';
$db = Backend\Core\DatabaseService::getInstance();
try {
    $sql = file_get_contents(__DIR__ . '/Backend/Database/migrations/011_secure_asset_retirement.sql');
    $db->exec($sql);
    echo "MIGRACIÓN 011 EXITOSA\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
