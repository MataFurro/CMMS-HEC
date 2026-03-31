<?php
/**
 * scripts/populate_criticality.php
 * Asignación de Puntos GE y Criticidad para Dashboard.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

try {
    $db = Backend\Core\DatabaseService::getInstance();
    echo "Iniciando actualización de Criticidad de Activos...\n";

    $assets = $db->query("SELECT id, name FROM assets")->fetchAll();
    $total = count($assets);
    $count = 0;

    $stmt = $db->prepare("UPDATE assets SET 
        funcion_ge = :f, 
        riesgo_ge_score = :r, 
        mantenimiento_ge = :m,
        criticality = :crit
        WHERE id = :id");

    foreach ($assets as $asset) {
        // Distribución: 15% Críticos, 35% Relevantes, 50% Bajos
        $randDist = rand(1, 100);
        
        if ($randDist <= 15) {
            // CRITICAL (GE >= 12)
            $f = rand(7, 10); // Soporte de vida
            $r = rand(4, 5);  // Riesgo alto
            $m = rand(4, 5);  // Intensivo
            $crit = 'CRITICAL';
        } elseif ($randDist <= 50) {
            // RELEVANT (GE 9-11)
            $f = rand(4, 6);  // Diagnóstico
            $r = rand(2, 3);
            $m = rand(2, 3);
            $crit = 'RELEVANT';
        } else {
            // LOW (GE < 9)
            $f = rand(1, 3);  // Apoyo
            $r = rand(1, 2);
            $m = rand(1, 2);
            $crit = 'LOW';
        }

        $stmt->execute([
            'f' => $f,
            'r' => $r,
            'm' => $m,
            'crit' => $crit,
            'id' => $asset['id']
        ]);
        $count++;
    }

    echo " - Se actualizaron $count assets con nuevos scores GE y niveles de criticidad.\n";
    echo "Procesamiento completado.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
