<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

$testIds = [3110, 3111, 3112];
$assetToKeep = 3113;

echo "--- LIMPIEZA QUIRÚRGICA DE DEMO ---\n";

foreach ($testIds as $id) {
    echo "Procesando ID: $id...\n";

    // 1. Eliminar de checklist_results
    $db->prepare("DELETE FROM checklist_results WHERE asset_id = ?")->execute([$id]);

    // 2. Eliminar de work_orders
    $db->prepare("DELETE FROM work_orders WHERE asset_id = ?")->execute([$id]);

    // 3. Eliminar de service_requests
    $db->prepare("DELETE FROM service_requests WHERE asset_id = ?")->execute([$id]);

    // 4. Eliminar de messenger_reports
    $db->prepare("DELETE FROM messenger_reports WHERE asset_id = ?")->execute([$id]);

    // 5. Finalmente eliminar el activo
    $db->prepare("DELETE FROM assets WHERE id = ?")->execute([$id]);

    echo "✅ ID $id y sus dependencias eliminadas.\n";
}

// Asegurar que el 3113 esté activo y bien configurado
$db->prepare("UPDATE assets SET status = 'OPERATIVE', en_uso = 1, retirement_reason = NULL, retirement_requested_at = NULL WHERE id = ?")->execute([$assetToKeep]);
echo "✅ Equipo ID $assetToKeep (Demo) restaurado completamente a OPERATIVO.\n";

$total = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
echo "\nNUEVO TOTAL EN DB: $total (Esperado: 3110)\n";
echo "-------------------------------------\n";
