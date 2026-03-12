<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$sql = "SELECT id, name, hec_id, status, en_uso FROM assets WHERE en_uso = 0 OR status IN ('PENDING_RETIREMENT', 'RETIRED') ORDER BY updated_at DESC";
$results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "--- EQUIPOS EN EL CEMENTERIO ---\n";
foreach ($results as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | HEC: {$r['hec_id']} | Status: {$r['status']} | En Uso: {$r['en_uso']}\n";
}
echo "-------------------------------\n";
