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
    if (softDeleteAsset($deleteId)) {
        $_SESSION['undo_delete_id'] = $deleteId;
        // Limpiamos los buffers activos de forma segura para no bloquear (timeout)
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header("Location: ?page=inventory&action=deleted");
        exit;
    } else {
        $error_msg = "No se pudo dar de baja el equipo. Verifique los logs.";
    }
}

// --- UNDO DELETE LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'undo' && isset($_SESSION['undo_delete_id'])) {
    if (restoreAsset($_SESSION['undo_delete_id'])) {
        unset($_SESSION['undo_delete_id']);
        echo "<script>window.location.href='?page=inventory&action=restored';</script>";
        exit;
    }
}

// --- FILTERING & PAGINATION LOGIC ---
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'ALL';
$locationFilter = $_GET['location'] ?? 'ALL';
$brandFilter = $_GET['brand'] ?? 'ALL';
$criticalityFilter = $_GET['criticality'] ?? 'ALL';
$familyFilter = $_GET['family'] ?? 'ALL';

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 15; // Reducido un poco para mejor visualización premium
$offset = ($page - 1) * $limit;

$activeFilters = [
    'location' => $locationFilter,
    'brand' => $brandFilter,
    'criticality' => $criticalityFilter,
    'family' => $familyFilter
];

// Define Status Labels for UI consistency
$statusLabels = [
    STATUS_OPERATIVE => 'Operativo',
    STATUS_MAINTENANCE => 'Mantención',
    STATUS_NO_OPERATIVE => 'Fuera de Servicio',
    STATUS_OPERATIVE_WITH_OBS => 'Observado'
];

$totalAssetsCount = countAssets($searchTerm, $statusFilter, $activeFilters);
$totalPages = ceil($totalAssetsCount / $limit);
$filteredAssets = searchAssets($searchTerm, $statusFilter, $limit, $offset, $activeFilters);

// Stats for Mini-Dashboard
$globalStatus = countAssetsByStatus();
$inventoryValue = getTotalInventoryValue();
$operativePct = $globalStatus['total'] > 0 ? round(($globalStatus['operative'] / $globalStatus['total']) * 100, 1) : 0;

// Get options for filters
$allBrandsCount = getBrandCounts();
$allLocationsCount = getLocationCounts();
$allCategories = getCategoryOptions();
$allCriticalities = getCriticalityOptions();

// --- HELPER: URL BUILDER ---
$filterParams = [
    'page' => 'inventory',
    'search' => $searchTerm,
    'status' => $statusFilter,
    'location' => $locationFilter,
    'brand' => $brandFilter,
    'criticality' => $criticalityFilter,
    'family' => $familyFilter
];
$buildUrl = function ($p, $overrides = []) use ($filterParams) {
    if ($p === null) $p = (isset($_GET['p']) ? (int)$_GET['p'] : 1);
    $params = array_merge($filterParams, $overrides);
    $params['p'] = $p;
    return '?' . http_build_query($params);
};
?>

