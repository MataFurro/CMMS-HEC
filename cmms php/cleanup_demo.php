<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

echo "--- LIMPIEZA DE DUPLICADOS DE DEMO ---\n";

// Eliminar los intentos fallidos del demo (3110, 3111, 3112)
$db->prepare("DELETE FROM assets WHERE id IN (3110, 3111, 3112)")->execute();
echo "✅ Eliminados IDs 3110, 3111, 3112 (duplicados de prueba).\n";

// Poner el último demo (3113) de vuelta a OPERATIVE
$db->prepare("UPDATE assets SET status = 'OPERATIVE', en_uso = 1 WHERE id = 3113")->execute();
echo "✅ Equipo ID 3113 restaurado a estado OPERATIVO.\n";

$total = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
echo "\nNUEVO TOTAL EN DB: $total (Esperado: 3110, que son 3109 + 1 demo)\n";
echo "-------------------------------------\n";
