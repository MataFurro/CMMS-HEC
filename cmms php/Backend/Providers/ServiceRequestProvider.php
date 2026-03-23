<?php
// Backend/Providers/ServiceRequestProvider.php

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/WorkOrderProvider.php';

/**
 * Obtener todas las solicitudes de servicio pendientes.
 */
function getPendingServiceRequests(): array {
    $db = \Backend\Core\DatabaseService::getInstance();
    $sql = "SELECT sr.*, a.name as asset_name, a.location, a.inventory_id, u.name as requester_name 
            FROM service_requests sr
            JOIN assets a ON sr.asset_id = a.id
            LEFT JOIN users u ON sr.requested_by = u.id
            WHERE sr.status = 'Pendiente'
            ORDER BY sr.created_at ASC";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Aprobar una solicitud y convertirla en Orden de Trabajo.
 */
function approveServiceRequest(string $id, ?string $diagnosis, ?int $techId, string $type = 'Correctiva'): string {
    $db = \Backend\Core\DatabaseService::getInstance();
    
    // 1. Obtener datos de la solicitud
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception("Solicitud no encontrada.");
    }
    
    // 2. Crear Orden de Trabajo usando el provider central
    $otId = createWorkOrder([
        'asset_id' => $request['asset_id'],
        'type' => $type,
        'priority' => $request['priority'],
        'observations' => $diagnosis ?: $request['description'],
        'assigned_tech_id' => $techId,
        'ms_request_id' => $id,
        'ms_email' => $request['requester_email']
    ]);
    
    if ($otId) {
        // 3. Actualizar estado de la solicitud
        $stmt = $db->prepare("UPDATE service_requests SET status = 'Convertida_OT', generated_ot_id = ? WHERE id = ?");
        $stmt->execute([$otId, $id]);
    }
    
    return $otId;
}

/**
 * Rechazar una solicitud (Archivar).
 */
function rejectServiceRequest(string $id, string $reason): bool {
    $db = \Backend\Core\DatabaseService::getInstance();
    $stmt = $db->prepare("UPDATE service_requests SET status = 'Rechazada', description = CONCAT(description, '\n\n[RECHAZO]: ', :reason) WHERE id = :id");
    return $stmt->execute(['id' => $id, 'reason' => $reason]);
}
