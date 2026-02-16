<?php

/**
 * API Mail/bridge.php
 * Clase que gestiona los mensajes del MS (Standalone).
 */

require_once __DIR__ . '/config.php';

class MessengerBridge
{

    /**
     * Recibe los datos y los procesa de forma interna.
     */
    public function enviarReporteAlSistema($data)
    {
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Nuevo reporte: " . $data['email_solicitante'] . " - Equipo: " . $data['nombre_equipo'] . PHP_EOL;
        file_put_contents(MS_LOG_FILE, $logEntry, FILE_APPEND);

        // Simulación de ID de rastreo
        return uniqid('MS-ST-');
    }

    /**
     * Envía el reporte técnico de vuelta al correo del solicitante.
     */
    public function enviarFeedback($correo, $detalle)
    {
        // Lógica de envío de correo standalone
        return true;
    }
}
