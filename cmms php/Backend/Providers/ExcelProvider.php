<?php

namespace Backend\Providers;

require_once __DIR__ . '/AssetProvider.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

/**
 * Gestiona la exportación e importación de datos en formato CSV y XLSX.
 * Utiliza ZipArchive para lectura nativa de Excel sin dependencias externas.
 * ─────────────────────────────────────────────────────
 */

/**
 * Importa activos desde un archivo (CSV o XLSX) subido.
 * @param array $fileData Información de $_FILES['excel_file'].
 * @return array Estadísticas de la importación [success, errors, count].
 */
function importAssetsFromFile(array $fileData): array
{
    $filePath = $fileData['tmp_name'];
    $fileName = $fileData['name'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $stats = ['success' => 0, 'errors' => 0, 'total' => 0, 'details' => []];
    $rows = [];

    try {
        if ($extension === 'xlsx') {
            $rows = parseXlsxToArray($filePath);
        } else {
            $rows = parseCsvToArray($filePath);
        }
    } catch (\Exception $e) {
        $stats['errors'] = 1;
        $stats['details'][] = $e->getMessage();
        return $stats;
    }

    if (empty($rows)) return $stats;

    $repo = new \Backend\Repositories\AssetRepository();

    // 1. Identificar Cabeceras (Primera fila)
    $headersRaw = array_shift($rows);
    if (!$headersRaw) return $stats;

    // 2. Mapeo inteligente de cabeceras (Sinónimos)
    $synonyms = [
        'id' => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario'],
        'name' => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo'],
        'model' => ['modelo', 'model'],
        'brand' => ['marca', 'fabricante', 'brand'],
        'serial_number' => ['serie', 'n de serie', 'n° de serie', 'serial', 's/n', 'numero de serie'],
        'criticality' => ['criticidad', 'criticality', 'prioridad', 'clasificacion'],
        'location' => ['ubicacion', 'servicio', 'area', 'unidad', 'departamento', 'servicio clínico', 'servicio clinico'],
        'sub_location' => ['sub-ubicacion', 'sububicacion', 'recinto', 'piso', 'sala', 'oficina', 'nivel'],
        'status' => ['estado', 'status', 'situacion'],
        'purchased_year' => ['año compra', 'fecha compra', 'año', 'adquisicion', 'año de adquisición', 'adquisición'],
        'total_useful_life' => ['vida útil', 'vida util (total)', 'vida util', 'vida util completa', 'vida util total'],
        'years_remaining' => ['vida útil residual', 'vida util residual', 'años restantes', 'años residuales', 'vida residual'],
        'acquisition_cost' => ['costo', 'valor', 'precio', 'costo de adquisición', 'costo adquisicion', 'valor de adquisicion', 'acquisition cost', 'valor comercial']
    ];

    // Función de limpieza estándar para comparación (Convertir a ASCII básico)
    $cleaner = function ($str) {
        if (!$str) return "";
        // Asegurar UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        $str = mb_strtolower(trim($str), 'UTF-8');
        // Mapa de normalización extendido
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

    $headers = [];
    $mappedCount = 0;
    $usedKeys = []; // Para evitar duplicados

    foreach ($headersRaw as $h) {
        $hClean = $cleaner($h);
        $mapped = null;

        // 1. Intentar Coincidencia EXACTA primero (Mayor precisión)
        foreach ($synonyms as $key => $list) {
            if (in_array($key, $usedKeys)) continue;
            foreach ($list as $s) {
                if ($hClean === $cleaner($s)) {
                    $mapped = $key;
                    $usedKeys[] = $key;
                    $mappedCount++;
                    break 2;
                }
            }
        }

        // 2. Intentar Coincidencia PARCIAL si no hubo exacta (Solo para llaves no usadas)
        if (!$mapped) {
            foreach ($synonyms as $key => $list) {
                if (in_array($key, $usedKeys)) continue;
                foreach ($list as $s) {
                    $sClean = $cleaner($s);
                    if (strlen($sClean) > 4 && strpos($hClean, $sClean) !== false) {
                        $mapped = $key;
                        $usedKeys[] = $key;
                        $mappedCount++;
                        break 2;
                    }
                }
            }
        }
        $headers[] = $mapped ?? $hClean;
    }

    // Heurística Fallback if mapping fails
    if ($mappedCount < 3 && count($headersRaw) > 10) {
        $headers = ['location', 'sub_location', 'category', 'sub-category', 'name', 'brand', 'model', 'serial_number', 'id', 'purchased_year', 'total_useful_life', 'years_remaining'];
    }

    // 3. Procesar Filas
    foreach ($rows as $data) {
        if (empty(array_filter($data))) continue;
        $stats['total']++;

        $row = array_combine(array_slice($headers, 0, count($data)), array_slice($data, 0, count($headers)));

        // --- LÓGICA DE VIDA ÚTIL ---
        $row['useful_life_pct'] = 100; // Por defecto
        if (isset($row['total_useful_life']) && isset($row['years_remaining'])) {
            $total = (float)$row['total_useful_life'];
            $rem = (float)$row['years_remaining'];
            if ($total > 0) {
                $row['useful_life_pct'] = round(($rem / $total) * 100);
            }
        }

        // --- SALVAGUARDAS CRÍTICAS ---
        // Si no se mapeó location/sub_location, intentar por posición o heurística específica
        foreach ($headersRaw as $idx => $hr) {
            $hrC = $cleaner($hr);
            if (empty($row['location']) && strpos($hrC, 'servicioclinico') !== false) $row['location'] = $data[$idx];
            if (empty($row['sub_location']) && (strpos($hrC, 'recinto') !== false || strpos($hrC, 'piso') !== false)) $row['sub_location'] = $data[$idx];
            if (empty($row['years_remaining']) && strpos($hrC, 'vidautilresidual') !== false) $row['years_remaining'] = $data[$idx];
            if (empty($row['total_useful_life']) && strpos($hrC, 'vidautil') !== false && strpos($hrC, 'residual') === false) $row['total_useful_life'] = $data[$idx];
        }

        // --- LÓGICA DE ID ROBUSTA ---
        // 1. Usar ID del Excel si existe
        $id = $row['id'] ?? null;

        // 2. Si no hay ID, intentar Serie
        if (empty($id) && !empty($row['serial_number'])) {
            $id = $row['serial_number'];
        }

        // 3. Si persiste el vacío, generar uno determinista basado en Nombre y Serie para evitar duplicados
        if (empty($id)) {
            $id = "ID-" . substr(md5(($row['name'] ?? 'EQ') . ($row['serial_number'] ?? uniqid())), 0, 8);
        }

        $row['id'] = $id;

        try {
            $existing = $repo->findById($id);
            $success = $existing
                ? $repo->partialUpdate($id, $row)
                : $repo->create($row);

            if ($success) $stats['success']++;
            else $stats['errors']++;
        } catch (\Exception $e) {
            $stats['errors']++;
            $stats['details'][] = $e->getMessage();
        }
    }

    return $stats;
}

/**
 * Lector XLSX Minimalista (Nativo PHP ZipArchive)
 */
function parseXlsxToArray(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        throw new \Exception("ERROR_ZIP_EXTENSION_MISSING: La extensión 'zip' no está habilitada en tu servidor PHP. Es necesaria para leer archivos .xlsx.");
    }
    $zip = new \ZipArchive();
    if ($zip->open($filePath) !== TRUE) return [];

    $rows = [];
    $sharedStrings = [];

    // 1. Cargar Shared Strings (Diccionario de Excel)
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = new \SimpleXMLElement($ssData);
        foreach ($xml->si as $si) {
            $sharedStrings[] = (string)($si->t ?? $si->r->t ?? "");
        }
    }

    // 2. Cargar Sheet1
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetData) {
        $xml = new \SimpleXMLElement($sheetData);
        foreach ($xml->sheetData->row as $row) {
            $currentRow = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                $type = (string)$cell['t'];

                if ($type == 's') { // Shared String
                    $val = $sharedStrings[(int)$val] ?? "";
                }

                // Manejo de índices de columnas (Excel salta celdas vacías)
                $ref = (string)$cell['r']; // E.g. "A1"
                $colIndex = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) {
                        $colIndex = $colIndex * 26 + (ord($ref[$i]) - 64);
                    } else break;
                }
                $currentRow[$colIndex - 1] = $val;
            }

            // Rellenar huecos si Excel omitió celdas vacías en medio
            if (!empty($currentRow)) {
                $maxCol = max(array_keys($currentRow));
                for ($i = 0; $i <= $maxCol; $i++) {
                    if (!isset($currentRow[$i])) $currentRow[$i] = "";
                }
                ksort($currentRow);
                $rows[] = $currentRow;
            }
        }
    }

    $zip->close();
    return $rows;
}

