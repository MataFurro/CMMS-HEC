<?php

/**
 * Script to force clear the session mock data and test DB connection
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/database/Database.php';

// Clear session caches
unset($_SESSION['MOCK_ASSETS_PERSIST']);
unset($_SESSION['MOCK_WORK_ORDERS_PERSIST']);

echo "Cache de sesión limpiada.\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "Conexión a la base de datos EXITOSA.\n";

    $stmt = $db->query("SELECT COUNT(*) FROM assets");
    $count = $stmt->fetchColumn();
    echo "Número de activos en la base de datos: $count\n";

    if ($count == 0) {
        echo "ADVERTENCIA: La base de datos está vacía. Asegúrate de haber importado backend/database/schema.sql\n";
    }
} catch (Exception $e) {
    echo "ERROR de conexión: " . $e->getMessage() . "\n";
}
