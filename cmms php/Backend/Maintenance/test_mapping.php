<?php
// test_mapping.php
// Simulate ExcelProvider mapping logic to verify fixes.

$headersRaw = explode(' | ', "SERVICIO CLÍNICO | RECINTO | CLASE | SUBCLASE | NOMBRE EQUIPO | MARCA | MODELO | SERIE | N° INVENTARIO | AÑO DE ADQUISICIÓN | VIDA ÚTIL | VIDA ÚTIL RESIDUAL | PROPIO / ARRIENDO / COMODATO | ESTADO (BUENO / REGULAR / MALO / BAJA) | CRÍTICO/ RELEVANTE / IM≥12 / NO APLICA |  EN GARANTÍA (SI / NO) | AÑO VENCIMIENTO GARANTÍA | BAJO PLAN DE MANTENIMIENTO (SI / NO) | AÑO INGRESO A PLAN DE MANTENIMIENTO (2023 / 2024 / 2025 / 2026) | MANTENIMIENTO INTERNO O MANTENIMIENTO EXTERNO O CONTRATO | NOMBRE DE PROVEEDOR O MANTENIMIENTO INTERNO | ID CONVENIO DE MANTENIMIENTO / ID DE REFERENCIA / COTIZACIÓN DE REFERENCIA |  COSTO ANUAL DE MANTENIMIENTO SEGÚN CONVENIO / PRECIO DE REFERENCIA MANTENIMIENTO ANUAL  | FRECUENCIA ANUAL DE MANTENIMIENTO");

$synonyms = [
    'id' => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario'],
    'name' => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo'],
    'model' => ['modelo', 'model'],
    'brand' => ['marca', 'fabricante', 'brand'],
    'serial_number' => ['serie', 'n de serie', 'n° de serie', 'serial', 's/n', 'numero de serie'],
    'risk_class' => ['clase', 'familia', 'especialidad', 'categoria', 'subclase', 'sub-clase', 'grupo'],
    'criticality' => ['criticidad', 'criticality', 'prioridad', 'clasificacion', 'criticorelevanteim12noaplica'],
    'location' => ['ubicacion', 'servicio', 'area', 'unidad', 'departamento', 'servicio clínico', 'servicio clinico'],
    'sub_location' => ['sub-ubicacion', 'sububicacion', 'recinto', 'piso', 'sala', 'oficina', 'nivel'],
    'status' => ['estado', 'status', 'situacion', 'estadobuenoregularmalobaja'],
    'purchased_year' => ['año compra', 'fecha compra', 'año', 'adquisicion', 'año de adquisición', 'adquisición'],
    'total_useful_life' => ['vida útil', 'vida util (total)', 'vida util', 'vida util completa', 'vida util total'],
    'years_remaining' => ['vida útil residual', 'vida util residual', 'años restantes', 'años residuales', 'vida residual'],
    'acquisition_cost' => ['costo adquisicion', 'valor adquisicion', 'precio adquisicion', 'valor de adquisicion', 'costo de adquisicion', 'acquisition cost', 'valor comercial', 'precio unitario'],
    'annual_maint_cost' => ['costo anual de mantenimiento', 'mantenimiento anual', 'precio de referencia mantenimiento anual', 'costo anual de mantenimiento segun convenio', 'presupuesto mantenimiento']
];

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
        'Ü' => 'u',
        '°' => '',
        '°' => '',
        '°' => '' // Handle degree symbol variations
    ];
    $str = strtr($str, $normalize);
    return preg_replace('/[^a-z0-9]/', '', $str);
};

$headers = [];
$usedKeys = [];

foreach ($headersRaw as $h) {
    $hClean = $cleaner($h);
    $mapped = null;

    // Exact
    foreach ($synonyms as $key => $list) {
        if (in_array($key, $usedKeys)) continue;
        foreach ($list as $s) {
            if ($hClean === $cleaner($s)) {
                $mapped = $key;
                $usedKeys[] = $key;
                break 2;
            }
        }
    }

    // Partial
    if (!$mapped) {
        foreach ($synonyms as $key => $list) {
            if (in_array($key, $usedKeys)) continue;
            foreach ($list as $s) {
                $sClean = $cleaner($s);
                if (strlen($sClean) > 4 && strpos($hClean, $sClean) !== false) {
                    $mapped = $key;
                    $usedKeys[] = $key;
                    break 2;
                }
            }
        }
    }
    $headers[] = $mapped ?? $hClean;
}

echo "MAPPED HEADERS:\n";
foreach ($headersRaw as $i => $raw) {
    echo "[$raw] => " . ($headers[$i] ?? 'NULL') . "\n";
}
