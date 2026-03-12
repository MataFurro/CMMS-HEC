<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';
require_once __DIR__ . '/Backend/Providers/ExcelProvider.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\AssetRepository;

$db = DatabaseService::getInstance();
// Force PDO to throw exceptions immediately so we can see them
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Override AssetRepository temporarily just to see the exact INSERT query failure
class DebugAssetRepository extends AssetRepository
{
    public function create(array $data): bool
    {
        try {
            return parent::create($data);
        } catch (\Exception $e) {
            echo "\n------------------\n";
            echo "FAILED TO INSERT!\n";
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "DATA: \n";
            print_r($data);
            echo "------------------\n";
            return false;
        }
    }
}

$repo = new DebugAssetRepository($db);

// Simulate the logic in ExcelProvider for just ROW 3 using the Excel file itself to be perfect
use function Backend\Providers\parseXlsxToArray;

$file = __DIR__ . '/Prueba 2.xlsx';
if (!file_exists($file)) {
    $file = 'C:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.xlsx';
}
$rows = parseXlsxToArray($file);
$headersRaw = array_shift($rows);

$cleaner = function ($h) {
    return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $h));
};
$headers = array_map(function ($h) use ($cleaner) {
    $h = $cleaner($h);
    $map = [
        'servicio' => 'location',
        'servicioclinico' => 'location',
        'recinto' => 'sub_location',
        'piso' => 'sub_location',
        'clase' => 'risk_class',
        'nombre' => 'name',
        'nombreequipo' => 'name',
        'marca' => 'brand',
        'modelo' => 'model',
        'serie' => 'serial_number',
        'nserie' => 'serial_number',
        'ninventario' => 'id',
        'inventario' => 'id',
        'adquisicion' => 'purchased_year',
        'aodeadquisicion' => 'purchased_year',
        'vidautil' => 'total_useful_life',
        'vidautilresidual' => 'years_remaining',
        'estadobuenoregularmalobaja' => 'status',
        'estado' => 'status',
        'criticorelevanteim12noaplica' => 'criticality',
        'critico' => 'criticality',
    ];
    return $map[$h] ?? $h;
}, $headersRaw);

// Row 3 is index 1 because shift removes header
$data = $rows[1]; // ROW 3 (Monitor)

$row = array_combine(array_slice($headers, 0, count($data)), array_slice($data, 0, count($headers)));
if (empty(trim($row['name'] ?? ''))) $row['name'] = 'SIN NOMBRE';

// normalize criticality
$c = mb_strtoupper(trim((string)$row['criticality']), 'UTF-8');
if (strpos($c, 'CRÍTICO') !== false || strpos($c, 'CRITICO') !== false) $row['criticality'] = 'CRITICAL';
elseif (strpos($c, 'RELEVANTE') !== false) $row['criticality'] = 'RELEVANT';
elseif (strpos($c, 'NO APLICA') !== false) $row['criticality'] = 'NA';
else $row['criticality'] = 'LOW';

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

$tul = $row['total_useful_life'];
$yr  = $row['years_remaining'];
if ($tul && $tul > 0 && $yr !== null) {
    $row['useful_life_pct'] = min(100, max(0, (int)round(($yr / $tul) * 100)));
}

$inventoryId = $row['id'] ?? null;
$row['inventory_id'] = $inventoryId;

$row['id'] = $inventoryId . '-' . substr(md5(uniqid()), 0, 4);

echo "Pushing parsed ROW 3 to AssetRepository::create...\n";
$repo->create($row);
echo "Finished.\n";
