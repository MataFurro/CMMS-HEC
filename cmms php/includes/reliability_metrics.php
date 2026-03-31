<?php
// includes/reliability_metrics.php
// Funciones para cálculo de métricas de confiabilidad basadas en análisis Weibull

/**
 * Calcula MTBF (Mean Time Between Failures) para un equipo
 * 
 * @param string $equipo_id ID del equipo
 * @param array $otCorrectivas Array de OT correctivas
 * @return float|null MTBF en días, null si no hay suficientes datos
 */
function calcularMTBF($equipo_id, $otCorrectivas)
{
    $fallos = array_filter($otCorrectivas, function ($ot) use ($equipo_id) {
        return ($ot['asset_id'] ?? '') === $equipo_id && !empty($ot['date']);
    });
    $numFallos = count($fallos);

    if ($numFallos <= 1)
        return null; // Necesita al menos 2 fallos

    // Ordenar por date
    usort($fallos, function ($a, $b) {
        $t1 = strtotime($a['date'] ?? '');
        $t2 = strtotime($b['date'] ?? '');
        return $t1 - $t2;
    });

    $first = reset($fallos);
    $last = end($fallos);

    // Tiempo entre primera y última falla (en días)
    $t1 = strtotime($first['date'] ?? '');
    $t2 = strtotime($last['date'] ?? '');

    if (!$t1 || !$t2)
        return null;

    $tiempoTotal = ($t2 - $t1) / 86400;

    return $tiempoTotal / ($numFallos - 1);
}

/**
 * Calcula MTTR (Mean Time To Repair) para un equipo
 * 
 * @param string $equipo_id ID del equipo
 * @param array $otCorrectivas Array de OT correctivas con duración
 * @return float MTTR en horas
 */
function calcularMTTR($equipo_id, $otCorrectivas)
{
    $fallos = array_filter($otCorrectivas, fn($ot) => ($ot['asset_id'] ?? '') === $equipo_id);
    $duraciones = array_column($fallos, 'duracion_horas');

    return count($duraciones) > 0 ? array_sum($duraciones) / count($duraciones) : 0;
}

/**
 * Calcula disponibilidad inherente (A_in)
 * 
 * @param float $MTBF en días
 * @param float $MTTR en horas
 * @return float Disponibilidad (0-1)
 */
function calcularDisponibilidad($MTBF, $MTTR)
{
    if ($MTBF === null || $MTBF == 0)
        return 0;

    // Convertir MTBF de días a horas
    $MTBF_horas = $MTBF * 24;

    return $MTBF_horas / ($MTBF_horas + $MTTR);
}

/**
 * Calcula el Número de Gestión del Equipo (GE) según Fennigkoh-Smith
 * 
 * @param array $equipo Array con campos: funcion_ge, riesgo_ge_score, mantenimiento_ge
 * @return int GE (si >= 12, equipo es prioritario)
 */
function calcularGE($equipo)
{
    if (is_object($equipo)) {
        $equipo = method_exists($equipo, 'toArray') ? $equipo->toArray() : (array)$equipo;
    }

    $funcion = $equipo['funcion_ge'] ?? ($equipo['funcion'] ?? 5);
    $riesgo = $equipo['riesgo_ge_score'] ?? ($equipo['riesgo'] ?? 3);
    $mantenimiento = $equipo['mantenimiento_ge'] ?? ($equipo['mantenimiento'] ?? 3);

    return (int)$funcion + (int)$riesgo + (int)$mantenimiento;
}

/**
 * Determina la frecuencia de mantenimiento preventivo según puntuación GE
 * 
 * @param int $ge Puntuación GE
 * @return string Etiqueta de frecuencia
 */
function obtenerFrecuenciaMP($ge)
{
    if ($ge >= 12) return 'Semestral (Alta)';
    if ($ge >= 9)  return 'Anual (Media)';
    return 'Según necesidad / Bianual (Baja)';
}

/**
 * Retorna la frecuencia en meses para cálculos de programación
 */
function obtenerMesesFrecuencia($ge)
{
    if ($ge >= 12) return 6;
    if ($ge >= 9)  return 12;
    return 24;
}

/**
 * Calcula GE ajustado según modelo Wang-Levenson
 * 
 * @param array $equipo Array con campos: prioridad, mantenimiento, tasaUso, riesgo
 * @return float GE ajustado
 */
function calcularGEAjustado($equipo)
{
    if (is_object($equipo)) {
        $equipo = method_exists($equipo, 'toArray') ? $equipo->toArray() : (array)$equipo;
    }

    $prioridad = $equipo['prioridad'] ?? 5;
    $mantenimiento = $equipo['mantenimiento'] ?? 3;
    $tasaUso = $equipo['tasaUso'] ?? 0.5; // 0-1
    $riesgo = $equipo['riesgo'] ?? 3;

    return ($prioridad + 2 * $mantenimiento) * $tasaUso + (2 * $riesgo);
}

/**
 * Genera alertas basadas en umbrales de confiabilidad
 * 
 * @param float $MTBF en días
 * @param float $MTTR en horas
 * @param float $disponibilidad 0-1
 * @param int $GE Número de Gestión
 * @return array Array de alertas
 */
