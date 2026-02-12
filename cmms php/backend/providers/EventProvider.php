<?php

/**
 * backend/providers/EventProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a eventos / historial del sistema.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../data/mock_data.php';

/**
 * Obtener eventos recientes del sistema
 */
function getRecentEvents(int $limit = 10): array
{
    global $MOCK_EVENTS;
    return array_slice($MOCK_EVENTS, 0, $limit);
}

/**
 * Obtener eventos por equipo
 */
function getEventsByAssetId(string $assetId): array
{
    global $MOCK_EVENTS;
    return array_filter($MOCK_EVENTS, function ($e) use ($assetId) {
        return stripos($e['subtitle'] ?? '', $assetId) !== false;
    });
}
