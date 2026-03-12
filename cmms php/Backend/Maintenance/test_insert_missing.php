<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\AssetRepository;

try {
    $db = DatabaseService::getInstance();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $repo = new AssetRepository($db);

    // Simulate Row 3
    $row = [
        'inventory_id' => '500000010342', // THIS IS THE DUPLICATE inventory_id
        'name' => 'Monitor Signos Vitales',
        'serial_number' => 'PSC-19430020B',
        'location' => 'HOSPITALIZACIÓN QUIRÚRGICA',
        'criticality' => 'NA',
        'status' => 'OPERATIVE',
        'ownership' => 'PROPIO'
    ];

    echo "Attempting to create row 3...\n";
    $repo->create($row);
    echo "Success!\n";
} catch (\PDOException $e) {
    echo "PDO EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
}
