<?php
// Simular carga de Dashboard para disparar la caché
$_GET['class'] = 'all';
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/Backend/Providers/WorkOrderProvider.php';

// Mock de funciones necesarias si no están en providers (para evitar fallos de salida)
if (!function_exists('getTechnicianProductivity')) { function getTechnicianProductivity() { return []; } }
if (!function_exists('getRecentEvents')) { function getRecentEvents() { return []; } }

$cacheFile = __DIR__ . '/storage/reliability_cache.json';
if (file_exists($cacheFile)) unlink($cacheFile);

echo "Iniciando simulacion de Dashboard...\n";
ob_start();
include __DIR__ . '/pages/dashboard.php';
ob_end_clean();

if (file_exists($cacheFile)) {
    echo "[OK] Archivo de caché generado: $cacheFile\n";
    $data = json_decode(file_get_contents($cacheFile), true);
    if (isset($data['reliabilityByFamily'])) {
        echo "[OK] Datos de confiabilidad encontrados en caché.\n";
    }
} else {
    echo "[ERROR] No se generó el archivo de caché.\n";
}
