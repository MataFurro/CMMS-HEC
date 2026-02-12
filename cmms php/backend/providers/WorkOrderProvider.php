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
        if ($order['id'] === $id) return $order;
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
        if (isset($counts[$key])) $counts[$key]++;
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
        if (isset($counts[$key])) $counts[$key]++;
    }
    return $counts;
}
