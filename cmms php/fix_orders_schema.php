<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = Backend\Core\DatabaseService::getInstance();
    echo "Limpiando y corrigiendo tabla work_orders...\n";

    // 1. Eliminar registros con ID vacío o inválido que bloquean el cambio
    $db->exec("DELETE FROM work_orders WHERE id = '' OR id = 0 OR id IS NULL");

    // 2. Intentar poner el ID como AUTO_INCREMENT
    // Primero nos aseguramos que sea INT y PK (si ya es PK, solo modify)
    $db->exec("ALTER TABLE work_orders MODIFY id INT NOT NULL AUTO_INCREMENT");

    echo "Esquema de work_orders normalizado.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
