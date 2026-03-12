<?php

namespace Backend\Repositories;

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;
use Backend\Models\WorkOrderEntity;
use function Backend\Providers\logAuditAction;
use PDO;
use Generator;

/**
 * Backend/Repositories/WorkOrderRepository.php
 * ─────────────────────────────────────────────────────
 * Acceso directo a la tabla 'work_orders'.
 * ─────────────────────────────────────────────────────
 */
require_once __DIR__ . '/../Providers/AuditProvider.php';
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
     * Obtener órdenes paginadas con filtros
     */
    public function findPaginated(int $limit, int $offset, array $filters = []): Generator
    {
        try {
            $sql = "SELECT wo.*, a.name as asset_name, a.location, u.name as assigned_tech_name 
                    FROM work_orders wo
                    LEFT JOIN assets a ON wo.asset_id = a.id
                    LEFT JOIN users u ON wo.assigned_tech_id = u.id
                    WHERE 1=1";

            $params = [];
            if (!empty($filters['status'])) {
                $sql .= " AND wo.status = :status";
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['type'])) {
                $sql .= " AND wo.type = :type";
                $params['type'] = $filters['type'];
            }

            $sql .= " ORDER BY wo.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);

            $stmt->execute();
            while ($row = $stmt->fetch()) {
                yield WorkOrderEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::findPaginated", ['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * Contar total de órdenes con filtros
     */
    public function count(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM work_orders wo WHERE 1=1";
            $params = [];
            if (!empty($filters['status'])) {
                $sql .= " AND wo.status = :status";
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['type'])) {
                $sql .= " AND wo.type = :type";
                $params['type'] = $filters['type'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
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
                    SUM(CASE WHEN LOWER(status) IN ('en curso') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN LOWER(status) IN ('en espera') THEN 1 ELSE 0 END) as on_hold,
                    SUM(CASE WHEN LOWER(status) IN ('closed', 'terminada', 'cerrada') THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN (LOWER(priority) IN ('alta', 'crítica', 'high', 'critical')) AND LOWER(status) NOT IN ('closed', 'terminada', 'cerrada') THEN 1 ELSE 0 END) as critical_today
                FROM work_orders";
        return $this->db->query($sql)->fetch();
    }

    /**
     * Obtener órdenes asignadas a un técnico específico
     */
    public function findByTechnician(string $techId): Generator
    {
        try {
            $sql = "SELECT wo.*, a.name as asset_name, a.location
                    FROM work_orders wo
                    LEFT JOIN assets a ON wo.asset_id = a.id
                    WHERE wo.assigned_tech_id = :tech_id
                    ORDER BY wo.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tech_id' => $techId]);
            while ($row = $stmt->fetch()) {
                yield WorkOrderEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::findByTechnician", ['techId' => $techId, 'error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * Obtener todas las órdenes que están asignadas a algún técnico
     */
    public function findAllAssigned(): Generator
    {
        try {
            $sql = "SELECT wo.*, a.name as asset_name, a.location, u.name as assigned_tech_name
                    FROM work_orders wo
                    LEFT JOIN assets a ON wo.asset_id = a.id
                    LEFT JOIN users u ON wo.assigned_tech_id = u.id
                    WHERE wo.assigned_tech_id IS NOT NULL AND TRIM(wo.assigned_tech_id) != ''
                    ORDER BY wo.created_at DESC";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch()) {
                yield WorkOrderEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::findAllAssigned", ['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * Actualizar estado de una OT
     */
    public function updateStatus(string $id, string $status): bool
    {
        $old_status = $this->findById($id)?->status;
        $stmt = $this->db->prepare("UPDATE work_orders SET status = :status, updated_at = NOW() WHERE id = :id");
        $result = $stmt->execute(['status' => $status, 'id' => $id]);

        if ($result) {
            logAuditAction('STATUS_CHANGE', 'WORK_ORDER', $id, "Cambio de estado manual", [
                'old' => $old_status,
                'new' => $status
            ]);
        }
        return $result;
    }

    /**
     * Crear una nueva OT
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, priority, observations, ms_request_id, ms_email, checklist_template, checklist_data, duration_hours, failure_code, service_warranty_date, final_asset_status, handover_confirmed_by, handover_location, handover_timestamp, coordination_stalled_reason, created_at) 
                VALUES (:id, :asset_id, :type, :status, :tech_id, :created_date, :priority, :observations, :ms_request_id, :ms_email, :checklist_template, :checklist_data, :duration_hours, :failure_code, :service_warranty_date, :final_asset_status, :handover_confirmed_by, :handover_location, :handover_timestamp, :coordination_stalled_reason, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $data['id'],
            ':asset_id' => $data['asset_id'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? 'En Curso',
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
            ':final_asset_status' => $data['final_asset_status'] ?? null,
            ':handover_confirmed_by' => $data['handover_confirmed_by'] ?? null,
            ':handover_location' => $data['handover_location'] ?? null,
            ':handover_timestamp' => $data['handover_timestamp'] ?? null,
            ':coordination_stalled_reason' => $data['coordination_stalled_reason'] ?? null
        ]);

        logAuditAction('CREATE', 'WORK_ORDER', $data['id'], "Apertura de Orden de Trabajo automática", [
            'type' => $data['type'],
            'asset_id' => $data['asset_id']
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

            $allowedFields = [
                'status',
                'completed_date',
                'duration_hours',
                'failure_code',
                'service_warranty_date',
                'final_asset_status',
                'observations',
                'checklist_data',
                'handover_confirmed_by',
                'handover_location',
                'handover_timestamp',
                'coordination_stalled_reason'
            ];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = ($field === 'checklist_data' && $data[$field] !== null) ? json_encode($data[$field]) : $data[$field];
                }
            }

            if (empty($fields)) return true;

            $sql = "UPDATE work_orders SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            if ($success) {
                logAuditAction('UPDATE', 'WORK_ORDER', $id, "Modificación técnica de la OT", $data);
            }

            return $success;
        } catch (\Exception $e) {
            LoggerService::error("Error en WorkOrderRepository::partialUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
