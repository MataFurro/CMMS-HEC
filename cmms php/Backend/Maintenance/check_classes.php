<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $classes = $db->query("SELECT name FROM asset_classes")->fetchAll(PDO::FETCH_COLUMN);
    echo "Asset Classes in DB:\n";
    foreach ($classes as $c) {
        echo "- $c\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
