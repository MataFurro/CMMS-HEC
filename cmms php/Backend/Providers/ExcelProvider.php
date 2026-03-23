<?php

namespace Backend\Providers;

require_once __DIR__ . '/AssetProvider.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

/**
 * Gestiona la exportación e importación de datos en formato CSV y XLSX.
 * Utiliza ZipArchive para lectura nativa de Excel sin dependencias externas.
 */

/**
 * Importa activos desde un archivo (CSV o XLSX) subido.
 */
function importAssetsFromFile(array $fileData): array
{
    $filePath = $fileData['tmp_name'];
    $fileName = $fileData['name'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $stats = ['success' => 0, 'updated' => 0, 'merged' => 0, 'skipped' => 0, 'errors' => 0, 'total' => 0, 'details' => [], 'conflicts' => []];
    $seenIdentityKeys = [];

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

    $repo = new AssetRepository();

    // 1. Identificar Cabeceras (Primera fila)
    $headersRaw = array_shift($rows);
    if (!$headersRaw) return $stats;

    // FunciÃ³n de limpieza estÃ¡ndar para comparaciÃ³n
    $cleaner = function ($str) {
        if (!$str) return "";
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        $str = mb_strtolower(trim($str), 'UTF-8');
        $normalize = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u'
        ];
        $str = strtr($str, $normalize);
        return preg_replace('/[^a-z0-9]/', '', $str);
    };

    $synonyms = [
        'id' => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'n° de inventario', 'n° inventario', 'numero de inventario', 'cod_inventario', 'cod inventario'],
        'name' => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo', 'nombre equipo'],
        'model' => ['modelo', 'model'],
        'brand' => ['marca', 'fabricante', 'brand'],
        'serial_number' => ['serie', 'n de serie', 'serial', 's/n', 'numero de serie', 'n° de serie'],
        'riesgo_ge' => ['clase', 'familia', 'especialidad', 'categoria', 'grupo'],
        'criticality' => ['criticidad', 'criticality', 'prioridad', 'clasificacion', 'criticorelevanteim12noaplica'],
        'location' => ['ubicacion', 'servicio', 'area', 'unidad', 'departamento', 'servicio clinico'],
        'sub_location' => ['sububicacion', 'recinto', 'piso', 'sala', 'oficina', 'nivel'],
        'subclase' => ['subclase', 'subfamilia', 'riesgo'],
        'status' => ['estado', 'status', 'situacion', 'estadobuenoregularmalobaja', 'propio arriendo comodato'],
        'purchased_year' => ['año compra', 'fecha compra', 'adquisicion', 'año de adquisición', 'ano de adquisicion'],
        'total_useful_life' => ['vida útil', 'vida util total', 'vida util'],
        'years_remaining' => ['vida útil residual', 'vida util residual', 'años restantes'],
        'acquisition_cost' => ['costo adquisicion', 'valor adquisicion', 'precio adquisicion', 'costo de adquisicion', 'acquisition cost', 'costo anual de mantenimiento segun convenio'],
        'annual_maint_cost' => ['costo anual de mantenimiento', 'mantenimiento anual', 'precio de referencia mantenimiento anual'],
        'system_id' => ['id sistema', 'codigo sistema', 'biocmms id', 'hec id', 'codigo sistema (id)'],
        'annual_frequency' => ['frecuencia anual de mantenimiento', 'frecuencia anual', 'veces al año', 'frecuencia anual de mantencion']
    ];

    $headers = [];
    $mappedCount = 0;
    foreach ($headersRaw as $index => $h) {
        $hClean = $cleaner($h);
        $mapped = null;
        foreach ($synonyms as $key => $list) {
            foreach ($list as $s) {
                if ($hClean === $cleaner($s)) {
                    $mapped = $key;
                    $mappedCount++;
                    break 2;
                }
            }
        }
        $headers[$index] = $mapped ?? $hClean;
    }

    // Heuristica Fallback
    if ($mappedCount < 3 && count($headersRaw) > 10) {
        $headers = ['location', 'sub_location', 'risk_class', 'category', 'name', 'brand', 'model', 'serial_number', 'id', 'purchased_year', 'total_useful_life', 'years_remaining', 'criticality', 'acquisition_cost'];
    }

    $normalizeCriticality = function ($val) use ($cleaner) {
        $c = $cleaner($val);
        $map = [
            'critical'     => 'CRITICAL',
            'critico'      => 'CRITICAL',
            'alta'         => 'CRITICAL',
            'im12'         => 'CRITICAL',
            'relevant'     => 'RELEVANT',
            'relevante'    => 'RELEVANT',
            'emi'          => 'RELEVANT',
            'media'        => 'RELEVANT',
            'low'          => 'LOW',
            'baja'         => 'LOW',
            'noaplica'     => 'LOW',
            'na'           => 'LOW'
        ];
        return $map[$c] ?? 'LOW';
    };

    $rowIndex = 0;
    foreach ($rows as $data) {
        $rowIndex++;
        if (empty(array_filter($data))) continue;

        $row = [];
        foreach ($headers as $idx => $key) {
            $row[$key] = $data[$idx] ?? null;
        }

        // Standard name fix
        if (empty(trim($row['name'] ?? ''))) $row['name'] = 'SIN NOMBRE';

        $stats['total']++;

        // Normalize
        if (isset($row['criticality'])) {
            $row['criticality'] = $normalizeCriticality($row['criticality']);
        }

        if (isset($row['annual_frequency'])) {
            $freq = preg_replace('/[^0-9.]/', '', (string)$row['annual_frequency']);
            $freqNum = (float)$freq;
            if ($freqNum > 0) $row['frecuencia_mp_meses'] = (int)round(12 / $freqNum);
        }

        // Map risk classes (Riesgo GE) - This is the primary specialty
        if (isset($row['riesgo_ge'])) {
            $claseClean = $cleaner($row['riesgo_ge']);
            $claseMap = [
                'apoyodiagnostico' => 'APOYO DIAGNÓSTICO',
                'apoyoendoscopico' => 'APOYO ENDOSCÓPICO',
                'apoyoindustrial' => 'APOYO INDUSTRIAL',
                'apoyoquirurgico' => 'APOYO QUIRÚRGICO',
                'apoyoterapeutico' => 'APOYO TERAPÉUTICO',
                'esterilizacion' => 'ESTERILIZACIÓN',
                'imagenologia' => 'IMAGENOLOGÍA',
                'laboratorio' => 'LABORATORIO / FARMACIA',
                'farmacia' => 'LABORATORIO / FARMACIA',
                'medfisrehabilitacion' => 'MED. FIS. REHABILITACIÓN',
                'mobiliario' => 'MOBILIARIO',
                'monitoreo' => 'MONITOREO',
                'odontologia' => 'ODONTOLOGÍA',
                'bajocosto' => 'BAJO COSTO'
            ];
            if (isset($claseMap[$claseClean])) {
                $row['riesgo_ge'] = $claseMap[$claseClean];
            } else {
                $row['riesgo_ge'] = mb_strtoupper(trim($row['riesgo_ge']), 'UTF-8');
            }
        }

        // Sanitization helpers
        $cleanYear = function ($v) { $v = preg_replace('/[^0-9]/', '', (string)$v); return ($v >= 1900 && $v <= 2100) ? (int)$v : null; };
        $cleanInt = function ($v) { $v = preg_replace('/[^0-9]/', '', (string)$v); return strlen($v) > 0 ? (int)$v : null; };
        $cleanDecimal = function ($v) { $v = preg_replace('/[^0-9.,\-]/', '', (string)$v); $v = str_replace(',', '.', $v); return is_numeric($v) ? (float)$v : 0.0; };

        $row['purchased_year'] = $cleanYear($row['purchased_year'] ?? null);
        $row['total_useful_life'] = $cleanInt($row['total_useful_life'] ?? null);
        $row['years_remaining'] = $cleanInt($row['years_remaining'] ?? null);
        $row['acquisition_cost'] = $cleanDecimal($row['acquisition_cost'] ?? 0);
        $row['annual_maint_cost'] = $cleanDecimal($row['annual_maint_cost'] ?? 0);

        // Useful life percentage calculation
        if (($row['total_useful_life'] ?? 0) > 0) {
            $row['useful_life_pct'] = round((($row['years_remaining'] ?? 0) / $row['total_useful_life']) * 100);
        } else {
            $row['useful_life_pct'] = 100;
        }

        try {
            // Identity Normalization
            $rawInvId = (string)($row['id'] ?? '');
            $rawSn = (string)($row['serial_number'] ?? '');
            
            // Remove non-breaking spaces and trim strings
            $cleanInvId = mb_strtoupper(trim(str_replace("\xc2\xa0", ' ', $rawInvId)));
            $cleanSn = mb_strtoupper(trim(str_replace("\xc2\xa0", ' ', $rawSn)));

            // Treat common placeholders as empty to avoid matching "N/A" with "N/A"
            $placeholders = ['', 'S/I', 'N/A', 'S/N', 'SIN SERIE', 'S/S', '0', '-', 'S/ID', 'S/N°', 'COMODATO'];
            if (in_array($cleanInvId, $placeholders)) $cleanInvId = '';
            if (in_array($cleanSn, $placeholders)) $cleanSn = '';

            $row['inventory_id'] = $cleanInvId;
            $row['serial_number'] = $cleanSn;

            // Identity Conflict Logic
            $type = null;

            if (empty($cleanInvId) && empty($cleanSn)) {
                $type = 'NO_ID';
            } else {
                // Key for Case A (Exact duplicate in file)
                $exactKey = 'A:' . $cleanInvId . '|' . $cleanSn;
                if (isset($seenIdentityKeys[$exactKey])) {
                    $type = 'CASE_A';
                }
                
                // Key for Case B (Same ID, different Serial Number)
                if (!$type && !empty($cleanInvId)) {
                    $idKey = 'B:' . $cleanInvId;
                    if (isset($seenIdentityKeys[$idKey]) && $seenIdentityKeys[$idKey] !== $cleanSn) {
                        $type = 'CASE_B';
                    }
                    $seenIdentityKeys[$idKey] = $cleanSn;
                }

                // Key for Case C (Same Serial Number, different ID)
                if (!$type && !empty($cleanSn)) {
                    $snKey = 'C:' . $cleanSn;
                    if (isset($seenIdentityKeys[$snKey]) && $seenIdentityKeys[$snKey] !== $cleanInvId) {
                        $type = 'CASE_C';
                    }
                    $seenIdentityKeys[$snKey] = $cleanInvId;
                }

                if (!$type) {
                    $seenIdentityKeys[$exactKey] = true;
                }
            }

            if ($type) {
                $stats['conflicts'][] = [
                    'type' => $type,
                    'row' => $rowIndex + 1, // Correct line number (Excel view)
                    'name' => $row['name'] ?? 'Equipo sin nombre',
                    'inventory_id' => $rawInvId ?: 'S/I',
                    'serial' => $rawSn ?: 'S/I', // for NO_ID table
                    'serial_number' => $rawSn ?: 'S/I', // for CASE_A/B/C tables
                    'location' => $row['location'] ?? 'S/I'
                ];
                
                if ($type === 'CASE_A') {
                    $stats['merged']++;
                    continue; // Skip exact duplicates
                }
                // Case B, C, and NO_ID will be created/skipped by repository but processed as successful creation here
            }

            $row['hec_id'] = \generateAssetHecId($row);
            $success = $repo->create($row);
            if ($success) {
                $stats['success']++;
            } else {
                $stats['errors']++;
                $stats['details'][] = "Row $rowIndex: Database error during creation.";
            }

        } catch (\Exception $e) {
            $stats['errors']++;
            $stats['details'][] = "Row $rowIndex: " . $e->getMessage();
        }
    }

    return $stats;
}

