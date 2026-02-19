<?php

/**
 * Backend/Providers/AssetProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Activos Biomédicos.
 * El frontend (pages/) SOLO usa estas funciones.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

/**
 * Obtener todos los activos
 */
function getAllAssets(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $repo = new AssetRepository();
    $assets = [];
    foreach ($repo->findAll() as $entity) {
        $assets[] = $entity->toArray();
    }
    return $assets;
}

/**
 * Obtener un activo por ID
 */
function getAssetById(string $id): ?array
{
    $repo = new AssetRepository();
    $entity = $repo->findById($id);
    return $entity ? $entity->toArray() : null;
}

/**
 * Buscar activos con filtros
 */
function searchAssets(string $search = '', string $statusFilter = 'ALL'): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $repo = new AssetRepository();
    $assets = [];
    foreach ($repo->search($search, $statusFilter) as $entity) {
        $assets[] = $entity->toArray();
    }
    return $assets;
}

/**
 * Obtener lista simplificada de activos
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
 * Obtener familias de activos con métricas agregadas
 */
function getAssetFamilies(): array
{
    $assets = getAllAssets();
    $families = [];

    foreach ($assets as $asset) {
        $familyName = $asset['family'] ?? 'Genérico';
        if (!isset($families[$familyName])) {
            $families[$familyName] = [
                'name' => $familyName,
                'icon' => $asset['family_icon'] ?? 'category',
                'total_assets' => 0,
                'hours_used' => 0,
                'avg_life_remaining' => 0,
                'total_failures' => 0,
                'downtime' => 0,
                'availability' => 0,
                'color' => $asset['family_color'] ?? '#334155',
                'life_sum' => 0
            ];
        }

        $families[$familyName]['total_assets']++;
        $families[$familyName]['hours_used'] += $asset['hours_used'] ?? 0;
        $families[$familyName]['life_sum'] += $asset['useful_life_pct'] ?? 0;
        $families[$familyName]['total_failures'] += $asset['total_failures'] ?? 0;
        $families[$familyName]['downtime'] += $asset['downtime_hours'] ?? 0;
    }

    foreach ($families as &$f) {
        $f['avg_life_remaining'] = $f['total_assets'] > 0 ? round($f['life_sum'] / $f['total_assets']) : 0;
        $totalPotentialHours = ($f['hours_used'] + $f['downtime']);
        $f['availability'] = $totalPotentialHours > 0 ? round(($f['hours_used'] / $totalPotentialHours) * 100, 1) : 100;
    }

    return array_values($families);
}

/**
 * Obtener estadísticas financieras consolidadas
 */
function getFinancialStats(): array
{
    $assets = getAllAssets();
    $totalVal = 0;
    foreach ($assets as $asset) {
        $totalVal += $asset['acquisition_cost'] ?? 0;
    }

    $totalReposicion = $totalVal * REPLACEMENT_COST_FACTOR;
    $costoMantenimiento = $totalVal * MAINTENANCE_COST_FACTOR;

    $obsolescencia = count(array_filter($assets, function ($a) {
        return ($a['useful_life_pct'] ?? 0) > 90;
    }));

    return [
        'valor_inventario' => $totalVal,
        'valor_reposicion' => $totalReposicion,
        'costo_mantenimiento_anual' => $costoMantenimiento,
        'tco_avg' => count($assets) > 0 ? $totalVal / count($assets) : 0,
        'obsolescencia_proxima' => $obsolescencia,
        'roi_contratos' => 0, // Limpiado (Antes 28%)
    ];
}

/**
 * Contar activos por estado
 */
function countAssetsByStatus(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [
            'total' => 0,
            'operative' => 0,
            'maintenance' => 0,
            'no_operative' => 0,
            'with_obs' => 0
        ];
    }
    $repo = new AssetRepository();
    return $repo->getStatusCounts();
}

/**
 * Contar activos por criticidad
 */
function countAssetsByCriticality(): array
{
    $assets = getAllAssets();
    $counts = ['CRITICAL' => 0, 'RELEVANT' => 0, 'LOW' => 0, 'NA' => 0];
    foreach ($assets as $a) {
        $key = $a['criticality'] ?? 'NA';
        if (isset($counts[$key]))
            $counts[$key]++;
    }
    return $counts;
}

/**
 * Obtener valor total del inventario
 */
function getTotalInventoryValue(): float
{
    return array_sum(array_column(getAllAssets(), 'acquisition_cost'));
}

/**
 * Obtener equipos en riesgo de capital
 */
function getCapitalRiskCount(): int
{
    return count(array_filter(getAllAssets(), function ($a) {
        return isset($a['years_remaining'], $a['total_useful_life'])
            && $a['total_useful_life'] > 0
            && ($a['years_remaining'] / $a['total_useful_life']) < 0.2;
    }));
}

/**
 * Obtener todas las ubicaciones únicas
 */
function getAllLocations(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $repo = new AssetRepository();
    return $repo->getUniqueLocations();
}

/**
 * Obtener observaciones de un activo
 */
function getAssetObservations(string $asset_id): array
{
    return [
        ['date' => date('Y-m-d H:i'), 'author' => 'Sistema BioCMMS', 'text' => 'Métrica de confiabilidad actualizada automáticamente.', 'type' => 'normal'],
    ];
}

/**
 * Obtener documentos vinculados
 */
function getAssetDocuments(string $asset_id): array
{
    return [
        ['name' => 'Ficha_Tecnica.pdf', 'type' => 'Ficha Técnica', 'size' => '3.1 MB', 'date' => date('Y-m-d', strtotime('-1 year'))],
    ];
}

/**
 * Obtener métricas de rendimiento
 */
function getAssetPerformanceMetrics(string $asset_id): array
{
    $asset = getAssetById($asset_id);
    if (!$asset) return [];
    $acquisition = $asset['acquisition_cost'] ?? 0;
    return [
        'uptime' => UPTIME_GOAL,
        'depreciacion_anual' => $acquisition / ($asset['total_useful_life'] ?: 1),
        'valor_residual' => $acquisition * RESIDUAL_VALUE_FACTOR,
        'costo_mtto_estimado' => $acquisition * MAINTENANCE_COST_FACTOR * 1.5,
    ];
}

/**
 * Guardar un nuevo activo en la base de datos
 */
function saveAsset(array $data): bool
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return true;
    }
    $repo = new AssetRepository();
    return $repo->create($data);
}
/**
 * Actualizar información técnica de un activo (Marca, Modelo, Serie, etc.)
 */
function updateAssetInfo(string $id, array $data): bool
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return true;
    }
    $repo = new AssetRepository();
    return $repo->partialUpdate($id, $data);
}

/**
 * Eliminar un activo
 */
function deleteAsset(string $id): bool
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return true;
    }
    $repo = new AssetRepository();
    return $repo->delete($id);
}
