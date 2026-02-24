<?php

/**
 * Backend/providers/WorkOrderProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Órdenes de Trabajo.
 * Acceso directo a MySQL (Repositorios).
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/WorkOrderRepository.php';
require_once __DIR__ . '/UserProvider.php';

use Backend\Repositories\WorkOrderRepository;

/**
 * Cargar el modelo e interfaz aquí para asegurar disponibilidad
 */
require_once __DIR__ . '/../Models/WorkOrderStatus.php';
require_once __DIR__ . '/../Models/WorkOrderEntity.php';

/**
 * Obtener todas las órdenes de trabajo usando Generadores
 */
function getAllWorkOrders(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $repo = new WorkOrderRepository();
    $orders = [];
    foreach ($repo->findAll() as $entity) {
        $orders[] = $entity->toArray();
    }
    return $orders;
}

/**
 * Obtener una OT por ID (retorna array para compatibilidad)
 */
function getWorkOrderById(string $id): ?array
{
    $repo = new WorkOrderRepository();
    $entity = $repo->findById($id);
    return $entity ? $entity->toArray() : null;
}

/**
 * Obtiene el historial de tiempos entre fallas (TBF) para un activo.
 * Usado para análisis de confiabilidad.
 */
function getAssetFailureHistory(string $assetId): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT completed_date FROM work_orders 
                WHERE asset_id = :asset_id 
                AND type = 'Correctiva' 
                AND status = 'Terminada' 
                ORDER BY completed_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['asset_id' => $assetId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($dates) < 2) return [];

        $tbfs = [];
        for ($i = 1; $i < count($dates); $i++) {
            $d1 = new DateTime($dates[$i - 1]);
            $d2 = new DateTime($dates[$i]);
            $diff = $d1->diff($d2)->days;
            if ($diff > 0) $tbfs[] = $diff;
        }

        return $tbfs;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener la fecha de la última OT correctiva terminada para un activo.
 */
function getLastCorrectiveDate(string $assetId): ?string
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT completed_date FROM work_orders 
                WHERE asset_id = :asset_id 
                AND type = 'Correctiva' 
                AND status = 'Terminada' 
                ORDER BY completed_date DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['asset_id' => $assetId]);
        return $stmt->fetchColumn() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtener OT correctivas (para cálculos de confiabilidad)
 */
function getCorrectiveWorkOrders(): array
{
    $orders = getAllWorkOrders();
    return array_filter($orders, fn($o) => ($o['type'] ?? '') === 'Correctiva');
}

/**
 * Contar OT por estado
 */
function countWorkOrdersByStatus(): array
{
    $orders = getAllWorkOrders();
    $counts = ['total' => count($orders), 'Pendiente' => 0, 'En Proceso' => 0, 'Terminada' => 0];
    foreach ($orders as $o) {
        $key = $o['status'] ?? 'Pendiente';
        if (isset($counts[$key]))
            $counts[$key]++;
    }
    return $counts;
}

/**
 * Contar OT por tipo
 */
function countWorkOrdersByType(): array
{
    $orders = getAllWorkOrders();
    $counts = ['Preventiva' => 0, 'Correctiva' => 0, 'Calibración' => 0];
    foreach ($orders as $o) {
        $key = $o['type'] ?? '';
        if (isset($counts[$key]))
            $counts[$key]++;
    }
    return $counts;
}

/**
 * Obtener estadísticas globales de OTs
 */
function getWorkOrderStats(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [
            'TOTAL' => 0,
            'Pendiente' => 0,
            'En Proceso' => 0,
            'Terminada' => 0,
            'CRITICAL_TODAY' => 0
        ];
    }
    $repo = new WorkOrderRepository();
    $stats = $repo->getStatusStats();

    return [
        'TOTAL' => (int) ($stats['total'] ?? 0),
        'Pendiente' => (int) ($stats['pending'] ?? 0),
        'En Proceso' => (int) ($stats['progress'] ?? 0),
        'Terminada' => (int) ($stats['completed'] ?? 0),
        'CRITICAL_TODAY' => (int) ($stats['critical_today'] ?? 0)
    ];
}

