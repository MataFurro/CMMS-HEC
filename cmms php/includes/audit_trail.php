<?php
// includes/audit_trail.php

/**
 * Función para registrar eventos en la Pista de Auditoría (Audit Trail)
 * Cumplimiento con FDA 21 CFR Part 11
 */
function logAuditAction($userId, $action, $targetId, $targetType, $data = [])
{
    $logFile = __DIR__ . '/../logs/audit_trail.json';
    $logDir = __DIR__ . '/../logs';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $entry = [
        'timestamp' => date('c'),
        'user_id' => $userId,
        'action' => $action,
        'target_id' => $targetId,
        'target_type' => $targetType,
        'details' => $data,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ];

    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }

    array_unshift($logs, $entry); // Agregar al principio

    // Mantener solo los últimos 1000 registros para esta demo
    if (count($logs) > 1000) {
        $logs = array_slice($logs, 0, 1000);
    }

    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

/**
 * Función para obtener los logs de auditoría de un activo específico
 */
function getAuditLogsByTarget($targetId)
{
    $logFile = __DIR__ . '/../logs/audit_trail.json';
    if (!file_exists($logFile)) return [];

    $logs = json_decode(file_get_contents($logFile), true) ?: [];
    return array_filter($logs, fn($log) => $log['target_id'] === $targetId);
}
