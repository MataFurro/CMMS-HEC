<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bridge.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => $_POST['email'] ?? '',
        'serie' => $_POST['serie'] ?? '',
        'equipo' => $_POST['equipo'] ?? '',
        'servicio' => $_POST['servicio'] ?? '',
        'texto' => $_POST['texto'] ?? '',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $filename = 'report_' . time() . '.' . $ext;
        if (!is_dir(MS_UPLOAD_DIR)) {
            mkdir(MS_UPLOAD_DIR, 0777, true);
        }
        move_uploaded_file($_FILES['imagen']['tmp_name'], MS_UPLOAD_DIR . $filename);
        $data['imagen_path'] = $filename;
    }

    $bridge = new MessengerBridge();
    $id = $bridge->procesarReporteInterno($data);

    echo json_encode([
        'status' => 'success',
        'tracking_id' => $id,
        'message' => 'Reporte recibido correctamente.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solo POST permitido']);
}