/**
 * Obtener tasa de adherencia al plan de mantenimiento (%)
 */
function getAdherenceRate(): int
{
    $stats = getWorkOrderStats();
    if ($stats['TOTAL'] === 0)
        return 100;
    return round(($stats['Terminada'] / $stats['TOTAL']) * 100);
}

function getWorkloadSaturation(): int
{
    $technicians = getTechnicianProductivity();
    if (empty($technicians))
        return 0;

    $totalCapacity = array_sum(array_column($technicians, 'capacity_pct'));
    return count($technicians) > 0 ? (int) round($totalCapacity / count($technicians)) : 0;
}

/**
 * Crear una Orden de Trabajo (Función Base para MySQL)
 */
function createWorkOrder(array $data): string
{
    $repo = new WorkOrderRepository();

    // Generar ID único dinámico (Formato: OT-2026-0001)
    $stats = getWorkOrderStats();
    $newCount = ($stats['TOTAL'] ?? 0) + 1;
    $newId = "OT-" . date("Y") . "-" . str_pad($newCount, 4, "0", STR_PAD_LEFT);

    // Preparar datos para el repositorio
    $dbData = [
        'id' => $newId,
        'asset_id' => $data['asset_id'] ?? 'S/N',
        'type' => $data['type'] ?? 'Correctiva',
        'status' => $data['status'] ?? 'Pendiente',
        'priority' => $data['priority'] ?? 'Media',
        'assigned_tech_id' => $data['assigned_tech_id'] ?? null,
        'created_date' => $data['created_date'] ?? date('Y-m-d'),
        'observations' => $data['observations'] ?? '',
        'ms_request_id' => $data['ms_request_id'] ?? null,
        'ms_email' => $data['ms_email'] ?? null,
        'checklist_template' => $data['checklist_template'] ?? null
    ];

    return $repo->create($dbData);
}

/**
 * Crear una Orden de Trabajo a partir de una solicitud (Proceso de Conversión)
 */
function createWorkOrderFromRequest(array $data): string
{
    // Mapear campos de mensajería a campos de OT
    $otData = [
        'asset_id' => $data['asset_id'],
        'type' => 'Correctiva',
        'priority' => $data['priority'] ?? 'Alta',
        'observations' => $data['problem'] ?? '',
        'ms_request_id' => $data['ms_request_id'],
        'ms_email' => $data['ms_email']
    ];

    return createWorkOrder($otData);
}

/**
 * Finalizar una OT y enviar notificación si aplica (Feedback Loop)
 */
