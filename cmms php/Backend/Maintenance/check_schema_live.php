<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SHOW COLUMNS FROM assets');
foreach ($stmt as $row) {
    echo $row['Field'] . " | " . $row['Key'] . "\n";
}
