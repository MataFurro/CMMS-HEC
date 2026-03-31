<?php
// pages/work_orders.php

// ── Backend Provider ──
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

// --- FILTERING & PAGINATION LOGIC ---
$tipoFilter = $_GET['tipo'] ?? '';
$estadoFilter = $_GET['estado'] ?? '';
$desdeFilter = $_GET['desde'] ?? '';
$hastaFilter = $_GET['hasta'] ?? '';

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 25;
$offset = ($page - 1) * $limit;

$activeFilters = [
    'type' => $tipoFilter,
    'status' => $estadoFilter,
    'date_from' => $desdeFilter,
    'date_to' => $hastaFilter
];

$totalOrdersCount = countTotalWorkOrders($activeFilters);
$totalPages = ceil($totalOrdersCount / $limit);
$orders = getWorkOrdersPaginated($limit, $offset, $activeFilters);
$stats = getWorkOrderStats();

// --- HELPER: URL BUILDER ---
$filterParams = [
    'page' => 'work_orders',
    'tipo' => $tipoFilter,
    'estado' => $estadoFilter,
    'desde' => $desdeFilter,
    'hasta' => $hastaFilter
];
$buildUrl = function ($p, $overrides = []) use ($filterParams) {
    if ($p === null) $p = (isset($_GET['p']) ? (int)$_GET['p'] : 1);
    $params = array_merge($filterParams, $overrides);
    $params['p'] = $p;
    return '?' . http_build_query($params);
};
?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <?php
    $headerActions = '';
    if (canModify()) {
        $headerActions = '
            <a href="?page=work_order_opening"
                class="group h-12 px-8 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-medical-blue/90 flex items-center gap-3 transition-all shadow-xl shadow-medical-blue/20 active:scale-95">
                <span class="material-symbols-outlined text-xl transition-transform group-hover:rotate-12">add_circle</span>
                Generar Nueva Orden
            </a>';
    }

    $preTitle = 'Operaciones';
    $title = 'Órdenes de Trabajo';
    $subTitle = 'Gestión de Mantenimiento';
    $icon = 'task_alt';
    $description = 'Seguimiento y ejecución de intervenciones técnicas en tiempo real.';
    $actions = $headerActions;
    include __DIR__ . '/../includes/components/header_master.php';
    ?>

    <!-- Filtros Rediseñados -->
    <div class="card-glass p-6 mb-8 border border-[var(--border-color)]/30">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
            <div class="md:col-span-3">
                <label class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase tracking-[0.15em] mb-3">
                    <span class="material-symbols-outlined text-sm text-medical-blue">category</span>
                    Tipo de Orden
                </label>
                <div class="relative group">
                    <select id="filter-tipo"
                        class="w-full bg-[var(--input-bg)] border-[var(--border-color)] rounded-xl px-4 py-2.5 text-xs text-[var(--text-main)] focus:outline-none focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue font-bold transition-all appearance-none cursor-pointer">
                        <option value="">Todos los Tipos</option>
                        <option value="Preventiva" <?= $tipoFilter === 'Preventiva' ? 'selected' : '' ?>>Preventiva</option>
                        <option value="Correctiva" <?= $tipoFilter === 'Correctiva' ? 'selected' : '' ?>>Correctiva</option>
                        <option value="Calibración" <?= $tipoFilter === 'Calibración' ? 'selected' : '' ?>>Calibración</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-text-muted/50 group-focus-within:text-medical-blue pointer-events-none transition-colors">expand_more</span>
                </div>
            </div>

            <div class="md:col-span-3">
                <label class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase tracking-[0.15em] mb-3">
                    <span class="material-symbols-outlined text-sm text-amber-500">rule</span>
                    Estado Actual
                </label>
                <div class="relative group">
                    <select id="filter-estado"
                        class="w-full bg-[var(--input-bg)] border-[var(--border-color)] rounded-xl px-4 py-2.5 text-xs text-[var(--text-main)] focus:outline-none focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue font-bold transition-all appearance-none cursor-pointer">
                        <option value="">Todos los Estados</option>
                        <option value="En Curso" <?= $estadoFilter === 'En Curso' ? 'selected' : '' ?>>En Curso</option>
                        <option value="Terminada" <?= $estadoFilter === 'Terminada' ? 'selected' : '' ?>>Terminada</option>
                        <option value="En Espera" <?= $estadoFilter === 'En Espera' ? 'selected' : '' ?>>En Espera</option>
                        <option value="Cancelada" <?= $estadoFilter === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-text-muted/50 group-focus-within:text-medical-blue pointer-events-none transition-colors">expand_more</span>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase tracking-[0.15em] mb-3">
                    <span class="material-symbols-outlined text-sm text-medical-blue">calendar_today</span>
                    Desde
                </label>
                <input type="date" id="filter-desde" value="<?= htmlspecialchars($desdeFilter) ?>"
                    class="w-full bg-[var(--input-bg)] border-[var(--border-color)] rounded-xl px-4 py-2.5 text-xs text-[var(--text-main)] focus:outline-none focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue font-bold transition-all">
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase tracking-[0.15em] mb-3">
                    <span class="material-symbols-outlined text-sm text-medical-blue">event_repeat</span>
                    Hasta
                </label>
                <input type="date" id="filter-hasta" value="<?= htmlspecialchars($hastaFilter) ?>"
                    class="w-full bg-[var(--input-bg)] border-[var(--border-color)] rounded-xl px-4 py-2.5 text-xs text-[var(--text-main)] focus:outline-none focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue font-bold transition-all">
            </div>

            <div class="md:col-span-2 flex gap-2 h-[41px]">
                <button onclick="applyFilters()"
                    class="flex-1 bg-medical-blue text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-medical-blue/90 transition-all shadow-lg shadow-medical-blue/20 active:scale-95">
                    FILTRAR
                </button>
                <button onclick="clearFilters()"
                    class="p-2 bg-panel-dark text-text-muted rounded-xl hover:text-red-500 transition-all border border-[var(--border-color)] group shadow-lg"
                    title="Limpiar Filtros">
                    <span class="material-symbols-outlined text-xl group-hover:rotate-90 transition-transform">filter_alt_off</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Row con Diseño Premium -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $label = 'En Curso';
        $value = str_pad($stats['En Curso'] ?? 0, 2, '0', STR_PAD_LEFT);
        $subValue = 'unids';
        $icon = 'pending_actions';
        $colorClass = 'blue-500';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'En Espera';
        $value = str_pad($stats['En Espera'] ?? 0, 2, '0', STR_PAD_LEFT);
        $subValue = 'unids';
        $icon = 'engineering';
        $colorClass = 'amber-500';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'Terminadas';
        $value = str_pad($stats['Terminada'] ?? 0, 2, '0', STR_PAD_LEFT);
        $subValue = 'unids';
        $icon = 'check_circle';
        $colorClass = 'emerald-500';
        include __DIR__ . '/../includes/components/metric_card.php';

        $label = 'Críticas Hoy';
        $value = str_pad($stats['CRITICAL_TODAY'] ?? 0, 2, '0', STR_PAD_LEFT);
        $subValue = 'unids';
        $icon = 'emergency_home';
        $colorClass = 'red-500';
        include __DIR__ . '/../includes/components/metric_card.php';
        ?>
    </div>

    <script>
        function applyFilters() {
            const tipo = document.getElementById('filter-tipo').value;
            const estado = document.getElementById('filter-estado').value;
            const desde = document.getElementById('filter-desde').value;
            const hasta = document.getElementById('filter-hasta').value;

            const params = new URLSearchParams(window.location.search);
            if (tipo) params.set('tipo', tipo);
            else params.delete('tipo');
            if (estado) params.set('estado', estado);
            else params.delete('estado');
            if (desde) params.set('desde', desde);
            else params.delete('desde');
            if (hasta) params.set('hasta', hasta);
            else params.delete('hasta');
            params.set('p', 1); // Reset to first page

            window.location.href = '?' + params.toString();
        }

        function clearFilters() {
            window.location.href = '?page=work_orders';
        }
    </script>

    <!-- Table -->
    <div class="card-glass overflow-hidden shadow-xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-medical-surface border-b-2 border-[var(--border-color)]">
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] w-40">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">fingerprint</span>
                            ID Orden
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] w-32">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">event</span>
                            Fecha
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] min-w-[200px]">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                            Activo
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] w-32">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">category</span>
                            Tipo
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] text-center w-36">
                        <div class="flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">priority_high</span>
                            Prioridad
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] text-center w-36">
                        <div class="flex items-center justify-center gap-2">
                            Estado
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] min-w-[180px]">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">engineering</span>
                            Técnico
                        </div>
                    </th>
                    <th class="px-4 py-4 text-xs font-black uppercase tracking-wider text-[var(--text-muted)] text-right">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-dark/30">
                <?php foreach ($orders as $ot): ?>
                    <tr class="ot-row hover:bg-medical-blue/5 transition-colors group" data-tipo="<?= $ot['type'] ?>"
                        data-estado="<?= $ot['status'] ?>" data-fecha="<?= $ot['date'] ?>">
                        <td class="px-4 py-4 font-mono text-sm text-medical-blue font-bold"><?= $ot['id'] ?></td>
                        <td class="px-4 py-4 text-xs font-bold text-text-muted whitespace-nowrap"><?= date('d/m/Y', strtotime($ot['date'])) ?></td>
                        <td class="px-4 py-4 text-sm font-bold text-text-main"><?= $ot['asset'] ?></td>
                        <td class="px-4 py-4 text-xs font-bold text-text-muted uppercase"><?= $ot['type'] ?></td>
                        <td class="px-4 py-4 text-center">
                            <?php
                            $prioClass = match ($ot['priority']) {
                                'Alta' => 'text-danger bg-danger/10 border-danger/20',
                                'Media' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
                                default => 'text-text-muted bg-panel-dark/50 border-border-dark/50'
                            };
                            ?>
                            <span class="px-2 py-1 rounded text-[10px] font-black uppercase border whitespace-nowrap <?= $prioClass ?>">
                                <?= $ot['priority'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <?php
                            $statusClass = match ($ot['status']) {
                                'Terminada' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                                'En Curso' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                                'En Espera' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
                                'Cancelada' => 'text-red-500 bg-red-500/10 border-red-500/20',
                                default => 'text-text-muted bg-panel-dark/50 border-border-dark/50'
                            };
                            ?>
                            <span
                                class="px-3 py-1 rounded-full text-[10px] font-black uppercase border whitespace-nowrap <?= $statusClass ?>">
                                <?= $ot['status'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full bg-medical-surface flex items-center justify-center border border-[var(--border-color)] group-hover:bg-medical-blue/20 transition-colors">
                                    <span class="material-symbols-outlined text-text-muted text-sm">person</span>
                                </div>
                                <span class="text-xs text-text-main font-bold truncate max-w-[120px]"><?= $ot['tech'] ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="?page=work_order_execution&id=<?= $ot['id'] ?>"
                                    class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl hover:bg-medical-blue hover:text-white transition-all border border-medical-blue/20 group/btn shadow-sm text-center"
                                    title="Ver Orden Técnica">
                                    <span class="material-symbols-outlined text-xl group-hover/btn:scale-110 transition-transform">visibility</span>
                                </a>
                                <?php if ($ot['status'] !== 'Terminada' && canExecuteWorkOrder()): ?>
                                    <a href="?page=work_order_execution&id=<?= $ot['id'] ?>&action=complete"
                                        class="px-5 py-2.5 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-xl text-[10px] font-black uppercase hover:bg-emerald-500 hover:text-white transition-all shadow-lg shadow-emerald-500/5 active:scale-95">
                                        EJECUTAR
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