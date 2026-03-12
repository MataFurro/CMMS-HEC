<?php
// test_gen_search.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

$_SESSION['user_id'] = 1;

$query = 'GEN';

try {
    $db = \Backend\Core\DatabaseService::getInstance();
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
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
