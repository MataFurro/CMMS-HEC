<?php

namespace Backend\Models;

use Backend\Models\WorkOrderStatus;
use DateTime;

/**
 * Entidad Inmutable para Órdenes de Trabajo (PHP 8.2+)
 */
readonly class WorkOrderEntity
{
    public function __construct(
        public string $id,
        public string $assetId,
        public string $type,
        public WorkOrderStatus $status = WorkOrderStatus::PENDING,
        public string $priority = 'Media',
        public ?string $assignedTechName = null,
        public ?DateTime $createdDate = null,
        public ?DateTime $completedDate = null,
        public ?string $assetName = null,
        public ?string $location = null,
        public ?string $observations = null,
        public ?string $msRequestId = null,
        public ?string $msEmail = null,
        public ?string $checklistTemplate = null,
        public ?float $durationHours = 0.0,
        public ?string $failureCode = null,
        public ?DateTime $serviceWarrantyDate = null,
        public ?string $finalAssetStatus = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            assetId: $data['asset_id'],
            type: $data['type'],
            status: WorkOrderStatus::tryFrom($data['status']) ?? WorkOrderStatus::PENDING,
            priority: $data['priority'] ?? 'Media',
            assignedTechName: $data['assigned_tech_name'] ?? null,
            createdDate: isset($data['created_date']) ? new DateTime($data['created_date']) : null,
            completedDate: isset($data['completed_date']) ? new DateTime($data['completed_date']) : null,
            assetName: $data['asset_name'] ?? null,
            location: $data['location'] ?? null,
            observations: $data['observations'] ?? null,
            msRequestId: $data['ms_request_id'] ?? null,
            msEmail: $data['ms_email'] ?? null,
            checklistTemplate: $data['checklist_template'] ?? null,
            durationHours: isset($data['duration_hours']) ? (float)$data['duration_hours'] : 0.0,
            failureCode: $data['failure_code'] ?? null,
            serviceWarrantyDate: isset($data['service_warranty_date']) ? new DateTime($data['service_warranty_date']) : null,
            finalAssetStatus: $data['final_asset_status'] ?? null
        );
    }

    /**
     * Convertir a array para compatibilidad con frontend legacy
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->assetId,
            'type' => $this->type,
            'status' => $this->status->value,
            'priority' => $this->priority,
            'assigned_tech_name' => $this->assignedTechName,
            'created_date' => $this->createdDate?->format('Y-m-d'),
            'completed_date' => $this->completedDate?->format('Y-m-d'),
            'asset_name' => $this->assetName,
            'location' => $this->location,
            'observations' => $this->observations,
            'ms_request_id' => $this->msRequestId,
            'ms_email' => $this->msEmail,
            'checklist_template' => $this->checklistTemplate,
            'duration_hours' => $this->durationHours,
            'failure_code' => $this->failureCode,
            'service_warranty_date' => $this->serviceWarrantyDate?->format('Y-m-d'),
            'final_asset_status' => $this->finalAssetStatus,
            'asset' => $this->assetName, // Legacy
            'tech' => $this->assignedTechName ?? 'Sin Asignar', // Real
            'date' => $this->createdDate?->format('Y-m-d') // Legacy compatibility
        ];
    }
}
