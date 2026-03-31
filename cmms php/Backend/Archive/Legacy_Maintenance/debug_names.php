<?php
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    $rows = $db->query("SELECT name, inventory_id, serial_number FROM assets LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
