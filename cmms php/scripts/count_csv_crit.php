<?php
$f = fopen('c:/Users/star_/OneDrive/Escritorio/Prueba 2.csv', 'r');
if (!$f) die("No open\n");
$headers = fgetcsv($f, 0, ';');
$counts = [];
while (($d = fgetcsv($f, 0, ';')) !== FALSE) {
    // Column 14 is "CRÍTICO/ RELEVANTE / IM≥12 / NO APLICA"
    $c = $d[14] ?? 'N/A';
    $counts[$c] = ($counts[$c] ?? 0) + 1;
}
echo "Criticality counts in Prueba 2.csv:\n";
print_r($counts);
fclose($f);
