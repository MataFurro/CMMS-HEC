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
    return count($technicians) > 0 ? round($totalCapacity / count($technicians)) : 0;
}
