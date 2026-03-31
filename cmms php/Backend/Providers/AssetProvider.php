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
require_once __DIR__ . '/../Repositories/WorkOrderRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Models/AssetStatus.php';
require_once __DIR__ . '/../Models/Criticality.php';
require_once __DIR__ . '/../Services/ReliabilityService.php';
require_once __DIR__ . '/../Services/CatalogService.php';
// La inclusión se hace al inicio para todas las funciones del provider
require_once __DIR__ . '/../Models/AssetEntity.php';
require_once __DIR__ . '/../../includes/reliability_metrics.php';

use Backend\Repositories\AssetRepository;
use Backend\Services\ReliabilityService;
use Backend\Services\CatalogService;

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
function getAssetById($id): ?array
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
 * Obtener marcas únicas con sus respectivos conteos
 */
function getBrandCounts(): array
{
    $repo = new AssetRepository();
    return $repo->getCountsByBrand();
}

/**
 * Obtener ubicaciones únicas con sus respectivos conteos
 */
function getLocationCounts(): array
{
    $repo = new AssetRepository();
    return $repo->getCountsByLocation();
}

/**
 * Obtener marcas únicas (legacy)
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
    return ['CRITICAL', 'RELEVANT', 'LOW'];
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
 * Obtener estadísticas financieras consolidadas (Dinámicas) - OPTIMIZADO
 */
function getFinancialStats(?array $assets = null): array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        return [
            'valor_inventario' => 0,
            'valor_reposicion' => 0,
            'costo_mantenimiento_anual' => 0,
            'tco_avg' => 0,
            'obsolescencia_proxima' => 0,
            'mantenimiento_mora' => 0,
            'missed_pms_count' => 0,
            'ahorro_in_house' => 0,
            'pm_compliance_rate' => 100
        ];
    }

    require_once __DIR__ . '/../../includes/constants.php';
    require_once __DIR__ . '/WorkOrderProvider.php';

    $assets = $assets ?: getAllAssets();
    $totalVal = 0;
    $obsolescencia = 0;
    $costoMantenimiento = 0;

    $assetIds = [];
    foreach ($assets as $asset) {
        $totalVal += $asset['acquisition_cost'] ?? 0;
        $costoMantenimiento += $asset['annual_maint_cost'] ?? 0;
        if (($asset['useful_life_pct'] ?? 0) <= 0) { // Vida útil excedida (Obsolescente)
            $obsolescencia++;
        }
        if (!empty($asset['id'])) {
            $assetIds[] = $asset['id'];
        }
    }

    if (!empty($assetIds)) {
        try {
            $db = \Backend\Core\DatabaseService::getInstance();
            $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
            $stmtHours = $db->prepare("SELECT SUM(duration_hours) FROM work_orders WHERE status = 'Terminada' AND asset_id IN ($placeholders)");
            $stmtHours->execute($assetIds);
            $totalHours = (float)$stmtHours->fetchColumn();
            $costoMantenimiento += ($totalHours * 25000); // 25000 CLP estimacion tarifa hh tecnico
        } catch(Exception $e) {}
    }

    $totalReposicion = $totalVal * REPLACEMENT_COST_FACTOR;

    // "Mantenimiento Mora" -> Detectar preventivos antiguos (HEC_MORA_DAYS)
    // Sincronizado con los estados reales: 'En Curso', 'En Espera'
    $db = \Backend\Core\DatabaseService::getInstance();
    $thresholdDate = date('Y-m-d', strtotime('-' . HEC_MORA_DAYS . ' days'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM work_orders WHERE type = 'Preventiva' AND status IN ('En Curso', 'En Espera') AND created_date < :threshold");
    $stmt->execute(['threshold' => $thresholdDate]);
    $missedPMs = (int)$stmt->fetchColumn();

    $mantenimientoMora = $missedPMs * PENALTY_MISSED_PM;

    return [
        'valor_inventario' => $totalVal,
        'valor_reposicion' => $totalReposicion,
        'costo_mantenimiento_anual' => $costoMantenimiento,
        'tco_avg' => count($assets) > 0 ? $totalVal / count($assets) : 0,
        'obsolescencia_proxima' => $obsolescencia,
        'mantenimiento_mora' => $mantenimientoMora,
        'missed_pms_count' => $missedPMs,
        'ahorro_in_house' => 67, // Valor estimado de ahorro por gestión propia
        'pm_compliance_rate' => getPMComplianceRate()
    ];
}

/**
 * Calcula la tasa de cumplimiento de preventivos (NotebookLM KPI)
 */
function getPMComplianceRate(): float
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $total = (int)$db->query("SELECT COUNT(*) FROM work_orders WHERE type = 'Preventiva'")->fetchColumn();
        if ($total === 0) return 100.0;

        $completed = (int)$db->query("SELECT COUNT(*) FROM work_orders WHERE type = 'Preventiva' AND status = 'Terminada'")->fetchColumn();
        return round(($completed / $total) * 100, 1);
    } catch (Exception $e) {
        return 0.0;
    }
}

