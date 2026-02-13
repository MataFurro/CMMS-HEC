<?php

/**
 * backend/providers/WorkOrderProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Órdenes de Trabajo.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../data/mock_data.php';

/**
 * Obtener todas las órdenes de trabajo
 */
function getAllWorkOrders(): array
{
    global $MOCK_WORK_ORDERS;
    // Persistencia en sesión para la demo sin base de datos real
    if (isset($_SESSION['MOCK_WORK_ORDERS_PERSIST'])) {
        return $_SESSION['MOCK_WORK_ORDERS_PERSIST'];
    }
    return $MOCK_WORK_ORDERS;
}

/**
 * Obtener una OT por ID
 */
function getWorkOrderById(string $id): ?array
{
    global $MOCK_WORK_ORDERS;
    foreach ($MOCK_WORK_ORDERS as $order) {
        if ($order['id'] === $id)
            return $order;
    }
    return null;
}

/**
 * Obtener OT correctivas (para cálculos de confiabilidad)
 */
function getCorrectiveWorkOrders(): array
{
    global $MOCK_OT_CORRECTIVAS;
    return $MOCK_OT_CORRECTIVAS;
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
    global $MOCK_WORK_ORDERS;
    $stats = [
        'TOTAL' => count($MOCK_WORK_ORDERS),
        'Pendiente' => 0,
        'En Proceso' => 0,
        'Terminada' => 0,
        'CRITICAL_TODAY' => 0
    ];

    foreach ($MOCK_WORK_ORDERS as $order) {
        $status = $order['status'] ?? 'Pendiente';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        if (($order['priority'] ?? '') === 'CRITICAL') {
            $stats['CRITICAL_TODAY']++;
        }
    }

    return $stats;
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

/**
 * Obtener saturación media del equipo (%)
 */
function getWorkloadSaturation(): int
{
    // Simulación basada en técnicos y sus OTs activas
    $technicians = getTechnicianRanking();
    $totalCapacity = array_sum(array_column($technicians, 'capacity'));
    return count($technicians) > 0 ? (int)round($totalCapacity / count($technicians)) : 0;
}

/**
 * Crear una Orden de Trabajo a partir de una solicitud (Proceso de Conversión)
 */
function createWorkOrderFromRequest(array $data): string
{
    global $MOCK_WORK_ORDERS;

    $newId = "OT-" . date("Y") . "-" . str_pad(count($MOCK_WORK_ORDERS) + 100, 4, "0", STR_PAD_LEFT);

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
        'location' => $data['location'] ?? 'Hospital General',
        'ms_email' => $data['ms_email'] ?? null
    ];

    $MOCK_WORK_ORDERS[] = $newOrder;
    $_SESSION['MOCK_WORK_ORDERS_PERSIST'] = $MOCK_WORK_ORDERS; // Persistencia en sesión para demo

    return $newId;
}

/**
 * Finalizar una OT y enviar notificación si aplica (Feedback Loop)
 */
function completeWorkOrder(string $otId): bool
{
    global $MOCK_WORK_ORDERS;

    foreach ($MOCK_WORK_ORDERS as &$order) {
        if ($order['id'] === $otId) {
            $order['status'] = 'Terminada';

            // Simulación de Feedback Loop a través del Messenger
            if (!empty($order['ms_email'])) {
                // En un sistema real, aquí llamaríamos a API Mail
                error_log("FEEDBACK LOOP: Notificando a " . $order['ms_email'] . " que la OT " . $otId . " ha finalizado.");
            }

            $_SESSION['MOCK_WORK_ORDERS_PERSIST'] = $MOCK_WORK_ORDERS;
            return true;
        }
    }

    return false;
}
