<?php
/**
 * scripts/populate_dashboard_v4.php
 * Generates historical Work Orders for all assets to fill dashboards.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Repositories/AssetRepository.php';
require_once __DIR__ . '/../Backend/Repositories/WorkOrderRepository.php';

use Backend\Core\DatabaseService;

$db = DatabaseService::getInstance();
echo "--- BioCMMS Dashboard Populator v4.0 ---\n";

// 1. Get all assets
$stmt = $db->query("SELECT id, name, location FROM assets WHERE en_uso = 1");
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($assets);

echo "Poblando historial para $total activos...\n";

$db->beginTransaction();

try {
    // Clear old OTs to avoid duplicates during re-import
    $db->exec("DELETE FROM work_orders");
    $db->exec("DELETE FROM checklist_results");

    $count = 0;
    foreach ($assets as $asset) {
        // Generate 1-2 historical OTs per asset
        $numOts = rand(1, 2);
        for ($i = 0; $i < $numOts; $i++) {
            $status = ($i === 0) ? 'CLOSED' : 'PROGRESS';
            if ($asset['id'] % 50 === 0) $status = 'OPEN';
            
            $type = ($asset['id'] % 2 === 0) ? 'PREVENTIVE' : 'CORRECTIVE';
            $date = date('Y-m-d', strtotime("-" . rand(1, 180) . " days"));
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            
            $stmt = $db->prepare("INSERT INTO work_orders 
                (id, asset_id, type, status, priority, observations, created_date, assigned_tech_id, final_asset_status) 
                VALUES (:id, :asset_id, :type, :status, :priority, :desc, :date, :tech, :diag)");
            
            $stmt->execute([
                ':id' => $uuid,
                ':asset_id' => $asset['id'],
                ':type' => $type,
                ':status' => $status,
                ':priority' => (rand(1,3) === 1 ? 'Alta' : 'Media'),
                ':desc' => "Mantenimiento " . ($type === 'PREVENTIVE' ? "Preventivo Anual" : "Correctivo por Falla"),
                ':date' => $date,
                ':tech' => 1,
                ':diag' => "Se realiza inspección técnica y pruebas de funcionamiento. Equipo operativo."
            ]);
            $count++;
        }
    }
    $db->commit();
    echo "Se generaron $count Órdenes de Trabajo histórico.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
