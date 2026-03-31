<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
$db = Backend\Core\DatabaseService::getInstance();
$rows = $db->query("SELECT name FROM assets WHERE name LIKE '%?%' LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
echo "Samples of corrupted names:\n";
echo implode("\n", $rows) . "\n";
