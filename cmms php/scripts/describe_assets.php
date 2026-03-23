<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

$db = Backend\Core\DatabaseService::getInstance();

echo "--- ESTRUCTURA DE TABLA ASSETS ---\n";

$stmt = $db->query("DESCRIBE assets");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo json_encode($row) . "\n";
}
