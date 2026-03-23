<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

$db = Backend\Core\DatabaseService::getInstance();

echo "--- VERIFICACIÓN DE SINCRONIZACIÓN (ASSET STATUS) ---\n";

$sql = "SELECT a.id, a.inventory_id, a.name, a.status as asset_status, wo.id as ot_id, wo.status as ot_status
        FROM assets a
        JOIN work_orders wo ON a.id = wo.asset_id
        WHERE wo.status IN ('En Curso', 'En Espera')
        ORDER BY a.updated_at DESC
        LIMIT 10";

$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "Asset [{$row['id']}] {$row['inventory_id']}: Status={$row['asset_status']} | OT: {$row['ot_id']} Status={$row['ot_status']}\n";
}

if (empty($results)) {
    echo "No se encontraron activos con OTs activas.\n";
}
