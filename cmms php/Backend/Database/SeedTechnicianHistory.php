<?php

/**
 * Backend/Database/SeedTechnicianHistory.php
 * ─────────────────────────────────────────────────────
 * Generador de historial de OTs para poblar gráficos del dashboard.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\UserRepository;

try {
    $db = DatabaseService::getInstance();

    // 1. Obtener IDs de técnicos y activos existentes
    $techs = $db->query("SELECT user_id FROM technicians")->fetchAll(PDO::FETCH_COLUMN);
    $assets = $db->query("SELECT id FROM assets")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($techs) || empty($assets)) {
        die("Error: No hay técnicos o activos en la base de datos.");
    }

    echo "Iniciando generación de historial de OTs...\n";

    // 2. Limpiar OTs existentes (opcional, pero ayuda a la demo limpia)
    // $db->exec("DELETE FROM work_orders"); 

    $types = ['Preventiva', 'Correctiva', 'Calibracion'];
    $priorities = ['Baja', 'Media', 'Alta'];
    $statuses = ['Terminada', 'En Curso', 'En Espera'];

    $count = 0;

    // 3. Generar 45 OTs terminadas (Historial)
    for ($i = 0; $i < 45; $i++) {
        $id = "OT-SEEDED-" . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        $assetId = $assets[array_rand($assets)];
        $techId = $techs[array_rand($techs)];
        $type = $types[array_rand($types)];
        $priority = $priorities[array_rand($priorities)];

        // Fechas aleatorias en los últimos 3 meses
        $daysAgo = rand(1, 90);
        $createdDate = date('Y-m-d H:i:s', strtotime("-$daysAgo days -" . rand(0, 23) . " hours -" . rand(0, 59) . " minutes"));

        // Variar el tiempo de resolución (gap entre creación y completado)
        $resolutionDays = rand(0, 7); // 0 to 7 days for resolution
        $resolutionHours = rand(1, 8); // 1 to 8 hours for actual work

        // Simulate some tasks taking longer to complete (e.g., waiting for parts)
        if (rand(0, 10) < 2) { // 20% chance of longer resolution time
            $resolutionDays = rand(8, 30); // 8 to 30 days
        }

        $completedDate = date('Y-m-d H:i:s', strtotime($createdDate . ' + ' . $resolutionDays . ' days +' . rand(0, 23) . ' hours +' . rand(0, 59) . ' minutes'));

        // Ensure completed_date is not in the future
        if (strtotime($completedDate) > time()) {
            $completedDate = date('Y-m-d H:i:s');
        }

        $sql = "INSERT IGNORE INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, observations, duration_hours) 
                VALUES (:id, :asset_id, :type, 'Terminada', :tech_id, :created_date, :completed_date, :priority, 'Mantenimiento preventivo/correctivo según bitácora.', :duration)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'asset_id' => $assetId,
            'type' => $type,
            'tech_id' => $techId,
            'created_date' => $createdDate,
            'completed_date' => $completedDate,
            'priority' => $priority,
            'duration' => $resolutionHours + (rand(0, 1) ? 0.5 : 0) // Actual work duration
        ]);
        $count++;
    }

    // 4. Generar 10 OTs activas (Carga de trabajo actual)
    for ($i = 0; $i < 10; $i++) {
        $id = "OT-ACTIVE-" . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        $assetId = $assets[array_rand($assets)];
        $techId = $techs[array_rand($techs)];
        $status = rand(0, 1) ? 'En Curso' : 'En Espera';

        $sql = "INSERT IGNORE INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, priority, observations) 
                VALUES (:id, :asset_id, :type, :status, :tech_id, :created_date, :priority, 'Intervención en curso.')";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'asset_id' => $assetId,
            'type' => $types[array_rand($types)],
            'status' => $status,
            'tech_id' => $techId,
            'created_date' => date('Y-m-d'),
            'priority' => $priorities[array_rand($priorities)]
        ]);
        $count++;
    }

    echo "¡Éxito! Se generaron $count Órdenes de Trabajo de historial.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