function completeWorkOrder(string $otId, array $executionData = []): bool
{
    $repo = new WorkOrderRepository();
    $order = $repo->findById($otId);

    if (!$order) {
        return false;
    }

    // Preparar datos para actualización parcial de la OT
    $updateData = [
        'status' => 'Terminada',
        'completed_date' => date('Y-m-d'),
        'failure_code' => $executionData['failure_code'] ?? null,
        'service_warranty_date' => $executionData['service_warranty_date'] ?? null,
        'final_asset_status' => $executionData['final_asset_status'] ?? 'OPERATIVE',
        'duration_hours' => $executionData['duration_hours'] ?? 0,
        'observations' => $executionData['observations'] ?? ($order->observations ?? '')
    ];

    $success = $repo->partialUpdate($otId, $updateData);

    if ($success) {
        // Auditoría base
        require_once __DIR__ . '/AuditProvider.php';
        \Backend\Providers\logAuditAction('OT_COMPLETED', 'WORK_ORDER', $otId, "Cierre manual por técnico. Estado final: " . ($executionData['final_asset_status'] ?? 'OPERATIVE'), [
            'duration' => $executionData['duration_hours'] ?? 0,
            'failure_code' => $updateData['failure_code']
        ]);
        \Backend\Core\LoggerService::info("Orden de Trabajo finalizada con datos extendidos", ['id' => $otId]);

        // Sincronización automática de estado del Activo si se proporcionó un estado final
        if (!empty($executionData['final_asset_status'])) {
            require_once __DIR__ . '/AssetProvider.php';
            updateAssetInfo($order->assetId, ['status' => $executionData['final_asset_status']]);
        }

        if ($order instanceof \Backend\Models\WorkOrderEntity && $order->msRequestId) {
            try {
                $db = \Backend\Core\DatabaseService::getInstance();

                $stmt = $db->prepare("UPDATE messenger_reports SET status = 'Finalizado' WHERE id = :id");
                $stmt->execute([':id' => $order->msRequestId]);

                \Backend\Core\LoggerService::info("FEEDBACK LOOP: Solicitud vinculada finalizada en MySQL.", ['ms_id' => $order->msRequestId]);
            } catch (Exception $e) {
                \Backend\Core\LoggerService::error("ERROR FEEDBACK LOOP", ['error' => $e->getMessage()]);
            }
        }

        // Cierre automático de preventivas por intervención mayor.
        if (($order->type ?? '') === 'Correctiva' && (float)($updateData['duration_hours'] ?? 0) >= 4.0) {
            cascadeClosePreventives($order->assetId, $otId);
        }
    }

    return $success;
}

/**
 * Cierre automático de preventivas por intervención mayor.
 */
function cascadeClosePreventives(string $assetId, string $triggerOtId): void
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();

        // 1. Buscar OTs preventivas pendientes para este equipo
        $stmt = $db->prepare("
            SELECT id FROM work_orders 
            WHERE asset_id = :asset_id 
              AND type = 'Preventiva' 
              AND status = 'Pendiente'
        ");
        $stmt->execute([':asset_id' => $assetId]);
        $pendings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pendings)) return;

        // 2. Cerrar cada una con observación técnica
        $closeStmt = $db->prepare("
            UPDATE work_orders 
            SET status = 'Terminada', 
                completed_date = CURRENT_DATE,
                observations = CONCAT(IFNULL(observations,''), '\n\n[BITÁCORA]: OT cerrada automáticamente por intervención técnica mayor (OT Origen: ', :trigger_id, '). Verificación realizada durante reparación.'),
                updated_at = NOW()
            WHERE id = :id
        ");

        foreach ($pendings as $p) {
            $closeStmt->execute([
                ':id' => $p['id'],
                ':trigger_id' => $triggerOtId
            ]);

            // Auditoría de la cascada
            require_once __DIR__ . '/AuditProvider.php';
            \Backend\Providers\logAuditAction('AUTO_CASCADE_CLOSURE', 'WORK_ORDER', $p['id'], "Cierre preventivo automático gatillado por OT Correctiva mayor ($triggerOtId).", [
                'trigger_ot' => $triggerOtId,
                'asset_id' => $assetId
            ]);

            \Backend\Core\LoggerService::info("SISTEMA: OT Preventiva cerrada automáticamente", ['id' => $p['id'], 'trigger' => $triggerOtId]);
        }
    } catch (Exception $e) {
        \Backend\Core\LoggerService::error("ERROR EN CIERRE AUTOMÁTICO", ['asset' => $assetId, 'error' => $e->getMessage()]);
    }
}

/**
 * Calcular el impacto financiero del downtime por área técnica
 */
