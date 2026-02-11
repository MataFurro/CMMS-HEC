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
    $fallos = array_filter($otCorrectivas, fn($ot) => $ot['equipo_id'] === $equipo_id);
    $numFallos = count($fallos);

    if ($numFallos <= 1) return null; // Necesita al menos 2 fallos

    // Ordenar por fecha
    usort($fallos, fn($a, $b) => strtotime($a['fecha']) - strtotime($b['fecha']));

    // Tiempo entre primera y última falla (en días)
    $tiempoTotal = (strtotime(end($fallos)['fecha']) - strtotime($fallos[0]['fecha'])) / 86400;

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
    $fallos = array_filter($otCorrectivas, fn($ot) => $ot['equipo_id'] === $equipo_id);
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
    if ($MTBF === null || $MTBF == 0) return 0;

    // Convertir MTBF de días a horas
    $MTBF_horas = $MTBF * 24;

    return $MTBF_horas / ($MTBF_horas + $MTTR);
}

/**
 * Calcula el Número de Gestión del Equipo (GE) según Fennigkoh-Smith
 * 
 * @param array $equipo Array con campos: funcion, riesgo, mantenimiento
 * @return int GE (si >= 12, equipo es prioritario)
 */
function calcularGE($equipo)
{
    $funcion = $equipo['funcion'] ?? 5; // 1-10 (default: 5)
    $riesgo = $equipo['riesgo'] ?? 3; // 1-5 (default: 3)
    $mantenimiento = $equipo['mantenimiento'] ?? 3; // 1-5 (default: 3)

    return $funcion + $riesgo + $mantenimiento;
}

/**
 * Calcula GE ajustado según modelo Wang-Levenson
 * 
 * @param array $equipo Array con campos: prioridad, mantenimiento, tasaUso, riesgo
 * @return float GE ajustado
 */
function calcularGEAjustado($equipo)
{
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
 * Calcula métricas globales del sistema
 * 
 * @param array $assets Array de equipos
 * @param array $otCorrectivas Array de OT correctivas
 * @return array Métricas globales
 */
function calcularMetricasGlobales($assets, $otCorrectivas)
{
    $mtbfs = [];
    $mttrs = [];
    $disponibilidades = [];

    foreach ($assets as $asset) {
        $mtbf = calcularMTBF($asset['id'], $otCorrectivas);
        $mttr = calcularMTTR($asset['id'], $otCorrectivas);

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
        'equipos_analizados' => count($mtbfs)
    ];
}
