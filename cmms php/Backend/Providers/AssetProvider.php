<?php

/**
 * Backend/Providers/AssetProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Activos Biomédicos.
 * El frontend (pages/) SOLO usa estas funciones.
 * Acceso directo a MySQL (Repositorios).
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';
require_once __DIR__ . '/../Services/ReliabilityService.php';

use Backend\Repositories\AssetRepository;
use Backend\Services\ReliabilityService;

/**
 * Obtener todos los activos usando Generadores internos
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
 * Obtener un activo por ID (retorna array para compatibilidad)
 */
function getAssetById(string $id): ?array
{
    $repo = new AssetRepository();
    $entity = $repo->findById($id);
    return $entity ? $entity->toArray() : null;
}

/**
 * Buscar activos con filtros usando Generadores
 */
function searchAssets(string $search = '', string $statusFilter = 'ALL', int $limit = 0, int $offset = 0, array $filters = []): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [];
    }
    $repo = new AssetRepository();
    $assets = [];
    foreach ($repo->searchPaginated($search, $statusFilter, $limit, $offset, $filters) as $entity) {
        $assets[] = $entity->toArray();
    }
    return $assets;
}

/**
 * Contar activos según filtros para paginación
 */
function countAssets(string $search = '', string $statusFilter = 'ALL', array $filters = []): int
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return 0;
    }
    $repo = new AssetRepository();
    return $repo->countSearchResults($search, $statusFilter, $filters);
}

/**
 * Obtener marcas únicas
 */
function getBrandOptions(): array
{
    $repo = new AssetRepository();
    return $repo->getUniqueBrands();
}

/**
 * Obtener criticidades únicas
 */
function getCriticalityOptions(): array
{
    $repo = new AssetRepository();
    return $repo->getUniqueCriticalities();
}

/**
 * Obtener categorías/clases de activos desde la tabla maestra
 */
function getCategoryOptions(): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        return $db->query("SELECT name FROM asset_classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return ['Monitoreo', 'No Monitoreo']; // Fallback
    }
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
 * Obtener estadísticas financieras consolidadas (Dinámicas)
 */
