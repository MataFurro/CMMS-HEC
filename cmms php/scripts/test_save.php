<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

echo "--- TEST SAVE ASSET ---\n";
$data = [
    'inventory_id' => 'TEST-' . time(),
    'name' => 'Equipo de Prueba Sincro',
    'status' => 'OPERATIVE',
    'en_uso' => 1
];

$id = saveAsset($data);
echo "Nuevo ID retornado: " . var_export($id, true) . "\n";

$db = Backend\Core\DatabaseService::getInstance();
$stmt = $db->prepare("SELECT id, inventory_id FROM assets WHERE id = ?");
$stmt->execute([$id]);
echo "Registro encontrado en DB: " . json_encode($stmt->fetch(PDO::FETCH_ASSOC)) . "\n";
