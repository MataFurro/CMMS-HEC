<?php
/**
 * scripts/seed_historical_data.php
 * Poblamiento masivo de datos para testing de BioCMMS.
 */

require_once __DIR__ . '/../config.php';
use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    echo "Iniciando población de datos históricos...\n";

    // 1. Obtener Activos y Técnicos
    $assets = $db->query("SELECT id FROM assets")->fetchAll(PDO::FETCH_COLUMN);
    $technicians = $db->query("SELECT user_id FROM technicians")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($assets) || empty($technicians)) {
        die("Error: No hay activos o técnicos en la base de datos.\n");
    }

    // 2. Limpiar datos antiguos (opcional, pero para esta prueba agregaremos)
    // $db->exec("DELETE FROM work_orders WHERE created_date < CURDATE()");

    $types = ['Preventiva', 'Correctiva', 'Calibracion'];
    $statuses = ['Terminada', 'En Proceso', 'Pendiente', 'En Espera'];
    $priorities = ['Baja', 'Media', 'Alta'];

    $count = 0;
    $now = new DateTime();
    
    // Generar 180 OTs (aprox 1 por día en promedio)
    for ($i = 0; $i < 180; $i++) {
        $asset_id = $assets[array_rand($assets)];
        $tech_id = $technicians[array_rand($technicians)];
        
        // Tipo ponderado: 60% Preventiva, 30% Correctiva, 10% Calibración
        $randType = rand(1, 100);
        $type = ($randType <= 60) ? 'Preventiva' : (($randType <= 90) ? 'Correctiva' : 'Calibracion');
        
        // Estado: 85% Terminada
        $randStatus = rand(1, 100);
        $status = ($randStatus <= 85) ? 'Terminada' : $statuses[array_rand($statuses)];
        
        $priority = $priorities[array_rand($priorities)];
        
        // Fecha aleatoria en los últimos 180 días
        $daysBack = rand(0, 180);
        $createdDate = (clone $now)->modify("-$daysBack days");
        $createdStr = $createdDate->format('Y-m-d');
        
        $completedStr = null;
        $duration = 0;
        
        if ($status === 'Terminada') {
            $completedDate = (clone $createdDate)->modify('+' . rand(0, 2) . ' days');
            $completedStr = $completedDate->format('Y-m-d');
            $duration = rand(10, 80) / 10; // 1.0 a 8.0 horas
        }

        $id = "OT-" . $createdDate->format('Y') . "-" . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) . "-" . $i;

        $sql = "INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, duration_hours)
                VALUES (:id, :asset_id, :type, :status, :tech_id, :created, :completed, :priority, :duration)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'asset_id' => $asset_id,
            'type' => $type,
            'status' => $status,
            'tech_id' => $tech_id,
            'created' => $createdStr,
            'completed' => $completedStr,
            'priority' => $priority,
            'duration' => $duration
        ]);
        $count++;
    }

    // 3. Generar algunas Solicitudes de Servicio (Service Requests)
    for ($j = 0; $j < 15; $j++) {
        $asset_id = $assets[array_rand($assets)];
        $daysBack = rand(0, 30);
        $createdDate = (clone $now)->modify("-$daysBack days");
        
        $sqlSR = "INSERT INTO service_requests (id, asset_id, requested_by, description, status, created_at)
                  VALUES (:id, :asset_id, :uid, :desc, :status, :created)";
        
        $stmtSR = $db->prepare($sqlSR);
        $stmtSR->execute([
            'id' => "SOL-" . $createdDate->format('Y') . "-" . str_pad($j, 4, '0', STR_PAD_LEFT),
            'asset_id' => $asset_id,
            'uid' => 5, // Dr Clínica
            'desc' => "Simulación de falla reportada por usuario en equipo $asset_id",
            'status' => 'Pendiente',
            'created' => $createdDate->format('Y-m-d H:i:s')
        ]);
    }

    echo "Población completada exitosamente. Se crearon $count órdenes de trabajo y 15 solicitudes.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
