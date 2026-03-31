<?php

namespace Backend\Helpers;

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Core/LoggerService.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';
require_once __DIR__ . '/../Repositories/WorkOrderRepository.php';
require_once __DIR__ . '/../Models/AssetEntity.php';
require_once __DIR__ . '/../Models/AssetStatus.php';
require_once __DIR__ . '/../Models/Criticality.php';
require_once __DIR__ . '/../Models/WorkOrderStatus.php';
require_once __DIR__ . '/../Models/WorkOrderEntity.php';

use Backend\Core\LoggerService;
use Backend\Repositories\AssetRepository;
use Backend\Repositories\WorkOrderRepository;

/**
 * [MEJORA - AUDITORÍA 2026]
 * Script de Importación Masiva de Inventario corregido para BioCMMS v4.5
 * ──────────────────────────────────────────────────────────────────
 * Esta versión recalcula la vida útil 'al vuelo' usando el año base 2026.
 * Previene que el Dashboard regrese a los 101 vencidos originales.
 */
class ImportInventory_Auditoria2026
{
    private AssetRepository $assetRepo;
    private WorkOrderRepository $woRepo;

    public function __construct()
    {
        $this->assetRepo = new AssetRepository();
        $this->woRepo = new WorkOrderRepository();
    }

    public function run(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            return ['error' => "Archivo no encontrado: $csvPath"];
        }

        $handle = fopen($csvPath, "r");
        $headers = fgetcsv($handle, 0, ";");

        $stats = [
            'assets_created' => 0,
            'assets_updated' => 0,
            'assets_skipped' => 0,
            'errors' => []
        ];

        while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
            if (count($row) < 11) continue;

            $assetId = trim($row[8]); // N° INVENTARIO
            if (empty($assetId)) continue;

            // --- LÓGICA DE AUDITORÍA 2026 ---
            $currentYear = 2026;
            $installYear = (int)$row[9]; // AÑO ADQUISICIÓN
            $usefulLife = (int)$row[10]; // VIDA ÚTIL TOTAL
            
            // Recalculamos basándonos en la fecha actual real de auditoría
            $age = $currentYear - $installYear;
            $yearsRemaining = $usefulLife - $age;
            $usefulLifePct = floor(max(0, ($yearsRemaining / $usefulLife) * 100));
            // --------------------------------

            $costRaw = $row[22] ?? '$0';
            $annualCost = (float) preg_replace('/[^0-9]/', '', $costRaw);

            $assetData = [
                'id' => $assetId,
                'name' => trim($row[4]),
                'brand' => trim($row[5]),
                'model' => trim($row[6]),
                'serial_number' => trim($row[7]),
                'location' => trim($row[0]),
                'sub_location' => trim($row[1]),
                'status' => $this->mapStatus($row[13]),
                'criticality' => $this->mapCriticality($row[14]),
                'riesgo_ge' => trim($row[2]),
                'purchased_year' => $installYear,
                'total_useful_life' => $usefulLife,
                'years_remaining' => $yearsRemaining, // VALOR CALCULADO dinámicamente
                'useful_life_pct' => $usefulLifePct, // VALOR CALCULADO dinámicamente
                'under_maintenance_plan' => (trim($row[17]) === 'SI' ? 1 : 0),
                'annual_maint_cost' => $annualCost,
                'frecuencia_mp_meses' => (int)($row[23] ?? 6) > 0 ? (12 / (int)$row[23]) : 6,
                'vendor' => trim($row[20]),
                'contract_id' => trim($row[21]),
                'ownership' => (trim($row[12]) === 'PROPIO' ? 'PROPIO' : 'TERCEROS')
            ];

            if (!$this->assetRepo->findById($assetId)) {
                if ($this->assetRepo->create($assetData)) $stats['assets_created']++;
            } else {
                if ($this->assetRepo->partialUpdate($assetId, $assetData)) $stats['assets_updated']++;
            }
        }

        fclose($handle);
        return $stats;
    }

    private function mapStatus(string $excelStatus): string
    {
        $status = strtoupper(trim($excelStatus));
        return match ($status) {
            'BUENO', 'OPERATIVO' => 'OPERATIVE',
            'REGULAR' => 'OPERATIVE_WITH_OBS',
            'MALO' => 'MAINTENANCE',
            'BAJA', 'NO OPERATIVO' => 'NO_OPERATIVE',
            default => 'OPERATIVE'
        };
    }

    private function mapCriticality(string $excelCrit): string
    {
        $crit = strtoupper(trim($excelCrit));
        if (str_contains($crit, 'CRÍTICO')) return 'CRITICAL';
        if (str_contains($crit, 'RELEVANTE')) return 'RELEVANT';
        return 'LOW';
    }
}
