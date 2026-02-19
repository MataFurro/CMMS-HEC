<?php

/**
 * Backend/providers/WorkOrderProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Órdenes de Trabajo.
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
            'total' => 0,
            'total_ot' => 0,
            'TOTAL' => 0,
            'Pendiente' => 0,
            'En Proceso' => 0,
            'Terminada' => 0,
            'CRITICAL_TODAY' => 0,
            'pending' => 0,
            'progress' => 0,
            'completed' => 0,
            'critical_today' => 0
        ];
    }
    $repo = new WorkOrderRepository();
    $stats = $repo->getStatusStats();

    return [
        'TOTAL' => (int) $stats['total'],
        'Pendiente' => (int) $stats['pending'],
        'En Proceso' => (int) $stats['progress'],
        'Terminada' => (int) $stats['completed'],
        'CRITICAL_TODAY' => (int) $stats['critical_today']
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

    $totalCapacity = array_sum(array_column($technicians, 'capacity'));
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
        'ms_email' => $data['ms_email'] ?? null
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
function completeWorkOrder(string $otId): bool
{
    $repo = new WorkOrderRepository();
    $order = $repo->findById($otId);

    if (!$order) {
        return false;
    }

    $success = $repo->updateStatus($otId, 'Terminada');

    if ($success) {
        \Backend\Core\LoggerService::info("Orden de Trabajo finalizada", ['id' => $otId]);

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
    }

    return $success;
}