function parseXlsxToArray(string $filePath): array
{
    $zip = new \ZipArchive();
    if ($zip->open($filePath) !== TRUE) return [];
    $rows = [];
    $sharedStrings = [];
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = new \SimpleXMLElement($ssData);
        foreach ($xml->si as $si) {
            if (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) $text .= (string)($r->t ?? '');
                $sharedStrings[] = $text;
            } else $sharedStrings[] = (string)($si->t ?? '');
        }
    }
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetData) {
        $xml = new \SimpleXMLElement($sheetData);
        foreach ($xml->sheetData->row as $row) {
            $currentRow = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                $type = (string)$cell['t'];
                if ($type == 's') $val = $sharedStrings[(int)$val] ?? "";
                $ref = (string)$cell['r'];
                $colIndex = 0;
                for ($i = 0; $i < strlen($ref); $i++) { if (ctype_alpha($ref[$i])) $colIndex = $colIndex * 26 + (ord($ref[$i]) - 64); else break; }
                $currentRow[$colIndex - 1] = $val;
            }
            if (!empty($currentRow)) {
                $maxCol = max(array_keys($currentRow));
                for ($i = 0; $i <= $maxCol; $i++) if (!isset($currentRow[$i])) $currentRow[$i] = "";
                ksort($currentRow);
                $rows[] = $currentRow;
            }
        }
    }
    $zip->close();
    return $rows;
}

function parseCsvToArray(string $filePath): array
{
    $rows = [];
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);
        $content = file_get_contents($filePath);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") { $encoding = 'UTF-8'; rewind($handle); fread($handle, 3); }
        else rewind($handle);
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            if ($encoding && $encoding !== 'UTF-8') $data = array_map(fn($v) => $v ? mb_convert_encoding($v, 'UTF-8', $encoding) : $v, $data);
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}

function exportAssetsToCsv() {
    // Basic implementation for export as requested
    $assets = getAllAssets();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    fputcsv($output, ['SERVICIO CLÍNICO', 'RECINTO', 'CLASE', 'SUBCLASE', 'NOMBRE EQUIPO', 'MARCA', 'MODELO', 'SERIE', 'N° INVENTARIO', 'CRÍTICO/ RELEVANTE / IM≥12 / NO APLICA'], ';');
    foreach ($assets as $a) {
        fputcsv($output, [$a['location'], $a['sub_location'], $a['riesgo_ge'], $a['subclase'], $a['name'], $a['brand'], $a['model'], $a['serial_number'], $a['inventory_id'], $a['criticality']], ';');
    }
    fclose($output);
    exit;
}

function exportFinancialReportToCsv() {
    // Placeholder to keep the file structure intact
    exit;
}
