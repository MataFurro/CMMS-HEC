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

    // 2. Poblar costos de adquisición en los activos (entre $500.000 y $15.000.000 CLP)
    // Esto asegura que haya datos para los análisis financieros
    $db->exec("UPDATE assets SET acquisition_cost = FLOOR(500000 + RAND() * 14500000) WHERE acquisition_cost IS NULL OR acquisition_cost = 0");
    echo "Costos de adquisición generados aleatoriamente para los equipos.\n";

    // 2. Limpiar datos antiguos (opcional, pero para esta prueba agregaremos)
    $db->exec("DELETE FROM work_orders");
    $db->exec("DELETE FROM service_requests");

    $types = ['Preventiva', 'Correctiva', 'Calibracion'];
    $statuses = ['Terminada', 'En Proceso', 'Pendiente', 'En Espera'];
    $priorities = ['Baja', 'Media', 'Alta'];

    $count = 0;
    $now = new DateTime();
    
    // Generar 4000 OTs (aprox 1.3 OTs por equipo en promedio)
    for ($i = 0; $i < 4000; $i++) {
        $asset_id = $assets[array_rand($assets)];
        $tech_id = $technicians[array_rand($technicians)];
        
        // Tipo ponderado: 70% Preventiva, 20% Correctiva, 10% Calibración
        $randType = rand(1, 100);
        $type = ($randType <= 70) ? 'Preventiva' : (($randType <= 90) ? 'Correctiva' : 'Calibracion');
        
        // Estado: 90% Terminada
        $randStatus = rand(1, 100);
        $status = ($randStatus <= 90) ? 'Terminada' : $statuses[array_rand($statuses)];
        
        $priority = $priorities[array_rand($priorities)];
        
        // Fecha aleatoria en el último año (365 días)
        $daysBack = rand(0, 365);
        $createdDate = (clone $now)->modify("-$daysBack days");
        $createdStr = $createdDate->format('Y-m-d');
        
        $completedStr = null;
        $duration = 0;
        $cost = 0;
        
        if ($status === 'Terminada') {
            $completedDate = (clone $createdDate)->modify('+' . rand(0, 5) . ' days');
            $completedStr = $completedDate->format('Y-m-d');
            $duration = rand(10, 120) / 10; // 1.0 a 12.0 horas
            $cost = rand(15000, 450000); // Costo repuestos/insumos
        }

        $id = "OT-" . $createdDate->format('Y') . "-" . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
        $obs = ($type === 'Preventiva') ? "[AUTO] Mantenimiento preventivo según protocolo. Pruebas funcionales exitosas." : "[AUTO] Atención de falla reportada. Cambio de componentes menores y verificación.";

        $sql = "INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, duration_hours, observations)
                VALUES (:id, :asset_id, :type, :status, :tech_id, :created, :completed, :priority, :duration, :obs)";
        
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
            'duration' => $duration,
            'obs' => $obs
        ]);
        $count++;
    }

    // 3. Generar algunas Solicitudes de Servicio (Service Requests)
    for ($j = 0; $j < 45; $j++) {
        $asset_id = $assets[array_rand($assets)];
        $daysBack = rand(0, 60);
        $createdDate = (clone $now)->modify("-$daysBack days");
        
        $sqlSR = "INSERT INTO service_requests (id, asset_id, requested_by, description, status, created_at)
                  VALUES (:id, :asset_id, :uid, :desc, :status, :created)";
        
        $stmtSR = $db->prepare($sqlSR);
        $stmtSR->execute([
            'id' => "SOL-" . $createdDate->format('Y') . "-" . str_pad($j + 1, 4, '0', STR_PAD_LEFT),
            'asset_id' => $asset_id,
            'uid' => 5, // Dr Clínica
            'desc' => "Simulación de falla reportada por usuario (Unidad de Enfermería) en equipo $asset_id",
            'status' => 'Pendiente',
            'created' => $createdDate->format('Y-m-d H:i:s')
        ]);
    }

    echo "Población completada exitosamente. Se crearon $count órdenes de trabajo y 45 solicitudes.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
