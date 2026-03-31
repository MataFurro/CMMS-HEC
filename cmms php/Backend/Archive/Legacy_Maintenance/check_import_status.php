<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$total = $pdo->query("SELECT COUNT(*) FROM assets WHERE en_uso=1")->fetchColumn();
echo "Assets in DB: $total\n";

// Check what the import stat file says
$debugFile = __DIR__ . '/Backend/Providers/debug_import.txt';
if (file_exists($debugFile)) {
    echo "\n=== debug_import.txt (latest import info) ===\n";
    echo file_get_contents($debugFile);
}

// Check the Apache error log for any import-related errors
$logFile = 'C:/xampp/apache/logs/error.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $importErrors = array_filter($lines, fn($l) => str_contains($l, 'inventory') || str_contains($l, 'import') || str_contains($l, 'assets'));
    $recent = array_slice(array_values($importErrors), -20);
    if ($recent) {
        echo "\n=== Recent import-related errors in Apache log ===\n";
        foreach ($recent as $l) echo $l;
    }
}

// Check how many have inventory_id = GEN-...
$genCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE inventory_id LIKE 'GEN-%'")->fetchColumn();
$realCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE inventory_id NOT LIKE 'GEN-%'")->fetchColumn();
echo "\nWith real inventory_id: $realCount\n";
echo "With GEN- inventory_id: $genCount\n";

// Check if there are any assets with null name (shouldn't happen)
$nullNames = $pdo->query("SELECT COUNT(*) FROM assets WHERE name IS NULL OR name=''")->fetchColumn();
echo "Assets with empty name: $nullNames\n";
