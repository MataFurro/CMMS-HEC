<?php

/**
 * simulate_usage.php
 * Simulación integral del ciclo de vida de mantenimiento biomédico.
 * ───────────────────────────────────────────────────────────────
 * 1. Genera Solicitudes Clínicas (Reports)
 * 2. Revisiones y Conversión a OTs Correctivas
 * 3. Ejecución Técnica y Cierre de OTs
 * 4. Impacto en Disponibilidad y KPIs
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/Backend/Providers/UserProvider.php';

use Backend\Core\DatabaseService;

echo "🧪 INICIANDO SIMULACIÓN DE FLUJO CMMS BIOMÉDICO...\n\n";

try {
    $db = DatabaseService::getInstance();

    // --- 0. PREPARACIÓN: Obtener recursos reales ---
    $assets = getAllAssets();
    if (empty($assets)) {
        throw new Exception("No hay activos en la base de datos para simular. Ejecute seed_full_system.php primero.");
    }

    $techs = getTechnicianProductivity();
    if (empty($techs)) {
        // Buscar usuarios con rol técnico si no hay en la tabla technicians
        $stmtTechUsers = $db->query("SELECT id, name FROM users WHERE role = 'TECHNICIAN' OR role = 'CHIEF_ENGINEER'");
        $techUsers = $stmtTechUsers->fetchAll(PDO::FETCH_ASSOC);
        if (empty($techUsers)) {
            throw new Exception("No hay técnicos disponibles para asignar trabajo.");
        }
    } else {
        // Mapear técnicos a sus IDs de usuario correspondientes
        $stmtTechUsers = $db->query("SELECT u.id, u.name FROM users u JOIN technicians t ON u.id = t.user_id");
        $techUsers = $stmtTechUsers->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 1. FASE DE SOLICITUDES (MESSENGER REPORTS) ---
    echo "📩 FASE 1: Generando Solicitudes Clínicas Masivas...\n";
    $requestCount = floor(count($assets) * 0.15); // Simular reportes para el 15% del inventario
    $randomKeys = array_rand($assets, min($requestCount, 50));
    if (!is_array($randomKeys)) $randomKeys = [$randomKeys];

    $requestIds = [];
    foreach ($randomKeys as $idx) {
        $asset = $assets[$idx];
        $idStr = $asset['id'];
        $nameStr = $asset['name'];
        $email = "clínico_" . strtolower(str_replace(' ', '_', $asset['location'] ?? 'central')) . "@hospital.cl";

        // Simular diferentes niveles de falla
        $fallas = [
            "Error persistente en calibración de sensor.",
            "Ruido excesivo durante operación nominal.",
            "Pantalla no responde a comandos táctiles.",
            "Batería no retiene carga suficiente para transporte.",
            "Equipo se apaga inesperadamente tras 10 minutos."
        ];
        $falla = $fallas[array_rand($fallas)];

        // Inyectar con fecha aleatoria en los últimos 3 meses para historial
        $daysAgo = rand(0, 90);
        $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        $stmt = $db->prepare("INSERT INTO messenger_reports (email, asset_id, asset_name, texto, status, created_at) VALUES (?, ?, ?, ?, 'Pendiente', ?)");
        $stmt->execute([$email, $idStr, $nameStr, $falla, $createdAt]);
        $reqId = $db->lastInsertId();
        $requestIds[] = ['id' => $reqId, 'asset_id' => $idStr, 'asset_name' => $nameStr, 'email' => $email, 'text' => $falla, 'date' => $createdAt];
    }
    echo "   [+] Se generaron " . count($requestIds) . " solicitudes distribuidas en el tiempo.\n";

    // --- 2. FASE DE CONVERSIÓN (CREACIÓN DE OTs) ---
    echo "\n🛠️ FASE 2: Conversión Masiva a OTs Correctivas...\n";
    $otIds = [];
    $toConvertCount = floor(count($requestIds) * 0.8); // 80% de conversión

    for ($i = 0; $i < $toConvertCount; $i++) {
        $req = $requestIds[$i];
        $tech = $techUsers[array_rand($techUsers)];
        $priority = (rand(0, 2) === 0 ? 'Alta' : 'Media');

        $otId = createWorkOrderFromRequest([
            'asset_id' => $req['asset_id'],
            'priority' => $priority,
            'problem' => $req['text'],
            'ms_request_id' => $req['id'],
            'ms_email' => $req['email']
        ]);

        // Ajustar fecha de creación para que coincida con la solicitud
        $db->prepare("UPDATE work_orders SET assigned_tech_id = ?, created_date = ?, priority = ? WHERE id = ?")
            ->execute([$tech['id'], date('Y-m-d', strtotime($req['date'])), $priority, $otId]);

        $otIds[] = ['id' => $otId, 'asset_id' => $req['asset_id'], 'date' => $req['date']];
    }
    echo "   [⚙️] Se convirtieron " . count($otIds) . " solicitudes en Órdenes de Trabajo.\n";

    // --- 3. FASE DE EJECUCIÓN Y CIERRE (HISTORIA DE MANTENIMIENTO) ---
    echo "\n✅ FASE 3: Ejecución Masiva y Cierre (Poblando el Historial)...\n";
    $toCompleteCount = floor(count($otIds) * 0.7); // 70% completadas

    for ($i = 0; $i < $toCompleteCount; $i++) {
        $ot = $otIds[$i];
        $duration = rand(1, 8) + (rand(0, 9) / 10);
        $finalStatus = rand(0, 10) === 0 ? 'OPERATIVE_WITH_OBS' : 'OPERATIVE';

        // Fecha de cierre: 1-3 días después de la creación
        $daysToComplete = rand(1, 4);
        $completedDate = date('Y-m-d', strtotime($ot['date'] . " + $daysToComplete days"));

        $res = completeWorkOrder($ot['id'], [
            'duration_hours' => $duration,
            'final_asset_status' => $finalStatus,
            'observations' => "Intervención realizada con éxito. Revisión de circuitos y validación con analizador patrón.",
            'failure_code' => 'GENERAL_REPAIR'
        ]);

        if ($res) {
            // Ajustar fecha de cierre real en la BD para que el Dashboard sea creíble
            $db->prepare("UPDATE work_orders SET completed_date = ? WHERE id = ?")->execute([$completedDate, $ot['id']]);
        }
    }
    echo "   [🏁] Se cerraron " . $toCompleteCount . " OTs exitosamente, generando historial de confiabilidad.\n";

    // --- 4. PREVENTIVAS DIRECTAS PARA CALENDARIO ---
    echo "\n📅 FASE 4: Inyectando Mantenimientos Preventivos (Planificación)...\n";
    $prevAssets = array_rand($assets, 3);
    foreach ($prevAssets as $idx) {
        $asset = $assets[$idx];
        $otId = createWorkOrder([
            'asset_id' => $asset['id'],
            'type' => 'Preventiva',
            'priority' => 'Baja',
            'status' => 'Pendiente',
            'observations' => 'Mantenimiento Preventivo Semestral según manual del fabricante.'
        ]);
        echo "   [🗓️] Preventiva Generada: $otId para: {$asset['name']}\n";
    }

    echo "\n🏆 SIMULACIÓN COMPLETADA EXITOSAMENTE.\n";
    echo "📊 El Dashboard ahora refleja la carga de trabajo de los técnicos y el uptime de los equipos.\n";
} catch (Exception $e) {
    echo "\n❌ FALLO EN SIMULACIÓN: " . $e->getMessage() . "\n";
    echo "StackTrace: " . $e->getTraceAsString() . "\n";
}
