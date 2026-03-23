<?php
require_once __DIR__ . '/../Backend/Providers/ExcelProvider.php';

$file = 'c:/Users/star_/OneDrive/Escritorio/Prueba 2.csv';
$fileData = [
    'tmp_name' => $file,
    'name' => 'Prueba 2.csv'
];

// We need to simulate the environment or just call the function
// Since it's a procedural style function in ExcelProvider.php, we can just call it if we include the file.
// But some functions might be missing if they are defined in other files.

// Let's just write a custom parser here to see what PHP sees.
$handle = fopen($file, "r");
$firstLine = fgets($handle);
$delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
rewind($handle);
$data = fgetcsv($handle, 0, $delimiter);
echo "Headers:\n";
print_r($data);

$data = fgetcsv($handle, 0, $delimiter);
echo "\nFirst Data Row:\n";
print_r($data);
fclose($handle);
