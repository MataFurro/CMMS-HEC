<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';

$db = \Backend\Core\DatabaseService::getInstance();
$q = $db->query("SELECT id, name, riesgo_ge, subclase, criticality FROM assets WHERE riesgo_ge LIKE '%APOYO%' LIMIT 10");

echo "--- PRUEBA DE GENERACIÓN DE HEC ID ---\n";
while ($r = $q->fetch()) {
    $hecId = generateAssetHecId([
        'riesgo_ge' => $r['riesgo_ge'],
        'subclase' => $r['subclase'],
        'criticality' => $r['criticality']
    ]);
    echo "ID: {$r['id']} | NOMBRE: {$r['name']} | CLASE: {$r['riesgo_ge']} | HEC_ID GENERADO: $hecId\n";
}
