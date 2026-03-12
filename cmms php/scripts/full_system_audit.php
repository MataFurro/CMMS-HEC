<?php

/**
 * scripts/full_system_audit.php
 * Auditoría profunda del sistema BioCMMS
 */

require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

use Backend\Core\DatabaseService;

echo "--- INICIANDO AUDITORÍA INTEGRAL BIOCMMS ---\n\n";

$db = DatabaseService::getInstance();

// 1. Verificación de Activos
echo "[1/4] Auditando Activos...\n";
$stmt = $db->query("SELECT COUNT(*) FROM assets WHERE en_uso = 1");
$countOperative = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM assets");
$countTotal = $stmt->fetchColumn();

echo " - Equipos Operativos: $countOperative\n";
echo " - Equipos Totales (inc. retirados): $countTotal\n";

if ($countOperative != 3110) {
    echo " [!] ADVERTENCIA: Se esperaban 3110 equipos operativos. Detectados: $countOperative\n";
} else {
    echo " [OK] Conteo de inventario exacto.\n";
}

// 2. Verificación de IDs HEC
echo "\n[2/4] Verificando Integridad de IDs HEC...\n";
$stmt = $db->query("SELECT id, inventory_id, name FROM assets WHERE inventory_id NOT LIKE 'HEC-%' AND en_uso = 1 LIMIT 5");
$badIds = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($badIds)) {
    echo " [!] ADVERTENCIA: Se encontraron activos sin formato HEC- (Ej: " . $badIds[0]['inventory_id'] . ")\n";
} else {
    echo " [OK] Todos los activos operativos tienen ID HEC.\n";
}

// 3. Verificación de Técnicos y Asignación
echo "\n[3/4] Auditando Perfiles Técnicos...\n";
$technicians = getActiveTechnicians();
echo " - Técnicos Detectados: " . count($technicians) . "\n";

if (empty($technicians)) {
    echo " [!] ERROR: No hay técnicos registrados en el sistema.\n";
} else {
    foreach ($technicians as $t) {
        echo "   > " . $t['name'] . " (" . ($t['specialty'] ?? 'General') . ")\n";
    }
    echo " [OK] Perfiles técnicos operativos.\n";
}

// 4. Verificación de Flujo de Bajas
echo "\n[4/4] Verificando Flujo de Bajas y Cementerio...\n";
$stmt = $db->query("SELECT COUNT(*) FROM assets WHERE status = 'RETIRED' OR status = 'PENDING_RETIREMENT'");
$countRetired = $stmt->fetchColumn();
echo " - Equipos en Cementerio/Trámite: $countRetired\n";

$stmt = $db->query("SELECT id FROM assets WHERE status = 'OPERATIVE' LIMIT 1");
$sampleId = $stmt->fetchColumn();

if ($sampleId) {
    echo " - Simulando disponibilidad de baja para Activo #$sampleId: [DISPONIBLE]\n";
}

echo "\n--- AUDITORÍA FINALIZADA ---\n";