/**
 * Obtiene estadísticas de cobertura de mantenimiento preventivo (NotebookLM)
 */
function getPMCoverageStats(): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $now = date('Y-m-d');
        
        $sql = "SELECT 
                    SUM(CASE WHEN next_maintenance_date >= :now1 THEN 1 ELSE 0 END) as al_dia,
                    SUM(CASE WHEN next_maintenance_date < :now2 THEN 1 ELSE 0 END) as atrasado,
                    SUM(CASE WHEN next_maintenance_date IS NULL OR next_maintenance_date = '' THEN 1 ELSE 0 END) as sin_plan
                FROM assets WHERE en_uso = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['now1' => $now, 'now2' => $now]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['al_dia' => 0, 'atrasado' => 0, 'sin_plan' => 0];
    } catch (Exception $e) {
        return ['al_dia' => 0, 'atrasado' => 0, 'sin_plan' => 0];
    }
}

/**
 * Obtiene los IDs de activos que tienen OTs activas (En Curso o En Espera)
 */
function getActiveWorkOrderAssetIds(): array
{
    return (new \Backend\Repositories\WorkOrderRepository())->getActiveWorkOrderAssetIds();
}

/**
 * Contar activos por estado - OPTIMIZADO y UNIFICADO
 */
function countAssetsByStatus(?array $assets = null): array
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

    $activeOtAssetIds = getActiveWorkOrderAssetIds();

    if (!$assets) {
        // Si no hay assets en memoria, usamos el repo pero ajustamos dinámicamente si es posible
        // Por simplicidad en este paso, cargamos todos y procesamos, o podríamos inyectar el ajuste en el repo.
        // Dado que son ~3000 assets, cargarlos es aceptable.
        $assets = getAllAssets();
    }

    $counts = [
        'total' => count($assets),
        'operative' => 0,
        'maintenance' => 0,
        'no_operative' => 0,
        'with_obs' => 0
    ];

    foreach ($assets as $a) {
        $status = $a['status'] ?? '';
        $id = (int)($a['id'] ?? 0);
        
        // INTERVENCIÓN ACTIVA: Si tiene OT en curso, reportamos como mantenimiento
        if ($id > 0 && in_array($id, $activeOtAssetIds)) {
            $counts['maintenance']++;
            continue;
        }

        if (in_array($status, ['OPERATIVE', 'BUENO'])) $counts['operative']++;
        elseif ($status === 'MAINTENANCE') $counts['maintenance']++;
        elseif (in_array($status, ['NO_OPERATIVE', 'MALO'])) $counts['no_operative']++;
        elseif (in_array($status, ['OPERATIVE_WITH_OBS', 'REGULAR'])) $counts['with_obs']++;
    }

    return $counts;
}

/**
 * Contar activos por criticidad
 */