function getDowntimeImpact(): array
{
    require_once __DIR__ . '/../../includes/constants.php';
    $db = \Backend\Core\DatabaseService::getInstance();

    // Query para obtener suma de horas por ubicación de activos
    $query = "
        SELECT a.location, SUM(wo.duration_hours) as total_hours
        FROM work_orders wo
        JOIN assets a ON wo.asset_id = a.id
        WHERE wo.status = 'Terminada' AND wo.type = 'Correctiva'
        GROUP BY a.location
    ";

    $stmt = $db->query($query);
    $results = $stmt->fetchAll();

    $impacts = [];
    $totalLoss = 0;

    foreach ($results as $row) {
        $location = $row['location'] ?? 'Default';
        $hours = (float)$row['total_hours'];
        $rate = AREA_COST_HOURS[$location] ?? AREA_COST_HOURS['Default'];

        $loss = $hours * $rate;
        $totalLoss += $loss;

        $impacts[] = [
            'area' => $location,
            'hours' => $hours,
            'loss' => $loss
        ];
    }

    // Ordenar por pérdida de mayor a menor
    usort($impacts, fn($a, $b) => $b['loss'] <=> $a['loss']);

    return [
        'total_loss' => $totalLoss,
        'areas' => array_slice($impacts, 0, 5) // Top 5 áreas
    ];
}

/**
 * Obtener el total de horas de inactividad (downtime) registradas.
 */
function getTotalDowntimeHours(): float
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $query = "SELECT SUM(duration_hours) FROM work_orders WHERE status = 'Terminada' AND type = 'Correctiva'";
        return (float)($db->query($query)->fetchColumn() ?: 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Guarda el progreso parcial de una OT sin cerrarla.
 */
function saveWorkOrderProgress(string $otId, array $executionData = []): bool
{
    $repo = new WorkOrderRepository();
    $order = $repo->findById($otId);

    if (!$order) {
        return false;
    }

    // Si la OT está pendiente, al guardar progreso pasa a 'En Proceso'
    $newStatus = ($order->status->value === 'Pendiente') ? 'En Proceso' : $order->status->value;

    $updateData = [
        'status' => $newStatus,
        'duration_hours' => $executionData['duration_hours'] ?? $order->durationHours,
        'failure_code' => $executionData['failure_code'] ?? $order->failureCode,
        'final_asset_status' => $executionData['final_asset_status'] ?? $order->finalAssetStatus,
        'observations' => $executionData['observations'] ?? $order->observations,
        'checklist_data' => $executionData['checklist_data'] ?? null
    ];

    return $repo->partialUpdate($otId, $updateData);
}

/**
 * Sube un adjunto para una OT
 */
function uploadOtAttachment(string $otId, string $assetId, array $fileInfo, string $category = 'evidencia', string $caption = ''): bool
{
    try {
        $uploadBaseDir = __DIR__ . '/../../storage/uploads/ot/' . $otId . '/';
        if (!is_dir($uploadBaseDir)) {
            mkdir($uploadBaseDir, 0777, true);
        }

        $fileName = time() . '_' . basename($fileInfo['name']);
        $targetPath = $uploadBaseDir . $fileName;
        $relativePath = 'storage/uploads/ot/' . $otId . '/' . $fileName;

        if (move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            $db = \Backend\Core\DatabaseService::getInstance();
            $stmt = $db->prepare("INSERT INTO ot_attachments (work_order_id, asset_id, uploaded_by, file_path, file_type, caption, category) VALUES (:ot_id, :asset_id, :user_id, :path, :type, :caption, :cat)");

            return $stmt->execute([
                'ot_id' => $otId,
                'asset_id' => $assetId,
                'user_id' => $_SESSION['user_id'] ?? null,
                'path' => $relativePath,
                'type' => $fileInfo['type'],
                'caption' => $caption,
                'cat' => $category
            ]);
        }
        return false;
    } catch (Exception $e) {
        \Backend\Core\LoggerService::error("Error al subir adjunto de OT", ['ot' => $otId, 'error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Obtener todos los adjuntos de una OT
 */
function getOtAttachments(string $otId): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $stmt = $db->prepare("SELECT * FROM ot_attachments WHERE work_order_id = :ot_id ORDER BY uploaded_at DESC");
        $stmt->execute(['ot_id' => $otId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
