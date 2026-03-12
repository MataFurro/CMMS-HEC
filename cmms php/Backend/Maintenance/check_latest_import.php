<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');

$total = $pdo->query("SELECT COUNT(*) FROM assets WHERE en_uso=1")->fetchColumn();
echo "Total Assets in DB: $total\n\n";

$debugFile = __DIR__ . '/Backend/Providers/debug_import.txt';
if (file_exists($debugFile)) {
    echo "=== debug_import.txt ===\n";
    echo file_get_contents($debugFile);
}

// Check how many have the synthetic IDs (Inventory ID + Hash)
$syntheticCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE en_uso=1 AND id REGEXP '^[0-9]+-[0-9a-f]{4}$'")->fetchColumn();
echo "\nAssets with synthetic IDs (differing serial numbers): $syntheticCount\n";