function countAssetsByCriticality(?array $assets = null): array
{
    $assets = $assets ?: getAllAssets();
    $counts = ['CRITICAL' => 0, 'RELEVANT' => 0, 'LOW' => 0, 'NA' => 0];
    foreach ($assets as $a) {
        $key = $a['criticality'] ?? 'NA';
        if (isset($counts[$key]))
            $counts[$key]++;
        elseif ($key === 'BAJO' || $key === 'BAJA')
            $counts['LOW']++;
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
function getExpiredOperativeCount(?array $assets = null): int
{
    $assets = $assets ?: getAllAssets();
    return count(array_filter($assets, function ($a) {
        if (isset($a['years_remaining'])) {
            return $a['years_remaining'] <= 0;
        }
        return ($a['useful_life_pct'] ?? 1) <= 0;
    }));
}

/**
 * Obtener equipos en riesgo de capital (< 20% vida restante)
 */
function getCapitalRiskCount(?array $assets = null): int
{
    $assets = $assets ?: getAllAssets();
    return count(array_filter($assets, function ($a) {
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
function getAssetObservations($asset_id): array
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
function getAssetDocuments($asset_id): array
{
    try {
        $db = \Backend\Core\DatabaseService::getInstance();
        $stmt = $db->prepare(
            "SELECT ota.*, wo.id as wo_code
             FROM ot_attachments ota
             LEFT JOIN work_orders wo ON ota.work_order_id = wo.id
             WHERE ota.asset_id = :asset_id
             ORDER BY ota.uploaded_at DESC"
        );
        $stmt->execute(['asset_id' => (string)$asset_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'name'    => $r['caption'] ?: basename($r['file_path']),
            'type'    => match (true) {
                str_starts_with($r['file_type'] ?? '', 'image/') => 'Foto',
                default => 'Documento'
            },
            'size'    => '',
            'date'    => substr($r['uploaded_at'] ?? '', 0, 10),
            'url'     => $r['file_path'] ?? '',
            'ot_ref'  => $r['wo_code'] ?? '',
            'category' => $r['category'] ?? 'evidencia',
        ], $rows);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener métricas de confiabilidad predictiva basadas en Weibull
 */
function getAssetReliabilityMetrics($asset_id, $assetData = null): array
{
    $asset = $assetData ?: getAssetById($asset_id);
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

    $daysSinceLastFailure = 0;
    $now = new DateTime();

    if ($lastFailure) {
        $lastDate = new DateTime($lastFailure);
        $daysSinceLastFailure = (int)$lastDate->diff($now)->days;
    } elseif (!empty($asset['fecha_instalacion'])) {
        // Fallback: Si no hay fallas, usar fecha de instalación
        $instDate = new DateTime($asset['fecha_instalacion']);
        $daysSinceLastFailure = (int)$instDate->diff($now)->days;
    } else {
        $daysSinceLastFailure = 30; // Hard fallback
    }

    // Asegurar que las métricas de confiabilidad estén cargadas
    if (!function_exists('calcularGE')) {
        require_once __DIR__ . '/../../includes/reliability_metrics.php';
    }

    return [
        'reliability' => ReliabilityService::calculateReliability($daysSinceLastFailure, $beta, $eta),
        'failure_prob_30d' => ReliabilityService::predictFailureProbability($daysSinceLastFailure, 30, $beta, $eta),
        'hazard_rate' => ReliabilityService::calculateHazardRate($daysSinceLastFailure, $beta, $eta),
        'beta' => $beta,
        'eta' => $eta,
        'days_in_service' => $daysSinceLastFailure,
        'data_quality' => count($history) >= 5 ? 'High' : (count($history) > 0 ? 'Medium' : 'Low (Suggested)'),
        'ge_score' => $ge = calcularGE($asset),
        'pm_frequency' => obtenerFrecuenciaMP($ge)
    ];
}

/**
 * Obtener métricas de rendimiento específicas de un activo
 */
function getAssetPerformanceMetrics($asset_id, $assetData = null): array
{
    $asset = $assetData ?: getAssetById($asset_id);
    if (!$asset) return [];

    $acquisition  = $asset['acquisition_cost'] ?? 0;
    $reliability  = getAssetReliabilityMetrics($asset_id, $asset);

    return [
        'uptime'              => UPTIME_GOAL,
        'depreciacion_anual'  => $acquisition / (($asset['total_useful_life'] ?? 1) ?: 1),
        'valor_residual'      => $acquisition * RESIDUAL_VALUE_FACTOR,
        'costo_mtto_estimado' => ($asset['annual_maint_cost'] ?? 0) > 0
            ? $asset['annual_maint_cost']
            : ($acquisition * MAINTENANCE_COST_FACTOR),
        'reliability_index'   => $reliability['reliability']     ?? 1.0,
        'next_failure_prob'   => $reliability['failure_prob_30d'] ?? 0.05,
        'ge_score'            => $reliability['ge_score']    ?? null,
        'pm_frequency'        => $reliability['pm_frequency'] ?? null,
    ];
}

/**
 * Obtener los activos con mayor riesgo de falla (Top N) - OPTIMIZADO
 */
function getTopRiskAssets(int $limit = 5): array
{
    $assets = getAllAssets();
    $riskList = [];

    foreach ($assets as $asset) {
        // Filtrar activos que pueden tener riesgo (en uso y operativos)
        $status = $asset['status'] ?? '';
        if ($status === 'OPERATIVE' || $status === 'BUENO') {
            // Pasamos el array $asset directamente para evitar N+1
            $metrics = getAssetReliabilityMetrics($asset['id'], $asset);
            $riskList[] = array_merge($asset, [
                'failure_prob' => $metrics['failure_prob_30d'] ?? 0,
                'days_in_service' => $metrics['days_in_service'] ?? 0
            ]);
        }
    }

    // Ordenar por probabilidad de falla DESC
    usort($riskList, function ($a, $b) {
        return ($b['failure_prob'] ?? 0) <=> ($a['failure_prob'] ?? 0);
    });

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
 * Generar un HEC ID basado en familia y criticidad
 */
function generateAssetHecId(array $data): string
{
    $familia = $data['riesgo_ge'] ?? 'GEN';
    $criticality = $data['criticality'] ?? 'LOW';

    $db = \Backend\Core\DatabaseService::getInstance();

    $famClean = preg_replace('/[^A-Za-z0-9]/', '', $familia);
    $prefijoFamilia = mb_strtoupper(mb_substr($famClean . 'XXX', 0, 3));

    // Refinado para "APOYO" según solicitud del usuario
    if ($prefijoFamilia === 'APO') {
        // Normalizar searchKey para ignorar acentos, incluyendo el NOMBRE del equipo
        $equipoName = $data['name'] ?? ($data['equipo'] ?? '');
        $searchKey = mb_strtoupper($familia . ' ' . $equipoName . ' ' . ($data['subclase'] ?? ''));
        $searchKey = strtr($searchKey, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N'
        ]);

        if (str_contains($searchKey, 'DIAG')) $prefijoFamilia = 'APD';
        elseif (str_contains($searchKey, 'INTERV')) $prefijoFamilia = 'API';
        elseif (str_contains($searchKey, 'TERAP')) $prefijoFamilia = 'APT';
        elseif (str_contains($searchKey, 'CRITIC')) $prefijoFamilia = 'APC';
        elseif (str_contains($searchKey, 'QUIRUR')) $prefijoFamilia = 'APQ';
        elseif (str_contains($searchKey, 'ENDOSCO')) $prefijoFamilia = 'APE';
        elseif (str_contains($searchKey, 'INDUST')) $prefijoFamilia = 'API';
        elseif (str_contains($searchKey, 'GENERAL')) $prefijoFamilia = 'APG';
    }

    $prefijoRiesgo = match (strtoupper($criticality)) {
        'CRITICAL', 'CRÍTICO', 'CRITICO' => 'CRI',
        'RELEVANT', 'RELEVANTE' => 'REL',
        'LOW', 'BAJO', 'NO APLICA', '>12', '12', 'NA' => 'NA',
        default => 'NA'
    };

    $basePrefix = "{$prefijoFamilia}-{$prefijoRiesgo}";

    $stmt = $db->prepare("SELECT hec_id FROM assets WHERE hec_id LIKE :prefix ORDER BY id DESC LIMIT 1");
    $stmt->execute(['prefix' => "{$basePrefix}-%"]);
    $lastHecId = $stmt->fetchColumn();

    $secuencia = 1;
    if ($lastHecId) {
        $parts = explode('-', $lastHecId);
        if (count($parts) === 3 && is_numeric($parts[2])) {
            $secuencia = (int)$parts[2] + 1;
        }
    }

    return sprintf("%s-%05d", $basePrefix, $secuencia);
}

/**
 * Guardar un nuevo activo en la base de datos
 */
function saveAsset(array $data): string|int|bool
{
    if (isReadOnly()) {
        throw new Exception("ACCESO_DENEGADO: El perfil actual no tiene permisos para crear activos.");
    }
    if (empty($data['hec_id'])) {
        $data['hec_id'] = generateAssetHecId($data);
    }

    $repo = new AssetRepository();
    return $repo->create($data);
}

/**
 * Actualizar información técnica de un activo (Marca, Modelo, Serie, etc.)
 */
function updateAssetInfo($id, array $data): bool
{
    if (isReadOnly()) {
        throw new Exception("ACCESO_DENEGADO: El perfil actual no tiene permisos para modificar activos.");
    }
    $repo = new AssetRepository();
    return $repo->partialUpdate($id, $data);
}

/**
 * Eliminar un activo (Hard Delete - No recomendado para activos con historia)
 */
function deleteAsset($id): bool
{
    $repo = new AssetRepository();
    return $repo->delete($id);
}

/**
 * Borrado suave de un activo (Control-Z compatible)
 */
function softDeleteAsset($id): bool
{
    if (isReadOnly()) {
        throw new Exception("ACCESO_DENEGADO: El perfil actual no tiene permisos para dar de baja activos.");
    }
    $repo = new AssetRepository();
    // Al dar de baja, marcamos como RETIRED y en_uso=0 vía repositorio
    return $repo->softDelete((string)$id);
}

/**
 * Restaurar un activo borrado suavemente
 */
function restoreAsset($id): bool
{
    $repo = new AssetRepository();
    // Al restaurar, volvemos a OPERATIVE y en_uso=1 vía partialUpdate directo
    return $repo->partialUpdate((string)$id, ['en_uso' => 1, 'status' => 'OPERATIVE']);
}


/**
 * Detectar si un equipo es de Monitoreo o No Monitoreo según su nombre.
 * Utiliza el servicio centralizado CatalogService v4.5 Enterprise.
 */
function _detectarMonitoreo(string $name): string
{
    $data = CatalogService::classifyEquipment($name);
    return $data[2] ? 'Monitoreo' : 'No Monitoreo'; // Índice 2 es es_monitoreo
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
    $activeOtAssetIds = (new \Backend\Repositories\WorkOrderRepository())->getActiveWorkOrderAssetIds();
    $grupos = [];

    // Priorizar clases oficiales
    $clasesOficiales = getCategoryOptions();
    foreach ($clasesOficiales as $c) {
        $key = mb_strtoupper(trim($c), 'UTF-8');
        $grupos[$key] = [
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
    if (!isset($grupos['OTROS'])) {
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
    }

    foreach ($assets as $asset) {
        $origGrupo = mb_strtoupper(trim($asset['riesgo_ge'] ?? 'OTROS'), 'UTF-8');
        $grupo = $origGrupo;

        // REFINAMIENTO ESPECIALIDAD: Si es OTROS o no existe en catálogo, probamos con subclase
        if ($grupo === 'OTROS' || !isset($grupos[$grupo])) {
            $fallback = mb_strtoupper(trim($asset['subclase'] ?? ''), 'UTF-8');
            if ($fallback && isset($grupos[$fallback])) {
                $grupo = $fallback;
            } else {
                // [HEURÍSTICA AVANZADA] Si sigue sin reconocerse, buscamos por nombre de equipo
                $nameLower = mb_strtoupper(trim($asset['name'] ?? ''), 'UTF-8');
                
                if (str_contains($nameLower, 'MONITOR') || str_contains($nameLower, 'OXIMETRO') || str_contains($nameLower, 'PULSI') || str_contains($nameLower, 'VITALES') || str_contains($nameLower, 'DESFIBR') || str_contains($nameLower, 'CARDIO') || str_contains($nameLower, 'ECG') || str_contains($nameLower, 'EKG')) {
                    $grupo = 'MONITOREO';
                } elseif (str_contains($nameLower, 'RAYOS') || str_contains($nameLower, 'ECOGRAFO') || str_contains($nameLower, 'MAMOGRAFO') || str_contains($nameLower, 'ARCO EN C') || str_contains($nameLower, 'IMAGEN') || str_contains($nameLower, 'RX') || str_contains($nameLower, 'RESONANCIA') || str_contains($nameLower, 'TOMO') || str_contains($nameLower, 'SCANNER')) {
                    $grupo = 'IMAGENOLOGÍA';
                } elseif (str_contains($nameLower, 'ESTERIL') || str_contains($nameLower, 'AUTOCLAVE') || str_contains($nameLower, 'LAVADORA') || str_contains($nameLower, 'SECADORA') || str_contains($nameLower, 'TERMO') || str_contains($nameLower, 'SELLADORA')) {
                    $grupo = 'ESTERILIZACIÓN';
                } elseif (str_contains($nameLower, 'DENTAL') || str_contains($nameLower, 'SILLON') || str_contains($nameLower, 'CURADO') || str_contains($nameLower, 'EQUIPO DENTAL')) {
                    $grupo = 'ODONTOLOGÍA';
                } elseif (str_contains($nameLower, 'LABORATORIO') || str_contains($nameLower, 'FARMACIA') || str_contains($nameLower, 'CENTRIFUGA') || str_contains($nameLower, 'REFRIGERADOR') || str_contains($nameLower, 'BAÑO') || str_contains($nameLower, 'ANALIZADOR') || str_contains($nameLower, 'COAGULO') || str_contains($nameLower, 'MICROSC') || str_contains($nameLower, 'EXTRACTOR') || str_contains($nameLower, 'CONGELADOR') || str_contains($nameLower, 'AGITADOR') || str_contains($nameLower, 'GABINETE')) {
                    $grupo = 'LABORATORIO / FARMACIA';
                } elseif (str_contains($nameLower, 'QUIRURGI') || str_contains($nameLower, 'BISTURI') || str_contains($nameLower, 'ANESTESIA') || str_contains($nameLower, 'MESA CX') || str_contains($nameLower, 'VAPORIZA') || str_contains($nameLower, 'LARINGOS') || str_contains($nameLower, 'LAMPARA') || str_contains($nameLower, 'LASER') || str_contains($nameLower, 'ELECTROBISTURI')) {
                    $grupo = 'APOYO QUIRÚRGICO';
                } elseif (str_contains($nameLower, 'TERAPIA') || str_contains($nameLower, 'VENTILADOR') || str_contains($nameLower, 'BOMBA') || str_contains($nameLower, 'INFUSION') || str_contains($nameLower, 'JERINGA') || str_contains($nameLower, 'PCP') || str_contains($nameLower, 'INCUBADORA') || str_contains($nameLower, 'FOTO') || str_contains($nameLower, 'HUMIDIF') || str_contains($nameLower, 'HIPOTERMIA') || str_contains($nameLower, 'DIALISIS')) {
                    $grupo = 'APOYO TERAPÉUTICO';
                } elseif (str_contains($nameLower, 'DIAGNOSTICO') || str_contains($nameLower, 'ELECTROCARDI') || str_contains($nameLower, 'OFTAL') || str_contains($nameLower, 'AUDI') || str_contains($nameLower, 'POTENCIAL') || str_contains($nameLower, 'HOLTER') || str_contains($nameLower, 'BERA')) {
                    $grupo = 'APOYO DIAGNÓSTICO';
                } elseif (str_contains($nameLower, 'INDUS') || str_contains($nameLower, 'PLANTA') || str_contains($nameLower, 'AIRE') || str_contains($nameLower, 'AGUA') || str_contains($nameLower, 'GRUPO') || str_contains($nameLower, 'CLIMA') || str_contains($nameLower, 'CALDERA')) {
                    $grupo = 'APOYO INDUSTRIAL';
                } elseif (str_contains($nameLower, 'REHAB') || str_contains($nameLower, 'FISIO') || str_contains($nameLower, 'ULTRASONIDO') || str_contains($nameLower, 'MESA DE TRAC') || str_contains($nameLower, 'TENS')) {
                    $grupo = 'MED. FIS. REHABILITACIÓ';
                } elseif (str_contains($nameLower, 'CAMILLA') || str_contains($nameLower, 'MOBILIARIO') || str_contains($nameLower, 'SILLA')) {
                    $grupo = 'MOBILIARIO';
                } else {
                    $grupo = 'OTROS';
                }

                // Verificación final del grupo heurístico contra el catálogo
                if ($grupo !== 'OTROS' && !isset($grupos[$grupo])) {
                    // Si no existe tal cual, intentamos búsqueda fuzzy en las llaves del catálogo
                    foreach (array_keys($grupos) as $key) {
                        if (str_contains($key, $grupo) || str_contains($grupo, $key)) {
                            $grupo = $key;
                            break;
                        }
                    }
                }
            }
        }

        $grupos[$grupo]['total']++;
        $grupos[$grupo]['valor_total'] += (float)($asset['acquisition_cost'] ?? 0);

        // DINAMISMO DE ESTADO: Si tiene OT activa, reportamos como MAINTENANCE
        $id = $asset['id'] ?? ($asset['inventory_id'] ?? null);
        $status = $asset['status'] ?? '';
        $isUnderMaintenance = in_array($id, $activeOtAssetIds);

        if ($isUnderMaintenance) {
            // No incrementamos operativos si está en mantenimiento dinámico
        } elseif (in_array($status, ['OPERATIVE', 'BUENO'])) {
            $grupos[$grupo]['operativos']++;
        }

        if (($asset['criticality'] ?? '') === 'CRITICAL')  $grupos[$grupo]['criticos']++;
        if (($asset['criticality'] ?? '') === 'RELEVANT')  $grupos[$grupo]['relevantes']++;
        
        // OBSOLETOS: % Vida Útil <= 0
        if (($asset['useful_life_pct'] ?? 100) <= 0)       $grupos[$grupo]['obsoletos']++;

        $grupos[$grupo]['equipos'][] = [
            'id'          => $asset['id'],
            'name'        => $asset['name'],
            'brand'       => $asset['brand'] ?? '-',
            'model'       => $asset['model'] ?? '-',
            'location'    => $asset['location'] ?? '-',
            'status'      => $isUnderMaintenance ? 'MAINTENANCE' : ($asset['status'] ?? '-'),
            'criticality' => $asset['criticality'] ?? '-',
            'crit_label'  => $critLabel[$asset['criticality']] ?? ($asset['criticality'] ?? '-'),
            'vida_util'   => $asset['useful_life_pct'] ?? 0,
            'costo'       => $asset['acquisition_cost'] ?? 0,
            'riesgo_biomedico' => $asset['riesgo_biomedico'] ?? 'N/A',
        ];
    }

    // Eliminar grupos vacíos (menos los oficiales)
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

/**
 * Obtener datos para el gráfico de Pareto de Downtime (Inactividad Acumulada)
 */
function getDowntimeParetoData(): array
{
    require_once __DIR__ . '/WorkOrderProvider.php';
    $db = \Backend\Core\DatabaseService::getInstance();

    // Sumar horas de falla por riesgo_ge (Familia)
    $query = "
        SELECT a.riesgo_ge, SUM(wo.duration_hours) as total_downtime
        FROM work_orders wo
        JOIN assets a ON wo.asset_id = a.id
        WHERE wo.status = 'Terminada' AND wo.type = 'Correctiva'
        GROUP BY a.riesgo_ge
        ORDER BY total_downtime DESC
    ";

    $results = $db->query($query)->fetchAll();
    $families = [];
    foreach ($results as $row) {
        $families[$row['riesgo_ge'] ?: 'OTROS'] = (float)$row['total_downtime'];
    }

    $totalDowntime = array_sum($families);
    $runningTotal = 0;
    $paretoData = [];

    foreach ($families as $name => $hours) {
        $runningTotal += $hours;
        $pct = $totalDowntime > 0 ? ($runningTotal / $totalDowntime) * 100 : 0;
        $paretoData[] = [
            'family' => $name,
            'hours' => round($hours, 1),
            'cumulative_pct' => round($pct, 1)
        ];
    }

    return $paretoData;
}

/**
 * Obtener Ola de Obsolescencia (Pérdida de Uptime Proyectada 2024-2030)
 */
function getAvailabilityLossWaveData(): array
{
    $assets = getAllAssets();
    $wave = [];

    for ($y = 2024; $y <= 2030; $y++) {
        $wave[$y] = ['expired_count' => 0, 'uptime_loss_risk' => 0.0];
    }

    foreach ($assets as $asset) {
        $yearsLeft = (int)($asset['years_remaining'] ?? 5);
        $expiryYear = 2024 + $yearsLeft;

        if ($expiryYear >= 2024 && $expiryYear <= 2030) {
            $wave[$expiryYear]['expired_count']++;
            // El riesgo de pérdida de uptime aumenta con la obsolescencia. 
            // Estimamos un 15% de indisponibilidad adicional por equipo obsoleto.
            $wave[$expiryYear]['uptime_loss_risk'] += 15.0;
        }
    }

    return $wave;
}

/**
 * Datos para el Waterfall de Tiempo Operativo (Uptime Erosion)
 */
function getUptimeWaterfallData(): array
{
    require_once __DIR__ . '/WorkOrderProvider.php';
    $totalHours = 30 * 24; // 1 mes teórico por equipo promedio
    $downtime = getTotalDowntimeHours();

    // Estimaciones para completar el reporte de tiempo
    $preventiveHours = $downtime * 0.4; // Ratio preventivo est.
    $logisticsHours = $downtime * 0.2;  // Tiempo de espera repuestos est.

    $netUptime = max(0, $totalHours - $downtime - $preventiveHours - $logisticsHours);

    return [
        ['label' => 'Teórico (100%)', 'value' => $totalHours, 'type' => 'base'],
        ['label' => 'Fallas (Reparación)', 'value' => -$downtime, 'type' => 'reduction'],
        ['label' => 'Mantenimiento Prev.', 'value' => -$preventiveHours, 'type' => 'reduction'],
        ['label' => 'Demoras Logísticas', 'value' => -$logisticsHours, 'type' => 'reduction'],
        ['label' => 'Uptime Efectivo', 'value' => $netUptime, 'type' => 'total']
    ];
}
