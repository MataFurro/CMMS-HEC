<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    $search = '%MV2000%';
    $stmt = $db->prepare("SELECT * FROM assets WHERE serial_number LIKE ? OR inventory_id LIKE ? OR name LIKE ? OR brand LIKE ?");
    $stmt->execute([$search, $search, $search, $search]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- RESULTADOS DE BÚSQUEDA EXHAUSTIVA PARA 'MV2000' ---\n";
    if (empty($results)) {
        echo "No se encontró ningún registro que contenga 'MV2000' en los campos principales.\n";
    } else {
        foreach ($results as $r) {
            echo "ID: {$r['id']} | Name: {$r['name']} | INV: {$r['inventory_id']} | S/N: {$r['serial_number']} | HEC: {$r['hec_id']}\n";
        }
    }

    // Contar duplicados potenciales de INV ID
    $stmt = $db->query("SELECT inventory_id, COUNT(*) as qty FROM assets GROUP BY inventory_id HAVING qty > 1");
    $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n--- DUPLICADOS DE N° INVENTARIO ---\n";
    echo "Total grupos duplicados: " . count($dupes) . "\n";
    if (!empty($dupes)) {
        print_r(array_slice($dupes, 0, 5));
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