function getFinancialStats(): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [
            'valor_inventario' => 0,
            'valor_reposicion' => 0,
            'costo_mantenimiento_anual' => 0,
            'tco_avg' => 0,
            'obsolescencia_proxima' => 0,
            'penalizacion_pm' => 0,
            'penalizacion_pi' => 0,
            'ahorro_in_house' => 0
        ];
    }

    require_once __DIR__ . '/../../includes/constants.php';
    require_once __DIR__ . '/WorkOrderProvider.php';

    $assets = getAllAssets();
    $totalVal = 0;
    $obsolescencia = 0;

    foreach ($assets as $asset) {
        $totalVal += $asset['acquisition_cost'] ?? 0;
        if (($asset['useful_life_pct'] ?? 0) > 90) {
            $obsolescencia++;
        }
    }

    $totalReposicion = $totalVal * REPLACEMENT_COST_FACTOR;
    $costoMantenimiento = $totalVal * MAINTENANCE_COST_FACTOR;

    $counts = countWorkOrdersByStatus();
    $missedPMs = (int)($counts['Pendiente'] ?? 0);
    $penalizacionPM = $missedPMs * PENALTY_MISSED_PM;

    $penalizacionPi = 0;
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $events = $db->query("SELECT COUNT(*) as total FROM asset_recalls")->fetch();
        $penalizacionPi = ($events['total'] ?? 0) * PENALTY_ADVERSE_EVENT;
    } catch (\Exception $dbEx) {
        // La tabla asset_recalls puede no existir aún. Se ignora el cálculo.
        error_log("AssetProvider::getFinancialStats - asset_recalls no encontrada: " . $dbEx->getMessage());
    }

    return [
        'valor_inventario' => $totalVal,
        'valor_reposicion' => $totalReposicion,
        'costo_mantenimiento_anual' => $costoMantenimiento,
        'tco_avg' => count($assets) > 0 ? $totalVal / count($assets) : 0,
        'obsolescencia_proxima' => $obsolescencia,
        'penalizacion_pm' => $penalizacionPM,
        'penalizacion_pi' => $penalizacionPi,
        'ahorro_in_house' => 67
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
 * Obtener equipos con vida útil contable excedida pero aún operativos
 */
function getExpiredOperativeCount(): int
{
    return count(array_filter(getAllAssets(), function ($a) {
        return ($a['useful_life_pct'] ?? 0) <= 0
            && ($a['status'] ?? '') === 'OPERATIVE';
    }));
}

/**
 * Obtener equipos en riesgo de capital (< 20% vida restante)
 */
function getCapitalRiskCount(): int
{
    return count(array_filter(getAllAssets(), function ($a) {
        return isset($a['years_remaining'], $a['total_useful_life'])
            && $a['total_useful_life'] > 0
            && ($a['years_remaining'] / $a['total_useful_life']) < 0.2
            && ($a['useful_life_pct'] ?? 0) > 0; // Excluir ya vencidos
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
    $locations = $repo->getUniqueLocations();
    $standard = ['Esterilización'];
    return array_unique(array_merge($standard, $locations));
}

/**
 * Obtener observaciones de un activo
 */
function getAssetObservations(string $asset_id): array
{
    $asset = getAssetById($asset_id);
    if (!$asset || empty($asset['observations'])) {
        return [];
    }

    return [
        [
            'date' => $asset['updated_at'] ?? date('Y-m-d H:i'),
            'author' => 'Sistema BioCMMS',
            'text' => $asset['observations'],
            'type' => 'normal'
        ],
    ];
}

/**
 * Obtener documentos vinculados (desde ot_attachments y otros orígenes)
 */
function getAssetDocuments(string $asset_id): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $stmt = $db->prepare("SELECT * FROM ot_attachments WHERE asset_id = :asset_id ORDER BY uploaded_at DESC");
        $stmt->execute(['asset_id' => $asset_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener métricas de confiabilidad predictiva basadas en Weibull
 */
function getAssetReliabilityMetrics(string $asset_id): array
{
    $asset = getAssetById($asset_id);
    if (!$asset) return [];

    require_once __DIR__ . '/WorkOrderProvider.php';
    $history = getAssetFailureHistory($asset_id);

    // Sugerir parámetros basados en categoría si no hay suficiente historia
    $suggested = ReliabilityService::getSuggestedParameters($asset['riesgo_ge'] ?? 'GENERIC');

    $params = ReliabilityService::estimateFromHistory($history);
    $beta = $params['beta'] ?? $suggested['beta'];
    $eta = $params['eta'] ?? $suggested['eta'];

    // Tiempo de operación desde la última falla (en días)
    $lastFailure = getLastCorrectiveDate($asset_id);
    $asset = (new \Backend\Repositories\AssetRepository())->findById($asset_id);

    $daysSinceLastFailure = 0;
    $now = new DateTime();

    if ($lastFailure) {
        $lastDate = new DateTime($lastFailure);
        $daysSinceLastFailure = (int)$lastDate->diff($now)->days;
    } elseif ($asset && $asset->fechaInstalacion) {
        // Fallback: Si no hay fallas, usar fecha de instalación (basado en el DB)
        $daysSinceLastFailure = (int)$asset->fechaInstalacion->diff($now)->days;
    } else {
        $daysSinceLastFailure = 30; // Hard fallback
    }

    return [
        'reliability' => ReliabilityService::calculateReliability($daysSinceLastFailure, $beta, $eta),
        'failure_prob_30d' => ReliabilityService::predictFailureProbability($daysSinceLastFailure, 30, $beta, $eta),
        'hazard_rate' => ReliabilityService::calculateHazardRate($daysSinceLastFailure, $beta, $eta),
        'beta' => $beta,
        'eta' => $eta,
        'days_in_service' => $daysSinceLastFailure,
        'data_quality' => count($history) >= 5 ? 'High' : (count($history) > 0 ? 'Medium' : 'Low (Suggested)')
    ];
}

/**
 * Obtener métricas de rendimiento específicas de un activo
 */
function getAssetPerformanceMetrics(string $asset_id): array
{
    $asset = getAssetById($asset_id);
    if (!$asset) return [];

    $acquisition = $asset['acquisition_cost'] ?? 0;
    $reliability = getAssetReliabilityMetrics($asset_id);

    return [
        'uptime' => UPTIME_GOAL,
        'depreciacion_anual' => $acquisition / ($asset['total_useful_life'] ?: 1),
        'valor_residual' => $acquisition * RESIDUAL_VALUE_FACTOR,
        'costo_mtto_estimado' => $acquisition * MAINTENANCE_COST_FACTOR * 1.5,
        'reliability_index' => $reliability['reliability'] ?? 1.0,
        'next_failure_prob' => $reliability['failure_prob_30d'] ?? 0.05
    ];
}

/**
 * Obtener los activos con mayor riesgo de falla (Top N)
 */
function getTopRiskAssets(int $limit = 5): array
{
    $assets = getAllAssets();
    $riskList = [];

    foreach ($assets as $asset) {
        if (($asset['criticality'] ?? '') === 'CRITICAL' || ($asset['status'] ?? '') === 'OPERATIVE') {
            $metrics = getAssetReliabilityMetrics($asset['id']);
            $riskList[] = array_merge($asset, [
                'failure_prob' => $metrics['failure_prob_30d'],
                'days_in_service' => $metrics['days_in_service']
            ]);
        }
    }

    // Ordenar por probabilidad de falla DESC
    usort($riskList, fn($a, $b) => $b['failure_prob'] <=> $a['failure_prob']);

    return array_slice($riskList, 0, $limit);
}

/**
 * Obtener estadísticas de impacto clínico (Downtime vs Atenciones)
 */
function getClinicalImpactStats(): array
{
    require_once __DIR__ . '/WorkOrderProvider.php';
    $totalHours = getTotalDowntimeHours();

    // Estimación: 1 hora de downtime = 0.5 atenciones afectadas (valor referencial para hospital público)
    $atencionesAfectadas = floor($totalHours * 0.5);

    return [
        'downtime_hours' => round($totalHours, 1),
        'patients_affected' => $atencionesAfectadas,
        'clinical_availability' => 98.4, // Meta referencial MINSAL
        'trend' => '-2.4% vs meta',
        'operating_continuity' => round($totalHours > 0 ? (1 - ($totalHours / (30 * 24))) * 100 : 99.9, 2)
    ];
}

/**
 * Guardar un nuevo activo en la base de datos
 */
function saveAsset(array $data): bool
{
    $repo = new AssetRepository();
    return $repo->create($data);
}

/**
 * Actualizar información técnica de un activo (Marca, Modelo, Serie, etc.)
 */
function updateAssetInfo(string $id, array $data): bool
{
    $repo = new AssetRepository();
    return $repo->partialUpdate($id, $data);
}

/**
 * Eliminar un activo
 */
function deleteAsset(string $id): bool
{
    $repo = new AssetRepository();
    return $repo->delete($id);
}

/**
 * Detectar si un equipo es de Monitoreo o No Monitoreo según su nombre.
 * Fallback para cuando riesgo_ge no está poblado aún.
 */
function _detectarMonitoreo(string $name): string
{
    $n = mb_strtolower($name, 'UTF-8');
    $kwMonitoreo = [
        'monitor',
        'ventilador',
        'desfibrilador',
        'oxímetro',
        'oximetro',
        'pulsioxímetro',
        'pulsioximetro',
        'ecógrafo',
        'ecografo',
        'electrocardiogr',
        'electrocardio',
        'tensiómetro',
        'tensiometro',
        'glucómetro',
        'glucometro',
        'glicómetro',
        'incubadora',
        'cuna térmica',
        'cuna termica',
        'bomba de infusión',
        'bomba de infusion',
        'bomba de jeringa',
        'infusor',
        'ultrasonido',
        'termómetro',
        'termometro',
        'arco en c',
        'rayos x',
        'ecmo',
        'marcapaso',
    ];
    foreach ($kwMonitoreo as $kw) {
        if (str_contains($n, $kw)) return 'Monitoreo';
    }
    return 'No Monitoreo';
}

/**
 * Obtener activos agrupados por Clase (Catálogo oficial).
 */
function getAssetsByClase(): array
{
    static $critLabel = [
        'CRITICAL' => 'Crítico',
        'RELEVANT' => 'Relevante',
        'LOW'      => 'Baja',
    ];

    $assets = getAllAssets();
    $grupos = [];

    // Priorizar clases oficiales
    $clasesOficiales = getCategoryOptions();
    foreach ($clasesOficiales as $c) {
        $grupos[$c] = [
            'clase'       => $c,
            'total'       => 0,
            'operativos'  => 0,
            'criticos'    => 0,
            'relevantes'  => 0,
            'valor_total' => 0.0,
            'obsoletos'   => 0,
            'equipos'     => [],
        ];
    }

    // Grupo para los que no tengan clase asignada o sea inválida
    $grupos['OTROS'] = [
        'clase'       => 'OTROS',
        'total'       => 0,
        'operativos'  => 0,
        'criticos'    => 0,
        'relevantes'  => 0,
        'valor_total' => 0.0,
        'obsoletos'   => 0,
        'equipos'     => [],
    ];

    foreach ($assets as $asset) {
        $grupo = mb_strtoupper(trim($asset['riesgo_ge'] ?? 'OTROS'), 'UTF-8');

        if (!isset($grupos[$grupo])) {
            $grupo = 'OTROS';
        }

        $grupos[$grupo]['total']++;
        $grupos[$grupo]['valor_total'] += (float)($asset['acquisition_cost'] ?? 0);

        if (($asset['status'] ?? '')      === 'OPERATIVE') $grupos[$grupo]['operativos']++;
        if (($asset['criticality'] ?? '') === 'CRITICAL')  $grupos[$grupo]['criticos']++;
        if (($asset['criticality'] ?? '') === 'RELEVANT')  $grupos[$grupo]['relevantes']++;
        if (($asset['useful_life_pct'] ?? 100) <= 0)       $grupos[$grupo]['obsoletos']++;

        $grupos[$grupo]['equipos'][] = [
            'id'          => $asset['id'],
            'name'        => $asset['name'],
            'brand'       => $asset['brand'] ?? '-',
            'model'       => $asset['model'] ?? '-',
            'location'    => $asset['location'] ?? '-',
            'status'      => $asset['status'] ?? '-',
            'criticality' => $asset['criticality'] ?? '-',
            'crit_label'  => $critLabel[$asset['criticality']] ?? ($asset['criticality'] ?? '-'),
            'vida_util'   => $asset['useful_life_pct'] ?? 0,
            'costo'       => $asset['acquisition_cost'] ?? 0,
        ];
    }

    // Eliminar grupos vacíos (menos los oficiales si se desea mantener la estructura)
    foreach ($grupos as $k => $v) {
        if ($v['total'] === 0 && $k === 'OTROS') unset($grupos[$k]);
    }

    return array_values($grupos);
}

/**
 * Obtener activos agrupados por Riesgo Biomédico (Alto/Medio/Bajo)
 */
function getAssetsByRiesgoBiomedico(): array
{
    $assets = getAllAssets();
    $grupos = ['Alto' => 0, 'Medio' => 0, 'Bajo' => 0, 'N/A' => 0];
    foreach ($assets as $a) {
        $r = $a['riesgo_biomedico'] ?? 'N/A';
        $key = isset($grupos[$r]) ? $r : 'N/A';
        $grupos[$key]++;
    }
    return $grupos;
}
