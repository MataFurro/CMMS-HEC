<?php

namespace Backend\Services;

/**
 * Backend/Services/CatalogService.php
 * ─────────────────────────────────────────────────────
 * Servicio centralizado para la clasificación de activos,
 * estimación de costos y asignación de criticidad clínica.
 * ─────────────────────────────────────────────────────
 */
class CatalogService
{
    /**
     * Catálogo maestro de equipos
     * Formato: 'keyword' => [costo_base_CLP, vida_años, es_monitoreo, criticidad]
     */
    private static array $equipmentCatalog = [
        // ── MONITOREO · CRÍTICO ──────────────────────────────────────
        'ventilador'              => [4800000,  10, true,  'CRITICAL'],
        'desfibrilador'           => [3200000,   8, true,  'CRITICAL'],
        'monitor cardio'          => [5200000,   8, true,  'CRITICAL'],
        'monitor de signos'       => [4800000,   8, true,  'CRITICAL'],
        'monitor signos vitales'  => [4800000,   8, true,  'CRITICAL'],
        'monitor multiparametro'  => [5500000,   8, true,  'CRITICAL'],
        'monitor multiparámetro'  => [5500000,   8, true,  'CRITICAL'],
        'oxímetro'                => [380000,     5, true,  'CRITICAL'],
        'oximetro'                => [380000,     5, true,  'CRITICAL'],
        'pulsioxímetro'           => [420000,     5, true,  'CRITICAL'],
        'pulsioximetro'           => [420000,     5, true,  'CRITICAL'],
        'ecmo'                    => [95000000, 12, true,  'CRITICAL'],
        'incubadora'              => [8500000,  10, true,  'CRITICAL'],
        'cuna térmica'            => [5200000,  10, true,  'CRITICAL'],
        'cuna termica'            => [5200000,  10, true,  'CRITICAL'],
        'monitor de presión'      => [2800000,   7, true,  'CRITICAL'],
        'monitor de presion'      => [2800000,   7, true,  'CRITICAL'],
        'bomba de infusión'       => [1500000,   7, true,  'CRITICAL'],
        'bomba de infusion'       => [1500000,   7, true,  'CRITICAL'],
        'bomba de jeringa'        => [980000,     7, true,  'CRITICAL'],
        'infusor'                 => [1200000,   7, true,  'CRITICAL'],

        // ── MONITOREO · RELEVANTE ────────────────────────────────────
        'monitor'                 => [4200000,   7, true,  'RELEVANT'],
        'electrocardio'           => [3800000,  10, true,  'RELEVANT'],
        'electrocardiógrafo'      => [3800000,  10, true,  'RELEVANT'],
        'ecógrafo'                => [28000000, 10, true,  'RELEVANT'],
        'ecografo'                => [28000000, 10, true,  'RELEVANT'],
        'glucómetro'              => [95000,      5, true,  'RELEVANT'],
        'glucometro'              => [95000,      5, true,  'RELEVANT'],
        'glicómetro'              => [95000,      5, true,  'RELEVANT'],

        // ── NO MONITOREO · CRÍTICO ───────────────────────────────────
        'anestesia'               => [18000000, 12, false, 'CRITICAL'],
        'rayos x'                 => [45000000, 10, false, 'CRITICAL'],
        'arco en c'               => [85000000, 10, false, 'CRITICAL'],
        'tomógrafo'               => [450000000, 12, false, 'CRITICAL'],
        'tomografo'               => [450000000, 12, false, 'CRITICAL'],
        'resonancia'              => [850000000, 15, false, 'CRITICAL'],
        'mamógrafo'               => [120000000, 12, false, 'CRITICAL'],
        'mamografo'               => [120000000, 12, false, 'CRITICAL'],
        'autoclave'               => [12000000, 15, false, 'CRITICAL'],

        // ── NO MONITOREO · RELEVANTE ─────────────────────────────────
        'centrífuga'              => [2800000,  10, false, 'RELEVANT'],
        'centrifuga'              => [2800000,  10, false, 'RELEVANT'],
        'láser'                   => [35000000, 10, false, 'RELEVANT'],
        'laser'                   => [35000000, 10, false, 'RELEVANT'],
        'litotriptor'             => [120000000, 15, false, 'RELEVANT'],
        'lámpara quirúrgica'      => [8500000,  15, false, 'RELEVANT'],
        'lampara quirurgica'      => [8500000,  15, false, 'RELEVANT'],
        'negatoscopio'            => [450000,    15, false, 'RELEVANT'],
        'aspirador'               => [850000,    10, false, 'RELEVANT'],
        'succionador'             => [850000,    10, false, 'RELEVANT'],

        // ── NO MONITOREO · BAJO (NO APLICA) ──────────────────────────
        'camilla'                 => [1200000,   12, false, 'LOW'],
        'refrigerador'            => [1800000,   10, false, 'LOW'],
        'freezer'                 => [2500000,   10, false, 'LOW'],
        'termo'                   => [150000,     5, false, 'LOW'],
        'silla'                   => [480000,    10, false, 'LOW'],
        'báscula'                 => [380000,    10, false, 'LOW'],
        'bascula'                 => [380000,    10, false, 'LOW'],
        'tallímetro'              => [280000,    10, false, 'LOW'],
        'tallimetro'              => [280000,    10, false, 'LOW'],
    ];

    /**
     * Clasificar un activo por su nombre y ubicación
     * 
     * @param string $name Nombre del activo
     * @return array [costo_base, vida_total, es_monitoreo, criticidad]
     */
    public static function classifyEquipment(string $name): array
    {
        $nameLower = mb_strtolower($name, 'UTF-8');

        foreach (self::$equipmentCatalog as $keyword => $data) {
            if (str_contains($nameLower, $keyword)) {
                return $data;
            }
        }

        // Default: Otros Equipos Clínicos
        return [450000, 10, false, 'RELEVANT'];
    }

    /**
     * Obtener el catálogo completo
     */
    public static function getCatalog(): array
    {
        return self::$equipmentCatalog;
    }
}
