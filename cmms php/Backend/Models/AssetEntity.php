<?php

namespace Backend\Models;

use Backend\Models\AssetStatus;
use Backend\Models\Criticality;
use DateTime;

/**
 * Entidad Inmutable para Activos (PHP 8.2+)
 */
readonly class AssetEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $serialNumber = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?string $location = null,
        public ?string $subLocation = null,
        public AssetStatus $status = AssetStatus::OPERATIVE,
        public Criticality $criticality = Criticality::RELEVANT,
        public float $acquisitionCost = 0.0,
        public ?DateTime $fechaInstalacion = null,
        public ?int $yearsRemaining = null,
        public int $usefulLifePct = 0,
        public ?string $imageUrl = null,
        public ?string $observations = null,
        public ?string $vendor = null,
        public ?string $ownership = null,
        public ?string $warrantyExpiration = null,
        public bool $underMaintenancePlan = false,
        public ?int $purchasedYear = null,
        public ?int $totalUsefulLife = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $riesgoGe = null,
        public ?string $codigoUmdns = null,
        public int $funcionGeScore = 0,
        public int $riesgoGeScore = 0,
        public int $mantenimientoGeScore = 0,
        public ?string $claseRiesgo = 'I',
        public ?string $riesgoBiomedico = 'Medio',
        public float $valorReposicion = 0.0,
        public int $frecuenciaMpMeses = 6
    ) {}

    /**
     * Mapear desde array de base de datos
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            serialNumber: $data['serial_number'] ?? null,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            location: $data['location'] ?? null,
            subLocation: $data['sub_location'] ?? null,
            status: AssetStatus::tryFrom($data['status']) ?? AssetStatus::OPERATIVE,
            criticality: Criticality::tryFrom($data['criticality']) ?? Criticality::RELEVANT,
            acquisitionCost: (float) ($data['acquisition_cost'] ?? 0.0),
            fechaInstalacion: isset($data['fecha_instalacion']) ? new DateTime($data['fecha_instalacion']) : null,
            yearsRemaining: isset($data['years_remaining']) ? (int) $data['years_remaining'] : null,
            usefulLifePct: (int) ($data['useful_life_pct'] ?? 0),
            imageUrl: $data['image_url'] ?? null,
            observations: $data['observations'] ?? null,
            vendor: $data['vendor'] ?? null,
            ownership: $data['ownership'] ?? null,
            warrantyExpiration: $data['warranty_expiration'] ?? null,
            underMaintenancePlan: (bool) ($data['under_maintenance_plan'] ?? false),
            purchasedYear: isset($data['purchased_year']) ? (int) $data['purchased_year'] : null,
            totalUsefulLife: isset($data['total_useful_life']) ? (int) $data['total_useful_life'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            categoryName: $data['category_name'] ?? null,
            riesgoGe: $data['riesgo_ge'] ?? null,
            codigoUmdns: $data['codigo_umdns'] ?? null,
            funcionGeScore: (int) ($data['funcion_ge'] ?? 0),
            riesgoGeScore: (int) ($data['riesgo_ge_score'] ?? 0),
            mantenimientoGeScore: (int) ($data['mantenimiento_ge'] ?? 0),
            claseRiesgo: $data['clase_riesgo'] ?? 'I',
            riesgoBiomedico: $data['riesgo_biomedico'] ?? 'Medio',
            valorReposicion: (float) ($data['valor_reposicion'] ?? 0.0),
            frecuenciaMpMeses: (int) ($data['frecuencia_mp_meses'] ?? 6)
        );
    }

    /**
     * Convertir a array para compatibilidad con frontend legacy
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serialNumber,
            'brand' => $this->brand,
            'model' => $this->model,
            'location' => $this->location,
            'sub_location' => $this->subLocation,
            'status' => $this->status->value,
            'criticality' => $this->criticality->value,
            'acquisition_cost' => $this->acquisitionCost,
            'fecha_instalacion' => $this->fechaInstalacion?->format('Y-m-d'),
            'years_remaining' => $this->yearsRemaining,
            'useful_life_pct' => $this->usefulLifePct,
            'image_url' => $this->imageUrl,
            'observations' => $this->observations,
            'vendor' => $this->vendor,
            'ownership' => $this->ownership,
            'warranty_expiration' => $this->warrantyExpiration,
            'under_maintenance_plan' => $this->underMaintenancePlan,
            'purchased_year' => $this->purchasedYear,
            'total_useful_life' => $this->totalUsefulLife,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'riesgo_ge' => $this->riesgoGe,
            'codigo_umdns' => $this->codigoUmdns,
            'funcion_ge' => $this->funcionGeScore,
            'riesgo_ge_score' => $this->riesgoGeScore,
            'mantenimiento_ge' => $this->mantenimientoGeScore,
            'clase_riesgo' => $this->claseRiesgo,
            'riesgo_biomedico' => $this->riesgoBiomedico,
            'valor_reposicion' => $this->valorReposicion,
            'frecuencia_mp_meses' => $this->frecuenciaMpMeses
        ];
    }
}
