<?php
// simulate_scenario.php

require_once __DIR__ . '/Backend/Core/DatabaseService.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    // 1. Validar el equipo objetivo
    $stmt = $db->query("SELECT id, name FROM assets WHERE name LIKE '%Ventilador%' LIMIT 1");
    $asset = $stmt->fetch();

    if (!$asset) {
        // Fallback: Si no hay ventiladores, toma el primer equipo que encuentre
        $stmt = $db->query("SELECT id, name FROM assets LIMIT 1");
        $asset = $stmt->fetch();
    }

    if (!$asset) {
        die("Error: No hay ningún equipo en la Base de Datos para simular. ¡Puebla la BD primero!");
    }

    $assetId = $asset['id'];
    $assetName = $asset['name'];
    $techId = 4; // Téc. Mario

    echo "<h3>Simulando Escenario de Vida Útil para: $assetName (ID: $assetId)</h3>";

    // --- OT 1: Primera Preventiva (Hace un año) ---
    $ot1Id = 'OT-SIM-001';
    $db->prepare("INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, checklist_template) 
                  VALUES (?, ?, 'Preventiva', 'Terminada', ?, '2025-06-01', '2025-06-02', 'Media', 'ventilador_mecanico')
                  ON DUPLICATE KEY UPDATE status='Terminada'")
        ->execute([$ot1Id, $assetId, $techId]);

    $cCheck1 = json_encode(['qualitative' => ['q_0' => 'pass', 'q_1' => 'pass']]);
    $db->prepare("INSERT INTO checklist_results (work_order_id, asset_id, template_key, qualitative_results, completed_at, completed_by)
                  VALUES (?, ?, 'ventilador_mecanico', ?, '2025-06-02 12:00:00', ?)")
        ->execute([$ot1Id, $assetId, $cCheck1, $techId]);
    echo "✓ Creada OT-SIM-001 (Preventiva 1)<br>";

    // --- OT 2: Segunda Preventiva (Hace 6 meses) ---
    $ot2Id = 'OT-SIM-002';
    $db->prepare("INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, checklist_template) 
                  VALUES (?, ?, 'Preventiva', 'Terminada', ?, '2025-12-01', '2025-12-02', 'Media', 'ventilador_mecanico')
                  ON DUPLICATE KEY UPDATE status='Terminada'")
        ->execute([$ot2Id, $assetId, $techId]);

    $cCheck2 = json_encode(['qualitative' => ['q_0' => 'pass', 'q_1' => 'pass']]);
    $db->prepare("INSERT INTO checklist_results (work_order_id, asset_id, template_key, qualitative_results, completed_at, completed_by)
                  VALUES (?, ?, 'ventilador_mecanico', ?, '2025-12-02 14:00:00', ?)")
        ->execute([$ot2Id, $assetId, $cCheck2, $techId]);
    echo "✓ Creada OT-SIM-002 (Preventiva 2)<br>";

    // --- OT 3: Preventiva que se convierte a Correctiva ---
    // En la base de datos queda como 'Correctiva' finalmente, pero dejamos rastro en audit_trail
    $ot3Id = 'OT-SIM-003';

    // Lo guardamos como Correctiva terminada
    $db->prepare("INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, checklist_template, observations) 
                  VALUES (?, ?, 'Correctiva', 'Terminada', ?, '2026-06-01', '2026-06-05', 'Alta', 'ventilador_mecanico', 'Durante la revisión preventiva programada, se detectó fallo crítico en la válvula de exhalación. Se modificó la OT de Preventiva a Correctiva de urgencia para cambio de repuesto.')
                  ON DUPLICATE KEY UPDATE type='Correctiva', status='Terminada'")
        ->execute([$ot3Id, $assetId, $techId]);

    // Rastro de auditoría de que cambió (para cumplir con FDA 21 CFR Part 11)
    $auditDetails = json_encode(['old_type' => 'Preventiva', 'new_type' => 'Correctiva', 'reason' => 'Fallo detectado durante inspección visual']);
    $db->prepare("INSERT INTO audit_trail (user_id, action, asset_id, target_type, details, timestamp) 
                  VALUES (?, 'CHANGE_OT_TYPE', ?, 'work_order', ?, '2026-06-01 10:30:00')")
        ->execute([$techId, $assetId, $auditDetails]);

    // Resultados de esta OT
    $cCheck3 = json_encode(['qualitative' => ['q_0' => 'pass', 'q_1' => 'fail', 'q_custom_label_abc' => 'Prueba de software', 'q_custom_val_abc' => 'pass']]);
    $db->prepare("INSERT INTO checklist_results (work_order_id, asset_id, template_key, qualitative_results, completed_at, completed_by)
                  VALUES (?, ?, 'ventilador_mecanico', ?, '2026-06-05 16:00:00', ?)")
        ->execute([$ot3Id, $assetId, $cCheck3, $techId]);
    echo "✓ Creada OT-SIM-003 (Entró como Preventiva, cambió a Correctiva y Terminada)<br>";

    $db->commit();
    echo "<br><b style='color:green;'>Simulación inyectada correctamente en la BD.</b><br>";
    echo "<a href='index.php?page=asset&id=PB-840-00122'>Ir a ver el Historial del Equipo (Ventilador)</a>";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error en la simulación: " . $e->getMessage();
}
