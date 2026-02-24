<?php

namespace Backend\Services;

/**
 * Backend/Services/ReliabilityService.php
 * ─────────────────────────────────────────────────────
 * Servicio de Análisis de Confiabilidad (Motor de Predicción).
 * Implementa Distribución de Weibull para predicción de fallas.
 * ─────────────────────────────────────────────────────
 */
class ReliabilityService
{
    /**
     * Calcula la Confiabilidad R(t) según Weibull.
     * R(t) = exp(-(t/eta)^beta)
     * 
     * @param float $t Tiempo de operación actual (ej. horas o días)
     * @param float $beta Parámetro de Forma (Shape) - β
     * @param float $eta Parámetro de Escala (Scale/Vida Característica) - η
     * @return float Probabilidad de supervivencia (0 a 1)
     */
    public static function calculateReliability(float $t, float $beta, float $eta): float
    {
        if ($eta <= 0 || $t < 0) return 0.0;
        return exp(-pow($t / $eta, $beta));
    }

    /**
     * Calcula la Tasa de Falla (Hazard Rate) h(t).
     * h(t) = (beta/eta) * (t/eta)^(beta-1)
     */
    public static function calculateHazardRate(float $t, float $beta, float $eta): float
    {
        if ($eta <= 0 || $t <= 0) return 0.0;
        return ($beta / $eta) * pow($t / $eta, $beta - 1);
    }

    /**
     * Estima la probabilidad de falla en un intervalo futuro.
     */
    public static function predictFailureProbability(float $currentTime, float $horizonTime, float $beta, float $eta): float
    {
        $r_current = self::calculateReliability($currentTime, $beta, $eta);
        $r_future = self::calculateReliability($currentTime + $horizonTime, $beta, $eta);

        if ($r_current <= 0) return 1.0;

        // P(falla en horizon | sobrevivió hasta currentTime)
        return ($r_current - $r_future) / $r_current;
    }

    /**
     * Obtiene los parámetros de Weibull sugeridos por categoría de equipo
     * (Basado en el Estado del Arte de Ingeniería Clínica)
     */
    public static function getSuggestedParameters(string $category): array
    {
        $defaults = [
            'INFUSION_PUMP'         => ['beta' => 1.5, 'eta' => 180],
            'VENTILATOR'            => ['beta' => 2.0, 'eta' => 360],
            'MONITOR'               => ['beta' => 1.2, 'eta' => 500],
            'DEFIBRILLATOR'         => ['beta' => 1.8, 'eta' => 210],
            'MONITOREO'             => ['beta' => 1.2, 'eta' => 500],
            'APOYO_TERAPÉUTICO'     => ['beta' => 1.8, 'eta' => 300],
            'APOYO_QUIRÚRGICO'      => ['beta' => 1.6, 'eta' => 400],
            'APOYO_ENDOSCÓPICO'     => ['beta' => 1.4, 'eta' => 200],
            'ESTERILIZACIÓN'        => ['beta' => 1.9, 'eta' => 600],
            'IMAGENOLOGÍA'          => ['beta' => 1.3, 'eta' => 800],
            'LABORATORIO_/_FARMACIA' => ['beta' => 1.2, 'eta' => 450],
            'MOBILIARIO'            => ['beta' => 1.1, 'eta' => 1500],
            'ODONTOLOGÍA'           => ['beta' => 1.3, 'eta' => 700],
            'GENERIC'               => ['beta' => 1.5, 'eta' => 365] // 1 año por defecto
        ];

        $key = strtoupper(str_replace(' ', '_', $category));
        return $defaults[$key] ?? $defaults['GENERIC'];
    }

    /**
     * Analiza el historial de una OT para estimar Beta y Eta (Simplificado)
     * En una implementación real, esto usaría Máxima Verosimilitud (MLE) o Mínimos Cuadrados.
     */
    public static function estimateFromHistory(array $failureTimes): array
    {
        if (count($failureTimes) < 2) {
            return ['beta' => 1.5, 'eta' => 5000, 'low_data' => true];
        }

        // Estimación simplificada de Eta (Vida media aproximada)
        $eta = array_sum($failureTimes) / count($failureTimes);

        // Beta default (1.5 sugiere desgaste, típico en biomédica)
        return [
            'beta' => 1.5,
            'eta' => $eta,
            'low_data' => count($failureTimes) < 5
        ];
    }
}
