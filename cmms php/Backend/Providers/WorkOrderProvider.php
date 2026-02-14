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
    // Simulación lógica: (OT Terminadas / OT Totales) * 100 con un factor de corrección
    $stats = getWorkOrderStats();
    if ($stats['TOTAL'] === 0)
        return 100;
    return round(($stats['Terminada'] / $stats['TOTAL']) * 100);
}

function getWorkloadSaturation(): int
{
    // Simulación basada en técnicos y sus OTs activas
    $technicians = getTechnicianProductivity();
    if (empty($technicians))
        return 0;

    $totalCapacity = array_sum(array_column($technicians, 'capacity'));
    return count($technicians) > 0 ? (int) round($totalCapacity / count($technicians)) : 0;
}

/**
 * Crear una Orden de Trabajo a partir de una solicitud (Proceso de Conversión)
 */
function createWorkOrderFromRequest(array $data): string
{
    global $MOCK_WORK_ORDERS;

    // Persistencia en sesión para que no se pierdan al navegar
    if (!isset($_SESSION['MOCK_WORK_ORDERS_PERSIST'])) {
        $_SESSION['MOCK_WORK_ORDERS_PERSIST'] = $MOCK_WORK_ORDERS;
    }

    $newId = "OT-" . date("Y") . "-" . str_pad(count($_SESSION['MOCK_WORK_ORDERS_PERSIST']) + 101, 4, "0", STR_PAD_LEFT);

    $newOrder = [
        'id' => $newId,
        'asset_id' => $data['asset_id'] ?? 'S/N',
        'asset_name' => $data['asset_name'] ?? 'Equipo Desconocido',
        'type' => $data['type'] ?? 'Correctiva',
        'status' => 'Pendiente',
        'priority' => $data['priority'] ?? 'Media',
        'tech' => $data['tech'] ?? 'Por Asignar',
        'date' => date("Y-m-d"),
        'problem' => $data['problem'] ?? '',
        'location' => $data['location'] ?? DEFAULT_HOSPITAL_NAME,
        'ms_email' => $data['ms_email'] ?? null,
        'ms_request_id' => $data['ms_request_id'] ?? null // Vinculación por ID
    ];

    $_SESSION['MOCK_WORK_ORDERS_PERSIST'][] = $newOrder;

    return $newId;
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

        // Feedback Loop: Actualizar base de datos del Mensajero
        if ($order instanceof \Backend\Models\WorkOrderEntity && $order->msRequestId) {
            try {
                $msDbPath = __DIR__ . '/../../API Mail/database/messenger.db';
                if (file_exists($msDbPath)) {
                    $db = new PDO('sqlite:' . $msDbPath);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $stmt = $db->prepare("UPDATE reports SET status = 'Finalizado' WHERE id = :id");
                    $stmt->execute([':id' => $order->msRequestId]);

                    \Backend\Core\LoggerService::info("FEEDBACK LOOP: Solicitud vinculada finalizada.", ['ms_id' => $order->msRequestId]);
                }
            } catch (Exception $e) {
                \Backend\Core\LoggerService::error("ERROR FEEDBACK LOOP", ['error' => $e->getMessage()]);
            }
        }
    }

    return $success;
}

