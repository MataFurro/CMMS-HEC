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
        if ($asset['id'] === $id)
            return $asset;
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
 * Obtener familias de activos con métricas agregadas
 */
function getAssetFamilies(): array
{
    global $MOCK_ASSETS;
    $families = [];

    foreach ($MOCK_ASSETS as $asset) {
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
        // Simulación de disponibilidad basada en downtime y horas totales (ejemplo)
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
    global $MOCK_ASSETS;
    $totalVal = 0;
    $totalReposicion = 0;
    $costoMantenimiento = 0;
    $obsolescencia = 0;

    foreach ($MOCK_ASSETS as $asset) {
        $totalVal += $asset['acquisition_cost'] ?? 0;
        $totalReposicion += ($asset['acquisition_cost'] ?? 1000) * 1.25;
        $costoMantenimiento += ($asset['acquisition_cost'] ?? 0) * 0.08;
        if (($asset['useful_life_pct'] ?? 0) > 90)
            $obsolescencia++;
    }

    return [
        'valor_inventario' => $totalVal,
        'valor_reposicion' => $totalReposicion,
        'costo_mantenimiento_anual' => $costoMantenimiento,
        'tco_avg' => $totalVal / (count($MOCK_ASSETS) ?: 1),
        'obsolescencia_proxima' => $obsolescencia,
        'roi_contratos' => 28,
    ];
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
 * Obtener equipos en riesgo de capital (< 20% vida útil)
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
 * Obtener todas las ubicaciones únicas de los activos
 */
function getAllLocations(): array
{
    $assets = getAllAssets();
    $locations = array_unique(array_filter(array_column($assets, 'location')));
    sort($locations);
    return $locations;
}

/**
 * Obtener observaciones de un activo específico
 */
function getAssetObservations(string $asset_id): array
{
    // Mock de observaciones - En un sistema real vendría de una tabla linked
    return [
        ['date' => date('Y-m-d H:i'), 'author' => 'Sistema BioCMMS', 'text' => 'Métrica de confiabilidad actualizada automáticamente.', 'type' => 'normal'],
        ['date' => '2026-02-11 14:30', 'author' => 'Ing. Laura', 'text' => 'Falla reportada en el sensor. Se inicia diagnóstico.', 'type' => 'warning'],
    ];
}

/**
 * Obtener documentos vinculados a un activo
 */
function getAssetDocuments(string $asset_id): array
{
    $asset = getAssetById($asset_id);
    $model = $asset['model'] ?? 'Generic';
    return [
        ['name' => "Manual_{$model}_ES.pdf", 'type' => 'Manual', 'size' => '2.4 MB', 'date' => '2025-01-15'],
        ['name' => 'Ficha_Tecnica.pdf', 'type' => 'Ficha Técnica', 'size' => '3.1 MB', 'date' => '2025-01-15'],
    ];
}
