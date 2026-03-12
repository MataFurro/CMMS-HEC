<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
$s = $db->query("DESC assets");
echo "--- ESTRUCTURA DE TABLA ASSETS ---\n";
while ($r = $s->fetch()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
