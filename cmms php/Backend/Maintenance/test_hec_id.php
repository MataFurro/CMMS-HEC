<?php
require_once __DIR__ . '/Backend/Providers/AssetProvider.php';

// Simulate data payload for a new asset
$data = [
    'name' => 'Monitor de Signos Vitales de Prueba',
    'brand' => 'Mindray',
    'model' => 'BeneVision N12',
    'serial_number' => 'N12-TEST-777',
    'location' => 'UTI Adulto',
    'sub_location' => 'Cama 1',
    'status' => 'Operativo',
    'riesgo_ge' => 'Monitoreo',  // Family -> MON
    'criticality' => 'CRÍTICO', // Risk -> CRI
    'useful_life_pct' => 100,
    'id' => '' // Optional inventory ID
];

echo "Simulating asset creation...\n";
$result = saveAsset($data);

if ($result) {
    echo "Asset created successfully!\n";
    // Check what the DB holds
    require_once __DIR__ . '/Backend/Core/DatabaseService.php';
    $db = DatabaseService::getInstance()->getConnection();

    // Find the latest asset by serial
    $stmt = $db->query("SELECT * FROM assets WHERE serial_number = 'N12-TEST-777' ORDER BY id DESC LIMIT 1");
    $created = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($created) {
        echo "DB Entry found!\n";
        echo "HEC ID generated: " . $created['hec_id'] . "\n";
        echo "Inventory ID: " . $created['inventory_id'] . "\n";

        // Clean up
        $db->query("DELETE FROM assets WHERE id = " . $created['id']);
        echo "Test data cleaned up.\n";
    }
} else {
    echo "Failed to create asset!\n";
}
