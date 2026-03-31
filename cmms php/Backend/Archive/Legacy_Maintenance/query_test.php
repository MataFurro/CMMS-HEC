<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT id, inventory_id, name, serial_number FROM assets WHERE inventory_id='500000010342'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
