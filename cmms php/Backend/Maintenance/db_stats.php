<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$stats = $db->query("SELECT criticality, count(*) as c FROM assets GROUP BY criticality")->fetchAll(PDO::FETCH_ASSOC);
print_r($stats);
