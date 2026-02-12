<?php

/**
 * backend/providers/AssetProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Activos Biomédicos.
 * El frontend (pages/) SOLO usa estas funciones.
 * Para migrar a BD: reemplazar las implementaciones internas.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../data/mock_data.php';

/**
 * Obtener todos los activos
 */
function getAllAssets(): array
{
    global $MOCK_ASSETS;
    return $MOCK_ASSETS;
}

/**
 * Obtener un activo por ID
 */
function getAssetById(string $id): ?array
{
    global $MOCK_ASSETS;
    foreach ($MOCK_ASSETS as $asset) {
        if ($asset['id'] === $id) return $asset;
    }
    return null;
}

/**
 * Buscar activos con filtros
 */
function searchAssets(string $search = '', string $statusFilter = 'ALL'): array
{
    $assets = getAllAssets();

    return array_filter($assets, function ($asset) use ($search, $statusFilter) {
        $matchSearch = empty($search) ||
            stripos($asset['name'], $search) !== false ||
            stripos($asset['brand'], $search) !== false ||
            stripos($asset['id'], $search) !== false;

        $matchStatus = $statusFilter === 'ALL' || $asset['status'] === $statusFilter;

        return $matchSearch && $matchStatus;
    });
}

/**
 * Obtener lista simplificada de activos (para selects/dropdowns)
 */
function getAssetOptions(): array
{
    $assets = getAllAssets();
    return array_map(function ($a) {
        return [
            'id' => $a['id'],
            'name' => $a['name'],
            'location' => $a['location'] ?? ''
        ];
    }, $assets);
}

/**
 * Contar activos por estado
 */
function countAssetsByStatus(): array
{
    $assets = getAllAssets();
    $counts = [
        'total' => count($assets),
        'operative' => 0,
        'maintenance' => 0,
        'no_operative' => 0,
        'with_obs' => 0
    ];
    foreach ($assets as $a) {
        match ($a['status']) {
            'OPERATIVE' => $counts['operative']++,
            'MAINTENANCE' => $counts['maintenance']++,
            'NO_OPERATIVE' => $counts['no_operative']++,
            'OPERATIVE_WITH_OBS' => $counts['with_obs']++,
            default => null
        };
    }
    return $counts;
}

/**
 * Contar activos por criticidad
 */
function countAssetsByCriticality(): array
{
    $assets = getAllAssets();
    $counts = ['CRITICAL' => 0, 'RELEVANT' => 0, 'LOW' => 0];
    foreach ($assets as $a) {
        $key = $a['criticality'] ?? 'LOW';
        if (isset($counts[$key])) $counts[$key]++;
    }
    return $counts;
}

/**
 * Obtener valor total del inventario
 */
function getTotalInventoryValue(): float
{
    return array_sum(array_column(getAllAssets(), 'acquisitionCost'));
}

/**
 * Obtener equipos en riesgo de capital (< 20% vida útil)
 */
function getCapitalRiskCount(): int
{
    return count(array_filter(getAllAssets(), function ($a) {
        return isset($a['yearsRemaining'], $a['totalUsefulLife'])
            && $a['totalUsefulLife'] > 0
            && ($a['yearsRemaining'] / $a['totalUsefulLife']) < 0.2;
    }));
}
