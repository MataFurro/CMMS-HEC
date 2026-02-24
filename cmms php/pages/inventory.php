<?php
// pages/inventory.php

// ── Backend Provider ──
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/ExcelProvider.php';

use function Backend\Providers\exportAssetsToCsv;
use function Backend\Providers\importAssetsFromFile;

// --- EXPORT LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'export' && canModify()) {
    exportAssetsToCsv();
}

// --- IMPORT LOGIC ---
$importStats = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file']) && canModify()) {
    $importStats = importAssetsFromFile($_FILES['excel_file']);
}

// --- DELETE LOGIC ---
if (isset($_GET['delete_id']) && canModify()) {
    $deleteId = $_GET['delete_id'];
    if (deleteAsset($deleteId)) {
        echo "<script>
    alert('Activo dado de baja correctamente.');
    window.location.href = '?page=inventory';
</script>";
        exit;
    }
}

// --- FILTERING & PAGINATION LOGIC ---
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'ALL';
$locationFilter = $_GET['location'] ?? 'ALL';
$brandFilter = $_GET['brand'] ?? 'ALL';
$criticalityFilter = $_GET['criticality'] ?? 'ALL';
$categoryFilter = $_GET['category'] ?? 'ALL'; // Note: In UI we call it "Clase", but param is "category"
$familyFilter = $_GET['family'] ?? 'ALL'; // This maps to riesgo_ge

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$activeFilters = [
    'location' => $locationFilter,
    'brand' => $brandFilter,
    'criticality' => $criticalityFilter,
    'category_id' => $categoryFilter,
    'family' => $familyFilter
];

$totalAssetsCount = countAssets($searchTerm, $statusFilter, $activeFilters);
$totalPages = ceil($totalAssetsCount / $limit);
$filteredAssets = searchAssets($searchTerm, $statusFilter, $limit, $offset, $activeFilters);

// Get options for filters
$allBrands = getBrandOptions();
$allLocations = getAllLocations();
$allCriticalities = getCriticalityOptions();
$allCategories = getCategoryOptions();

// --- HELPER: URL BUILDER (Usabilidad) ---
$filterParams = [
    'page' => 'inventory',
    'search' => $searchTerm,
    'status' => $statusFilter,
    'location' => $locationFilter,
    'brand' => $brandFilter,
    'criticality' => $criticalityFilter,
    'category' => $categoryFilter,
    'family' => $familyFilter
];
$buildUrl = function ($p, $overrides = []) use ($filterParams) {
    if ($p === null) $p = (isset($_GET['p']) ? (int)$_GET['p'] : 1);
    $params = array_merge($filterParams, $overrides);
    $params['p'] = $p;
    return '?' . http_build_query($params);
};

// Función helper para resaltar términos (UX)
function highlight($text, $term)
{
    if (empty($term)) return htmlspecialchars($text);
    return preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark class="bg-yellow-500/30 text-inherit p-0 rounded">$1</mark>', htmlspecialchars($text));
}
?>

