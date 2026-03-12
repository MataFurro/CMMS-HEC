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
 * Script de Importación Masiva de Inventario (Prueba 2.csv)
 */
class ImportInventory
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
        // El archivo usa Windows-1252 y punto y coma
        $headers = fgetcsv($handle, 0, ";");

        $stats = [
            'assets_created' => 0,
            'assets_updated' => 0,
            'assets_skipped' => 0,
            'ots_created' => 0,
            'errors' => []
        ];

        $rowCount = 0;
        while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
            $rowCount++;
            // Mapeo simple por posición basado en el head del CSV
            // 0: SERVICIO CLÍNICO; 1: RECINTO; 2: CLASE; 3: SUBCLASE; 4: NOMBRE EQUIPO; 5: MARCA; 6: MODELO; 7: SERIE; 
            // 8: N° INVENTARIO; 9: AÑO DE ADQUISICIÓN; 10: VIDA ÚTIL; 11: VIDA ÚTIL RESIDUAL; 12: PROPIO...; 
            // 13: ESTADO (BUENO/REGULAR...); 14: CRÍTICO/RELEVANTE...; 15: EN GARANTÍA; 16: AÑO VENCIMIENTO;
            // 17: BAJO PLAN MP; 18: AÑO INGRESO MP; 19: TIPO MANT; 20: PROVEEDOR; 21: ID CONVENIO; 
            // 22: COSTO ANUAL; 23: FRECUENCIA; 24-35: ENERO-DICIEMBRE (AÑO ACTUAL); 36-47: ENERO-DICIEMBRE (AÑO SIG)

            if (count($row) < 9) continue;

            $assetId = trim($row[8]);
            if (empty($assetId)) continue;

            // Limpieza de Costo
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
                'riesgo_ge' => trim($row[2]), // Mapeo de CLASE
                'purchased_year' => (int)$row[9],
                'total_useful_life' => (int)$row[10],
                'years_remaining' => (int)$row[11],
                'under_maintenance_plan' => (trim($row[17]) === 'SI' ? 1 : 0),
                'annual_maint_cost' => $annualCost,
                'frecuencia_mp_meses' => (int)($row[23] ?? 6) > 0 ? (12 / (int)$row[23]) : 6, // Convertir frecuencia anual a meses
                'vendor' => trim($row[20]),
                'contract_id' => trim($row[21]),
                'valor_reposicion' => $annualCost * 10,
                'clase_riesgo' => 'IIa',
                'riesgo_biomedico' => $this->mapBiomedicalRisk(trim($row[2])),
                'ownership' => (trim($row[12]) === 'PROPIO' ? 'PROPIO' : 'TERCEROS'),
                'observations' => "Importado desde Inventario Maestro."
            ];

            // Crear o saltar Activo
            if (!$this->assetRepo->findById($assetId)) {
                if ($this->assetRepo->create($assetData)) {
                    $stats['assets_created']++;
                } else {
                    $stats['errors'][] = "Falla al crear activo $assetId en línea $rowCount";
                    continue;
                }
            } else {
                // Actualizar datos si ya existe
                if ($this->assetRepo->partialUpdate($assetId, $assetData)) {
                    $stats['assets_updated']++;
                } else {
                    $stats['assets_skipped']++;
                }
            }

            /* 
            // Generar OTs Preventivas
            // Columnas 24 a 35 son Enero a Diciembre del año actual
            for ($month = 0; $month < 12; $month++) {
                $cell = trim($row[24 + $month] ?? '');
                if ($cell === 'X' || $cell === 'MP') {
                    $monthNum = str_pad($month + 1, 2, '0', STR_PAD_LEFT);
                    $scheduledDate = date('Y') . "-$monthNum-01";

                    $woId = "PM-" . $assetId . "-" . date('Y') . $monthNum;

                    if (!$this->woRepo->findById($woId)) {
                        $this->woRepo->create([
                            'id' => $woId,
                            'asset_id' => $assetId,
                            'type' => 'Preventiva',
                            'status' => 'En Curso',
                            'created_date' => $scheduledDate,
                            'priority' => ($assetData['criticality'] === 'CRITICAL' ? 'Alta' : 'Media'),
                            'observations' => "Mantenimiento Preventivo Programado según Inventario Maestro. Proveedor: " . $assetData['vendor']
                        ]);
                        $stats['ots_created']++;
                    }
                }
            }
            */
        }

        fclose($handle);
        return $stats;
    }

    private function mapStatus(string $excelStatus): string
    {
        $status = strtoupper(trim($excelStatus));
        return match ($status) {
            'BUENO', 'OPERATIVO', 'OPERANDO' => 'OPERATIVE',
            'REGULAR' => 'OPERATIVE_WITH_OBS',
            'MALO', 'EN REPARACIÓN', 'MANTENIMIENTO' => 'MAINTENANCE',
            'BAJA', 'NO OPERATIVO', 'FUERA DE SERVICIO' => 'NO_OPERATIVE',
            default => 'OPERATIVE'
        };
    }

    private function mapCriticality(string $excelCrit): string
    {
        $crit = strtoupper(trim($excelCrit));
        if (str_contains($crit, 'CRÍTICO')) return 'CRITICAL';
        if (str_contains($crit, 'RELEVANTE') || str_contains($crit, 'IM≥12')) return 'RELEVANT';
        if (str_contains($crit, 'NO APLICA')) return 'NA';
        return 'LOW';
    }

    private function mapBiomedicalRisk(string $clase): string
    {
        $c = strtoupper(trim($clase));
        if (str_contains($c, 'TERAPÉUTICO') || str_contains($c, 'MONITOREO') || str_contains($c, 'QUIRÚRGICO')) return 'Alto';
        if (str_contains($c, 'DIAGNÓSTICO') || str_contains($c, 'IMAGENOLOGÍA') || str_contains($c, 'ESTERILIZACIÓN')) return 'Medio';
        if (str_contains($c, 'INDUSTRIAL') || str_contains($c, 'REHABILITACIÓN') || str_contains($c, 'LABORATORIO')) return 'Bajo';
        return 'N/A';
    }
}
