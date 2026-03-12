<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');

$total = $pdo->query('SELECT COUNT(*) FROM assets WHERE en_uso = 1')->fetchColumn();
echo "Total in DB: $total\n";

$genCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE inventory_id LIKE 'GEN-%' AND en_uso = 1")->fetchColumn();
echo "Total GEN- in DB: $genCount\n";

$file = __DIR__ . '/Backend/Providers/debug_import.txt';
if (file_exists($file)) {
    echo "Debug Log exists. Outputting tail:\n";
    $lines = file($file);
    $tail = array_slice($lines, -20);
    echo implode("", $tail);
} else {
    echo "No debug log found.\n";
}
