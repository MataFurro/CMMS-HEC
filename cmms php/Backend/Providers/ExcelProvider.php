<?php

namespace Backend\Providers;

require_once __DIR__ . '/AssetProvider.php';
require_once __DIR__ . '/../Repositories/AssetRepository.php';

use Backend\Repositories\AssetRepository;

/**
 * Gestiona la exportaciÃ³n e importaciÃ³n de datos en formato CSV y XLSX.
 * Utiliza ZipArchive para lectura nativa de Excel sin dependencias externas.
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 */

/**
 * Importa activos desde un archivo (CSV o XLSX) subido.
 * @param array $fileData InformaciÃ³n de $_FILES['excel_file'].
 * @return array EstadÃ­sticas de la importaciÃ³n [success, errors, count].
 */
function importAssetsFromFile(array $fileData): array
{
    $filePath = $fileData['tmp_name'];
    $fileName = $fileData['name'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $stats = ['success' => 0, 'updated' => 0, 'merged' => 0, 'skipped' => 0, 'errors' => 0, 'total' => 0, 'details' => [], 'conflicts' => []];
    $seenInventoryIds = []; // track same-file duplicate identity keys (invId|sn)


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

    // 2. Mapeo inteligente de cabeceras (SinÃ³nimos)
    $synonyms = [
        'id' => ['id', 'id inventario', 'codigo', 'identificador', 'asset id', 'tag', 'n de inventario', 'nÂ° de inventario', 'nÂ° inventario', 'numero de inventario'],
        'name' => ['nombre', 'equipo', 'descripcion', 'activo', 'nombre del equipo'],
        'model' => ['modelo', 'model'],
        'brand' => ['marca', 'fabricante', 'brand'],
        'serial_number' => ['serie', 'n de serie', 'nÂ° de serie', 'serial', 's/n', 'numero de serie'],
        'risk_class' => ['clase', 'familia', 'especialidad', 'categoria', 'subclase', 'sub-clase', 'grupo'],
        'criticality' => ['criticidad', 'criticality', 'prioridad', 'clasificacion', 'criticorelevanteim12noaplica'],
        'location' => ['ubicacion', 'servicio', 'area', 'unidad', 'departamento', 'servicio clÃ­nico', 'servicio clinico'],
        'sub_location' => ['sub-ubicacion', 'sububicacion', 'recinto', 'piso', 'sala', 'oficina', 'nivel'],
        'status' => ['estado', 'status', 'situacion', 'estadobuenoregularmalobaja'],
        'purchased_year' => ['aÃ±o compra', 'fecha compra', 'aÃ±o', 'adquisicion', 'aÃ±o de adquisiciÃ³n', 'adquisiciÃ³n'],
        'total_useful_life' => ['vida Ãºtil', 'vida util (total)', 'vida util', 'vida util completa', 'vida util total'],
        'years_remaining' => ['vida Ãºtil residual', 'vida util residual', 'aÃ±os restantes', 'aÃ±os residuales', 'vida residual'],
        'acquisition_cost' => ['costo adquisicion', 'valor adquisicion', 'precio adquisicion', 'valor de adquisicion', 'costo de adquisicion', 'acquisition cost', 'valor comercial', 'precio unitario'],
        'annual_maint_cost' => ['costo anual de mantenimiento', 'mantenimiento anual', 'precio de referencia mantenimiento anual', 'costo anual de mantenimiento segun convenio', 'presupuesto mantenimiento'],
        'system_id' => ['id sistema', 'codigo sistema', 'biocmms id', 'hec id', 'codigo sistema (id)', 'id (sistema)'],
        'annual_frequency' => ['frecuencia anual de mantenimiento', 'frecuencia anual', 'veces al aÃ±o', 'frecuencia anual de mantencion']
    ];

    // FunciÃ³n de limpieza estÃ¡ndar para comparaciÃ³n (Convertir a ASCII bÃ¡sico)
    $cleaner = function ($str) {
        if (!$str) return "";
        // Asegurar UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = @mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        $str = mb_strtolower(trim($str), 'UTF-8');
        // Mapa de normalizaciÃ³n extendido
        $normalize = [
            'Ã¡' => 'a',
            'Ã©' => 'e',
            'Ã­' => 'i',
            'Ã³' => 'o',
            'Ãº' => 'u',
            'Ã±' => 'n',
            'Ã¼' => 'u',
            'Ã' => 'a',
            'Ã‰' => 'e',
            'Ã' => 'i',
            'Ã“' => 'o',
            'Ãš' => 'u',
            'Ã‘' => 'n',
            'Ãœ' => 'u'
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

        // 1. Intentar Coincidencia EXACTA primero (Mayor precisiÃ³n)
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

    // --- DEBUG PROBE ---
    $debugInfo = "FILE: $fileName\n";
    $debugInfo .= "ROWS: " . count($rows) . "\n";
    $debugInfo .= "HEADERS RAW: " . implode(" | ", $headersRaw) . "\n";
    $debugInfo .= "HEADERS MAPPED: " . implode(" | ", $headers) . "\n";
    if (!empty($rows)) {
        $debugInfo .= "FIRST ROW RAW: " . implode(" | ", $rows[0]) . "\n";
    }
    file_put_contents(__DIR__ . '/debug_import.txt', $debugInfo);
    // -------------------

    // HeurÃ­stica Fallback if mapping fails
    if ($mappedCount < 3 && count($headersRaw) > 10) {
        $headers = ['location', 'sub_location', 'risk_class', 'category', 'name', 'brand', 'model', 'serial_number', 'id', 'purchased_year', 'total_useful_life', 'years_remaining', 'criticality', 'acquisition_cost'];
    }

    // --- NORMALIZACIÃ“N DE CRITICIDAD (segÃºn estÃ¡ndar MINSAL acreditaciÃ³n) ---
    // EMC = Equipos MÃ©dicos CrÃ­ticos (soporte vital, monitoreo, anestesia)
    // EMR = Equipos MÃ©dicos Relevantes (apoyo transversal a seguridad)
    // EMI = Equipos de InterÃ©s (F&S >= 12) -> mapeado a RELEVANT
    // LOW = No Aplica / Fuera de clasificaciÃ³n
    $normalizeCriticality = function ($val) use ($cleaner) {
        $c = $cleaner($val);
        $map = [
            // CRITICAL: soporte vital
            'critical' => 'CRITICAL',
            'critico' => 'CRITICAL',
            'criticos' => 'CRITICAL',
            'alta' => 'CRITICAL',
            'emc' => 'CRITICAL',
            'prioritario' => 'CRITICAL',
            'prioritarios' => 'CRITICAL',
            'esencial' => 'CRITICAL',
            'vital' => 'CRITICAL',
            'soportevital' => 'CRITICAL',
            // RELEVANT: apoyo transversal, EMI (F&S >=12)
            'relevant' => 'RELEVANT',
            'relevante' => 'RELEVANT',
            'relevantes' => 'RELEVANT',
            'emr' => 'RELEVANT',
            'emi' => 'RELEVANT',
            'im12' => 'RELEVANT',
            'media' => 'RELEVANT',
            'importante' => 'RELEVANT',
            'apoyo' => 'RELEVANT',
            // LOW: no aplica
            'low' => 'LOW',
            'baja' => 'LOW',
            'bajas' => 'LOW',
            'noaplica' => 'LOW',
            'na' => 'LOW',
            'menor' => 'LOW',
            'noesencial' => 'LOW',
            'sinclasificacion' => 'LOW'
        ];
        return $map[$c] ?? 'LOW';
    };

    // 3. Procesar Filas
    $rowIndex = 0;
    foreach ($rows as $data) {
        $rowIndex++;
        if (empty(array_filter($data))) continue;

        // Handle rows with no name instead of skipping
        $row = array_combine(array_slice($headers, 0, count($data)), array_slice($data, 0, count($headers)));
        if (empty(trim($row['name'] ?? ''))) {
            $row['name'] = 'SIN NOMBRE';
            $stats['details'][] = "âš ï¸ Fila $rowIndex: Equipo sin nombre en el archivo Excel se importÃ³ como 'SIN NOMBRE'.";
        }
        $stats['total']++;


        // Normalizar criticidad
        if (isset($row['criticality'])) {
            $row['criticality'] = $normalizeCriticality($row['criticality']);
        }

        // --- GESTIÃ“N DE FRECUENCIA ANUAL ---
        if (isset($row['annual_frequency'])) {
            $freq = preg_replace('/[^0-9.]/', '', (string)$row['annual_frequency']);
            $freqNum = (float)$freq;
            if ($freqNum > 0) {
                // ConversiÃ³n: Frecuencia 2 veces/aÃ±o -> Cada 6 meses
                $row['frecuencia_mp_meses'] = (int)round(12 / $freqNum);
            }
        }

        // --- MAPEO CLASE â†’ riesgo_ge (catÃ¡logo oficial HEC) ---
        if (isset($row['risk_class'])) {
            $claseRaw = mb_strtoupper(trim($row['risk_class']), 'UTF-8');

            // Usar el cleaner para normalizaciÃ³n insensible a acentos/encoding
            $claseClean = $cleaner($row['risk_class']);

            // Mapeo basado en llaves "limpias" (sin acentos, sin espacios)
            $claseMap = [
                'apoyodiagnostico'            => 'APOYO DIAGNÃ“STICO',
                'apoyoendoscopico'            => 'APOYO ENDOSCÃ“PICO',
                'apoyoindustrial'             => 'APOYO INDUSTRIAL',
                'apoyoquirurgico'             => 'APOYO QUIRÃšRGICO',
                'apoyoterapeutico'            => 'APOYO TERAPÃ‰UTICO',
                'apoyoterapeuticobajocosto'   => 'APOYO TERAPÃ‰UTICO',
                'esterilizacion'              => 'ESTERILIZACIÃ“N',
                'imagenologia'                => 'IMAGENOLOGÃA',
                'laboratorio'                 => 'LABORATORIO / FARMACIA',
                'farmacia'                    => 'LABORATORIO / FARMACIA',
                'laboratoriofarmacia'         => 'LABORATORIO / FARMACIA',
                'medfisrehabilitacion'        => 'MED. FIS. REHABILITACIÃ“N',
                'rehabilitacion'              => 'MED. FIS. REHABILITACIÃ“N',
                'mobiliario'                  => 'MOBILIARIO',
                'monitoreo'                   => 'MONITOREO',
                'odontologia'                 => 'ODONTOLOGÃA',
                'bajocosto'                   => 'BAJO COSTO',
                'equiposmedicos'              => 'GENERAL',
                'equipomedico'                => 'GENERAL',
                'instrumental'                => 'INSTRUMENTAL',
            ];

            // Si coincide con una llave limpia, usar el valor oficial. Si no, dejar el original limpio (o bruto).
            if (isset($claseMap[$claseClean])) {
                $row['riesgo_ge'] = $claseMap[$claseClean];
            } else {
                // Fallback: Si no hay en el mapa, intentar limpiar un poco pero mantener el texto original capitalizado
                $row['riesgo_ge'] = $claseRaw;
            }
            unset($row['risk_class']);
        }

        // --- LÃ“GICA DE VIDA ÃšTIL ---
        $row['useful_life_pct'] = 100; // Por defecto
        if (isset($row['total_useful_life']) && isset($row['years_remaining'])) {
            $total = (float)$row['total_useful_life'];
            $rem = (float)$row['years_remaining'];
            if ($total > 0) {
                $row['useful_life_pct'] = round(($rem / $total) * 100);
            }
        }

        // --- SALVAGUARDAS CRÃTICAS ---
        // Si no se mapeÃ³ location/sub_location, intentar por posiciÃ³n o heurÃ­stica especÃ­fica
        foreach ($headersRaw as $idx => $hr) {
            $hrC = $cleaner($hr);
            if (empty($row['location']) && strpos($hrC, 'servicioclinico') !== false) $row['location'] = $data[$idx];
            if (empty($row['sub_location']) && (strpos($hrC, 'recinto') !== false || strpos($hrC, 'piso') !== false)) $row['sub_location'] = $data[$idx];
            if (empty($row['years_remaining']) && strpos($hrC, 'vidautilresidual') !== false) $row['years_remaining'] = $data[$idx];
            if (empty($row['total_useful_life']) && strpos($hrC, 'vidautil') !== false && strpos($hrC, 'residual') === false) $row['total_useful_life'] = $data[$idx];
            // Salvaguarda para clase (columna 3 del Excel original)
            if (empty($row['riesgo_ge']) && ($hrC === 'clase' || strpos($hrC, 'subclase') === false && strpos($hrC, 'clase') !== false)) {
                $row['riesgo_ge'] = mb_strtoupper(trim($data[$idx] ?? ''), 'UTF-8');
            }
        }

        // --- ID RESOLUTION STRATEGY ---
        // The system uses auto-increment PKs internally.
        // 'inventory_id' stores the original Excel ID for deduplication.
        $rawId = $row['id'] ?? null;
        $genericValues = ['S/S', 'S/I', 'N/A', 'SIN SERIE', 'COMODATO', 'COMPRA', '0', '-', 'DESC', 'POR DEFINIR', 'MANTENCION'];
        $isGeneric = empty($rawId) || in_array(mb_strtoupper(trim($rawId)), $genericValues);

        // For generic IDs, we preserve the original value (e.g. "S/N") or leave it blank
        $inventoryId = $rawId;


        // --- SANITIZACIÃ“N DE CAMPOS CRÃTICOS ---
        // MySQL NO_ZERO_DATE rechaza '0000' en YEAR/DATE.
        // PDO envÃ­a '' (string vacÃ­o) para campos no mapeados â†’ falla silenciosa.
        // Convertir strings vacÃ­os a null para YEAR, DATE y DECIMAL.
        $cleanYear = function ($v): ?int {
            $v = preg_replace('/[^0-9]/', '', (string)$v);
            $v = (int)$v;
            return ($v >= 1900 && $v <= 2100) ? $v : null;
        };
        $cleanInt = function ($v): ?int {
            $v = preg_replace('/[^0-9]/', '', (string)$v);
            return strlen($v) > 0 ? (int)$v : null;
        };
        $cleanDecimal = function ($v): float {
            $v = preg_replace('/[^0-9.,\-]/', '', (string)$v);
            $v = str_replace(',', '.', $v);
            return is_numeric($v) ? (float)$v : 0.0;
        };
        $cleanDate = function ($v): ?string {
            $v = trim((string)$v);
            if (empty($v) || $v === '0' || $v === '-') return null;
            // Accept YYYY-MM-DD or DD-MM-YYYY or DD/MM/YYYY
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v)) return $v;
            if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $v, $m)) return "$m[3]-$m[2]-$m[1]";
            return null;
        };

        $row['purchased_year']     = $cleanYear($row['purchased_year'] ?? null);
        $row['total_useful_life']  = $cleanInt($row['total_useful_life'] ?? null);
        $row['years_remaining']    = $cleanInt($row['years_remaining'] ?? null);
        $row['acquisition_cost']   = $cleanDecimal($row['acquisition_cost'] ?? 0);
        $row['annual_maint_cost']  = $cleanDecimal($row['annual_maint_cost'] ?? 0);
        $row['fecha_instalacion']  = $cleanDate($row['fecha_instalacion'] ?? null);
        $row['warranty_expiration'] = $cleanDate($row['warranty_expiration'] ?? null);

        // Recalculate useful_life_pct with sanitized values
        $tul = $row['total_useful_life'];
        $yr  = $row['years_remaining'];
        if ($tul && $tul > 0 && $yr !== null) {
            $row['useful_life_pct'] = min(100, max(0, (int)round(($yr / $tul) * 100)));
        }

        $row['inventory_id'] = $inventoryId;

        try {
            $serialNum  = trim((string)($row['serial_number'] ?? ''));
            $cleanSn    = mb_strtoupper((string)$serialNum);
            $cleanInvId = mb_strtoupper((string)$inventoryId);

            // Generic serial values that should NOT be used as unique keys
            $genericSns = ['S/S', 'S/I', 'S/N', 'N/A', 'SIN SERIE', 'COMODATO', '0', '-', '', 'NULL', 'SIN NUMERO'];
            $hasValidSn = !in_array($cleanSn, $genericSns) && strlen($cleanSn) > 2;

            // When BOTH inventory_id AND serial_number are generic, we now
            // preserve the raw S/N label instead of generating a GEN- hash.
            if ($isGeneric && !$hasValidSn) {
                $inventoryId = empty($inventoryId) ? 'S/N' : $inventoryId;
                $cleanInvId  = mb_strtoupper($inventoryId);
                $row['inventory_id'] = $inventoryId;
            }

            // Identity key for duplicate detection (Case A).
            // Only usable when BOTH fields are real/valid identifiers.
            // If either is generic (S/N, S/I...) force uniqueness so each row
            // creates its own asset and is never wrongly skipped as duplicate.
            if (!$hasValidSn || $isGeneric) {
                $identityKey = $cleanInvId . '|' . $cleanSn . '|' . $rowIndex;
            } else {
                $identityKey = $cleanInvId . '|' . $cleanSn;
            }

            // CASE A: Exact duplicate within this file (same ID + same SN).
            // Only when BOTH fields are identical is it a true duplicate.
            if (isset($seenInventoryIds[$identityKey])) {
                $stats['merged']++;
                $stats['conflicts'][] = [
                    'type'         => 'A',
                    'row'          => $rowIndex,
                    'name'         => $row['name'],
                    'inventory_id' => $inventoryId,
                    'serial'       => $serialNum,
                    'detail'       => "Fila $rowIndex: Duplicado exacto [{$row['name']}] (ID: '$inventoryId', SN: '$serialNum'). Se conto como 1."
                ];
                continue;
            }

            // Register identity key for future duplicate detection
            $seenInventoryIds[$identityKey] = $rowIndex;

            // Warn when both identity fields are missing/generic â€” asset has no reliable key.
            if ($isGeneric && !$hasValidSn) {
                $stats['conflicts'][] = [
                    'type'         => 'NO_ID',
                    'row'          => $rowIndex,
                    'name'         => $row['name'],
                    'inventory_id' => $inventoryId,
                    'serial'       => $serialNum,
                    'detail'       => "Fila $rowIndex: [{$row['name']}] no tiene N\xc2\xb0 Inventario ni N\xc2\xb0 Serie validos. Se creo como equipo separado sin identificador confiable."
                ];
            }

            // DB LOOKUP STRATEGY:
            // 1. Prioridad: CÃ“DIGO SISTEMA (ID) -> Si existe, es la Ãºnica fuente de verdad.
            // 2. Fallback: NÂ° Inventario + SN confirmation.
            $exactMatch   = null;
            $partialMatch = null;
            $candidates   = [];

            // A. BÃºsqueda por ID de Sistema (HEC ID)
            $systemId = trim((string)($row['system_id'] ?? ''));
            if (!empty($systemId) && !in_array(strtoupper($systemId), $genericSns)) {
                $exactMatch = $repo->findById($systemId);
            }

            // B. Fallback a Inventory ID si no hay exactMatch por System ID
            if (!$exactMatch && !$isGeneric) {
                $candidates = $repo->findAllByInventoryId($inventoryId);
                foreach ($candidates as $c) {
                    $dbSerial = mb_strtoupper(trim((string)($c->serialNumber ?? '')));
                    if ($dbSerial === $cleanSn) {
                        $exactMatch = $c;
                        break;
                    }
                    if (!$hasValidSn && in_array($dbSerial, $genericSns)) {
                        $partialMatch = $c;
                    }
                }
            }

            if ($exactMatch) {
                // Same ID + Same SN in DB -> Update
                $success = $repo->partialUpdate($exactMatch->id, $row);
                if ($success) $stats['updated']++;
                else {
                    $stats['errors']++;
                    $stats['details'][] = "ERROR Fila $rowIndex: error al actualizar '{$row['name']}'";
                }
            } elseif ($partialMatch) {
                // Partial fill
                $success = $repo->partialUpdate($partialMatch->id, $row);
                if ($success) $stats['updated']++;
                else {
                    $stats['errors']++;
                    $stats['details'][] = "ERROR Fila $rowIndex: error al completar datos de '{$row['name']}'";
                }
            } elseif (!empty($candidates) && !$isGeneric) {
                // CASE B: Same ID in DB but different SN -> different physical asset -> CREATE NEW
                $existingSn = trim((string)($candidates[0]->serialNumber ?? ''));
                $row['id']  = $inventoryId;
                $row['hec_id'] = \generateAssetHecId($row);
                $success    = $repo->create($row);
                if ($success) {
                    $stats['success']++;
                    $stats['conflicts'][] = [
                        'type'         => 'B',
                        'row'          => $rowIndex,
                        'name'         => $row['name'],
                        'inventory_id' => $inventoryId,
                        'serial'       => $serialNum,
                        'detail'       => "Fila $rowIndex: [{$row['name']}] comparte N Inventario '$inventoryId' con otro equipo (SN existente: '$existingSn'). Creado como equipo separado."
                    ];
                } else {
                    $stats['errors']++;
                    $stats['details'][] = "ERROR Fila $rowIndex: error al crear '{$row['name']}' (Caso B)";
                }
            } else {
                // CASE D: New asset (or generic ID that we treat as new) -> CREATE
                $row['id'] = $inventoryId;
                $row['hec_id'] = \generateAssetHecId($row);
                $success   = $repo->create($row);
                if ($success) $stats['success']++;
                else {
                    $stats['errors']++;
                    $stats['details'][] = "ERROR Fila $rowIndex: error al crear '{$row['name']}'";
                }
            }
        } catch (\Exception $e) {
            $stats['errors']++;
            $stats['details'][] = "âŒ Fila $rowIndex '{$row['name']}': " . $e->getMessage();
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
        throw new \Exception("ERROR_ZIP_EXTENSION_MISSING: La extensiÃ³n 'zip' no estÃ¡ habilitada en tu servidor PHP. Es necesaria para leer archivos .xlsx.");
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
            // Handle rich-text strings: concatenate ALL <r><t>...</t></r> runs.
            // The old code only read the first run, losing text for formatted cells.
            if (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)($r->t ?? '');
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = (string)($si->t ?? '');
            }
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

                // Manejo de Ã­ndices de columnas (Excel salta celdas vacÃ­as)
                $ref = (string)$cell['r']; // E.g. "A1"
                $colIndex = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) {
                        $colIndex = $colIndex * 26 + (ord($ref[$i]) - 64);
                    } else break;
                }
                $currentRow[$colIndex - 1] = $val;
            }

            // Rellenar huecos si Excel omitiÃ³ celdas vacÃ­as en medio
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
 * Exporta todos los activos a un archivo CSV descargable siguiendo el formato de "Prueba 2.csv".
 */
