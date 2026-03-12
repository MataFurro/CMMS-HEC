<?php

/**
 * api/search_assets.php
 * Endpoint para búsqueda rápida de activos (Autocomplete).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

header('Content-Type: application/json');

// Verificar sesión (iniciada en config.php)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    // Buscar por ID, Inventory ID, HEC ID, Serie o Nombre
    $sql = "SELECT id, inventory_id, hec_id, name, serial_number, location 
            FROM assets 
            WHERE en_uso = 1 
            AND (
                id LIKE :q1 OR 
                inventory_id LIKE :q2 OR 
                hec_id LIKE :q3 OR 
                serial_number LIKE :q4 OR 
                name LIKE :q5
            )
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([
        'q1' => $searchTerm,
        'q2' => $searchTerm,
        'q3' => $searchTerm,
        'q4' => $searchTerm,
        'q5' => $searchTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
