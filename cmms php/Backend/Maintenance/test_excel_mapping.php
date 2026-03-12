<?php

// Replicación de la lógica de limpieza de ExcelProvider.php
$cleaner = function ($str) {
    if (!$str) return "";
    $str = mb_strtolower(trim($str), 'UTF-8');
    $normalize = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ñ' => 'n',
        'ü' => 'u',
        'Á' => 'a',
        'É' => 'e',
        'Í' => 'i',
        'Ó' => 'o',
        'Ú' => 'u',
        'Ñ' => 'n',
        'Ü' => 'u'
    ];
    $str = strtr($str, $normalize);
    return preg_replace('/[^a-z0-9]/', '', $str);
};

$synonyms = [
    'id' => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario'],
    'name' => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo'],
    'criticality' => ['criticidad', 'criticality', 'prioridad', 'clasificacion'],
];

// Test de cabeceras comunes
$testHeaders = ['ID INVENTARIO', 'EQUIPO', 'CRITICIDAD'];

echo "--- Test de Mapeo de Cabeceras ---\n";
foreach ($testHeaders as $h) {
    $hClean = $cleaner($h);
    $mapped = "NULL";
    foreach ($synonyms as $key => $list) {
        foreach ($list as $s) {
            if ($hClean === $cleaner($s)) {
                $mapped = $key;
                break 2;
            }
        }
    }
    echo "Header: '$h' -> Clean: '$hClean' -> Mapped: $mapped\n";
}

// Test de normalización de valores
$normalizeCriticality = function ($val) use ($cleaner) {
    $c = $cleaner($val);
    $map = [
        'critical' => 'CRITICAL',
        'critico' => 'CRITICAL',
        'alta' => 'CRITICAL',
        'prioritario' => 'CRITICAL',
        'relevant' => 'RELEVANT',
        'relevante' => 'RELEVANT',
        'media' => 'RELEVANT',
        'low' => 'LOW',
        'baja' => 'LOW',
        'noaplica' => 'LOW',
        'na' => 'LOW'
    ];
    return $map[$c] ?? 'RELEVANT';
};

echo "\n--- Test de Normalización de Valores ---\n";
$testValues = ['Crítico', 'RELEVANTE', 'No Aplica', 'Baja', 'N/A', 'OTRO'];
foreach ($testValues as $v) {
    $res = $normalizeCriticality($v);
    echo "Value: '$v' -> Result: $res\n";
}
