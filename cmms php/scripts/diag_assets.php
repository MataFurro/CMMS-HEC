<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

$db = Backend\Core\DatabaseService::getInstance();

echo "--- CONTEO DE ACTIVOS POR ORIGEN ---\n";

$q1 = $db->query("SELECT COUNT(*) FROM assets WHERE observations LIKE '%[BIO-V2]%'");
echo "Activos [BIO-V2]: " . $q1->fetchColumn() . "\n";

$q2 = $db->query("SELECT COUNT(*) FROM assets WHERE observations LIKE '%[DATOS DE SIMULACIÓN]%'");
echo "Activos [SIM v1]: " . $q2->fetchColumn() . "\n";

$q3 = $db->query("SELECT id, inventory_id, name, observations FROM assets WHERE id = 1");
$row = $q3->fetch(PDO::FETCH_ASSOC);
echo "Asset ID 1 Detail: " . json_encode($row) . "\n";

$q4 = $db->query("SELECT COUNT(*) FROM assets");
echo "Total Assets: " . $q4->fetchColumn() . "\n";
