<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
$db = Backend\Core\DatabaseService::getInstance();
$count = $db->query("SELECT COUNT(*) FROM assets WHERE name LIKE '%?%'")->fetchColumn();
echo "Remaining '?' in names: $count\n";