function generarAlertas($MTBF, $MTTR, $disponibilidad, $GE)
{
    $alertas = [];

    // 1. MTBF bajo
    if ($MTBF !== null && $MTBF < 30) {
        $alertas[] = [
            'tipo' => 'warning',
            'icono' => 'warning',
            'mensaje' => 'MTBF bajo: ' . round($MTBF, 1) . ' días',
            'accion' => 'Revisar mantenimiento preventivo',
            'color' => 'amber-500'
        ];
    }

    // 2. MTTR alto
    if ($MTTR > 8) {
        $alertas[] = [
            'tipo' => 'danger',
            'icono' => 'schedule',
            'mensaje' => 'MTTR alto: ' . round($MTTR, 1) . ' horas',
            'accion' => 'Optimizar proceso de reparación',
            'color' => 'red-500'
        ];
    }

    // 3. Disponibilidad baja
    if ($disponibilidad < 0.95 && $disponibilidad > 0) {
        $alertas[] = [
            'tipo' => 'critical',
            'icono' => 'error',
            'mensaje' => 'Disponibilidad: ' . round($disponibilidad * 100, 1) . '%',
            'accion' => 'Equipo requiere atención inmediata',
            'color' => 'red-600'
        ];
    }

    // 4. GE alto (equipo crítico)
    if ($GE >= 15) {
        $alertas[] = [
            'tipo' => 'info',
            'icono' => 'priority_high',
            'mensaje' => 'Equipo crítico (GE=' . $GE . ')',
            'accion' => 'Priorizar en programa de mantenimiento',
            'color' => 'medical-blue'
        ];
    }

    return $alertas;
}

/**
 * Calcula métricas globales del sistema de forma optimizada
 * 
 * @param array $assets Array de equipos
 * @param array $otCorrectivas Array de OT correctivas
 * @return array Métricas globales
 */
function calcularMetricasGlobales($assets, $otCorrectivas)
{
    // Agrupar OTs por activo una sola vez
    $otsPorActivo = [];
    foreach ($otCorrectivas as $ot) {
        $aid = $ot['asset_id'] ?? '';
        if ($aid) {
            $otsPorActivo[$aid][] = $ot;
        }
    }

    $mtbfs = [];
    $mttrs = [];
    $disponibilidades = [];
    $totalDowntime = 0;
    
    $now = time();

    foreach ($assets as $asset) {
        $assetId = $asset['id'] ?? null;
        if (!$assetId) continue;

        $otsActivo = $otsPorActivo[$assetId] ?? [];

        // Calcular días en servicio
        $instDate = !empty($asset['fecha_instalacion']) ? strtotime($asset['fecha_instalacion']) : strtotime('-1 year');
        $daysInService = max(1, ($now - $instDate) / 86400);
        $numFallos = count($otsActivo);

        // MTBF dinámico basado en tiempo en servicio total y cantidad de fallos
        $mtbf = $numFallos > 0 ? $daysInService / $numFallos : $daysInService;
        $mttr = calcularMTTR_Internal($assetId, $otsActivo);

        // Sumar downtime real
        $assetDowntime = array_sum(array_column($otsActivo, 'duration_hours'));
        $totalDowntime += $assetDowntime;

        if ($mtbf !== null) {
            $mtbfs[] = $mtbf;
            $mttrs[] = $mttr;
            $disponibilidades[] = calcularDisponibilidad($mtbf, $mttr);
        }
    }

    return [
        'mtbf_promedio' => count($mtbfs) > 0 ? array_sum($mtbfs) / count($mtbfs) : 0,
        'mttr_promedio' => count($mttrs) > 0 ? array_sum($mttrs) / count($mttrs) : 0,
        'disponibilidad_promedio' => count($disponibilidades) > 0 ? array_sum($disponibilidades) / count($disponibilidades) : 0,
        'total_downtime_hours' => $totalDowntime,
        'equipos_analizados' => count($mtbfs)
    ];
}

/**
 * Versión interna de calcularMTBF que ya recibe las OTs filtradas
 */
function calcularMTBF_Internal($equipo_id, $fallos)
{
    $numFallos = count($fallos);
    if ($numFallos <= 1) return null;

    usort($fallos, function ($a, $b) {
        return strtotime($a['date'] ?? '') - strtotime($b['date'] ?? '');
    });

    $first = reset($fallos);
    $last = end($fallos);
    $t1 = strtotime($first['date'] ?? '');
    $t2 = strtotime($last['date'] ?? '');

    if (!$t1 || !$t2) return null;
    $tiempoTotal = ($t2 - $t1) / 86400;

    return $tiempoTotal / ($numFallos - 1);
}

/**
 * Versión interna de calcularMTTR que ya recibe las OTs filtradas
 */
function calcularMTTR_Internal($equipo_id, $fallos)
{
    $duraciones = array_column($fallos, 'duration_hours');
    return count($duraciones) > 0 ? array_sum($duraciones) / count($duraciones) : 0;
}
