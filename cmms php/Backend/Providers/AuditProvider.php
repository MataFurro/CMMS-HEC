<?php

namespace Backend\Providers;

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Core/LoggerService.php';

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;
use PDO;
use Exception;

/**
 * Backend/Providers/AuditProvider.php
 * ─────────────────────────────────────────────────────
 * Proveedor de Auditoría de Sistema (FDA 21 CFR Part 11 compliant).
 * Registra la acción, justificación técnica y trazabilidad.
 * ─────────────────────────────────────────────────────
 */

/**
 * Registra una acción en la pista de auditoría con justificación técnica.
 */
function logAuditAction(string $action, string $targetType, ?string $targetId, string $reason, array $details = []): bool
{
    try {
        $db = DatabaseService::getInstance();

        $sql = "INSERT INTO audit_trail (user_id, action, asset_id, target_type, details, ip_address, timestamp) 
                VALUES (:user_id, :action, :asset_id, :target_type, :details, :ip, NOW())";

        $stmt = $db->prepare($sql);

        // Determinar si debemos vincular a un asset_id real en la DB
        // Solo si el targetType es ASSET y el targetId es numérico (ID interno de DB)
        $assetIdForDb = null;
        if (strtoupper($targetType) === 'ASSET' && !empty($targetId) && is_numeric($targetId)) {
            $assetIdForDb = (int)$targetId;
        }

        // Enriquecer detalles con el razonamiento sistemático
        $enrichedDetails = array_merge($details, [
            'agentic_reasoning' => $reason,
            'system_version' => 'v4.7-stable',
            'session_id' => session_id() ?: 'cli'
        ]);

        return $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? 1, // Default Admin if not set
            ':action' => strtoupper($action),
            ':asset_id' => $assetIdForDb,
            ':target_type' => $targetType,
            ':details' => json_encode(array_merge($enrichedDetails, ['target_id' => $targetId])),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        LoggerService::error("AUDIT LOGGING FAILED", [
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Obtener el historial de auditoría para un activo específico.
 */
function getAssetAuditHistory(string $assetId): array
{
    try {
        $db = DatabaseService::getInstance();
        $sql = "SELECT at.*, u.name as user_name 
                FROM audit_trail at
                LEFT JOIN users u ON at.user_id = u.id
                WHERE at.asset_id = :asset_id
                ORDER BY at.timestamp DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':asset_id' => $assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
