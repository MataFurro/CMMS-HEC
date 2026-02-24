<?php
// pages/asset.php (Asset History)

require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

$id = $_GET['id'] ?? 'UNKNOWN';
$asset = getAssetById($id);

if (!$asset) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Activo no encontrado</h1></div>";
    return;
}

// Backend Providers
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/AuditProvider.php';

use function Backend\Providers\getAssetAuditHistory;

// Get all work orders and filter for this asset
$allWorkOrders = getAllWorkOrders();
$workOrders = array_filter($allWorkOrders, fn($wo) => ($wo['asset_id'] ?? '') === $id);

// Get dynamic observations, documents, and performance metrics
$observations = getAssetObservations($id);
$documents = getAssetDocuments($id);
$metrics = getAssetPerformanceMetrics($id);

require_once 'config.php';
require_once 'includes/audit_trail.php';

?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="?page=inventory"
                class="p-3 bg-medical-surface rounded-xl text-text-muted hover:text-text-main hover:bg-slate-200 dark:hover:bg-slate-700 border border-border-dark transition-all flex items-center justify-center">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-text-main tracking-tight flex items-center gap-3">
                    <?= $asset['name'] ?>
                    <span
                        class="text-lg text-text-muted font-mono bg-medical-surface px-2 py-1 rounded-lg border border-border-dark">
                        <?= $asset['id'] ?>
                    </span>
                </h1>
                <p class="text-text-muted text-sm font-bold uppercase tracking-wider mt-1">
                    <?= $asset['brand'] ?> <?= $asset['model'] ?>
                </p>
            </div>
        </div>
        <?php if (canModify()): ?>
            <div class="flex gap-3">
                <button
                    class="px-6 py-2 bg-medical-blue text-white rounded-xl font-bold hover:bg-medical-blue/90 shadow-lg shadow-medical-blue/20">
                    Editar Activo
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="card-glass p-2">
                <img src="<?= $asset['image_url'] ?>" class="w-full h-64 object-cover rounded-lg" alt="Asset Image">
            </div>

            <div class="card-glass p-6 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-text-muted border-b border-border-dark pb-2">
                    Estado Actual</h3>

                <?php
                // ── Mapa de Status ──────────────────────────────────────────
                $statusMap = [
                    'OPERATIVE'             => ['Operativo',           'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'],
                    'OPERATIVE_WITH_OBS'    => ['Op. con Obs.',        'bg-amber-500/10   text-amber-500   border-amber-500/20'],
                    'MAINTENANCE'           => ['En Mantención',       'bg-blue-500/10    text-blue-500    border-blue-500/20'],
                    'NO_OPERATIVE'          => ['Fuera de Servicio',   'bg-red-500/10     text-red-500     border-red-500/20'],
                ];
                [$stLabel, $stClass] = $statusMap[$asset['status']] ?? [$asset['status'], 'bg-slate-500/10 text-slate-400 border-slate-500/20'];

                // ── Mapa de Criticidad ──────────────────────────────────────
                $critMap = [
                    'CRITICAL' => ['Crítico',   'bg-red-500/10    text-red-500    border-red-500/20'],
                    'RELEVANT' => ['Relevante', 'bg-amber-500/10  text-amber-500  border-amber-500/20'],
                    'LOW'      => ['No Aplica', 'bg-slate-500/10  text-slate-400  border-slate-500/20'],
                ];
                [$crLabel, $crClass] = $critMap[$asset['criticality']] ?? [$asset['criticality'], 'bg-slate-500/10 text-slate-400 border-slate-500/20'];

                // ── Clasificación Monitoreo (on-the-fly si vacío) ───────────
                $rge = $asset['riesgo_ge'] ?? '';
                $officialClasses = ['MONITOREO', 'NO MONITOREO', 'APOYO ENDOSCÓPICO', 'ESTERILIZACIÓN', 'APOYO QUIRÚRGICO', 'APOYO TERAPÉUTICO', 'IMAGENOLOGÍA', 'LABORATORIO / FARMACIA', 'MOBILIARIO', 'ODONTOLOGÍA'];

                $rgeUpper = mb_strtoupper(trim($rge), 'UTF-8');
                $isOfficial = in_array($rgeUpper, $officialClasses);
                $isLegacy = in_array(mb_strtolower(trim($rge)), ['life support', 'high risk', 'general', 'standard', '']);

                if (!$isOfficial || $isLegacy) {
                    $detected = _detectarMonitoreo($asset['name']);
                    // Solo sobreescribir si el actual está vacío o es legacy
                    if ($isLegacy || empty($rge)) {
                        $rge = $detected;
                    }
                }
                $rgeIcon  = $rge === 'Monitoreo' ? '📡' : '🔧';
                $rgeClass = $rge === 'Monitoreo'
                    ? 'bg-blue-500/10 text-blue-400 border-blue-500/20'
                    : 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                ?>

                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-text-muted">Estado</span>
                    <span class="px-3 py-1 rounded-full border text-xs font-black uppercase <?= $stClass ?>">
                        <?= $stLabel ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-text-muted">Criticidad</span>
                    <span class="px-3 py-1 rounded-full border text-xs font-black uppercase <?= $crClass ?>">
                        <?= $crLabel ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-text-muted">Ubicación</span>
                    <span class="text-xs text-text-main font-bold"><?= $asset['location'] ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-text-muted">Serie</span>
                    <span class="text-xs text-text-main font-mono"><?= $asset['serial_number'] ?></span>
                </div>
                <!-- Campos Normativos Sidebar -->
                <div class="pt-2 border-t border-border-dark mt-2 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-text-muted">Clasificación</span>
                        <span class="px-2 py-0.5 rounded border text-[10px] font-black <?= $rgeClass ?>">
                            <?= $rgeIcon ?> <?= $rge ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-text-muted">Cód. UMDNS</span>
                        <span class="text-xs text-text-main font-mono"><?= $asset['codigo_umdns'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 bg-medical-blue/5 rounded-lg border border-medical-blue/10">
                        <span class="text-xs font-black text-medical-blue uppercase tracking-widest flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">timer</span>
                            Horas de Uso
                        </span>
                        <span class="text-sm text-text-main font-black"><?= number_format($asset['hours_used'] ?? 0) ?> h</span>
                    </div>
                </div>
            </div>

            <!-- Alertas de Tecnovigilancia -->
            <?php if (!empty($asset['recalls'])): ?>
                <div class="card-glass border-red-500/30 bg-red-500/5 p-6 animate-pulse">
                    <h3 class="text-xs font-black uppercase tracking-widest text-red-500 flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        Tecnovigilancia Activa
                    </h3>
                    <?php foreach ($asset['recalls'] as $recall): ?>
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-white"><?= $recall['id'] ?> - <?= $recall['agency'] ?? 'ISP' ?></p>
                            <p class="text-[10px] text-slate-400 leading-tight"><?= $recall['description'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabs Content -->
        <div class="lg:col-span-2 card-glass p-8">
            <!-- Tab Headers -->
            <div class="flex gap-2 mb-6 border-b border-border-dark overflow-x-auto">
                <button onclick="switchTab('ot')" id="tab-ot"
                    class="tab-button active px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">assignment</span>
                        Órdenes de Trabajo
                    </span>
                </button>
                <button onclick="switchTab('obs')" id="tab-obs"
                    class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">comment</span>
                        Observaciones
                    </span>
                </button>
                <button onclick="switchTab('docs')" id="tab-docs"
                    class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">folder</span>
                        Documentos
                    </span>
                </button>
                <button onclick="switchTab('cont')" id="tab-cont"
                    class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">account_balance</span>
                        Contabilidad
                    </span>
                </button>
                <button onclick="switchTab('audit')" id="tab-audit"
                    class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">receipt_long</span>
                        Auditoría
                    </span>
                </button>
            </div>

            <!-- Tab Content: Work Orders -->
            <div id="content-ot" class="tab-content">
                <h3 class="text-lg font-bold text-text-main mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">history</span>
                    Historial de Órdenes de Trabajo
                </h3>

                <!-- Filtros -->
                <div class="card-glass p-4 mb-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label
                                class="text-xs font-bold text-text-muted uppercase tracking-wider mb-2 block">Tipo</label>
                            <select id="filter-tipo"
                                class="w-full bg-medical-surface border border-border-dark rounded-lg px-3 py-2 text-sm text-text-main focus:outline-none focus:border-medical-blue">
                                <option value="">Todos</option>
                                <option value="Preventivo">Preventivo</option>
                                <option value="Correctivo">Correctivo</option>
                                <option value="Calibración">Calibración</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-text-muted uppercase tracking-wider mb-2 block">Estado</label>
                            <select id="filter-estado"
                                class="w-full bg-medical-surface border border-border-dark rounded-lg px-3 py-2 text-sm text-text-main focus:outline-none focus:border-medical-blue">
                                <option value="">Todos</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Terminada">Terminada</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-text-muted uppercase tracking-wider mb-2 block">Desde</label>
                            <input type="date" id="filter-desde"
                                class="w-full bg-medical-surface border border-border-dark rounded-lg px-3 py-2 text-sm text-text-main focus:outline-none focus:border-medical-blue">
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-text-muted uppercase tracking-wider mb-2 block">Hasta</label>
                            <input type="date" id="filter-hasta"
                                class="w-full bg-medical-surface border border-border-dark rounded-lg px-3 py-2 text-sm text-text-main focus:outline-none focus:border-medical-blue">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="applyFilters()"
                            class="px-4 py-2 bg-medical-blue text-white rounded-lg font-bold hover:bg-medical-blue/90 transition-all text-sm uppercase tracking-wider">
                            Filtrar
                        </button>
                        <button onclick="clearFilters()"
                            class="px-4 py-2 bg-medical-surface border border-border-dark text-text-muted rounded-lg font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all text-sm uppercase tracking-wider">
                            Limpiar
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" id="ot-table">
                        <thead>
                            <tr class="border-b border-border-dark">
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    ID</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    Tipo</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    Estado</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    Fecha</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    Técnico</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-text-muted">
                                    Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workOrders as $ot): ?>
                                <tr class="ot-row border-b border-border-dark hover:bg-slate-200 dark:hover:bg-slate-800/30 transition-colors"
                                    data-tipo="<?= $ot['type'] ?>" data-estado="<?= $ot['status'] ?>"
                                    data-fecha="<?= $ot['date'] ?>">
                                    <td class="py-4 px-4">
                                        <a href="?page=work_order_execution&id=<?= $ot['id'] ?>"
                                            class="text-medical-blue hover:text-medical-blue/80 font-bold text-sm">
                                            <?= $ot['id'] ?>
                                        </a>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="text-sm text-text-main"><?= $ot['type'] ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($ot['status'] === 'Terminada'): ?>
                                            <span
                                                class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                                                <?= $ot['status'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="px-2 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-bold">
                                                <?= $ot['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-text-muted"><?= $ot['date'] ?></td>
                                    <td class="py-4 px-4 text-sm text-text-main"><?= $ot['tech'] ?></td>
                                    <td class="py-4 px-4">
                                        <a href="?page=work_order_execution&id=<?= $ot['id'] ?>"
                                            class="text-xs font-bold text-medical-blue hover:text-medical-blue/80 uppercase tracking-wider">
                                            Ver Detalles →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Observations -->
            <div id="content-obs" class="tab-content hidden">
                <h3 class="text-lg font-bold text-text-main mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">comment</span>
                    Observaciones Técnicas
                </h3>

                <div class="relative pl-4 border-l-2 border-border-dark space-y-6">
                    <?php foreach ($observations as $obs): ?>
                        <div class="relative pl-6">
                            <?php if ($obs['type'] === 'critical'): ?>
                                <div
                                    class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-red-500 border-2 border-red-500 ring-4 ring-medical-dark">
                                </div>
                            <?php elseif ($obs['type'] === 'warning'): ?>
                                <div
                                    class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-amber-500 border-2 border-amber-500 ring-4 ring-medical-dark">
                                </div>
                            <?php else: ?>
                                <div
                                    class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-panel-dark border-2 border-medical-blue ring-4 ring-medical-dark">
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-text-muted">person</span>
                                    <span class="text-sm font-bold text-text-main"><?= $obs['author'] ?></span>
                                </div>
                                <span class="text-xs font-bold text-text-muted"><?= $obs['date'] ?></span>
                            </div>

                            <?php if ($obs['type'] === 'critical'): ?>
                                <div class="bg-danger/10 border border-danger/20 rounded-xl p-4">
                                    <p class="text-sm text-danger"><?= $obs['text'] ?></p>
                                </div>
                            <?php elseif ($obs['type'] === 'warning'): ?>
                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                                    <p class="text-sm text-amber-500"><?= $obs['text'] ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-text-main/80 text-sm"><?= $obs['text'] ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab Content: Documents -->
            <div id="content-docs" class="tab-content hidden">
                <h3 class="text-lg font-bold text-text-main mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">folder</span>
                    Documentos del Equipo
                </h3>

                <div class="space-y-3">
                    <?php foreach ($documents as $doc): ?>
                        <div
                            class="flex items-center justify-between p-4 bg-medical-surface rounded-xl hover:bg-medical-blue/5 transition-all border border-border-dark">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-lg bg-medical-blue/10 border border-medical-blue/20 flex items-center justify-center">
                                    <?php if ($doc['type'] === 'Foto'): ?>
                                        <span class="material-symbols-outlined text-medical-blue">image</span>
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-medical-blue">description</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-text-main"><?= $doc['name'] ?></p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-text-muted font-bold"><?= $doc['type'] ?></span>
                                        <span class="text-xs text-text-muted opacity-50">•</span>
                                        <span class="text-xs text-text-muted"><?= $doc['size'] ?></span>
                                        <span class="text-xs text-text-muted opacity-50">•</span>
                                        <span class="text-xs text-text-muted"><?= $doc['date'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="px-4 py-2 bg-medical-blue/10 text-medical-blue rounded-lg hover:bg-medical-blue/20 transition-all text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">download</span>
                                Descargar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab Content: Contabilidad -->
            <div id="content-cont" class="tab-content hidden">
                <h3 class="text-lg font-bold text-text-main mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">account_balance</span>
                    Información Contable
                </h3>

                <div class="space-y-6">
                    <!-- Adquisición -->
                    <div class="card-glass p-6">
                        <h4
                            class="text-xs font-black uppercase tracking-widest text-text-muted border-b border-border-dark pb-2 mb-4">
                            Adquisición</h4>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Año
                                    Compra</span>
                                <p class="text-xl font-bold text-text-main mt-1">
                                    <?= $asset['purchased_year'] ?? '-' ?>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Fecha
                                    Instalación</span>
                                <p class="text-xl font-bold text-emerald-500 mt-1">
                                    <?= $asset['fecha_instalacion'] ?? '-' ?>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Costo</span>
                                <p class="text-xl font-bold text-medical-blue mt-1">
                                    $<?= number_format($asset['acquisition_cost'] ?? 0, 0, ',', '.') ?> CLP</p>
                            </div>
                            <div class="lg:col-span-2">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Proveedor</span>
                                <p class="text-sm text-text-main mt-1"><?= $asset['vendor'] ?? '-' ?></p>
                            </div>
                            <div>
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Garantía</span>
                                <p class="text-xs font-bold text-amber-500 mt-1 uppercase">Vence:
                                    <?= $asset['warranty_expiration'] ?? '-' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Análisis de Vida Útil -->
                    <div class="card-glass p-6">
                        <h4
                            class="text-xs font-black uppercase tracking-widest text-text-muted border-b border-border-dark pb-2 mb-4">
                            Análisis de Vida Útil</h4>

                        <!-- Barra de Progreso -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-text-muted font-bold">Vida Útil Consumida</span>
                                    <?php if (($asset['useful_life_pct'] ?? 0) <= 0 && $asset['status'] === STATUS_OPERATIVE): ?>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-500 border border-amber-500/20 text-[9px] font-black uppercase tracking-widest animate-pulse">
                                            Audit Contable Requerido
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs <?= ($asset['useful_life_pct'] ?? 0) <= 0 ? 'text-red-500 font-black' : 'text-medical-blue font-bold' ?>">
                                    <?= ($asset['useful_life_pct'] ?? 0) <= 0 ? 'EXCEDIDA' : ($asset['useful_life_pct'] ?? 0) . '%' ?>
                                </span>
                            </div>
                            <div class="w-full bg-medical-surface border border-border-dark rounded-full h-3 overflow-hidden">
                                <?php
                                $barValue = ($asset['useful_life_pct'] ?? 0);
                                $barColor = 'from-medical-blue to-cyan-400';
                                if ($barValue <= 0) {
                                    $barColor = 'from-red-600 to-red-400';
                                    $barValue = 100; // Full red if exceeded
                                } elseif ($barValue <= 20) {
                                    $barColor = 'from-amber-600 to-amber-400';
                                }
                                ?>
                                <div class="bg-gradient-to-r <?= $barColor ?> h-3 rounded-full transition-all"
                                    style="width: <?= $barValue ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-xs text-text-muted">
                                    <?= ($asset['total_useful_life'] ?? 0) - ($asset['years_remaining'] ?? 0) ?> años consumidos
                                </span>
                                <span class="text-xs <?= ($asset['years_remaining'] ?? 0) < 0 ? 'text-red-500 font-bold' : 'text-text-muted' ?>">
                                    <?= ($asset['years_remaining'] ?? 0) < 0 ? abs($asset['years_remaining']) . ' años en exceso' : ($asset['years_remaining'] ?? 0) . ' años restantes' ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Vida Útil
                                    Total</span>
                                <p class="text-xl font-bold text-text-main mt-1"><?= $asset['total_useful_life'] ?? '-' ?>
                                    años</p>
                            </div>
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Depreciación
                                    Anual (Meta)</span>
                                <p class="text-xl font-bold text-text-main mt-1">
                                    $<?= number_format($metrics['depreciacion_anual'] ?? 0, 0, ',', '.') ?> CLP
                                </p>
                            </div>
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Uptime
                                    Clínico</span>
                                <p class="text-xl font-bold text-emerald-500 mt-1">
                                    <?= $metrics['uptime'] ?? UPTIME_GOAL ?>%
                                </p>
                            </div>
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Mantenimiento
                                    planificado</span>
                                <p class="text-xl font-bold text-medical-blue mt-1">
                                    <?= $asset['under_maintenance_plan'] ? 'Sí' : 'No' ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-medical-blue/10 border border-medical-blue/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-medical-blue text-sm">info</span>
                                <span class="text-xs font-bold text-medical-blue uppercase tracking-wider">Valor
                                    Residual Estimado</span>
                            </div>
                            <p class="text-lg font-bold text-text-main">
                                $<?= number_format($metrics['valor_residual'] ?? 0, 0, ',', '.') ?> CLP</p>
                        </div>
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-red-500 text-sm">event_busy</span>
                                <span class="text-xs font-bold text-red-500 uppercase tracking-wider">Fin Vida Útil
                                    Técnica</span>
                            </div>
                            <p class="text-lg font-bold text-text-main"><?= $asset['vencimiento_vida_util'] ?? '-' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Métricas de Mantenimiento (Simuladas) -->
                    <div class="card-glass p-6">
                        <h4
                            class="text-xs font-black uppercase tracking-widest text-text-muted border-b border-border-dark pb-2 mb-4">
                            Métricas de Mantenimiento</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Plan
                                    Activo</span>
                                <p class="text-xl font-bold text-text-main mt-1">
                                    <?= $asset['under_maintenance_plan'] ? 'Sí' : 'No' ?>
                                </p>
                            </div>
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Costo
                                    Estimado</span>
                                <p class="text-xl font-bold text-text-main mt-1">
                                    $<?= number_format($metrics['costo_mtto_estimado'] ?? 0, 0, ',', '.') ?> CLP</p>
                            </div>
                            <div class="bg-medical-surface border border-border-dark p-4 rounded-lg">
                                <span class="text-xs text-text-muted font-bold uppercase tracking-wider">Uptime</span>
                                <p class="text-xl font-bold text-emerald-500 mt-1">
                                    <?= $metrics['uptime'] ?? UPTIME_GOAL ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Audit -->
            <div id="content-audit" class="tab-content hidden">
                <h3 class="text-lg font-bold text-text-main mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">receipt_long</span>
                    Historial de Auditoría de Procesos
                </h3>

                <?php
                $auditLogs = getAssetAuditHistory($id);
                if (empty($auditLogs)):
                ?>
                    <div class="text-center py-12 card-glass bg-medical-surface/20">
                        <span class="material-symbols-outlined text-4xl text-text-muted mb-2">history</span>
                        <p class="text-text-muted font-bold">No hay registros de auditoría para este equipo.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($auditLogs as $log):
                            $details = json_decode($log['details'], true);
                            $isSystem = isset($details['agentic_reasoning']) || (isset($log['action']) && strpos($log['action'], 'AUTO') !== false);
                        ?>
                            <div class="p-4 rounded-xl border <?= $isSystem ? 'border-medical-blue/30 bg-medical-blue/5' : 'border-border-dark bg-medical-surface' ?> transition-all">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex gap-3">
                                        <div class="w-8 h-8 rounded-lg <?= $isSystem ? 'bg-medical-blue/20 text-medical-blue' : 'bg-medical-surface text-text-muted' ?> flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg"><?= $isSystem ? 'settings_suggest' : 'person' ?></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-text-main"><?= $log['action'] ?></p>
                                            <p class="text-[10px] text-text-muted font-bold uppercase tracking-wider">
                                                <?= $log['user_name'] ?? 'Usuario Desconocido' ?> • <?= date('d/m/Y H:i', strtotime($log['timestamp'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($isSystem): ?>
                                        <span class="px-2 py-0.5 rounded bg-medical-blue/10 text-medical-blue text-[9px] font-black uppercase tracking-widest border border-medical-blue/20">
                                            Sistema
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isSystem && isset($details['agentic_reasoning'])): ?>
                                    <div class="mt-3 pl-11 border-l-2 border-medical-blue/20">
                                        <p class="text-xs text-text-muted italic">"<?= $details['agentic_reasoning'] ?>"</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'text-medical-blue', 'border-b-2', 'border-medical-blue');
            btn.classList.add('text-text-muted');
        });

        // Show selected tab content
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Add active class to selected button
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('active', 'text-medical-blue', 'border-b-2', 'border-medical-blue');
        activeBtn.classList.remove('text-text-muted');
    }

    function applyFilters() {
        const tipo = document.getElementById('filter-tipo').value;
        const estado = document.getElementById('filter-estado').value;
        const desde = document.getElementById('filter-desde').value;
        const hasta = document.getElementById('filter-hasta').value;

        const rows = document.querySelectorAll('.ot-row');

        rows.forEach(row => {
            let show = true;

            // Filtro por tipo
            if (tipo && row.dataset.tipo !== tipo) {
                show = false;
            }

            // Filtro por estado
            if (estado && row.dataset.estado !== estado) {
                show = false;
            }

            // Filtro por fecha desde
            if (desde && row.dataset.fecha < desde) {
                show = false;
            }

            // Filtro por fecha hasta
            if (hasta && row.dataset.fecha > hasta) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    function clearFilters() {
        document.getElementById('filter-tipo').value = '';
        document.getElementById('filter-estado').value = '';
        document.getElementById('filter-desde').value = '';
        document.getElementById('filter-hasta').value = '';

        document.querySelectorAll('.ot-row').forEach(row => {
            row.style.display = '';
        });
    }
</script>

<style>
    .tab-button {
        position: relative;
        color: var(--text-muted);
    }

    .tab-button.active {
        color: var(--medical-blue);
    }

    .tab-button:hover {
        color: var(--medical-blue);
    }
</style>