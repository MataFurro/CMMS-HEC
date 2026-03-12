<?php

/**
 * Backend/Providers/BulkProvider.php
 * ─────────────────────────────────────────────────────
 * Operaciones masivas sobre activos y órdenes de trabajo.
 * Exclusivo para CHIEF_ENGINEER.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';
require_once __DIR__ . '/../Repositories/WorkOrderRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Providers/AssetProvider.php';

use Backend\Repositories\AssetRepository;
use Backend\Repositories\WorkOrderRepository;
use Backend\Repositories\UserRepository;
use Backend\Core\DatabaseService;

/**
 * Actualizar un campo en múltiples activos.
 * @param array $ids       Lista de asset IDs
 * @param string $field    Campo a actualizar (criticality | status | location | under_maintenance_plan | ownership)
 * @param mixed $value     Nuevo valor
 * @return array ['success' => n, 'errors' => [...]]
 */
function bulkUpdateField(array $ids, string $field, $value): array
{
    $stats = ['success' => 0, 'errors' => []];

    // Whitelist de campos seguros para actualización masiva
    $allowedFields = ['criticality', 'status', 'location', 'sub_location', 'under_maintenance_plan', 'ownership', 'riesgo_ge', 'annual_maint_cost', 'acquisition_cost'];
    if (!in_array($field, $allowedFields)) {
        $stats['errors'][] = "Campo '$field' no permitido para edición masiva.";
        return $stats;
    }

    if (empty($ids)) {
        $stats['errors'][] = 'No se seleccionaron equipos.';
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // ── Snapshot para deshacer (Ctrl-Z) ─────────────────────────────
        if (session_status() === PHP_SESSION_NONE) session_start();
        $snapStmt = $db->prepare("SELECT id, `{$field}` AS prev FROM assets WHERE id IN ({$placeholders})");
        $snapStmt->execute($ids);
        $snapshot = [];
        foreach ($snapStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $snapshot[$row['id']] = $row['prev'];
        }
        $_SESSION['bulk_undo'] = ['field' => $field, 'snapshot' => $snapshot, 'ts' => time()];
        unset($_SESSION['bulk_undo_orders']); // limpia undo de OTs anterior
        // ─────────────────────────────────────────────────────────────────

        $sql = "UPDATE assets SET {$field} = ? WHERE id IN ({$placeholders})";
        $params = array_merge([$value], $ids);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $stats['success'] = $stmt->rowCount();
        $db->commit();
    } catch (\Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Crear órdenes de trabajo preventivas masivamente para una lista de activos.
 * @param array $assetIds            Lista de asset IDs
 * @param array $options             [description, technician_id, scheduled_date, priority]
 * @return array ['success' => n, 'errors' => [...]]
 */
function bulkCreateMaintenanceOrders(array $assetIds, array $options): array
{
    $stats = ['success' => 0, 'errors' => [], 'created_ids' => []];

    if (empty($assetIds)) {
        $stats['errors'][] = 'No se seleccionaron equipos.';
        return $stats;
    }

    try {
        $db  = DatabaseService::getInstance();
        $db->beginTransaction();
        $sql = "INSERT INTO work_orders 
                    (id, asset_id, type, priority, status, 
                     assigned_tech_id, created_date, observations, created_at) 
                VALUES 
                    (:id, :asset_id, 'Preventiva', :priority, 'En Curso', 
                     :technician_id, :created_date, :observations, NOW())";

        $stmt = $db->prepare($sql);
        $today = date('Ymd');

        foreach ($assetIds as $assetId) {
            // Generar ID único para cada OT masiva: PRE-BU-[YYYYMMDD]-RAND
            $otId = 'PRE-BU-' . $today . '-' . strtoupper(substr(uniqid(), -4));

            // Mapeo de prioridad a español para la DB
            $priority = $options['priority'] ?? 'Media';
            if ($priority === 'HIGH') $priority = 'Alta';
            if ($priority === 'LOW') $priority = 'Baja';
            if ($priority === 'MEDIUM') $priority = 'Media';

            $stmt->execute([
                ':id'             => $otId,
                ':asset_id'       => $assetId,
                ':priority'       => $priority,
                ':technician_id'  => $options['technician_id'] ?? null,
                ':created_date'   => $options['scheduled_date'] ?? date('Y-m-d'),
                ':observations'   => $options['description'] ?? 'Mantenimiento preventivo generado masivamente.',
            ]);

            $stats['created_ids'][] = $otId;
            $stats['success']++;
        }

        // ── Snapshot OTs creadas para deshacer ───────────────────────────
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['bulk_undo_orders'] = ['ids' => $stats['created_ids'], 'ts' => time()];
        unset($_SESSION['bulk_undo']);
        // ─────────────────────────────────────────────────────────────────
        $db->commit();
    } catch (\Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Deshacer la última operación de edición masiva de campos.
 * Restaura los valores previos desde $_SESSION['bulk_undo'].
 */
function bulkUndo(): array
{
    $stats = ['success' => 0, 'errors' => []];
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['bulk_undo'])) {
        $stats['errors'][] = 'No hay operación para deshacer.';
        return $stats;
    }

    $field    = $_SESSION['bulk_undo']['field'];
    $snapshot = $_SESSION['bulk_undo']['snapshot'];
    $ts       = $_SESSION['bulk_undo']['ts'] ?? 0;

    // Sólo permitir deshacer dentro de los últimos 5 minutos
    if ((time() - $ts) > 300) {
        unset($_SESSION['bulk_undo']);
        $stats['errors'][] = 'El tiempo para deshacer ha expirado (máx. 5 min).';
        return $stats;
    }

    $allowedFields = ['criticality', 'status', 'location', 'sub_location', 'under_maintenance_plan', 'ownership', 'riesgo_ge', 'annual_maint_cost'];
    if (!in_array($field, $allowedFields)) {
        $stats['errors'][] = "Campo '$field' no permitido.";
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE assets SET `{$field}` = :val WHERE id = :id");
        foreach ($snapshot as $id => $prev) {
            $stmt->execute([':val' => $prev, ':id' => $id]);
            $stats['success']++;
        }
        $db->commit();
        unset($_SESSION['bulk_undo']);
    } catch (\Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Deshacer la última creación masiva de OTs (elimina las OTs generadas).
 */
function bulkUndoOrders(): array
{
    $stats = ['success' => 0, 'errors' => []];
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['bulk_undo_orders']['ids'])) {
        $stats['errors'][] = 'No hay OTs que deshacer.';
        return $stats;
    }

    $ts = $_SESSION['bulk_undo_orders']['ts'] ?? 0;
    if ((time() - $ts) > 300) {
        unset($_SESSION['bulk_undo_orders']);
        $stats['errors'][] = 'El tiempo para deshacer ha expirado (máx. 5 min).';
        return $stats;
    }

    $ids = array_map('intval', $_SESSION['bulk_undo_orders']['ids']);
    if (empty($ids)) {
        $stats['errors'][] = 'Lista de OTs vacía.';
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $db->beginTransaction();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        // Usar 'En Curso' en lugar de 'PENDING'
        $stmt = $db->prepare("DELETE FROM work_orders WHERE id IN ({$ph}) AND status = 'En Curso'");
        $stmt->execute($ids);
        $stats['success'] = $stmt->rowCount();
        $db->commit();
        unset($_SESSION['bulk_undo_orders']);
    } catch (\Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Reasignar técnico en las órdenes de trabajo activas de múltiples activos.
 * @param array $assetIds    Lista de IDs de activos beneficiarios
 * @param int|null $techId   ID del técnico a asignar (null para desasignar)
 * @return array ['success' => n, 'errors' => [...]]
 */
function bulkReassignTechnician(array $assetIds, ?int $techId): array
{
    $stats = ['success' => 0, 'errors' => []];
    if (empty($assetIds)) {
        $stats['errors'][] = 'No se seleccionaron equipos.';
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $placeholders = implode(',', array_fill(0, count($assetIds), '?'));

        // Reasignamos OTs que estén 'En Curso' o 'En Espera' para los activos seleccionados
        $sql = "UPDATE work_orders 
                SET assigned_tech_id = ?, updated_at = NOW() 
                WHERE asset_id IN ({$placeholders}) 
                AND status IN ('En Curso', 'En Espera')";

        $params = array_merge([$techId], $assetIds);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $stats['success'] = $stmt->rowCount();

        if ($stats['success'] > 0) {
            require_once __DIR__ . '/../Providers/AuditProvider.php';
            $techName = $techId ? 'Técnico ID: ' . $techId : 'Sin técnico';
            foreach ($assetIds as $id) {
                \Backend\Providers\logAuditAction('BULK_REASSIGN', 'ASSET', $id, "Reasignación masiva de OTs activas a: $techName", ['tech_id' => $techId]);
            }
        }
    } catch (\Exception $e) {
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Obtener activos con filtros básicos para la tabla de gestión masiva.
 */
function getBulkAssets(string $search = '', string $criticality = '', string $status = '', string $clase = '', int $limit = 100, int $offset = 0): array
{
    try {
        $db     = DatabaseService::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(a.name LIKE :search1 OR a.inventory_id LIKE :search2 OR a.location LIKE :search3 OR a.brand LIKE :search4 OR a.model LIKE :search5 OR a.status LIKE :search6)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
            $params[':search4'] = '%' . $search . '%';
            $params[':search5'] = '%' . $search . '%';
            $params[':search6'] = '%' . $search . '%';
        }
        if (!empty($criticality) && $criticality !== 'ALL') {
            $where[] = "a.criticality = :criticality";
            $params[':criticality'] = $criticality;
        }
        if (!empty($status) && $status !== 'ALL') {
            $where[] = "a.status = :status";
            $params[':status'] = $status;
        }
        if (!empty($clase) && $clase !== 'ALL') {
            $where[] = "a.riesgo_ge = :clase";
            $params[':clase'] = $clase;
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT a.id, a.inventory_id, a.name, a.brand, a.model,
                       a.location, a.sub_location, a.criticality, a.status,
                       a.riesgo_ge, a.under_maintenance_plan, a.acquisition_cost
                FROM assets a
                WHERE {$whereStr}
                ORDER BY a.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Contar activos para paginación en la tabla masiva.
 */
function countBulkAssets(string $search = '', string $criticality = '', string $status = '', string $clase = ''): int
{
    try {
        $db     = DatabaseService::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(a.name LIKE :search1 OR a.inventory_id LIKE :search2 OR a.location LIKE :search3 OR a.brand LIKE :search4 OR a.model LIKE :search5 OR a.status LIKE :search6)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
            $params[':search4'] = '%' . $search . '%';
            $params[':search5'] = '%' . $search . '%';
            $params[':search6'] = '%' . $search . '%';
        }
        if (!empty($criticality) && $criticality !== 'ALL') {
            $where[] = "a.criticality = :criticality";
            $params[':criticality'] = $criticality;
        }
        if (!empty($status) && $status !== 'ALL') {
            $where[] = "a.status = :status";
            $params[':status'] = $status;
        }
        if (!empty($clase) && $clase !== 'ALL') {
            $where[] = "a.riesgo_ge = :clase";
            $params[':clase'] = $clase;
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM assets a WHERE {$whereStr}";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (\Exception $e) {
        return 0;
    }
}
/**
 * Mover activos a "Baja Pendiente" (Periodo de Gracia 24h) con verificación de contraseña.
 * @param array $ids         Lista de asset IDs
 * @param string $password   Contraseña del usuario actual para autorizar
 * @param string $reason     Motivo de la baja (obligatorio)
 * @return array ['success' => n, 'errors' => [...]]
 */
function bulkDeleteAssets(array $ids, string $password, string $reason = ''): array
{
    $stats = ['success' => 0, 'errors' => []];

    if (empty($ids)) {
        $stats['errors'][] = 'No se seleccionaron equipos.';
        return $stats;
    }

    if (empty(trim($reason))) {
        $stats['errors'][] = 'Debe proporcionar un motivo para la baja del equipo.';
        return $stats;
    }

    // 1. Verificar contraseña
    if (session_status() === PHP_SESSION_NONE) session_start();
    $userId = $_SESSION['user_id'] ?? 0;

    $userRepo = new \Backend\Repositories\UserRepository();
    if (!$userRepo->verifyPassword($userId, $password)) {
        $stats['errors'][] = 'Contraseña de autorización incorrecta.';
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Snapshot para auditoría
        $sql = "UPDATE assets 
                SET status = 'PENDING_RETIREMENT', 
                    retirement_reason = ?, 
                    retirement_requested_at = NOW(),
                    updated_at = NOW()
                WHERE id IN ({$placeholders})";

        $params = array_merge([$reason], $ids);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $stats['success'] = $stmt->rowCount();

        if ($stats['success'] > 0) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['bulk_undo_delete'] = ['ids' => $ids, 'ts' => time()];
            unset($_SESSION['bulk_undo']);
            unset($_SESSION['bulk_undo_orders']);

            require_once __DIR__ . '/../Providers/AuditProvider.php';
            foreach ($ids as $id) {
                \Backend\Providers\logAuditAction('RETIRE_REQUEST', 'ASSET', $id, "Solicitud de baja: $reason", ['reason' => $reason]);
            }
            $stats['message'] = "{$stats['success']} equipos movidos a 'Baja Pendiente'. Tienes 24h para deshacer esta acción.";
        } else {
            $stats['errors'][] = 'No se pudieron actualizar los equipos seleccionados.';
        }
        $db->commit();
    } catch (\Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}

/**
 * Finalizar automáticamente las bajas que superaron las 24 horas de gracia.
 * Se llama al cargar páginas administrativas para mantenimiento en segundo plano.
 */
function finalizePendingRetirements(): array
{
    $stats = ['success' => 0, 'errors' => []];
    try {
        $db = DatabaseService::getInstance();
        // Buscar equipos en PENDING_RETIREMENT con > 24h
        $sql = "UPDATE assets 
                SET en_uso = 0, 
                    status = 'RETIRED',
                    updated_at = NOW()
                WHERE status = 'PENDING_RETIREMENT' 
                  AND retirement_requested_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND en_uso = 1";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $stats['success'] = $stmt->rowCount();

        if ($stats['success'] > 0) {
            require_once __DIR__ . '/../Providers/AuditProvider.php';
            // Nota: No tenemos los IDs fácilmente aquí sin un SELECT previo, 
            // pero podemos loguear la acción genérica o hacer un SELECT primero.
            \Backend\Providers\logAuditAction('SYSTEM_RETIRE_FINALIZE', 'SYSTEM', 0, "Finalización automática de {$stats['success']} bajas pendientes.");
        }
    } catch (\Exception $e) {
        $stats['errors'][] = 'Error en finalización masiva: ' . $e->getMessage();
    }
    return $stats;
}

/**
 * Forzar la finalización de una baja inmediatamente (sin esperar 24h).
 */
function finalizeAssetNow($id): array
{
    $stats = ['success' => 0, 'errors' => []];
    try {
        $db = DatabaseService::getInstance();
        // Permitimos finalizar si está en PENDING_RETIREMENT o si ya tiene en_uso=0 pero status incorrecto
        $sql = "UPDATE assets 
                SET en_uso = 0, 
                    status = 'RETIRED',
                    updated_at = NOW()
                WHERE id = ? AND (status = 'PENDING_RETIREMENT' OR en_uso = 0)";

        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $stats['success'] = $stmt->rowCount();

        if ($stats['success'] > 0) {
            require_once __DIR__ . '/../Providers/AuditProvider.php';
            \Backend\Providers\logAuditAction('RETIRE_FINALIZE', 'ASSET', $id, "Baja finalizada manualmente por el usuario.");
        } else {
            $stats['errors'][] = 'El equipo no pudo ser finalizado (verifique su estado actual).';
        }
    } catch (\Exception $e) {
        $stats['errors'][] = 'Error: ' . $e->getMessage();
    }
    return $stats;
}

/**
 * Restaurar activos que están en "Baja Pendiente" o "Dados de Baja".
 * @param array $ids         Lista de asset IDs (opcional, si viene de undo de sesión)
 * @return array ['success' => n, 'errors' => [...]]
 */
function bulkRestoreAssets(?array $ids = null): array
{
    $stats = ['success' => 0, 'errors' => []];
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($ids === null) {
        $undoData = $_SESSION['bulk_undo_delete'] ?? null;
        $ids = $undoData['ids'] ?? [];
    }

    if (empty($ids)) {
        $stats['errors'][] = 'No hay equipos para restaurar.';
        return $stats;
    }

    try {
        $db = DatabaseService::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE assets 
                SET en_uso = 1, 
                    status = 'OPERATIVE', 
                    retirement_requested_at = NULL, 
                    retirement_reason = NULL,
                    updated_at = NOW() 
                WHERE id IN ({$placeholders})";

        $stmt = $db->prepare($sql);
        $stmt->execute($ids);

        $stats['success'] = $stmt->rowCount();

        if ($stats['success'] > 0) {
            require_once __DIR__ . '/../Providers/AuditProvider.php';
            foreach ($ids as $id) {
                \Backend\Providers\logAuditAction('RESTORE', 'ASSET', $id, "Equipo restaurado desde el cementerio/papelera.");
            }
            unset($_SESSION['bulk_undo_delete']);
            $stats['message'] = $stats['success'] . ' equipo' . ($stats['success'] > 1 ? 's reincorporados' : ' reincorporado') . ' con éxito.';
        }
    } catch (\Exception $e) {
        $stats['errors'][] = 'Error DB: ' . $e->getMessage();
    }

    return $stats;
}