/**
 * Lector CSV Robusto
 */
function parseCsvToArray(string $filePath): array
{
    $rows = [];
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}


/**
 * Exporta todos los activos a un archivo CSV descargable.
 */
function exportAssetsToCsv()
{
    $assets = getAllAssets();

    // Headers para descarga
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventario_biomedico_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // Bom para UTF-8 (Excel friendly)
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeceras del CSV
    if (!empty($assets)) {
        fputcsv($output, array_keys($assets[0]));

        foreach ($assets as $asset) {
            fputcsv($output, $asset);
        }
    }

    fclose($output);
    exit;
}


/**
 * Exporta un reporte financiero consolidado (MINSAL format) a CSV.
 */
function exportFinancialReportToCsv()
{
    require_once __DIR__ . '/AssetProvider.php';
    require_once __DIR__ . '/WorkOrderProvider.php';

    $stats = getFinancialStats();
    $downtime = getDowntimeImpact();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_minsal_financiero_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Sección 1: KPIs Globales
    fputcsv($output, ['REPORTE FINANCIERO CONSOLIDADO - BIO-CMMS']);
    fputcsv($output, ['Fecha Generación', date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    fputcsv($output, ['KPI', 'Valor (USD)']);
    fputcsv($output, ['Valor Total Inventario', $stats['valor_inventario']]);
    fputcsv($output, ['Costo Mantenimiento Anual', $stats['costo_mantenimiento_anual']]);
    fputcsv($output, ['TCO Promedio', $stats['tco_avg']]);
    fputcsv($output, ['Pérdida por Inactividad (Downtime)', $downtime['total_loss']]);
    fputcsv($output, []);

    // Sección 2: Impacto por Área
    if (!empty($downtime['areas'])) {
        fputcsv($output, ['DETALLE DE IMPACTO POR ÁREA TÉCNICA']);
        fputcsv($output, ['Área', 'Horas Falla', 'Pérdida Estimada (USD)']);
        foreach ($downtime['areas'] as $area) {
            fputcsv($output, [$area['area'], $area['hours'], $area['loss']]);
        }
    }

    fclose($output);
    exit;
}
