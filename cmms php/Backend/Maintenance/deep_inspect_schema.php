<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

function checkTable($db, $tableName)
{
    echo "\n--- ESTRUCTURA DE LA TABLA $tableName ---\n";
    $cols = $db->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if (strpos(strtolower($c['Field']), 'asset') !== false) {
            echo "(!) Field: {$c['Field']} | Type: {$c['Type']} | Key: {$c['Key']}\n";
        } else {
            echo "Field: {$c['Field']} | Type: {$c['Type']} | Key: {$c['Key']}\n";
        }
    }
}

checkTable($db, 'service_requests');
checkTable($db, 'checklist_results');
checkTable($db, 'messenger_reports');
echo "------------------------------------------\n";
