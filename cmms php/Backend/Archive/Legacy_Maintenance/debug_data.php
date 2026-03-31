<?php
require 'config.php';
require 'Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

echo "--- DATA AUDIT ---" . PHP_EOL;

$totalAssets = $db->query('SELECT COUNT(*) FROM assets')->fetchColumn();
echo "Assets: $totalAssets" . PHP_EOL;

$totalOT = $db->query('SELECT COUNT(*) FROM work_orders')->fetchColumn();
echo "Total OTs: $totalOT" . PHP_EOL;

$termCorr = $db->query("SELECT COUNT(*) FROM work_orders WHERE status = 'Terminada' AND type = 'Correctiva'")->fetchColumn();
echo "OTs Term/Corr (Downtime Source): $termCorr" . PHP_EOL;

$locations = $db->query("SELECT DISTINCT location FROM assets")->fetchAll(PDO::FETCH_COLUMN);
echo "Locations found: " . implode(', ', $locations) . PHP_EOL;

$riesgo_ge = $db->query("SELECT DISTINCT riesgo_ge FROM assets")->fetchAll(PDO::FETCH_COLUMN);
echo "riesgo_ge (Families) found: " . implode(', ', $riesgo_ge) . PHP_EOL;
