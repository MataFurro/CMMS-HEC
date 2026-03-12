<?php
// verify_point_1.php
require 'config.php';
require_once __DIR__ . '/Backend/Repositories/WorkOrderRepository.php';

use Backend\Repositories\WorkOrderRepository;

$db = Backend\Core\DatabaseService::getInstance();

// Buscar un activo real
$asset = $db->query("SELECT id FROM assets LIMIT 1")->fetch();
if (!$asset) {
    // Si no hay activos, creamos uno temporal para la prueba
    echo "Info: No hay activos, creando 'TEST-ASSET'...\n";
    $db->exec("INSERT INTO assets (id, name, status) VALUES ('TEST-ASSET', 'Equipo de Prueba', 'OPERATIVE')");
    $assetId = 'TEST-ASSET';
} else {
    $assetId = $asset['id'];
}

$repo = new WorkOrderRepository();
$testId = 'OT-TEST-' . time();

echo "1. Probando creación para activo $assetId...\n";
$repo->create([
    'id' => $testId,
    'asset_id' => $assetId,
    'type' => 'Correctiva',
    'status' => 'Pendiente',
    'priority' => 'Alta'
]);

echo "2. Probando modificación...\n";
$repo->partialUpdate($testId, [
    'observations' => 'Modificación automática de prueba Punto 1',
    'status' => 'En Proceso'
]);

echo "3. Verificando tabla audit_trail...\n";
// Buscamos en 'details' el ID de la OT
$stmt = $db->prepare("SELECT * FROM audit_trail WHERE details LIKE :id ORDER BY timestamp DESC LIMIT 2");
$stmt->execute(['id' => "%$testId%"]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($logs) >= 2) {
    echo "EXITO: Se encontraron " . count($logs) . " entradas de auditoría relacionadas con $testId.\n";
    foreach ($logs as $log) {
        echo " - Acción: " . $log['action'] . " | Timestamp: " . $log['timestamp'] . "\n";
    }
} else {
    echo "FALLO: No se registraron los logs esperados. Cantidad: " . count($logs) . "\n";
    // Debug
    $all = $db->query("SELECT * FROM audit_trail ORDER BY timestamp DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Últimos logs en DB:\n";
    foreach ($all as $a) {
        echo " - " . $a['action'] . " | " . $a['target_type'] . " | " . substr($a['details'], 0, 50) . "...\n";
    }
}
