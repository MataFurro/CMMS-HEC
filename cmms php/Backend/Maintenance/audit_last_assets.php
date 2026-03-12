<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

echo "--- AUDITORÍA DE ÚLTIMOS EQUIPOS CREADOS ---\n";
$sql = "SELECT id, name, inventory_id, hec_id, status, created_at FROM assets ORDER BY id DESC LIMIT 10";
$results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Status: {$r['status']} | Created: {$r['created_at']}\n";
}

$total = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
echo "\nTOTAL REAL EN DB: $total\n";
echo "-------------------------------------------\n";
