<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
$db = \Backend\Core\DatabaseService::getInstance();

$countInUse = $db->query("SELECT COUNT(*) FROM assets WHERE en_uso = 1 AND status != 'RETIRED' AND status != 'PENDING_RETIREMENT'")->fetchColumn();
echo "Total Activos (Operativos/Visibles): $countInUse\n";

$countTotal = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
echo "Total Registros en DB: $countTotal\n";

$demo = $db->query("SELECT id, name, status, en_uso FROM assets WHERE id = 3113")->fetch(PDO::FETCH_ASSOC);
echo "Estado Equipo Demo (3113): " . ($demo ? "{$demo['name']} | Status: {$demo['status']} | En Uso: {$demo['en_uso']}" : "No encontrado") . "\n";
