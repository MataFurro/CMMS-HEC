<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = Backend\Core\DatabaseService::getInstance();
    $stmt = $db->query("DESCRIBE work_orders");
    echo "COLUMNAS DE WORK_ORDERS:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
