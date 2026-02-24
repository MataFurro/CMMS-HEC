<?php

namespace Backend\Repositories;

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;
use Backend\Models\WorkOrderEntity;
use PDO;
use Generator;

/**
 * Backend/Repositories/WorkOrderRepository.php
 * ─────────────────────────────────────────────────────
 * Acceso directo a la tabla 'work_orders'.
 * ─────────────────────────────────────────────────────
 */
class WorkOrderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Obtener todas las órdenes con datos del activo usando Generadores
     * @return Generator<WorkOrderEntity>
     */
    public function findAll(): Generator
    {
        try {
            $sql = "SELECT wo.*, a.name as asset_name, a.location, u.name as assigned_tech_name 
                    FROM work_orders wo
                    LEFT JOIN assets a ON wo.asset_id = a.id
                    LEFT JOIN users u ON wo.assigned_tech_id = u.id
                    ORDER BY wo.created_at DESC";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch()) {
                yield WorkOrderEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::findAll", ['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * Buscar una OT por ID
     */
    public function findById(string $id): ?WorkOrderEntity
    {
        try {
            $sql = "SELECT wo.*, a.name as asset_name, a.location, u.name as assigned_tech_name
                    FROM work_orders wo
                    LEFT JOIN assets a ON wo.asset_id = a.id
                    LEFT JOIN users u ON wo.assigned_tech_id = u.id
                    WHERE wo.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $order = $stmt->fetch();
            return $order ? WorkOrderEntity::fromArray($order) : null;
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::findById", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener estadísticas de OTs por estado
     */
    public function getStatusStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pendiente' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'En Proceso' THEN 1 ELSE 0 END) as progress,
                    SUM(CASE WHEN status = 'Terminada' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN priority = 'Alta' AND status != 'Terminada' THEN 1 ELSE 0 END) as critical_today
                FROM work_orders";
        return $this->db->query($sql)->fetch();
    }

    /**
     * Actualizar estado de una OT
     */
    public function updateStatus(string $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE work_orders SET status = :status, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Crear una nueva OT
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, priority, observations, ms_request_id, ms_email, checklist_template, checklist_data, duration_hours, failure_code, service_warranty_date, final_asset_status, created_at) 
                VALUES (:id, :asset_id, :type, :status, :tech_id, :created_date, :priority, :observations, :ms_request_id, :ms_email, :checklist_template, :checklist_data, :duration_hours, :failure_code, :service_warranty_date, :final_asset_status, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $data['id'],
            ':asset_id' => $data['asset_id'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? 'Pendiente',
            ':tech_id' => $data['assigned_tech_id'] ?? null,
            ':created_date' => $data['created_date'] ?? date('Y-m-d'),
            ':priority' => $data['priority'] ?? 'Media',
            ':observations' => $data['observations'] ?? null,
            ':ms_request_id' => $data['ms_request_id'] ?? null,
            ':ms_email' => $data['ms_email'] ?? null,
            ':checklist_template' => $data['checklist_template'] ?? null,
            ':checklist_data' => isset($data['checklist_data']) ? json_encode($data['checklist_data']) : null,
            ':duration_hours' => $data['duration_hours'] ?? 0,
            ':failure_code' => $data['failure_code'] ?? null,
            ':service_warranty_date' => $data['service_warranty_date'] ?? null,
            ':final_asset_status' => $data['final_asset_status'] ?? null
        ]);

        return $data['id'];
    }

    /**
     * Actualización parcial para cierre de OT
     */
    public function partialUpdate(string $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $id];

            $allowedFields = ['status', 'completed_date', 'duration_hours', 'failure_code', 'service_warranty_date', 'final_asset_status', 'observations', 'checklist_data'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = ($field === 'checklist_data') ? json_encode($data[$field]) : $data[$field];
                }
            }

            if (empty($fields)) return true;

            $sql = "UPDATE work_orders SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::partialUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
