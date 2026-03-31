<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$count = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
echo "TOTAL_ASSETS:" . $count . "\n";
