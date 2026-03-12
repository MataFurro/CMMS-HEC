<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SHOW CREATE TABLE assets');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
