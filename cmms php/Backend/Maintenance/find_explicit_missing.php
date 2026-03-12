<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');

$missingIds = [
    '500000010342',
    '500000010343',
    '500000010344',
    '500000010345',
    '500000006990',
    '500000011348',
    '500000008021',
    '500000000176',
    '500000001056',
    '500000000321',
    '500000000699',
    '500000012044',
    'COM02314',
    'COM02315'
];

foreach ($missingIds as $id) {
    $stmt = $pdo->prepare("SELECT id, name, serial_number, location FROM assets WHERE inventory_id = :id");
    $stmt->execute(['id' => $id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        echo "Found for $id:\n";
        print_r($rows);
    } else {
        echo "Not found in DB at all: $id\n";
    }
}
