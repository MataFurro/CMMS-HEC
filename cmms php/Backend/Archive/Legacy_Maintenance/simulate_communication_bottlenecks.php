<?php

/**
 * simulate_communication_bottlenecks.php
 * Simulador de conflictos de cordinación y gaps de comunicación.
 * ───────────────────────────────────────────────────────────────
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/Backend/Providers/AuditProvider.php';

use Backend\Core\DatabaseService;

echo "🕵️ INICIANDO SIMULACIÓN DE CUELLOS DE BOTELLA EN COMUNICACIÓN...\n\n";

try {
    $db = DatabaseService::getInstance();

    // 1. ESCENARIO: El equipo está en uso (Conflicto de Coordinación)
    echo "🚨 ESCENARIO 1: Conflicto de disponibilidad (Equipo en uso)\n";
    $stmt = $db->query("SELECT id, name, location FROM assets WHERE criticality = 'CRITICAL' LIMIT 1");
    $criticalAsset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($criticalAsset) {
        $otId = createWorkOrder([
            'asset_id' => $criticalAsset['id'],
            'type' => 'Preventiva',
            'priority' => 'Alta',
            'observations' => 'Mantenimiento Preventivo Mensual - Crítico.'
        ]);

        // Simulamos que el técnico 'Mario' llega pero el servicio no suelta el equipo
        echo "   [!] Técnico llega a {$criticalAsset['location']}: El equipo está siendo usado en un procedimiento.\n";

        // PROBLEMA: No hay un estado "Postpuesto por Servicio"
        \Backend\Providers\logAuditAction(
            'COMMUNICATION_DELAY',
            'WORK_ORDER',
            $otId,
            "FALLO DE COORDINACIÓN: El servicio de {$criticalAsset['location']} informa que el equipo {$criticalAsset['name']} estará ocupado por las próximas 4 horas.",
            ['reason' => 'Equipment in use', 'service_contact' => 'Nurse Supervisor']
        );
        echo "   [✓] Log de auditoría grabado como retraso de comunicación.\n";
    }

    // 2. ESCENARIO: Baja de Equipo que requiere coordinación física
    echo "\n📦 ESCENARIO 2: Coordinación de Retiro Físico de Activo\n";
    $stmt = $db->query("SELECT id, name FROM assets WHERE en_uso = 1 LIMIT 1 OFFSET 3");
    $retireAsset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($retireAsset) {
        echo "   [↓] Solicitando baja de {$retireAsset['name']} ({$retireAsset['id']})...\n";

        // Simular que el ingeniero solicita la baja
        $reason = "Obsolescencia técnica y falla recurrente en placa madre. Requiere retiro físico inmediato.";

        // Actualizamos estado a PENDING_RETIREMENT (lógica del Punto 1 implementada)
        $db->prepare("UPDATE assets SET status = 'NO_OPERATIVE', observations = CONCAT(observations, '\nPENDIENTE DE RETIRO: ', ?) WHERE id = ?")
            ->execute([$reason, $retireAsset['id']]);

        \Backend\Providers\logAuditAction(
            'RETIRE_REQUEST',
            'ASSET',
            $retireAsset['id'],
            "COORDINACIÓN REQUERIDA: Se solicita al servicio de logística el retiro físico y coordinación de entrega con el proveedor externo.",
            ['retirement_reason' => $reason]
        );
        echo "   [✓] Activo marcado como No Operativo; auditoría generada para coordinar logística.\n";
    }

    // 3. ESCENARIO: Feedback Loop - Mensaje Vago
    echo "\n📧 ESCENARIO 3: Notificación de Cierre sin Contexto\n";
    $stmt = $db->query("SELECT id, asset_id FROM work_orders WHERE status = 'En Proceso' LIMIT 1");
    $openOt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($openOt) {
        echo "   [✓] Cerrando OT {$openOt['id']} con mensaje técnico estándar.\n";
        completeWorkOrder($openOt['id'], [
            'duration_hours' => 1.5,
            'final_asset_status' => 'OPERATIVE',
            'observations' => 'Reparación de cable de poder finalizada.'
        ]);

        // Simulamos el "problema" de comunicación: el usuario recibe "Reparación finalizada" 
        // pero no sabe si ya puede usar el equipo o si el técnico lo dejó en bodega.
        \Backend\Providers\logAuditAction(
            'USER_UNCERTAINTY',
            'WORK_ORDER',
            $openOt['id'],
            "GAP DE COMUNICACIÓN: El usuario recibe confirmación pero no hay registro de entrega/recepción física (Handshake).",
            ['clinical_impact' => 'Medium']
        );
    }

    echo "\n⚠️ RESULTADOS DE LA SIMULACIÓN DE PROBLEMAS:\n";
    echo "1. Falta de estado 'Waiting Response' o 'Postponed' en OTs genera tiempos muertos invisibles.\n";
    echo "2. La baja de activos requiere un flujo 'Handover' explícito que no está en el software.\n";
    echo "3. Las notificaciones automáticas son puramente de sistema y no humanas.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
