<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
$db = Backend\Core\DatabaseService::getInstance();
$stmt = $db->query("DESCRIBE assets");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columns in assets table:\n";
foreach ($rows as $r) {
    echo " - " . $r['Field'] . " (" . $r['Type'] . ")\n";
}
