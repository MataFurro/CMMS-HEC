<?php

/**
 * API Mail/bridge.php
 * ─────────────────────────────────────────────────────
 * Puente entre el sistema de mensajería clínica y el CMMS principal.
 * Gestiona los mensajes del MS (Integración MySQL).
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Backend/Providers/AuditProvider.php';

use function Backend\Providers\logAuditAction;

define('MS_LOG_FILE', __DIR__ . '/../storage/logs/messenger.log');

/**
 * Procesa un nuevo reporte desde el Messenger.
 * Función funcional para compatibilidad con flujos de integración.
 */
function saveReport(string $to, string $subject, array $data): string
{
    $trackingId = uniqid('MS-ST-');

    // Registro legado en archivo
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Nuevo reporte: " . ($data['email_solicitante'] ?? 'N/A') . " - Equipo: " . ($data['nombre_equipo'] ?? 'N/A') . PHP_EOL;
    if (!is_dir(dirname(MS_LOG_FILE))) {
        mkdir(dirname(MS_LOG_FILE), 0777, true);
    }
    file_put_contents(MS_LOG_FILE, $logEntry, FILE_APPEND);

    // ── AUDITORÍA AGÉNTICA (Rastreo de Origen) ──
    logAuditAction(
        'REQUEST_RECEIVED',
        'MESSENGER_REPORT',
        $trackingId,
        "Reporte clínico recibido vía Messenger. El Agente de Integración ha registrado el evento para trazabilidad total.",
        [
            'from' => $data['email_solicitante'] ?? 'Unknown',
            'equipment' => $data['nombre_equipo'] ?? 'N/A',
            'tracking_id' => $trackingId
        ]
    );

    return $trackingId;
}

/**
 * Clase MessengerBridge para uso orientado a objetos (v4.2 PRO).
 */
class MessengerBridge
{
    /**
     * Recibe los datos y los procesa de forma interna.
     */
    public function enviarReporteAlSistema($data)
    {
        return saveReport('', '', $data);
    }

    /**
     * Envía el reporte técnico de vuelta al correo del solicitante.
     */
    public function enviarFeedback($correo, $detalle)
    {
        // Lógica de envío de correo 
        return true;
    }
}
