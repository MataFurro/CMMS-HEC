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