<div x-data="{ 
    selectedItems: [],
    showFilters: <?= ($searchTerm || $statusFilter !== 'ALL' || $locationFilter !== 'ALL') ? 'true' : 'false' ?>
}" class="space-y-8 animate-in fade-in duration-700">

    <!-- Import Report Panel -->
    <?php if ($importStats): ?>
        <div class="bg-white dark:bg-panel-dark rounded-3xl border border-medical-blue/20 p-6 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-64 h-64 bg-medical-blue/5 rounded-full blur-3xl -mx-20 -my-20 pointer-events-none"></div>
            <div class="flex items-center justify-between mb-6 relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-medical-blue/10 flex items-center justify-center border border-medical-blue/20">
                        <span class="material-symbols-outlined text-medical-blue text-2xl font-variation-fill">file_download_done</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-text-main tracking-tight">Resultado de la Importación</h3>
                        <p class="text-sm text-text-muted font-medium"><?= $importStats['total'] ?> filas procesadas desde el archivo Excel.</p>
                    </div>
                </div>
                <button onclick="this.closest('.bg-white').style.display='none'" class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-red-100 text-slate-500 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-2 relative z-10">
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-black text-emerald-600 mb-1"><?= $importStats['success'] ?></div>
                    <div class="text-[10px] font-black text-emerald-600/80 uppercase tracking-widest">Nuevos Creados</div>
                </div>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-black text-blue-600 mb-1"><?= $importStats['updated'] ?></div>
                    <div class="text-[10px] font-black text-blue-600/80 uppercase tracking-widest">Actualizados</div>
                </div>
                <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-black text-amber-600 mb-1"><?= $importStats['merged'] ?? 0 ?></div>
                    <div class="text-[10px] font-black text-amber-600/80 uppercase tracking-widest">Fusión Interna</div>
                </div>
                <div class="bg-slate-500/10 border border-slate-500/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-black text-slate-600 mb-1"><?= $importStats['skipped'] ?? 0 ?></div>
                    <div class="text-[10px] font-black text-slate-600/80 uppercase tracking-widest">Omitidos Vaciós</div>
                </div>
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-black text-red-600 mb-1"><?= $importStats['errors'] ?></div>
                    <div class="text-[10px] font-black text-red-600/80 uppercase tracking-widest">Con Errores</div>
                </div>
            </div>

            <?php if (!empty($importStats['details'])): ?>
                <div class="mt-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 relative z-10">
                    <h4 class="text-xs font-black text-slate-500 mb-3 uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">info</span>
                        Registro de Excepciones y Omisiones
                    </h4>
                    <ul class="space-y-2 max-h-48 overflow-y-auto text-xs text-slate-600 font-mono pr-2 custom-scrollbar">
                        <?php foreach ($importStats['details'] as $detail): ?>
                            <li class="flex gap-2">
                                <span class="text-slate-400">&bull;</span>
                                <span><?= htmlspecialchars($detail) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($importStats['conflicts'])): ?>
                <?php
                $conflictsA    = array_filter($importStats['conflicts'], fn($c) => $c['type'] === 'A');
                $conflictsB    = array_filter($importStats['conflicts'], fn($c) => $c['type'] === 'B');
                $conflictsC    = array_filter($importStats['conflicts'], fn($c) => $c['type'] === 'C');
                $conflictsNoId = array_filter($importStats['conflicts'], fn($c) => $c['type'] === 'NO_ID');
                ?>
                <div class="mt-6 relative z-10 space-y-4">
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-amber-500">warning</span>
                        Alertas de Identidad Detectadas (<?= count($importStats['conflicts']) ?> total)
                    </h4>

                    <?php if (!empty($conflictsB)): ?>
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300/50 rounded-2xl p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <span class="material-symbols-outlined text-amber-500 text-xl mt-0.5">content_copy</span>
                                <div>
                                    <p class="text-sm font-black text-amber-700 dark:text-amber-400">Caso B — Mismo N° Inventario, distinto N° Serie (<?= count($conflictsB) ?> equipos)</p>
                                    <p class="text-xs text-amber-600/80 mt-0.5">Estos equipos comparten el mismo N° de Inventario pero tienen series distintas. Se crearon como equipos separados en la base de datos. <strong>Revisa si el N° de Inventario en el Excel es correcto.</strong></p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-amber-600/70 font-bold border-b border-amber-200">
                                            <th class="pb-2 pr-4">Fila</th>
                                            <th class="pb-2 pr-4">Equipo</th>
                                            <th class="pb-2 pr-4">N° Inventario</th>
                                            <th class="pb-2">N° Serie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        <?php foreach ($conflictsB as $c): ?>
                                            <tr class="text-slate-700 dark:text-slate-300">
                                                <td class="py-1.5 pr-4 font-mono"><?= $c['row'] ?></td>
                                                <td class="py-1.5 pr-4"><?= htmlspecialchars($c['name']) ?></td>
                                                <td class="py-1.5 pr-4 font-mono text-amber-700"><?= htmlspecialchars($c['inventory_id']) ?></td>
                                                <td class="py-1.5 font-mono"><?= htmlspecialchars($c['serial']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($conflictsC)): ?>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-300/50 rounded-2xl p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <span class="material-symbols-outlined text-blue-500 text-xl mt-0.5">merge</span>
                                <div>
                                    <p class="text-sm font-black text-blue-700 dark:text-blue-400">Caso C — Mismo N° Serie, distinto N° Inventario (<?= count($conflictsC) ?> equipos)</p>
                                    <p class="text-xs text-blue-600/80 mt-0.5">Estos equipos tienen el mismo N° de Serie que otro ya registrado pero con un N° Inventario diferente. Se contaron como <strong>1 solo equipo</strong> para evitar duplicar el mismo activo físico.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-blue-600/70 font-bold border-b border-blue-200">
                                            <th class="pb-2 pr-4">Fila</th>
                                            <th class="pb-2 pr-4">Equipo</th>
                                            <th class="pb-2 pr-4">N° Inventario</th>
                                            <th class="pb-2">N° Serie (compartido)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-blue-100">
                                        <?php foreach ($conflictsC as $c): ?>
                                            <tr class="text-slate-700 dark:text-slate-300">
                                                <td class="py-1.5 pr-4 font-mono"><?= $c['row'] ?></td>
                                                <td class="py-1.5 pr-4"><?= htmlspecialchars($c['name']) ?></td>
                                                <td class="py-1.5 pr-4 font-mono"><?= htmlspecialchars($c['inventory_id']) ?></td>
                                                <td class="py-1.5 font-mono text-blue-700"><?= htmlspecialchars($c['serial']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($conflictsA)): ?>
                        <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-300/50 rounded-2xl p-5">
                            <div class="flex items-start gap-3 mb-2">
                                <span class="material-symbols-outlined text-slate-400 text-xl mt-0.5">file_copy</span>
                                <div>
                                    <p class="text-sm font-black text-slate-600 dark:text-slate-400">Caso A &mdash; Duplicados exactos en el Excel (<?= count($conflictsA) ?> equipos)</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Estos equipos aparecen m&aacute;s de una vez con el mismo ID y mismo N&deg; Serie. Se contaron como <strong>1</strong>.</p>
                                </div>
                            </div>
                            <ul class="text-xs font-mono text-slate-500 space-y-1 mt-2 pl-9">
                                <?php foreach ($conflictsA as $c): ?>
                                    <li>Fila <?= $c['row'] ?>: [<?= htmlspecialchars($c['name']) ?>] ID: <?= htmlspecialchars($c['inventory_id']) ?> &middot; SN: <?= htmlspecialchars($c['serial']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($conflictsNoId)): ?>
                        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-300/50 rounded-2xl p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <span class="material-symbols-outlined text-orange-500 text-xl mt-0.5">help_outline</span>
                                <div>
                                    <p class="text-sm font-black text-orange-700 dark:text-orange-400">Sin Identificadores &mdash; N&deg; Inventario ni N&deg; Serie v&aacute;lidos (<?= count($conflictsNoId) ?> equipos)</p>
                                    <p class="text-xs text-orange-600/80 mt-0.5">Estos equipos no tienen N&deg; de Inventario ni N&deg; de Serie confiables. Se crearon como equipos separados pero <strong>no podr&aacute;n identificarse en futuras importaciones</strong>. Asigna identificadores manualmente.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-orange-600/70 font-bold border-b border-orange-200">
                                            <th class="pb-2 pr-4">Fila</th>
                                            <th class="pb-2 pr-4">Equipo</th>
                                            <th class="pb-2 pr-4">Ubicaci&oacute;n</th>
                                            <th class="pb-2">SN registrado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-orange-100">
                                        <?php foreach ($conflictsNoId as $c): ?>
                                            <tr class="text-slate-700 dark:text-slate-300">
                                                <td class="py-1.5 pr-4 font-mono"><?= $c['row'] ?></td>
                                                <td class="py-1.5 pr-4"><?= htmlspecialchars($c['name']) ?></td>
                                                <td class="py-1.5 pr-4 font-mono text-orange-700"><?= htmlspecialchars($c['inventory_id']) ?></td>
                                                <td class="py-1.5 font-mono text-slate-400"><?= htmlspecialchars($c['serial'] ?: '—') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- SweetAlert notification to ensure user notices anomalies -->
        <?php if (($importStats['errors'] > 0) || ($importStats['merged'] > 0) || ($importStats['skipped'] > 0) || !empty($importStats['conflicts'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Aviso de Importación',
                            html: 'El archivo Excel se ha procesado, pero se detectaron algunas anomalías:<br><br>' +
                                '<ul style="text-align:left; font-size: 0.9em; display:inline-block;">' +
                                '<?= $importStats['errors'] > 0 ? "<li><b>{$importStats['errors']}</b> fila(s) con errores graves.</li>" : "" ?>' +
                                '<?= $importStats['merged'] > 0 ? "<li><b>{$importStats['merged']}</b> fila(s) duplicadas (fusionadas en 1).</li>" : "" ?>' +
                                '<?= $importStats['skipped'] > 0 ? "<li><b>{$importStats['skipped']}</b> fila(s) omitidas por estar vacías.</li>" : "" ?>' +
                                '<?= !empty($importStats['conflicts']) ? "<li><b>" . count($importStats['conflicts']) . "</b> alerta(s) de identidad (ID o SN compartidos). Revisa el panel de abajo.</li>" : "" ?>' +
                                '</ul><br>Por favor, revisa el panel <b>Resultado de la Importación</b> para ver los detalles.',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#0ea5e9'
                        });
                    }
                });
            </script>

        <?php elseif ($importStats['success'] > 0 || $importStats['updated'] > 0): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Importación Exitosa!',
                            text: 'Se procesaron <?= $importStats['total'] ?> filas sin errores ni duplicados.',
                            confirmButtonText: 'Genial',
                            confirmButtonColor: '#10b981'
                        });
                    }
                });
            </script>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Header Section -->
    <?php
    $headerActions = '';
    if (canModify()) {
        $headerActions = '
            <div class="group relative">
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="file" name="excel_file" id="excel_input" class="hidden" accept=".xlsx, .xls, .csv" onchange="document.getElementById(\'importForm\').submit()">
                    <button type="button" onclick="document.getElementById(\'excel_input\').click()"
                        class="h-12 flex items-center gap-3 px-6 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 rounded-2xl hover:bg-emerald-500 hover:text-white transition-all duration-300 font-bold shadow-lg shadow-emerald-500/5 active:scale-95">
                        <span class="material-symbols-outlined text-xl">upload_file</span>
                        <span class="text-xs uppercase tracking-widest">' . BTN_UPLOAD_EXCEL . '</span>
                    </button>
                </form>
            </div>
            <a href="?page=inventory&action=export"
                class="h-12 flex items-center gap-3 px-6 bg-white dark:bg-panel-dark text-text-main border border-border-color rounded-2xl hover:border-medical-blue/50 hover:bg-medical-blue/5 transition-all duration-300 font-bold shadow-xl active:scale-95">
                <span class="material-symbols-outlined text-xl text-medical-blue">download</span>
                <span class="text-xs uppercase tracking-widest">Exportar</span>
            </a>
            <a href="?page=new_asset"
                class="h-12 flex items-center gap-3 px-8 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue-hover transition-all duration-300 font-black shadow-xl shadow-medical-blue/20 active:scale-95">
                <span class="material-symbols-outlined text-xl">add_circle</span>
                <span class="text-xs uppercase tracking-widest">Nuevo Activo</span>
            </a>';
    }

    $preTitle = 'BioCMMS Engine';
    $title = 'Gestión de Activos';
    $subTitle = 'Inventario Maestro';
    $icon = 'inventory_2';
    $description = 'Control centralizado del equipamiento médico de alta complejidad.';
    $actions = $headerActions;
    include __DIR__ . '/../includes/components/header_master.php';
    ?>

    <!-- Mini Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $label = 'Total Activos';
        $value = number_format($globalStatus['total']);
        $icon = 'precision_manufacturing';
        $colorClass = 'medical-blue';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'Operatividad';
        $value = $operativePct . '%';
        $icon = 'monitor_heart';
        $colorClass = 'emerald-500';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'En Mantención';
        $value = number_format($globalStatus['maintenance'] ?? 0);
        $icon = 'engineering';
        $colorClass = 'amber-500';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'Valor Inventario';
        $value = '$' . number_format(($inventoryValue ?? 0) / 1000000, 1) . 'M';
        $icon = 'payments';
        $colorClass = 'blue-500';
        include __DIR__ . '/../includes/components/metric_card.php';
        ?>
    </div>

    <!-- Advanced Search & Filters -->
    <div class="card-glass p-1 p-md-4 shadow-2xl overflow-hidden">
        <form method="GET" class="space-y-6">
            <input type="hidden" name="page" value="inventory">

            <div class="flex flex-col xl:flex-row gap-4">
                <!-- Main Search -->
                <div class="relative flex-1 group">
                    <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-text-muted group-focus-within:text-medical-blue transition-colors duration-300">search</span>
                    <input name="search" value="<?= htmlspecialchars($searchTerm) ?>"
                        class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-2xl pl-14 pr-6 py-4 text-sm focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue outline-none placeholder:text-[var(--text-muted)] text-[var(--text-main)] transition-all font-bold"
                        placeholder="Buscar por nombre, serie, ID o marca..." />
                </div>

                <div class="flex flex-wrap gap-4">
                    <!-- Status Filter -->
                    <div class="w-48 relative">
                        <select name="status" onchange="this.form.submit()"
                            class="w-full h-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-2xl px-6 py-4 text-xs font-bold uppercase tracking-widest outline-none appearance-none cursor-pointer text-[var(--text-main)] focus:border-medical-blue transition-all">
                            <option value="ALL">Todos los Estados</option>
                            <option value="<?= STATUS_OPERATIVE ?>" <?= $statusFilter === STATUS_OPERATIVE ? 'selected' : '' ?>>Operativos</option>
                            <option value="<?= STATUS_OPERATIVE_WITH_OBS ?>" <?= $statusFilter === STATUS_OPERATIVE_WITH_OBS ? 'selected' : '' ?>>Con Observaciones</option>
                            <option value="<?= STATUS_MAINTENANCE ?>" <?= $statusFilter === STATUS_MAINTENANCE ? 'selected' : '' ?>>En Mantención</option>
                            <option value="<?= STATUS_NO_OPERATIVE ?>" <?= $statusFilter === STATUS_NO_OPERATIVE ? 'selected' : '' ?>>Fuera de Servicio</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-text-muted/50 pointer-events-none">expand_more</span>
                    </div>

                    <!-- Toggle Filters Button -->
                    <button type="button" @click="showFilters = !showFilters"
                        :class="showFilters ? 'bg-medical-blue text-white shadow-medical-blue/20' : 'bg-medical-dark/50 text-text-muted'"
                        class="h-14 flex items-center gap-3 px-6 border border-border-color rounded-2xl transition-all duration-300 font-bold active:scale-95 shadow-lg">
                        <span class="material-symbols-outlined text-xl">tune</span>
                        <span class="text-xs uppercase tracking-widest">Filtros</span>
                    </button>

                    <button type="submit"
                        class="h-14 bg-medical-blue text-white px-10 rounded-2xl font-black uppercase tracking-[0.2em] text-xs hover:bg-medical-blue-hover transition-all shadow-xl shadow-medical-blue/20 active:scale-95">
                        Buscar
                    </button>
                </div>
            </div>

            <!-- Expandable Filters Section -->
            <div x-show="showFilters" x-collapse x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-6 border-t border-border-color/30 mt-6">
                    <!-- Location -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-xs">location_on</span> Servicio Clínico
                        </label>
                        <select name="location" onchange="this.form.submit()" class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-5 py-3.5 text-xs font-bold outline-none appearance-none text-[var(--text-main)] focus:border-medical-blue">
                            <option value="ALL">Cualquiera</option>
                            <?php foreach ($allLocationsCount as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $locationFilter === $loc['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['location']) ?> (<?= $loc['count'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Family/Class -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-xs">category</span> Clase de Activo
                        </label>
                        <select name="family" class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-5 py-3.5 text-xs font-bold outline-none appearance-none text-[var(--text-main)] focus:border-medical-blue">
                            <option value="ALL">Todas las Clases</option>
                            <?php foreach ($allCategories as $f): ?>
                                <option value="<?= htmlspecialchars($f) ?>" <?= $familyFilter === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Brand -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-xs">factory</span> Marca
                        </label>
                        <select name="brand" onchange="this.form.submit()" class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-5 py-3.5 text-xs font-bold outline-none appearance-none text-[var(--text-main)] focus:border-medical-blue">
                            <option value="ALL">Todas las Marcas</option>
                            <?php foreach ($allBrandsCount as $b): ?>
                                <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $brandFilter === $b['brand'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['brand']) ?> (<?= $b['count'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Criticality -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-text-muted px-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-xs">priority_high</span> Criticidad
                        </label>
                        <select name="criticality" class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-5 py-3.5 text-xs font-bold outline-none appearance-none text-[var(--text-main)] focus:border-medical-blue">
                            <option value="ALL">Todas</option>
                            <?php foreach ($allCriticalities as $crit): ?>
                                <option value="<?= htmlspecialchars($crit) ?>" <?= $criticalityFilter === $crit ? 'selected' : '' ?>><?= htmlspecialchars($crit) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reset Area -->
                    <div class="flex items-end">
                        <a href="?page=inventory" class="w-full h-12 flex items-center justify-center gap-2 text-red-500 bg-red-500/10 border border-red-500/20 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-300 font-bold text-xs uppercase tracking-widest">
                            <span class="material-symbols-outlined text-lg">close</span>
                            Limpiar Filtros
                        </a>
                    </div>
                </div>
            </div>
            <!-- Active Filter Chips -->
            <?php
            $activeChips = [];
            if ($statusFilter !== 'ALL') {
                $statusVal = $statusLabels[$statusFilter] ?? $statusFilter;
                $activeChips['status'] = ['label' => 'Estado', 'value' => $statusVal];
            }
            if ($locationFilter !== 'ALL') $activeChips['location'] = ['label' => 'Ubicación', 'value' => $locationFilter];
            if ($brandFilter !== 'ALL') $activeChips['brand'] = ['label' => 'Marca', 'value' => $brandFilter];
            if ($familyFilter !== 'ALL') $activeChips['family'] = ['label' => 'Clase', 'value' => $familyFilter];
            if ($criticalityFilter !== 'ALL') $activeChips['criticality'] = ['label' => 'Criticidad', 'value' => $criticalityFilter];
            ?>

            <?php if (!empty($activeChips)): ?>
                <div class="flex flex-wrap gap-2 pt-4 border-t border-border-color/10">
                    <span class="text-[9px] font-black text-text-muted uppercase tracking-widest flex items-center mr-2">Filtros Activos:</span>
                    <?php foreach ($activeChips as $key => $chip): ?>
                        <div class="flex items-center gap-2 px-3 py-1 bg-medical-blue/5 border border-medical-blue/20 rounded-full group hover:bg-medical-blue/10 transition-colors">
                            <span class="text-[10px] font-bold text-text-muted"><?= $chip['label'] ?>:</span>
                            <span class="text-[10px] font-black text-medical-blue"><?= htmlspecialchars($chip['value'] ?? '') ?></span>
                            <a href="<?= $buildUrl(1, [$key => 'ALL']) ?>" class="flex items-center justify-center w-4 h-4 rounded-full text-medical-blue/40 group-hover:text-red-500 hover:bg-red-50 transition-all">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <a href="?page=inventory" class="text-[9px] font-black text-red-500 uppercase tracking-widest hover:underline ml-auto flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">filter_alt_off</span> Limpiar Todo
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Inventory Table Container (for AJAX) -->
    <div id="inventory-container" class="space-y-8">
        <div class="card-glass overflow-hidden shadow-2xl relative">
            <div id="table-loader" class="absolute inset-0 bg-white/50 dark:bg-panel-dark/50 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-4 border-medical-blue border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-[10px] font-black text-medical-blue uppercase tracking-[0.2em]">Actualizando...</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-medical-dark/50 border-b border-border-color/30">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted">Activo Biomédico</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted">Identificación</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted text-center">Estado Técnico</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted">Ubicación</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted text-center">Vida Útil</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-text-muted text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-color/10">
                        <?php if (empty($filteredAssets)): ?>
                            <tr>
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <span class="material-symbols-outlined text-6xl text-text-muted/20 mb-4">search_off</span>
                                    <p class="text-text-muted font-bold tracking-widest uppercase text-xs">No se encontraron activos con estos criterios</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($filteredAssets as $asset): ?>
                            <tr class="hover:bg-medical-blue/5 transition-all duration-300 group">
                                <!-- Name & Brand -->
                                <td class="px-8 py-7">
                                    <div class="flex items-center gap-5">
                                        <div class="w-16 h-16 rounded-2xl border border-border-color/30 overflow-hidden bg-medical-dark group-hover:scale-105 transition-transform duration-500 shadow-sm relative">
                                            <img src="<?= $asset['image_url'] ?>" class="w-full h-full object-cover p-1 opacity-90" alt="<?= $asset['name'] ?>">
                                            <div class="absolute inset-0 ring-1 ring-inset ring-black/5 rounded-2xl"></div>
                                        </div>
                                        <div>
                                            <a href="?page=asset&id=<?= $asset['id'] ?>" class="font-black text-lg text-text-main hover:text-medical-blue transition-colors block leading-none mb-1.5">
                                                <?= highlight($asset['name'], $searchTerm) ?>
                                            </a>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black uppercase tracking-widest text-text-muted bg-text-muted/5 px-2 py-0.5 rounded"><?= highlight($asset['brand'], $searchTerm) ?></span>
                                                <span class="text-[10px] font-bold text-text-muted/50"><?= highlight($asset['model'], $searchTerm) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- ID, Serial & Criticality -->
                                <td class="px-6 py-7">
                                    <div class="space-y-2">
                                        <?php if (!empty($asset['hec_id'])): ?>
                                            <div class="flex items-center gap-2">
                                                <span class="px-1.5 py-0.5 rounded-md bg-medical-blue/10 text-[10px] font-black text-medical-blue uppercase tracking-widest shadow-sm border border-medical-blue/20" title="ID Propio HEC">HEC</span>
                                                <span class="font-mono text-sm font-black text-text-main"><?= highlight($asset['hec_id'], $searchTerm) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="grid grid-cols-1 gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] font-black text-text-muted/70 uppercase w-9">INV:</span>
                                                <span class="font-mono text-[10px] font-bold text-text-muted"><?= highlight($asset['inventory_id'] ?? '-', $searchTerm) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] font-black text-text-muted/70 uppercase w-9">SERIE:</span>
                                                <span class="font-mono text-[10px] font-bold text-text-muted"><?= highlight($asset['serial_number'] ?? 'S/N', $searchTerm) ?></span>
                                            </div>
                                        </div>

                                    </div>
                                </td>

                                <!-- Status & Criticality -->
                                <td class="px-6 py-7 text-center">
                                    <?php
                                    $statusConf = match ($asset['status']) {
                                        STATUS_OPERATIVE => ['label' => 'Operativo', 'class' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'],
                                        STATUS_MAINTENANCE => ['label' => 'Mantención', 'class' => 'bg-amber-500/10 text-amber-600 border-amber-500/20'],
                                        STATUS_NO_OPERATIVE => ['label' => 'Fuera de Servicio', 'class' => 'bg-red-500/10 text-red-600 border-red-500/20'],
                                        STATUS_OPERATIVE_WITH_OBS => ['label' => 'Observado', 'class' => 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20'],
                                        default => ['label' => $asset['status'], 'class' => 'bg-slate-500/10 text-slate-600 border-slate-500/20']
                                    };
                                    ?>
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border shadow-sm <?= $statusConf['class'] ?>">
                                            <?= $statusConf['label'] ?>
                                        </span>

                                        <?php
                                        $critValue = strtoupper($asset['criticality'] ?? 'NA');
                                        $displayCrit = in_array($critValue, ['LOW', 'NA', 'NO APLICA']) ? 'NA' : $critValue;

                                        $critClasses = match ($critValue) {
                                            'CRITICAL', 'CRITICO', 'CRÍTICO' => 'text-red-600 bg-red-50 border-red-200',
                                            'RELEVANT', 'RELEVANTE' => 'text-amber-600 bg-amber-50 border-amber-200',
                                            default => 'text-slate-500 bg-slate-50 border-slate-200'
                                        };
                                        ?>
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black border shadow-xs <?= $critClasses ?> uppercase">
                                            <?= $displayCrit ?>
                                        </span>

                                        <?php if (in_array($critValue, ['CRITICAL', 'CRITICO', 'CRÍTICO'])): ?>
                                            <span class="flex items-center gap-1 text-[8px] font-black text-red-500 uppercase tracking-widest animate-pulse">
                                                <span class="w-1 h-1 rounded-full bg-red-500"></span> Prioridad Crítica
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Location -->
                                <td class="px-6 py-7">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-text-main text-sm"><?= $asset['location'] ?: 'Cualquiera' ?></span>
                                        <span class="text-[10px] text-text-muted uppercase font-bold tracking-tight"><?= $asset['sub_location'] ?: 'Sin Especificar' ?></span>
                                    </div>
                                </td>

                                <!-- Life Cycle -->
                                <td class="px-6 py-7">
                                    <div class="w-32 mx-auto space-y-2">
                                        <div class="flex justify-between items-end">
                                            <span class="text-[9px] font-black text-text-muted uppercase">Vida Restante</span>
                                            <span class="text-xs font-black text-text-main"><?= max(0, $asset['useful_life_pct']) ?>%</span>
                                        </div>
                                        <div class="h-2 w-full bg-medical-dark rounded-full overflow-hidden border border-border-color/20 p-0.5 shadow-inner">
                                            <?php
                                            $barColor = $asset['useful_life_pct'] > 50 ? 'bg-emerald-500' : ($asset['useful_life_pct'] > 20 ? 'bg-amber-500' : 'bg-red-500 animate-pulse');
                                            ?>
                                            <div class="h-full rounded-full transition-all duration-1000 shadow-lg <?= $barColor ?>" style="width: <?= max(0, min(100, $asset['useful_life_pct'])) ?>%"></div>
                                        </div>
                                        <p class="text-[8px] font-bold text-text-muted/60 text-right uppercase"><?= $asset['years_remaining'] > 0 ? $asset['years_remaining'] . ' años restantes' : 'Vencido' ?></p>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-8 py-7 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-60 group-hover:opacity-100 transition-all duration-300">
                                        <a href="?page=asset&id=<?= $asset['id'] ?>"
                                            class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-2xl hover:bg-medical-blue hover:text-white transition-all shadow-lg active:scale-95" title="Ficha Técnica">
                                            <span class="material-symbols-outlined text-xl">visibility</span>
                                        </a>
                                        <?php if (canModify()): ?>
                                            <button onclick="confirmDeletion(event, '?page=inventory&delete_id=<?= $asset['id'] ?>')"
                                                class="p-2.5 bg-red-500/10 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-lg active:scale-95" title="Dar de baja">
                                                <span class="material-symbols-outlined text-xl">delete</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php
    $currentPage = $page;
    $totalItems = $totalAssetsCount;
    $itemsPerPage = $limit;
    $label = 'activos';
    include __DIR__ . '/../includes/components/pagination.php';
    ?>

    <!-- SWAL NOTIFICATIONS & MODALS -->
    <script>
        // Custom confirmation modal for deletion (replacing native confirm)
        function confirmDeletion(event, redirectUrl) {
            event.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Dar de baja el equipo?',
                    text: 'Esta acción enviará el equipo al histórico.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, dar de baja',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = redirectUrl;
                    }
                });
            } else {
                if (confirm('¿Confirma dar de baja este activo? Esta acción enviará el equipo al histórico.')) {
                    window.location.href = redirectUrl;
                }
            }
        }

        // Post-action notifications
        document.addEventListener('DOMContentLoaded', function() {
            // --- Live Search & dynamic filtering ---
            const filterForm = document.querySelector('form[method="GET"]');
            const searchInput = filterForm.querySelector('input[name="search"]');
            const container = document.getElementById('inventory-container');
            const loader = document.getElementById('table-loader');
            let searchTimeout;

            const updateTable = async () => {
                loader.classList.remove('opacity-0', 'pointer-events-none');
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                params.set('p', '1'); // Reset to page 1 on search

                try {
                    const response = await fetch(`?${params.toString()}&ajax=1`);
                    const html = await response.text();

                    // Create temporary div to parse HTML
                    const temp = document.createElement('div');
                    temp.innerHTML = html;

                    const newContent = temp.querySelector('#inventory-container').innerHTML;
                    container.innerHTML = newContent;

                    // Update URL without reload
                    window.history.pushState({}, '', `?${params.toString()}`);
                } catch (error) {
                    console.error('Error updating inventory:', error);
                } finally {
                    loader.classList.add('opacity-0', 'pointer-events-none');
                }
            };

            // Event listener for main search (with debounce)
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(updateTable, 400);
                });
            }

            // --- Existing popups ---
            <?php if (isset($_GET['action']) && $_GET['action'] === 'deleted'): ?>
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: 'El equipo ha sido dado de baja correctamente y movido al histórico.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#10b981'
                    });
                }
            <?php endif; ?>
        });
    </script>
</div>