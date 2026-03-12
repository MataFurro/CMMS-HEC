<?php

/**
 * Backend/providers/EventProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a eventos / historial del sistema.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';

use Backend\Core\DatabaseService;

/**
 * Obtener eventos recientes del sistema desde audit_trail
 */
function getRecentEvents(int $limit = 10): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $db = DatabaseService::getInstance();
    $sql = "SELECT a.action as title, u.name as subtitle, a.timestamp as time, a.target_type 
            FROM audit_trail a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.timestamp DESC
            LIMIT :limit";

    $stmt = $db->prepare($sql);
    $stmt->execute(['limit' => $limit]);
    $results = $stmt->fetchAll();

    return array_map(function ($row) {
        $icon = match ($row['target_type']) {
            'ASSET' => 'precision_manufacturing',
            'WORK_ORDER' => 'task',
            'USER' => 'person',
            default => 'info'
        };

        return [
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'time' => (new DateTime($row['time']))->format('H:i'),
            'icon' => $icon,
            'color' => '#3b82f6'
        ];
    }, $results);
}

/**
 * Obtener eventos por equipo
 */
function getEventsByAssetId(string $assetId): array
{
    $db = DatabaseService::getInstance();
    $sql = "SELECT a.action as title, u.name as subtitle, a.timestamp as time 
            FROM audit_trail a
            JOIN users u ON a.user_id = u.id
            WHERE a.asset_id = :asset_id
            ORDER BY a.timestamp DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute(['asset_id' => $assetId]);
    return $stmt->fetchAll();
}
/**
 * Obtener eventos para la agenda (OTs reales y sugerencias)
 */
function getAgendaEvents(string $startDate, string $endDate): array
{
    try {
        $db = DatabaseService::getInstance();
        $sql = "SELECT w.id, w.asset_id, w.asset_name, w.type, w.status, w.priority, w.date, w.technician, a.location 
                FROM work_orders w
                LEFT JOIN assets a ON w.asset_id = a.id
                WHERE w.date >= :start AND w.date <= :end
                ORDER BY w.date ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        $orders = $stmt->fetchAll();

        return array_map(function ($ot) {
            return [
                'id' => $ot['id'],
                'type' => 'WORK_ORDER',
                'category' => $ot['type'],
                'title' => $ot['asset_name'],
                'date' => $ot['date'],
                'status' => $ot['status'],
                'priority' => $ot['priority'],
                'tech' => $ot['technician'] ?? 'Sin asignar',
                'location' => $ot['location'] ?? 'General',
                'color' => $ot['priority'] === 'CRITICAL' ? 'danger' : 'medical-blue'
            ];
        }, $orders);
    } catch (Exception $e) {
        return [];
    }
}
/**
 * Generar sugerencias predictivas de mantenimiento basadas en confiabilidad
 */
function getPredictiveAgendaEvents(string $startDate, string $endDate): array
{
    require_once __DIR__ . '/AssetProvider.php';
    require_once __DIR__ . '/../Services/ReliabilityService.php';
    require_once __DIR__ . '/WorkOrderProvider.php';

    $assets = getAllAssets();
    $suggestions = [];
    $endDateTime = new DateTime($endDate);
    $startDateTime = new DateTime($startDate);

    foreach ($assets as $asset) {
        // Solo para equipos críticos o de monitoreo por ahora para no saturar
        if (!in_array($asset['riesgo_ge'] ?? '', ['MONITOREO', 'APOYO TERAPÉUTICO', 'IMAGENOLOGÍA'])) continue;

        $params = \Backend\Services\ReliabilityService::getSuggestedParameters($asset['riesgo_ge'] ?? 'GENERIC');

        // Calcular tiempo de operación desde la última OT correctiva o creación
        $lastDate = getLastCorrectiveDate($asset['id']) ?? $asset['created_at'] ?? '2026-01-01';
        $lastDateTime = new DateTime($lastDate);

        // Proyectar cuándo la confiabilidad cae bajo 0.85 (umbral de seguridad)
        // R(t) = 0.85 => exp(-(t/eta)^beta) = 0.85 => -(t/eta)^beta = ln(0.85)
        // t = eta * (-ln(0.85))^(1/beta)
        $t_threshold = $params['eta'] * pow(-log(0.85), 1 / $params['beta']);

        $suggestedDateTime = clone $lastDateTime;
        $suggestedDateTime->modify("+" . round($t_threshold) . " days");

        if ($suggestedDateTime >= $startDateTime && $suggestedDateTime <= $endDateTime) {
            $suggestions[] = [
                'id' => 'PRED-' . $asset['id'],
                'type' => 'PREDICTIVE',
                'category' => 'Sugerencia Predictiva',
                'title' => $asset['name'],
                'date' => $suggestedDateTime->format('Y-m-d'),
                'status' => 'SUGGESTED',
                'priority' => 'MEDIUM',
                'tech' => 'IA BioCMMS',
                'color' => 'amber-500',
                'location' => $asset['location'] ?? 'Ubicación Desconocida'
            ];
        }
    }

    return $suggestions;
}
