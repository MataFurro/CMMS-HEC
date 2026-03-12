<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();
// Como la tabla está vacía en este punto del hilo, intentaré buscar en los logs o si hay datos previos
// Pero sé que el usuario subirá 3109 equipos. 
// Intentaré simular o pedir ejemplos si no hay datos.
$s = $db->query("SELECT name FROM assets LIMIT 50");
$names = $s->fetchAll(PDO::FETCH_COLUMN);
if (empty($names)) {
    echo "NO_DATA_FOUND\n";
} else {
    foreach ($names as $n) echo "NAME: $n\n";
}
