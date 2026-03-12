<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

echo "--- ESTRUCTURA DE LA TABLA work_orders ---\n";
$cols = $db->query("DESCRIBE work_orders")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "Field: {$c['Field']} | Type: {$c['Type']} | Null: {$c['Null']} | Key: {$c['Key']}\n";
}

echo "\n--- ESTRUCTURA DE LA TABLA assets ---\n";
$cols = $db->query("DESCRIBE assets")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "Field: {$c['Field']} | Type: {$c['Type']} | Null: {$c['Null']} | Key: {$c['Key']}\n";
}
echo "------------------------------------------\n";