<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 mb-4">
                <a href="?page=dashboard" class="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted hover:text-medical-blue transition-colors">BioCMMS</a>
                <span class="material-symbols-outlined text-xs text-text-muted opacity-50">chevron_right</span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue bg-medical-blue/10 px-2 py-0.5 rounded">Gestión de Activos</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight text-[var(--text-main)] flex items-center gap-4">
                Inventario Maestro
                <span class="text-medical-blue font-light text-2xl tracking-normal opacity-60">Biomédico</span>
            </h1>
            <p class="text-text-muted mt-2 text-lg">Central de activos clínicos con soporte de vida y monitoreo avanzado.</p>
        </div>
        <div class="flex items-center gap-4">
            <?php if (canModify()): ?>
                <a href="?page=inventory&action=export"
                    class="h-11 flex items-center gap-3 px-6 bg-medical-surface text-text-main border border-border-dark rounded-2xl hover:bg-medical-blue/10 transition-all font-bold shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">download</span>
                    <span><?= BTN_DOWNLOAD_EXCEL ?></span>
                </a>
            <?php endif; ?>

            <?php if (canModify()): ?>
                <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                    <input type="file" name="excel_file" id="excel_input" class="hidden" accept=".xlsx, .xls, .csv"
                        onchange="this.form.submit()">
                    <button type="button" onclick="document.getElementById('excel_input').click()"
                        class="h-11 flex items-center gap-3 px-6 bg-excel-green text-white rounded-2xl hover:bg-excel-green/90 transition-all font-bold shadow-xl shadow-excel-green/20 active:scale-95">
                        <span class="material-symbols-outlined text-xl">upload_file</span>
                        <span><?= BTN_UPLOAD_EXCEL ?></span>
                    </button>
                </form>
                <a href="?page=new_asset"
                    class="h-11 flex items-center gap-3 px-8 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20 active:scale-95">
                    <span class="material-symbols-outlined text-xl">add_box</span>
                    <span><?= BTN_NEW_ASSET ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Import Results -->
    <?php if ($importStats): ?>
        <div class="card-glass p-6 border-l-4 <?= $importStats['errors'] > 0 ? 'border-amber-500' : 'border-success' ?> animate-in slide-in-from-top-4 duration-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-2 <?= $importStats['errors'] > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-success/10 text-success' ?> rounded-lg">
                        <span class="material-symbols-outlined"><?= $importStats['errors'] > 0 ? 'info' : 'check_circle' ?></span>
                    </div>
                    <div>
                        <h4 class="font-bold text-[var(--text-main)]">Resultado de la Importación</h4>
                        <p class="text-xs text-[var(--text-muted)]">Total procesados: <?= $importStats['total'] ?> | Éxito: <?= $importStats['success'] ?> | Errores: <?= $importStats['errors'] ?></p>
                    </div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-text-muted hover:text-danger transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card-glass p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-medical-blue"></div>

        <form method="GET" class="space-y-6">
            <input type="hidden" name="page" value="inventory">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:col-span-12 gap-4">
                <!-- Búsqueda Principal -->
                <div class="md:col-span-2 lg:col-span-5 relative group">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted group-focus-within:text-medical-blue transition-colors">search</span>
                    <input name="search" value="<?= htmlspecialchars($searchTerm) ?>"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl pl-12 pr-6 py-3.5 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none placeholder:text-text-muted/50 text-text-main transition-all"
                        placeholder="Buscar por nombre, marca, serie o ID..." />
                </div>

                <!-- Filtro de Clase (riesgo_ge) -->
                <div class="lg:col-span-3">
                    <select name="family"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl px-6 py-3.5 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-text-main">
                        <option value="ALL">Todas las Clases</option>
                        <?php foreach ($allCategories as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $familyFilter === $f ? 'selected' : '' ?>>
                                📁 <?= htmlspecialchars($f) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro de Estado -->
                <div class="lg:col-span-3">
                    <select name="status"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl px-6 py-3.5 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-text-main">
                        <option value="ALL">Cualquier Estado</option>
                        <option value="<?= STATUS_OPERATIVE ?>" <?= $statusFilter === STATUS_OPERATIVE ? 'selected' : '' ?>>Operativo</option>
                        <option value="<?= STATUS_MAINTENANCE ?>" <?= $statusFilter === STATUS_MAINTENANCE ? 'selected' : '' ?>>En Mantención</option>
                        <option value="<?= STATUS_OPERATIVE_WITH_OBS ?>" <?= $statusFilter === STATUS_OPERATIVE_WITH_OBS ? 'selected' : '' ?>>Operativo con Obs.</option>
                        <option value="<?= STATUS_NO_OPERATIVE ?>" <?= $statusFilter === STATUS_NO_OPERATIVE ? 'selected' : '' ?>>Fuera de Servicio</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="lg:col-span-4 flex gap-3">
                    <button type="submit"
                        class="flex-1 bg-medical-blue text-white rounded-2xl py-3.5 text-sm font-black uppercase tracking-widest hover:bg-medical-blue/90 shadow-lg shadow-medical-blue/20 transition-all active:scale-95">
                        Filtrar
                    </button>
                    <a href="?page=inventory"
                        class="h-12 w-12 flex items-center justify-center bg-medical-surface border border-border-dark rounded-2xl text-text-muted hover:text-danger hover:border-danger/30 transition-all active:scale-95" title="Limpiar Filtros">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                    </a>
                </div>
            </div>

            <!-- Filtros Avanzados (Grid Inferior) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-border-dark">
                <!-- Filtro de Ubicación -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-4">Servicio Clínico</label>
                    <select name="location"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl px-6 py-3 text-xs focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-text-main">
                        <option value="ALL">Todos los Servicios</option>
                        <?php foreach ($allLocations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= $locationFilter === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro de Marca -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-4">Marca / Fabricante</label>
                    <select name="brand"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl px-6 py-3 text-xs focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-text-main">
                        <option value="ALL">Todas las Marcas</option>
                        <?php foreach ($allBrands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>" <?= $brandFilter === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro de Criticidad -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-4">Nivel de Criticidad</label>
                    <select name="criticality"
                        class="w-full bg-medical-surface border border-border-dark rounded-2xl px-6 py-3 text-xs focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-text-main">
                        <option value="ALL">Cualquier Criticidad</option>
                        <?php foreach ($allCriticalities as $crit): ?>
                            <option value="<?= htmlspecialchars($crit) ?>" <?= $criticalityFilter === $crit ? 'selected' : '' ?>><?= htmlspecialchars($crit) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Filter Chips -->
            <?php
            $hasFilters = $searchTerm !== '' || $statusFilter !== 'ALL' || $locationFilter !== 'ALL' || $brandFilter !== 'ALL' || $criticalityFilter !== 'ALL' || $categoryFilter !== 'ALL' || $familyFilter !== 'ALL';
            if ($hasFilters):
            ?>
                <div class="flex flex-wrap gap-2 pt-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-text-muted flex items-center mr-2">Filtros Activos:</span>
                    <?php if ($searchTerm !== ''): ?>
                        <div class="px-3 py-1 bg-medical-blue text-white text-[10px] font-bold rounded-full flex items-center gap-2">
                            <span>Búsqueda: <?= htmlspecialchars($searchTerm) ?></span>
                            <a href="<?= $buildUrl(null, ['search' => '']) ?>" class="hover:opacity-70"><span class="material-symbols-outlined text-sm">close</span></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($familyFilter !== 'ALL'): ?>
                        <div class="px-3 py-1 bg-medical-blue text-white text-[10px] font-bold rounded-full flex items-center gap-2">
                            <span>Clase: <?= htmlspecialchars($familyFilter) ?></span>
                            <a href="<?= $buildUrl(null, ['family' => 'ALL']) ?>" class="hover:opacity-70"><span class="material-symbols-outlined text-sm">close</span></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($categoryFilter !== 'ALL'): ?>
                        <div class="px-3 py-1 bg-medical-blue/50 text-white text-[10px] font-bold rounded-full flex items-center gap-2">
                            <span>Sub-Cat: <?= htmlspecialchars($categoryFilter) ?></span>
                            <a href="<?= $buildUrl(null, ['category' => 'ALL']) ?>" class="hover:opacity-70"><span class="material-symbols-outlined text-sm">close</span></a>
                        </div>
                    <?php endif; ?>
                    <a href="?page=inventory" class="text-[10px] font-bold text-danger hover:underline ml-2">Limpiar Todo</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="card-glass overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-medical-surface/50 border-b border-border-dark">
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted">Activo / Modelo</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted">N° de Serie</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted text-center">Criticidad</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted">Ubicación / Sub</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted text-center">Estado</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted text-center">Vida Útil (Rem / Total)</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-text-muted text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-dark/50">
                    <?php
                    foreach ($filteredAssets as $asset):
                    ?>
                        <tr class="hover:bg-medical-blue/5 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 rounded-xl border border-border-dark overflow-hidden bg-medical-dark p-1 shrink-0 group-hover:scale-110 transition-transform">
                                        <img src="<?= $asset['image_url'] ?>" class="w-full h-full object-cover rounded-lg" alt="<?= $asset['name'] ?>">
                                    </div>
                                    <div>
                                        <a href="?page=asset&id=<?= $asset['id'] ?>" class="font-bold text-[var(--text-main)] hover:text-medical-blue transition-colors block text-base leading-tight">
                                            <?= highlight($asset['name'], $searchTerm) ?>
                                        </a>
                                        <div class="text-xs text-[var(--text-muted)] mt-1 uppercase font-semibold flex items-center gap-2">
                                            <?= highlight($asset['brand'], $searchTerm) ?> <?= highlight($asset['model'], $searchTerm) ?> · <span class="font-mono text-medical-blue"><?= highlight($asset['id'], $searchTerm) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 font-mono text-xs text-medical-blue font-bold"><?= $asset['serial_number'] ?? '-' ?></td>
                            <td class="px-6 py-6 text-center">
                                <?php
                                $critMap = ['CRITICAL' => ['Crítico', 'bg-danger/10 text-danger border-danger/30'], 'RELEVANT' => ['Relevante', 'bg-amber-500/10 text-amber-400 border-amber-500/30'], 'LOW' => ['No Aplica', 'bg-slate-500/10 text-slate-400 border-slate-500/30']];
                                [$cLabel, $cClass] = $critMap[$asset['criticality']] ?? [$asset['criticality'], 'bg-slate-500/10 text-slate-400 border-slate-500/30'];
                                $esMonitoreo = ($asset['riesgo_ge'] ?? '') === 'Monitoreo';
                                ?>
                                <div class="flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase border <?= $cClass ?>"><?= $cLabel ?></span>
                                    <?php if (!empty($asset['riesgo_ge'])): ?>
                                        <span class="text-[9px] font-bold <?= $esMonitoreo ? 'text-blue-400' : 'text-slate-500' ?>"><?= $esMonitoreo ? '📡 Monitoreo' : '🔧 No Monitoreo' ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="font-bold text-[var(--text-main)] text-sm"><?= !empty($asset['location']) ? $asset['location'] : 'Sin Servicio' ?></div>
                                <div class="text-[10px] text-[var(--text-muted)] uppercase mt-0.5 font-bold tracking-tight"><?= !empty($asset['sub_location']) ? $asset['sub_location'] : 'Sin Recinto' ?></div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <?php
                                $statusClass = match ($asset['status']) {
                                    STATUS_OPERATIVE => 'bg-success/10 text-success border-success/30',
                                    STATUS_MAINTENANCE => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
                                    STATUS_OPERATIVE_WITH_OBS => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/30',
                                    STATUS_NO_OPERATIVE => 'bg-red-500/10 text-red-500 border-red-500/30',
                                    default => 'bg-slate-700/10 text-slate-500 border-slate-700/30'
                                };
                                ?>
                                <span class="px-4 py-1.5 rounded-xl text-xs font-black inline-flex items-center gap-2 uppercase tracking-wide border <?= $statusClass ?>">
                                    <?= $asset['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-6">
                                <div class="w-28 mx-auto">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black <?= $asset['useful_life_pct'] <= 0 ? 'text-red-500' : 'text-text-muted' ?>">
                                                <?= $asset['useful_life_pct'] <= 0 ? 'Excedida' : $asset['useful_life_pct'] . '%' ?>
                                            </span>
                                            <span class="text-[8px] font-bold text-text-muted/60 uppercase">
                                                <?= ($asset['years_remaining'] ?? 0) ?> / <?= ($asset['total_useful_life'] ?? 0) ?> Años
                                            </span>
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full bg-medical-surface rounded-full overflow-hidden border border-border-dark">
                                        <?php
                                        $barColor = 'bg-success';
                                        if ($asset['useful_life_pct'] <= 0) $barColor = 'bg-red-500 animate-pulse';
                                        elseif ($asset['useful_life_pct'] <= 20) $barColor = 'bg-amber-500';

                                        $barWidth = max(0, min(100, $asset['useful_life_pct'] > 0 ? $asset['useful_life_pct'] : 100));
                                        ?>
                                        <div class="h-full <?= $barColor ?>" style="width: <?= $barWidth ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?page=asset&id=<?= $asset['id'] ?>" class="px-4 py-2 bg-medical-blue/10 text-medical-blue rounded-xl hover:bg-medical-blue hover:text-white transition-all border border-medical-blue/20 flex items-center gap-2 text-[10px] font-black uppercase tracking-wider shadow-lg shadow-medical-blue/5">
                                        <span class="material-symbols-outlined text-sm">history</span>
                                        Historial
                                    </a>
                                    <?php if (canModify()): ?>
                                        <a href="?page=inventory&delete_id=<?= $asset['id'] ?>" onclick="return confirm('¿Está seguro de que desea dar de baja este equipo?')" class="p-2 text-text-muted hover:text-red-500 hover:bg-red-500/10 rounded-xl border border-transparent hover:border-red-500/30 transition-all shadow-lg">
                                            <span class="material-symbols-outlined text-xl">delete_sweep</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between card-glass px-8 py-4 mt-6">
            <div class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-widest">
                Mostrando <span class="text-medical-blue"><?= ($offset + 1) ?>-<?= min($totalAssetsCount, $offset + $limit) ?></span> de <?= $totalAssetsCount ?> equipos
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="<?= $buildUrl(1) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-medical-blue/10 text-medical-blue border border-medical-blue/20 hover:bg-medical-blue hover:text-white transition-all">
                        <span class="material-symbols-outlined">first_page</span>
                    </a>
                <?php endif; ?>

                <?php
                $startRange = max(1, $page - 2);
                $endRange = min($totalPages, $page + 2);
                for ($i = $startRange; $i <= $endRange; $i++):
                ?>
                    <a href="<?= $buildUrl($i) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-xs transition-all border <?= $i === $page ? 'bg-medical-blue text-white border-medical-blue shadow-lg shadow-medical-blue/20' : 'bg-medical-blue/10 text-medical-blue border-medical-blue/20 hover:bg-medical-blue hover:text-white' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $buildUrl($totalPages) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-medical-blue/10 text-medical-blue border border-medical-blue/20 hover:bg-medical-blue hover:text-white transition-all">
                        <span class="material-symbols-outlined">last_page</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>