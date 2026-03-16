<?php
/**
 * bio_cmms_verify_unification.php
 * Verificador del Flujo 7: Unificación de OTs
 */

require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

function verifyUnification() {
    echo "=== Iniciando Verificación Flujo 7 (Unificación) ===\n";
    $db = \Backend\Core\DatabaseService::getInstance();

    // 1. Preparar datos de prueba
    $assetIdNum = 999999; // ID Numérico para evitar errores
    $correctiveId = 'OT-CORR-UNIFY'; // La tabla work_orders usa varchar para ID
    $preventiveId = 'OT-PREV-UNIFY';

    echo "1. Limpiando datos antiguos...\n";
    $db->prepare("DELETE FROM work_orders WHERE asset_id = :id")->execute([':id' => $assetIdNum]);
    $db->prepare("DELETE FROM assets WHERE id = :id")->execute([':id' => $assetIdNum]);

    echo "2. Creando activo y OTs...\n";
    $db->prepare("INSERT INTO assets (id, name, status, created_at) VALUES (:id, 'Equipo Unificación', 'OPERATIVE', NOW())")
       ->execute([':id' => $assetIdNum]);

    // Crear Correctiva
    $db->prepare("INSERT INTO work_orders (id, asset_id, type, status, created_date, created_at) VALUES (:id, :aid, 'Correctiva', 'En Curso', CURRENT_DATE, NOW())")
       ->execute([':id' => $correctiveId, ':aid' => $assetIdNum]);

    // Crear Preventiva
    $db->prepare("INSERT INTO work_orders (id, asset_id, type, status, created_date, created_at) VALUES (:id, :aid, 'Preventiva', 'En Curso', CURRENT_DATE, NOW())")
       ->execute([':id' => $preventiveId, ':aid' => $assetIdNum]);

    echo "3. Verificando detección de preventivas mediante backend...\n";
    $pendings = getPendingPreventivesForAsset($assetIdNum);
    $found = false;
    foreach($pendings as $p) {
        if($p['id'] === $preventiveId) $found = true;
    }

    if(!$found) {
        echo "❌ Error: No se detectó la OT preventiva pendiente.\n";
        return;
    }
    echo "✅ OT preventiva detectada correctamente.\n";

    echo "4. Ejecutando completeWorkOrder con unificación...\n";
    $executionData = [
        'final_asset_status' => 'OPERATIVE',
        'duration_hours' => 2,
        'observations' => 'Reparación correctiva exitosa.'
    ];

    $success = completeWorkOrder($correctiveId, $executionData, [$preventiveId]);

    if($success) {
        echo "✅ Función completeWorkOrder retornó éxito.\n";
    } else {
        echo "❌ Error: completeWorkOrder falló.\n";
        return;
    }

    echo "5. Validando estados transformados...\n";
    $stmtCorr = $db->prepare("SELECT status, observations FROM work_orders WHERE id = :id");
    $stmtCorr->execute([':id' => $correctiveId]);
    $corrStatus = $stmtCorr->fetch(PDO::FETCH_ASSOC);

    $stmtPrev = $db->prepare("SELECT status, observations FROM work_orders WHERE id = :id");
    $stmtPrev->execute([':id' => $preventiveId]);
    $prevStatus = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    if($corrStatus['status'] === 'Terminada') {
        echo "✅ OT Correctiva: TERMINADA.\n";
    } else {
        echo "❌ OT Correctiva: Status incorrecto (" . $corrStatus['status'] . ").\n";
    }

    if($prevStatus['status'] === 'Terminada') {
        echo "✅ OT Preventiva: TERMINADA.\n";
        if(strpos($prevStatus['observations'], '[UNIFICACIÓN]') !== false) {
             echo "✅ OT Preventiva: Marcada con etiqueta [UNIFICACIÓN].\n";
        } else {
             echo "❌ OT Preventiva: No tiene etiqueta de unificación.\n";
        }
    } else {
        echo "❌ OT Preventiva: Status incorrecto (" . $prevStatus['status'] . ").\n";
    }

    // Verificar actualización de fecha preventivo en activo (Flujo 2 integrado)
    $stmtAsset = $db->prepare("SELECT next_maintenance_date FROM assets WHERE id = :id");
    $stmtAsset->execute([':id' => $assetIdNum]);
    $assetData = $stmtAsset->fetch(PDO::FETCH_ASSOC);

    if($assetData['next_maintenance_date']) {
        echo "✅ Activo: Próxima fecha mantenimiento actualizada (" . $assetData['next_maintenance_date'] . ").\n";
    } else {
        echo "❌ Activo: Próxima fecha mantenimiento NO actualizada.\n";
    }

    echo "=== Verificación Finalizada ===\n";
}

verifyUnification();
