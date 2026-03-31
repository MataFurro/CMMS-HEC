<?php

/**
 * create_test_data.php
 * Genera equipos de prueba y órdenes de trabajo de forma robusta con FECHAS REALES.
 */

require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    echo "Iniciando generación de datos de prueba...\n";

    // 1. Definir Equipos de Prueba
    $testAssets = [
        ['name' => 'Monitor Signos Vitales TEST B', 'brand' => 'Mindray', 'model' => 'uMEC10', 'serial_number' => 'MR-UM-B1', 'inventory_id' => 'T301', 'criticality' => 'CRITICAL', 'status' => 'OPERATIVE', 'fecha_instalacion' => date('Y-m-d', strtotime('-2 years'))],
        ['name' => 'Electrocardiógrafo TEST B', 'brand' => 'Nihon Kohden', 'model' => 'ECG-2150', 'serial_number' => 'NK-2150-B2', 'inventory_id' => 'T302', 'criticality' => 'RELEVANT', 'status' => 'OPERATIVE', 'fecha_instalacion' => date('Y-m-d', strtotime('-1 year'))]
    ];

    $createdIds = [];
    foreach ($testAssets as $data) {
        $id = saveAsset($data);
        if ($id) {
            echo "[OK] Equipo: {$data['name']} (ID: $id)\n";
            $createdIds[] = $id;
        }
    }

    // 2. Crear OTs
    if (!empty($createdIds)) {
        // Usar CAST para evitar problemas de ordenamiento de strings
        $maxId = $db->query("SELECT MAX(CAST(id AS UNSIGNED)) FROM work_orders WHERE id REGEXP '^[0-9]+$'")->fetchColumn() ?: 0;
        $nextId = $maxId + 1;

        $techId = $db->query("SELECT id FROM users WHERE role = 'TECHNICIAN' LIMIT 1")->fetchColumn() ?: 1;

        foreach ($createdIds as $assetId) {
            $sql = "INSERT INTO work_orders (
                        id, asset_id, type, status, assigned_tech_id, priority, 
                        observations, duration_hours, final_asset_status, 
                        created_date, created_at, completed_date, scheduled_at, checklist_template
                    ) VALUES (
                        :id, :asset_id, 'Correctiva', 'Terminada', :tech_id, 'Alta', 
                        'Mantenimiento de prueba para validación de gráficos.', :duration, 'OPERATIVE', 
                        CURRENT_DATE, NOW(), CURRENT_DATE, NOW(), 'formato_general'
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id' => (string)$nextId,
                'asset_id' => $assetId,
                'tech_id' => $techId,
                'duration' => rand(2, 5)
            ]);
            echo "[OK] OT ID $nextId creada para Activo $assetId\n";
            $nextId++;
        }
    }

    echo "Finalizado con éxito.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
