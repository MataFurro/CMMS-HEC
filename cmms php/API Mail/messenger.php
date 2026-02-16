<?php

/**
 * API Mail/messenger.php
 * Punto de entrada para reportes rápidos del personal clínico.
 * Recibe: email, serie, equipo, texto e imagen.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bridge.php';

header('Content-Type: application/json');

// Simulación de procesamiento de entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email_solicitante' => $_POST['email'] ?? '',
        'serie_equipo'      => $_POST['serie'] ?? '',
        'nombre_equipo'     => $_POST['equipo'] ?? '',
        'descripcion'       => $_POST['texto'] ?? '',
        'timestamp'         => date('Y-m-d H:i:s')
    ];

    // Procesar Imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $filename = 'report_' . time() . '.' . $ext;
        if (!is_dir(MS_UPLOAD_DIR)) {
            mkdir(MS_UPLOAD_DIR, 0777, true);
        }
        move_uploaded_file($_FILES['imagen']['tmp_name'], MS_UPLOAD_DIR . $filename);
        $data['path_imagen'] = $filename;
    }

    // Transportar al sistema (Simulado)
    $bridge = new MessengerBridge();
    $result = $bridge->enviarReporteAlSistema($data);

    echo json_encode([
        'status' => 'success',
        'message' => 'El Mensajero ha recibido los datos correctamente.',
        'tracking_id' => $result,
        'received_data' => $data
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Se requiere método POST']);
}
