<?php
require_once 'Backend/Providers/BulkProvider.php';
require_once 'Backend/Core/DatabaseService.php';

$search = '';
$crit = '';
$status = 'OPERATIVE';
$clase = '';

$assets = getBulkAssets($search, $crit, $status, $clase);
echo "Total found for OPERATIVE: " . count($assets) . "\n";
print_r(array_slice($assets, 0, 2));

$all = countBulkAssets('', '', '', '');
echo "Total assets in DB: " . $all . "\n";

$statusCounts = \Backend\Core\DatabaseService::getInstance()->query("SELECT status, COUNT(*) FROM assets GROUP BY status")->fetchAll();
print_r($statusCounts);
