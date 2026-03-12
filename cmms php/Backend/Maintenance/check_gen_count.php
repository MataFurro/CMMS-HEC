<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');

$total = $pdo->query("SELECT COUNT(*) FROM assets WHERE en_uso=1")->fetchColumn();
echo "Total Assets: $total\n";

$genCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE inventory_id LIKE 'GEN-%'")->fetchColumn();
echo "Assets with GEN- logic count: $genCount\n";

$realCount = $pdo->query("SELECT COUNT(*) FROM assets WHERE inventory_id NOT LIKE 'GEN-%'")->fetchColumn();
echo "Assets with real Inventory IDs count: $realCount\n";
