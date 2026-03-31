<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$s = $db->query("SELECT DISTINCT riesgo_ge, subclase FROM assets WHERE riesgo_ge LIKE '%APOYO%' LIMIT 20");
echo "--- MUESTRA DE CLASE Y SUBCLASE DE APOYO ---\n";
while ($r = $s->fetch()) {
    echo "CLASE: " . $r['riesgo_ge'] . " | SUBCLASE: " . $r['subclase'] . "\n";
}
