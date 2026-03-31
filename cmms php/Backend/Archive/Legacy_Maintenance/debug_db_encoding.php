<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

$db = Backend\Core\DatabaseService::getInstance();
$assets = $db->query("SELECT id, name FROM assets LIMIT 30")->fetchAll();

echo "ASSETS NAMES DUMP (HEX analysis):\n";
foreach ($assets as $a) {
    echo "ID: " . $a['id'] . " | NAME: " . $a['name'] . " | HEX: " . bin2hex($a['name'] ?? '') . "\n";
}
