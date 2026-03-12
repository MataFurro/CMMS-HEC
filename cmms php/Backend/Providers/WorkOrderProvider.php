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
 * Obtener órdenes paginadas
 */
function getWorkOrdersPaginated(int $limit = 25, int $offset = 0, array $filters = []): array
{
    $repo = new WorkOrderRepository();
    $orders = [];
    foreach ($repo->findPaginated($limit, $offset, $filters) as $entity) {
        $orders[] = $entity->toArray();
    }
    return $orders;
}

/**
 * Contar órdenes
 */
function countTotalWorkOrders(array $filters = []): int
{
    $repo = new WorkOrderRepository();
    return $repo->count($filters);
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
 * Obtener OTs de un activo específico directamente (sin cargar todo el dataset)
 */
function getWorkOrdersByAssetId(string|int $assetId): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT wo.*, u.name as tech_name
                FROM work_orders wo
                LEFT JOIN users u ON wo.assigned_tech_id = u.id
                WHERE wo.asset_id = :asset_id
                ORDER BY wo.created_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['asset_id' => (string)$assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtiene el historial de tiempos entre fallas (TBF) para un activo.
 * Usado para análisis de confiabilidad.
 */
function getAssetFailureHistory($assetId): array
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
function getLastCorrectiveDate($assetId): ?string
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
 * Obtener OT correctivas (Optimizado con SQL directo)
 */
function getCorrectiveWorkOrders(): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT * FROM work_orders WHERE type = 'Correctiva'";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Contar OT por estado (Optimizado)
 */
function countWorkOrdersByStatus(): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT status, COUNT(*) as count FROM work_orders GROUP BY status";
        $results = $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'total' => array_sum($results),
            'En Curso' => (int)($results['En Curso'] ?? 0),
            'En Espera' => (int)($results['En Espera'] ?? 0),
            'Terminada' => (int)($results['Terminada'] ?? 0),
            'Cancelada' => (int)($results['Cancelada'] ?? 0)
        ];
    } catch (Exception $e) {
        return ['total' => 0, 'En Curso' => 0, 'En Espera' => 0, 'Terminada' => 0, 'Cancelada' => 0];
    }
}

/**
 * Contar OT por tipo (Optimizado)
 */
function countWorkOrdersByType(): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $sql = "SELECT type, COUNT(*) as count FROM work_orders GROUP BY type";
        return $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener estadísticas globales de OTs
 */