function exportAssetsToCsv()
{
    $assets = getAllAssets();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario_biomedico_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');

    $output = fopen('php://output', 'w');

    // Bom para UTF-8 (Excel friendly)
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeceras exactas de "Prueba 2.csv" + CÃ³digo Sistema
    $headersExport = [
        'CÃ“DIGO SISTEMA (ID)',
        'SERVICIO CLÃNICO',
        'RECINTO',
        'CLASE',
        'SUBCLASE',
        'NOMBRE EQUIPO',
        'MARCA',
        'MODELO',
        'SERIE',
        'NÂ° INVENTARIO',
        'AÃ‘O DE ADQUISICIÃ“N',
        'VIDA ÃšTIL',
        'VIDA ÃšTIL RESIDUAL',
        'PROPIO / ARRIENDO / COMODATO',
        'ESTADO (BUENO / REGULAR / MALO / BAJA)',
        'CRÃTICO/ RELEVANTE / IMâ‰¥12 / NO APLICA',
        'EN GARANTÃA (SI / NO)',
        'AÃ‘O VENCIMIENTO GARANTÃA',
        'BAJO PLAN DE MANTENIMIENTO (SI / NO)',
        'AÃ‘O INGRESO A PLAN DE MANTENIMIENTO',
        'MANTENIMIENTO INTERNO O MANTENIMIENTO EXTERNO O CONTRATO',
        'NOMBRE DE PROVEEDOR O MANTENIMIENTO INTERNO',
        'ID CONVENIO DE MANTENIMIENTO / ID DE REFERENCIA / COTIZACIÃ“N DE REFERENCIA',
        'COSTO ANUAL DE MANTENIMIENTO SEGÃšN CONVENIO / PRECIO DE REFERENCIA MANTENIMIENTO ANUAL',
        'FRECUENCIA ANUAL DE MANTENIMIENTO',
        // Meses AÃ±o 1
        'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE',
        // Meses AÃ±o 2
        'ENERO_2', 'FEBRERO_2', 'MARZO_2', 'ABRIL_2', 'MAYO_2', 'JUNIO_2', 'JULIO_2', 'AGOSTO_2', 'SEPTIEMBRE_2', 'OCTUBRE_2', 'NOVIEMBRE_2', 'DICIEMBRE_2'
    ];

    fputcsv($output, $headersExport, ';');

    foreach ($assets as $asset) {
        $freq_meses = (int)($asset['frecuencia_mp_meses'] ?? 6);
        $annual_freq = ($freq_meses > 0) ? round(12 / $freq_meses, 1) : 0;
        
        $row = [
            $asset['hec_id'] ?? $asset['id'], // CÃ“DIGO SISTEMA (ID)
            $asset['location'] ?? '',
            $asset['sub_location'] ?? '',
            $asset['riesgo_ge'] ?? '',
            $asset['subclase'] ?? '',
            $asset['name'] ?? '',
            $asset['brand'] ?? '',
            $asset['model'] ?? '',
            $asset['serial_number'] ?? '',
            $asset['inventory_id'] ?? '',
            $asset['purchased_year'] ?? '',
            $asset['total_useful_life'] ?? '',
            $asset['years_remaining'] ?? '',
            $asset['ownership'] ?? 'PROPIO',
            $asset['status'] ?? 'BUENO',
            $asset['criticality'] ?? 'LOW',
            ($asset['warranty_expiration'] ? 'SI' : 'NO'),
            $asset['warranty_expiration'] ?? '',
            ($asset['under_maintenance_plan'] ? 'SI' : 'NO'),
            $asset['purchased_year'] ?? '',
            ($asset['contract_id'] ? 'MANTENIMIENTO EXTERNO' : 'MANTENIMIENTO INTERNO'),
            $asset['vendor'] ?? '',
            $asset['contract_id'] ?? '',
            $asset['annual_maint_cost'] ?? 0,
            $annual_freq
        ];

        // Rellenar los 24 meses con vacÃ­o (BioCMMS los gestiona dinÃ¡micamente)
        for ($i = 0; $i < 24; $i++) {
            $row[] = '';
        }

        fputcsv($output, $row, ';');
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

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_minsal_financiero_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // SecciÃ³n 1: KPIs Globales
    fputcsv($output, ['REPORTE FINANCIERO CONSOLIDADO - BIO-CMMS']);
    fputcsv($output, ['Fecha GeneraciÃ³n', date('Y-m-d H:i:s')]);
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
