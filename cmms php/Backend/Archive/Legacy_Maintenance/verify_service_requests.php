<?php
// Script to simulate end-to-end Service Request to OT Feedback Loop

require_once __DIR__ . '/API Mail/bridge.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

$db = \Backend\Core\DatabaseService::getInstance();
echo "--- TEST 1: SIMULATE INCOMING EMAIL ---\n";
// 1. Simulate Incoming Email
$trackingId = saveReport('soporte@hec.cl', 'Falla en Monitor', [
    'email_solicitante' => 'dr.smith@hospital.cl',
    'nombre_equipo' => 'Monitor de Signos Vitales',
    'serie_equipo' => 'VM-9002',
    'descripcion' => 'La pantalla parpadea y se apaga.',
    'path_imagen' => null
]);

echo "Created Service Request: " . $trackingId . "\n";

// Verify it exists in DB
$stmt = $db->prepare("SELECT * FROM service_requests WHERE id = :id");
$stmt->execute([':id' => $trackingId]);
$sr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sr || $sr['requester_email'] !== 'dr.smith@hospital.cl') {
    die("FAILED: Service Request not found or email mismatch.\n");
}
echo "SUCCESS: Service Request inserted successfully.\n\n";


echo "--- TEST 2: CONVERT TO OT ---\n";
// 2. Convert to OT
createWorkOrderFromRequest([
    'asset_id'      => 'S/N',
    'asset_name'    => 'Monitor de Signos Vitales Serie: VM-9002',
    'problem'       => 'La pantalla parpadea y se apaga.',
    'priority'      => 'Alta',
    'ms_email'      => 'dr.smith@hospital.cl',
    'ms_request_id' => $trackingId
]);

// Need to find the OT created
$stmtOT = $db->prepare("SELECT id FROM work_orders WHERE ms_request_id = :ms_id");
$stmtOT->execute([':ms_id' => $trackingId]);
$ot = $stmtOT->fetch(PDO::FETCH_ASSOC);

if (!$ot) {
    die("FAILED: Work Order not created or not linked.\n");
}
$otId = $ot['id'];
echo "SUCCESS: Converted to OT: " . $otId . "\n\n";


echo "--- TEST 3: COMPLETE OT AND TRIGGER FEEDBACK LOOP ---\n";
// 3. Mark OT as Completed
// Mock Execution Data
$executionData = [
    'time_spent' => 2,
    'total_cost' => 50000,
    'final_asset_status' => 'OPERATIVE',
    'parts_used' => [],
    'closing_remarks' => 'Pantalla reemplazada, equipo operativo.'
];

completeWorkOrder($otId, $executionData);

// Verify SR status is 'Finalizada'
$stmtFinal = $db->prepare("SELECT status FROM service_requests WHERE id = :id");
$stmtFinal->execute([':id' => $trackingId]);
$srFinal = $stmtFinal->fetch(PDO::FETCH_ASSOC);

if ($srFinal['status'] !== 'Finalizada') {
    die("FAILED: Service Request status is not 'Finalizada', it is '{$srFinal['status']}'.\n");
}
echo "SUCCESS: Service Request marked as Finalizada.\n";

// Verify Audit Log for the email feedback
$stmtAudit = $db->prepare("SELECT details FROM audit_trail WHERE action = 'FEEDBACK_EMAIL_SENT' AND entity_id = :id ORDER BY created_at DESC LIMIT 1");
$stmtAudit->execute([':id' => $trackingId]);
$audit = $stmtAudit->fetch(PDO::FETCH_ASSOC);

if (!$audit) {
    die("FAILED: Audit log 'FEEDBACK_EMAIL_SENT' not found.\n");
}

echo "SUCCESS: Simulated Email Audit Log found:\n";
echo print_r(json_decode($audit['details'], true), true) . "\n";
echo "\n--- ALL TESTS PASSED ---\n";

// Cleanup Test Data
$db->exec("DELETE FROM checklist_data WHERE work_order_id = '$otId'");
$db->exec("DELETE FROM ot_attachments WHERE ot_id = '$otId'");
$db->exec("DELETE FROM work_orders WHERE id = '$otId'");
$db->exec("DELETE FROM service_requests WHERE id = '$trackingId'");
$db->exec("DELETE FROM audit_trail WHERE entity_id = '$trackingId'");
echo "Cleaned up test data.\n";