function getWorkOrderStats(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [
            'TOTAL' => 0,
            'En Curso' => 0,
            'En Espera' => 0,
            'Terminada' => 0,
            'CRITICAL_TODAY' => 0
        ];
    }
    $repo = new WorkOrderRepository();
    $stats = $repo->getStatusStats();

    return [
        'TOTAL' => (int) ($stats['total'] ?? 0),
        'En Curso' => (int) ($stats['pending'] ?? 0) + (int) ($stats['progress'] ?? 0),
        'En Espera' => (int) ($stats['on_hold'] ?? 0),
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
    $db = \Backend\Core\DatabaseService::getInstance();

    // 1. Determinar Prefijo según Tipo
    $type = $data['type'] ?? 'Correctiva';
    $prefix = match ($type) {
        'Correctiva' => 'COR',
        'Preventiva' => 'PRE',
        'Calibración' => 'CAL',
        'Instalación' => 'INS',
        default => 'OT'
    };

    // 2. Generar ID con secuencia diaria: [PREFIX]-[YYYYMMDD]-[SEQ]
    $today = date('Ymd');
    $dateFilter = date('Y-m-d');

    // Contar cuántas OTs se han creado hoy para este tipo
    $stmt = $db->prepare("SELECT COUNT(*) FROM work_orders WHERE created_date = :today AND type = :type");
    $stmt->execute([':today' => $dateFilter, ':type' => $type]);
    $countToday = (int)$stmt->fetchColumn();
    $sequence = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

    $newId = "{$prefix}-{$today}-{$sequence}";

    // 3. Resolución de ID de activo si se proporciona el inventory_id (string)
    $assetId = $data['asset_id'] ?? null;
    if ($assetId && !is_numeric($assetId)) {
        require_once __DIR__ . '/AssetProvider.php';
        $asset = getAssetById($assetId);
        if ($asset) {
            $assetId = $asset['id'];
        }
    }

    // Preparar datos para el repositorio
    $dbData = [
        'id' => $newId,
        'asset_id' => $assetId ?? 0,
        'type' => $type,
        'status' => $data['status'] ?? 'En Curso',
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
        'observations' => $executionData['observations'] ?? ($order->observations ?? ''),
        // Nuevos campos de entrega (Handover)
        'handover_confirmed_by' => $executionData['handover_confirmed_by'] ?? null,
        'handover_location' => $executionData['handover_location'] ?? null,
        'handover_timestamp' => date('Y-m-d H:i:s')
    ];

    $success = $repo->partialUpdate($otId, $updateData);

    if ($success) {
        // Auditoría base con enriquecimiento de handover
        $handoverMsg = $updateData['handover_confirmed_by'] ? " | Entregado a: " . $updateData['handover_confirmed_by'] . " en " . ($updateData['handover_location'] ?? 'ubicación original') : "";

        require_once __DIR__ . '/AuditProvider.php';
        \Backend\Providers\logAuditAction('OT_COMPLETED', 'WORK_ORDER', $otId, "Cierre manual por técnico. Estado final: " . ($executionData['final_asset_status'] ?? 'OPERATIVE') . $handoverMsg, [
            'duration' => $executionData['duration_hours'] ?? 0,
            'failure_code' => $updateData['failure_code'],
            'handover_by' => $updateData['handover_confirmed_by'],
            'handover_loc' => $updateData['handover_location']
        ]);
        \Backend\Core\LoggerService::info("Orden de Trabajo finalizada con datos de entrega técnica", ['id' => $otId]);

        // Sincronización automática de estado del Activo si se proporcionó un estado final
        if (!empty($executionData['final_asset_status'])) {
            require_once __DIR__ . '/AssetProvider.php';
            // Mapeo defensivo: Si viene del frontend como 'OUT_OF_SERVICE' o 'Fuera de Servicio', convertir a 'NO_OPERATIVE'
            $finalStatus = $executionData['final_asset_status'];
            if ($finalStatus === 'OUT_OF_SERVICE' || $finalStatus === 'Fuera de Servicio') {
                $finalStatus = 'NO_OPERATIVE';
            }
            updateAssetInfo($order->assetId, ['status' => $finalStatus]);
        }

        if ($order instanceof \Backend\Models\WorkOrderEntity && $order->msRequestId) {
            try {
                $db = \Backend\Core\DatabaseService::getInstance();

                $stmt = $db->prepare("UPDATE service_requests SET status = 'Finalizada' WHERE id = :id");
                $stmt->execute([':id' => $order->msRequestId]);

                // ── SIMULATED EMAL FEEDBACK LOOP ──
                if (($executionData['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE') {
                    $getEmailStmt = $db->prepare("SELECT requester_email FROM service_requests WHERE id = :id");
                    $getEmailStmt->execute([':id' => $order->msRequestId]);
                    $requestRow = $getEmailStmt->fetch(PDO::FETCH_ASSOC);

                    if ($requestRow && !empty($requestRow['requester_email'])) {
                        $recipient = $requestRow['requester_email'];
                        // Simulated SMTP Send
                        $emailBody = "Estimado usuario,\nSu solicitud (Ref: {$order->msRequestId}) ha sido completada.\nEl equipo correspondiente a la OT {$otId} se encuentra actualmente OPERATIVO.\nSaludos, Departamento de Ingeniería Clínica.";

                        \Backend\Providers\logAuditAction('FEEDBACK_EMAIL_SENT', 'SERVICE_REQUEST', $order->msRequestId, "Simulación: Correo enviado a $recipient", ['body' => $emailBody]);
                        \Backend\Core\LoggerService::info("FEEDBACK LOOP EMAIL SENT", ['to' => $recipient, 'ms_id' => $order->msRequestId]);
                    }
                }

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
function cascadeClosePreventives($assetId, string $triggerOtId): void
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();

        // 1. Buscar OTs preventivas pendientes para este equipo
        $stmt = $db->prepare("
            SELECT id FROM work_orders 
            WHERE asset_id = :asset_id 
              AND type = 'Preventiva' 
              AND status = 'En Curso'
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
 * Posponer una OT por problemas de coordinación clínica
 */
function stallWorkOrderByCoordination(string $otId, string $reason): bool
{
    $repo = new WorkOrderRepository();

    $success = $repo->partialUpdate($otId, [
        'status' => 'En Espera',
        'coordination_stalled_reason' => $reason
    ]);

    if ($success) {
        // Log opcional — no debe bloquear la pausa si la tabla no existe
        try {
            $db = \Backend\Core\DatabaseService::getInstance();
            $stmt = $db->prepare("INSERT INTO coordination_logs (work_order_id, stalled_at, reason) VALUES (?, NOW(), ?)");
            $stmt->execute([$otId, $reason]);
        } catch (\Exception $e) {
            // Tabla coordination_logs puede no existir — no es crítico
        }

        try {
            require_once __DIR__ . '/AuditProvider.php';
            \Backend\Providers\logAuditAction('COMMUNICATION_STALLED', 'WORK_ORDER', $otId, "Mantenimiento detenido por descoordinación clínica: " . $reason);
        } catch (\Throwable $e) {
            // Audit log falló — no es crítico
        }
    }

    return $success;
}

/**
 * Reanudar una OT que estaba marcada como retrasada por servicio
 */
function resumeStalledWorkOrder(string $otId): bool
{
    $repo = new WorkOrderRepository();
    $db = \Backend\Core\DatabaseService::getInstance();

    $success = $repo->partialUpdate($otId, [
        'status' => 'En Curso'
    ]);

    if ($success) {
        $stmt = $db->prepare("UPDATE coordination_logs SET resumed_at = NOW() WHERE work_order_id = ? AND resumed_at IS NULL");
        $stmt->execute([$otId]);

        require_once __DIR__ . '/AuditProvider.php';
        \Backend\Providers\logAuditAction('COMMUNICATION_RESUMED', 'WORK_ORDER', $otId, "Coordinación resuelta. Técnico reanuda labores.");
    }

    return $success;
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

    // Consulta ligera: solo el status, sin JOINs
    $db = \Backend\Core\DatabaseService::getInstance();
    $stmt = $db->prepare("SELECT status FROM work_orders WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $otId]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    // Mantener el status actual al guardar progreso
    $newStatus = $row['status'];

    $updateData = [
        'status' => $newStatus,
        'duration_hours' => $executionData['duration_hours'] ?? null,
        'failure_code' => $executionData['failure_code'] ?? null,
        'final_asset_status' => $executionData['final_asset_status'] ?? null,
        'service_warranty_date' => $executionData['service_warranty_date'] ?? null,
        'observations' => $executionData['observations'] ?? null,
        'checklist_data' => $executionData['checklist_data'] ?? null
    ];

    // Limpiar nulls para no sobrescribir datos existentes
    $updateData = array_filter($updateData, fn($v) => $v !== null);
    $updateData['status'] = $newStatus; // Siempre incluir status

    return $repo->partialUpdate($otId, $updateData);
}

/**
 * Sube un adjunto para una OT
 */
function uploadOtAttachment(string $otId, $assetId, array $fileInfo, string $category = 'evidencia', string $caption = ''): bool
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

/**
 * Cancela una orden de trabajo.
 */
function cancelWorkOrder($id, $reason = '')
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE work_orders SET status = 'Cancelada', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            require_once __DIR__ . '/AuditProvider.php';
            \Backend\Providers\logAuditAction('CANCELAR', 'WORK_ORDER', $id, "OT Cancelada: $reason");
            $db->commit();
            return true;
        }

        $db->rollBack();
        return false;
    } catch (\Throwable $e) {
        if (isset($db)) $db->rollBack();
        return false;
    }
}
