<?php
/**
 * scripts/populate_massive_v2.php (FIXED)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
require_once __DIR__ . '/../Backend/Providers/UserProvider.php'; // Para isReadOnly
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['user_id'] = 1; 
$_SESSION['user_role'] = ROLE_CHIEF_ENGINEER;

$db = Backend\Core\DatabaseService::getInstance();

echo "--- INICIANDO SIMULACIÓN TÉCNICA (V2 RE-RUN) ---\n";

// Limpiar simulaciones anteriores para no duplicar basura
$db->query("DELETE FROM work_orders WHERE observations LIKE '%[BIO-V2]%' OR observations LIKE '%[V2-LIVE]%' OR observations LIKE '%[V2-STALL]%'");
$db->query("DELETE FROM assets WHERE observations LIKE '%[BIO-V2]%'");

$techCatalog = [
    ['name' => 'Monitor Signos Vitales Pro', 'family' => 'MONITOREO', 'freq' => 6, 'probs' => ['Falla en sensor SpO2', 'Batería agotada', 'Ruido en señal ECG']],
    ['name' => 'Bomba de Infusión Universal', 'family' => 'BOMBAS', 'freq' => 12, 'probs' => ['Error de oclusión', 'Falla en sensor de aire', 'Descalibración volumétrica']],
    ['name' => 'Ventilador Mecánico de Transporte', 'family' => 'VENTILADORES', 'freq' => 4, 'probs' => ['Falla en celda de O2', 'Fuga en circuito', 'Error de software']],
    ['name' => 'Desfibrilador Externo', 'family' => 'DESFIBRILADORES', 'freq' => 24, 'probs' => ['Error de autotest', 'Paletas dañadas', 'Falla en carga de energía']],
    ['name' => 'Incubadora de Neonatología', 'family' => 'CUIDADOS CRÍTICOS', 'freq' => 6, 'probs' => ['Falla en sensor de temperatura', 'Ruido en motor ventilador', 'Fisura en cúpula']]
];

$locations = ['UCI Adultos', 'UCI Neonatal', 'Urgencias', 'Pabellón 1', 'Pabellón 3', 'Piso 2 Ala Norte'];
$assetIds = [];

echo "Creando Activos...\n";
for ($i = 0; $i < 20; $i++) {
    $item = $techCatalog[array_rand($techCatalog)];
    $invId = 'HEC-V2-' . rand(10000, 99999);
    
    $data = [
        'inventory_id' => $invId,
        'name' => $item['name'] . " " . ($i+1),
        'brand' => 'Medical Brand ' . rand(1, 5),
        'model' => 'Model-' . chr(rand(65, 90)) . rand(10, 99),
        'serial_number' => 'SN-' . strtoupper(substr(md5(uniqid()), 0, 10)),
        'location' => $locations[array_rand($locations)],
        'criticality' => rand(0, 1) ? 'CRITICAL' : 'RELEVANT',
        'status' => 'OPERATIVE',
        'riesgo_ge' => $item['family'],
        'frecuencia_mp_meses' => $item['freq'],
        'en_uso' => 1,
        'ownership' => 'Propio',
        'observations' => '[BIO-V2] Registro técnico enriquecido.'
    ];
    
    saveAsset($data);
    
    // Recuperar ID real por inventory_id (Dada la inconsistencia de lastInsertId)
    $stmt = $db->prepare("SELECT id FROM assets WHERE inventory_id = ?");
    $stmt->execute([$invId]);
    $realId = $stmt->fetchColumn();
    
    if ($realId) {
        $assetIds[] = $realId;
    }
}

echo "Generando Historial y Checklists...\n";
foreach ($assetIds as $aid) {
    // 1 OT histórica terminada
    $type = rand(0, 5) > 1 ? 'Preventiva' : 'Correctiva';
    $complDate = date('Y-m-d', strtotime('-' . rand(10, 150) . ' days'));
    
    $otId = createWorkOrder([
        'asset_id' => $aid,
        'type' => $type,
        'status' => 'Terminada',
        'observations' => "[BIO-V2] Intervención técnica exitosa. Pruebas de seguridad eléctrica OK."
    ]);
    
    if ($otId) {
        $db->prepare("UPDATE work_orders SET completed_date = ?, status = 'Terminada', duration_hours = ? WHERE id = ?")
           ->execute([$complDate, 1.5, $otId]);
           
        // Checklist
        $db->prepare("INSERT INTO checklist_results (work_order_id, asset_id, template_key, qualitative_results, quantitative_results, completed_at) 
                      VALUES (?, ?, 'GENERIC_PMP', ?, ?, ?)")
           ->execute([$otId, $aid, json_encode(['Limpieza'=>'OK', 'Cables'=>'OK']), json_encode(['Power'=>220]), $complDate]);
    }
}

echo "Simulando Estados 'MAINTENANCE' Activos...\n";
foreach (array_slice($assetIds, 0, 5) as $aid) {
    $otId = createWorkOrder([
        'asset_id' => $aid,
        'type' => 'Correctiva',
        'status' => 'En Curso',
        'observations' => '[V2-LIVE] Equipo en revisión por falla reportada por servicio.'
    ]);
    
    // Verificar si el activo cambió a MAINTENANCE automáticamente
    $check = $db->prepare("SELECT status FROM assets WHERE id = ?");
    $check->execute([$aid]);
    $currStatus = $check->fetchColumn();
    echo "Activo $aid -> Estado: $currStatus (OT: $otId)\n";
}

echo "\n--- SIMULACIÓN FINALIZADA ---";
