<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$s = $db->query("SELECT DISTINCT subclase FROM assets WHERE riesgo_ge LIKE '%APOYO%'");
echo "--- SUBCLASES DE APOYO ---\n";
while ($r = $s->fetch()) {
    echo $r['subclase'] . "\n";
}
