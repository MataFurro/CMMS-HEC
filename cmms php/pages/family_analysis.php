<?php
// pages/family_analysis.php
// Clasificación de equipos: Monitoreo / No Monitoreo
// Criticidad: Crítico / Relevante / No Aplica

// Force cache refresh handled by index.php
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Services/CatalogService.php';

// Datos agrupados por clasificación (campo riesgo_ge)
$clasesData    = getAssetsByClase();
$riesgoData    = getAssetsByRiesgoBiomedico();
$paretoData    = getDowntimeParetoData();
$selectedClase = $_GET['clase_filter'] ?? '';
$allClases     = array_column($clasesData, 'clase');

// KPIs globales iniciales
$totalAssets     = array_sum(array_column($clasesData, 'total'));
$totalOperativos = array_sum(array_column($clasesData, 'operativos'));
$totalCriticos   = array_sum(array_column($clasesData, 'criticos'));
$totalValor      = array_sum(array_column($clasesData, 'valor_total'));
$totalObsoletos  = array_sum(array_column($clasesData, 'obsoletos'));
$disponibilidad  = $totalAssets > 0 ? round(($totalOperativos / $totalAssets) * 100, 1) : 0;

// Configuración visual - PALETA DE ALTO CONTRASTE
$critLabel  = ['CRITICAL' => 'Crítico',   'RELEVANT' => 'Relevante', 'LOW' => 'No Aplica'];
$critBadge  = [
    'CRITICAL' => 'bg-red-500/10 text-red-500 border border-red-500/20',
    'RELEVANT' => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
    'CRITICAL' => 'bg-red-500/10 text-red-500 border border-red-500/20',
    'RELEVANT' => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
    'LOW'      => 'bg-[var(--border-color)]/20 text-[var(--text-muted)] border border-[var(--border-color)]/30'
];

$claseColors = [
    'Monitoreo'       => ['hex' => '#10b981', 'badge' => 'bg-emerald-600/10 text-emerald-500',   'icon' => 'monitor_heart'],
    'No Monitoreo'    => ['hex' => '#0ea5e9', 'badge' => 'bg-sky-600/10 text-sky-500', 'icon' => 'build'],
    'Sin Clasificar'  => ['hex' => 'var(--text-muted)', 'badge' => 'bg-panel-dark text-[var(--text-muted)]', 'icon' => 'device_unknown'],
];
$defaultColor = ['hex' => 'var(--text-muted)', 'badge' => 'bg-panel-dark text-[var(--text-muted)]', 'icon' => 'devices'];

$claseLabels = json_encode(array_column($clasesData, 'clase'));
$claseHexes  = json_encode(array_map(fn($c) => ($claseColors[$c['clase']] ?? $defaultColor)['hex'], $clasesData));

// Datos para Doughnut (Ahora por CRITICIDAD)
$critChartData = [
    'labels' => ['Crítico', 'Relevante', 'No Aplica'],
    'hex'    => ['#dc2626', '#d97706', '#2563eb'] // Rojo, Ámbar, Azul (Cambiado de gris)
];
?>

<div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold text-[var(--text-main)] tracking-tight">Análisis de Gestión Clínica</h1>
            <p class="text-sm text-[var(--text-muted)] mt-1 uppercase tracking-widest font-black flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-medical-blue animate-pulse"></span>
                Auditoría de Criticidad y Distribución Patrimonial
            </p>
        </div>
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted/60 group-focus-within:text-medical-blue transition-colors">search</span>
                <input type="text" id="familySearch" placeholder="Buscar familia o equipo..."
                    class="h-12 pl-12 pr-4 bg-[var(--input-bg)] border-2 border-[var(--border-color)] text-[var(--text-main)] rounded-2xl text-sm font-bold focus:ring-4 focus:ring-medical-blue/20 focus:border-medical-blue outline-none hover:border-medical-blue/30 transition-all w-64">
            </div>
            <button id="clearAllFilters" class="hidden h-12 px-6 bg-red-600/10 text-red-500 border border-red-500/30 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-red-600/20 transition-all flex items-center gap-2 group">
                <span class="material-symbols-outlined text-sm group-hover:rotate-180 transition-transform duration-500">filter_alt_off</span>
                Limpiar
            </button>
            <select id="claseSelector"
                class="h-12 px-5 bg-[var(--input-bg)] border-2 border-[var(--border-color)] text-[var(--text-main)] rounded-2xl text-sm font-black focus:ring-4 focus:ring-medical-blue/20 focus:border-medical-blue outline-none cursor-pointer hover:border-medical-blue/30 transition-all">
                <option value="">Todas las Especialidades</option>
                <?php foreach ($allClases as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $selectedClase === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        <div class="card-glass p-6 border-l-4 border-l-medical-blue">
            <div class="flex justify-between items-start">
                <span class="material-symbols-outlined text-medical-blue text-2xl">devices</span>
                <span class="text-[9px] font-black text-medical-blue bg-medical-blue/10 px-2 py-0.5 rounded-full uppercase">Padrón</span>
            </div>
            <p class="stat-label mt-4 text-[var(--text-muted)] font-bold uppercase tracking-tighter text-[10px]">Equipos Totales</p>
            <h3 class="stat-value text-3xl font-black text-[var(--text-main)] mt-1" id="kpi-total"><?= $totalAssets ?></h3>
        </div>

        <div class="card-glass p-6 border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-start">
                <span class="material-symbols-outlined text-emerald-500 text-2xl">check_circle</span>
                <span class="text-[9px] font-black text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-full uppercase" id="kpi-disp"><?= $disponibilidad ?>%</span>
            </div>
            <p class="stat-label mt-4 text-[var(--text-muted)] font-bold uppercase tracking-tighter text-[10px]">Disponibilidad</p>
            <h3 class="stat-value text-3xl font-black text-[var(--text-main)] mt-1" id="kpi-operativos"><?= $totalOperativos ?></h3>
        </div>

        <div class="card-glass p-6 border-l-4 border-l-red-600">
            <div class="flex justify-between items-start">
                <span class="material-symbols-outlined text-red-600 text-2xl">warning</span>
                <span class="text-[9px] font-black text-red-600 bg-red-600/10 px-2 py-0.5 rounded-full uppercase">Críticos</span>
            </div>
            <p class="stat-label mt-4 text-[var(--text-muted)] font-bold uppercase tracking-tighter text-[10px]">Criticidad Vital</p>
            <h3 class="stat-value text-3xl font-black text-[var(--text-main)] mt-1" id="kpi-criticos"><?= $totalCriticos ?></h3>
        </div>

        <div class="card-glass p-6 border-l-4 border-l-amber-600">
            <div class="flex justify-between items-start">
                <span class="material-symbols-outlined text-amber-600 text-2xl">history_toggle_off</span>
                <span class="text-[9px] font-black text-amber-600 bg-amber-600/10 px-2 py-0.5 rounded-full uppercase">Inactivos</span>
            </div>
            <p class="stat-label mt-4 text-[var(--text-muted)] font-bold uppercase tracking-tighter text-[10px]">Fuera de Servicio</p>
            <h3 class="stat-value text-3xl font-black text-[var(--text-main)] mt-1" id="kpi-obsoletos"><?= $totalObsoletos ?></h3>
        </div>

        <div class="card-glass p-6 border-l-4 border-l-indigo-600">
            <div class="flex justify-between items-start">
                <span class="material-symbols-outlined text-indigo-600 text-2xl">payments</span>
                <span class="text-[9px] font-black text-indigo-600 bg-indigo-600/10 px-2 py-0.5 rounded-full uppercase">CLP</span>
            </div>
            <p class="stat-label mt-4 text-[var(--text-muted)] font-bold uppercase tracking-tighter text-[10px]">Valor Conservación</p>
            <h3 class="stat-value text-xl font-black text-[var(--text-main)] mt-1 overflow-hidden truncate" id="kpi-valor">$<?= number_format($totalValor, 0, ',', '.') ?></h3>
        </div>
    </div>

    <!-- Charts Container -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Chart 1: Investment by Specialty -->
        <div class="lg:col-span-7 card-glass p-8 relative overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="w-1 h-6 bg-medical-blue rounded"></span>
                        Inversión Patrimonial por Especialidad
                    </h3>
                    <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-wider mt-1">Distribución de activos por área clínica</p>
                </div>
                <div class="p-2 bg-panel-dark/50 border border-[var(--border-color)]">
                    <span class="material-symbols-outlined text-medical-blue text-sm">bar_chart</span>
                </div>
            </div>
            <div class="relative h-[360px]"><canvas id="claseChart"></canvas></div>
        </div>

        <!-- Chart 2: Criticality Distribution -->
        <div class="lg:col-span-5 card-glass p-8 relative overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="w-1 h-6 bg-red-600 rounded"></span>
                        Distribución por Criticidad
                    </h3>
                    <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-wider mt-1">Impacto asistencial y prioridad de atención</p>
                </div>
                <div class="p-2 bg-panel-dark/50 border border-[var(--border-color)]">
                    <span class="material-symbols-outlined text-red-600 text-sm">donut_small</span>
                </div>
            </div>
            <div class="relative h-[360px]"><canvas id="critChart"></canvas></div>
        </div>
    </div>

    <!-- Pareto Analysis -->
    <div class="card-glass p-10 relative overflow-hidden border-t-2 border-t-red-600/20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 relative z-10">
            <div>
                <h2 class="text-2xl font-black text-[var(--text-main)] tracking-tighter flex items-center gap-3 uppercase">
                    <span class="p-2 bg-red-600 text-white rounded-xl shadow-lg shadow-red-600/20">
                        <span class="material-symbols-outlined text-2xl">analytics</span>
                    </span>
                    Pareto de Inactividad (80/20)
                </h2>
                <p class="text-xs text-[var(--text-muted)] mt-2 font-bold uppercase tracking-[0.2em] max-w-2xl leading-relaxed">
                    Identificación de las familias de equipos que acumulan el 80% de la indisponibilidad total. El objetivo es focalizar los planes preventivos en estos cuellos de botella críticos.
                </p>
            </div>
            <div class="hidden lg:block">
                <div class="px-5 py-3 bg-red-600/10 border border-red-600/20 rounded-2xl flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-[9px] font-black text-red-600 uppercase tracking-widest">Foco Sugerido</p>
                        <p class="text-xs font-black text-[var(--text-main)]">Optimización PM</p>
                    </div>
                    <span class="material-symbols-outlined text-red-600 text-3xl">lightbulb</span>
                </div>
            </div>
        </div>
        <div class="relative h-[450px] z-10">
            <canvas id="paretoChart"></canvas>
        </div>
    </div>

    <!-- Assets List -->
    <div class="space-y-6" id="assetsContainer">
        <!-- Generado por JS -->
    </div>
</div>

<template id="groupTemplate">
    <div class="card-glass overflow-hidden transition-all duration-500 group">
        <div class="p-6 cursor-pointer flex items-center justify-between hover:bg-medical-blue/5 transition-colors" onclick="toggleGroup(this)">
            <div class="flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-medical-blue/10 flex items-center justify-center text-medical-blue shadow-inner group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl group-icon">devices</span>
                </div>
                <div>
                    <h4 class="text-xl font-black text-[var(--text-main)] group-title">Especialidad</h4>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-[var(--text-muted)] text-[10px] font-black rounded uppercase tracking-wider group-badge">Patrimonio Activo</span>
                        <span class="text-[10px] text-slate-400 font-bold group-count">0 Equipos</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-8">
                <div class="text-right hidden sm:block">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Inversión Estimada</p>
                    <p class="text-lg font-black text-[var(--text-main)] group-investment">$0</p>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:text-medical-blue transition-colors chevron">expand_more</span>
            </div>
        </div>
        <div class="hidden border-t border-slate-100 dark:border-slate-800 group-content">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-panel-dark border-b border-[var(--border-color)]">
                            <th class="px-6 py-4 text-[10px] font-black text-text-muted/60 uppercase tracking-widest">Identificador</th>
                            <th class="px-6 py-4 text-[10px] font-black text-text-muted/60 uppercase tracking-widest">Marca / Modelo</th>
                            <th class="px-6 py-4 text-[10px] font-black text-text-muted/60 uppercase tracking-widest text-center">Status Operativo</th>
                            <th class="px-6 py-4 text-[10px] font-black text-text-muted/60 uppercase tracking-widest text-center">Criticidad</th>
                            <th class="px-6 py-4 text-[10px] font-black text-text-muted/60 uppercase tracking-widest text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 group-table-body">
                        <!-- Filas -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style>
    .card-glass {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
    }

    .dark .card-glass {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const RAW_DATA = <?= json_encode($clasesData) ?>;
    const CRIT_MAP = {
        'CRITICAL': 'Crítico',
        'RELEVANT': 'Relevante',
        'LOW': 'No Aplica'
    };

    let currentClassFilter = '<?= $selectedClase ?>';
    let currentCritFilter = '';
    let currentSearchFilter = '';

    // Chart Handles
    let claseChart, critChart, paretoChart;

    window.addEventListener('load', () => {
        initDashboard();
    });

    function getThemeTextColor() {
        return document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569';
    }

    function initDashboard() {
        if (typeof Chart === 'undefined') return;

        // Configuración Global de Chart.js
        Chart.defaults.color = getThemeTextColor();
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.weight = 'bold';

        initCharts();
        updateDashboard();

        // Listeners
        document.getElementById('familySearch').addEventListener('input', (e) => {
            currentSearchFilter = e.target.value.toLowerCase().trim();
            updateDashboard();
        });

        document.getElementById('claseSelector').addEventListener('change', (e) => {
            currentClassFilter = e.target.value;
            updateDashboard();
        });

        document.getElementById('clearAllFilters').addEventListener('click', () => {
            currentClassFilter = '';
            currentCritFilter = '';
            currentSearchFilter = '';
            document.getElementById('claseSelector').value = '';
            document.getElementById('familySearch').value = '';
            updateDashboard();
        });
    }

    function initCharts() {
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const labelColor = getThemeTextColor();

        // 1. Bar Chart: Specialty
        const barCtx = document.getElementById('claseChart').getContext('2d');
        claseChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= $claseLabels ?>,
                datasets: [{
                    data: [],
                    backgroundColor: <?= $claseHexes ?>,
                    borderRadius: 12,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const clicked = claseChart.data.labels[elements[0].index];
                        // Toggle: si ya está activo el mismo filtro, quitarlo
                        currentClassFilter = (currentClassFilter === clicked) ? '' : clicked;
                        document.getElementById('claseSelector').value = currentClassFilter;
                        updateDashboard();
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#0f172a' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#0f172a',
                        bodyColor: labelColor,
                        borderColor: isDark ? '#1e293b' : '#e2e8f0',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: labelColor
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: labelColor,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // 2. Doughnut Chart: Criticality
        const doughnutCtx = document.getElementById('critChart').getContext('2d');
        critChart = new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($critChartData['labels']) ?>,
                datasets: [{
                    data: [],
                    backgroundColor: <?= json_encode($critChartData['hex']) ?>,
                    borderColor: isDark ? '#0f172a' : '#ffffff',
                    borderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const clicked = critChart.data.labels[elements[0].index];
                        const key = Object.keys(CRIT_MAP).find(k => CRIT_MAP[k] === clicked);
                        currentCritFilter = (currentCritFilter === key) ? '' : key;
                        updateDashboard();
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: labelColor,
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // 3. Pareto Chart
        const pCtx = document.getElementById('paretoChart').getContext('2d');
        const pData = <?= json_encode($paretoData) ?>;
        paretoChart = new Chart(pCtx, {
            type: 'bar',
            data: {
                labels: pData.map(d => d.family),
                datasets: [{
                        label: 'Horas Downtime',
                        data: pData.map(d => d.hours),
                        backgroundColor: '#dc2626d0',
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: '% Acumulado',
                        data: pData.map(d => d.cumulative_pct),
                        type: 'line',
                        borderColor: '#fbbf24',
                        borderWidth: 3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: labelColor
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: '#dc2626'
                        }
                    },
                    y1: {
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#fbbf24'
                        }
                    },
                    x: {
                        ticks: {
                            color: labelColor,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    function normalizeCrit(val) {
        if (!val) return 'LOW';
        const v = val.toString().toUpperCase().trim();
        if (v === 'CRITICAL' || v === 'CRÍTICO' || v === 'CRITICO') return 'CRITICAL';
        if (v === 'RELEVANT' || v === 'RELEVANTE') return 'RELEVANT';
        return 'LOW'; // NA, No aplica, everything else
    }

    function updateDashboard() {
        const filtered = [];
        RAW_DATA.forEach(group => {
            group.equipos.forEach(asset => {
                const normCrit = normalizeCrit(asset.criticality);
                const matchesClass = !currentClassFilter || group.clase === currentClassFilter;
                const matchesCrit = !currentCritFilter || normCrit === currentCritFilter;
                const matchesSearch = !currentSearchFilter ||
                    asset.name.toLowerCase().includes(currentSearchFilter) ||
                    (asset.brand && asset.brand.toLowerCase().includes(currentSearchFilter)) ||
                    group.clase.toLowerCase().includes(currentSearchFilter);

                if (matchesClass && matchesCrit && matchesSearch) {
                    filtered.push({
                        ...asset,
                        groupClass: group.clase,
                        normalizedCrit: normCrit
                    });
                }
            });
        });

        // Update KPIs
        const stats = {
            total: filtered.length,
            operativos: filtered.filter(a => a.status === 'OPERATIVE').length,
            criticos: filtered.filter(a => a.normalizedCrit === 'CRITICAL').length,
            obsoletos: filtered.filter(a => a.status === 'NO_OPERATIVE').length,
            valor: filtered.reduce((sum, a) => sum + parseFloat(a.costo || 0), 0)
        };

        animateValue('kpi-total', stats.total);
        animateValue('kpi-operativos', stats.operativos);
        animateValue('kpi-criticos', stats.criticos);
        animateValue('kpi-obsoletos', stats.obsoletos);

        const disp = stats.total > 0 ? ((stats.operativos / stats.total) * 100).toFixed(1) : 0;
        document.getElementById('kpi-disp').innerText = disp + '%';

        document.getElementById('kpi-valor').innerText = '$' + new Intl.NumberFormat('de-DE').format(stats.valor);
        document.getElementById('clearAllFilters').classList.toggle('hidden',
            !currentClassFilter && !currentCritFilter && !currentSearchFilter);

        updateCharts(filtered);
        renderAssets(filtered);
    }

    function updateCharts(filteredData) {
        const classDist = {};
        RAW_DATA.forEach(g => classDist[g.clase] = 0);
        filteredData.forEach(a => classDist[a.groupClass]++);
        claseChart.data.datasets[0].data = claseChart.data.labels.map(l => classDist[l] || 0);
        claseChart.update();

        const critDist = {
            'CRITICAL': 0,
            'RELEVANT': 0,
            'LOW': 0
        };
        filteredData.forEach(a => {
            if (critDist[a.normalizedCrit] !== undefined) critDist[a.normalizedCrit]++;
        });
        critChart.data.datasets[0].data = [critDist['CRITICAL'], critDist['RELEVANT'], critDist['LOW']];
        critChart.update();
    }

    function renderAssets(filtered) {
        const container = document.getElementById('assetsContainer');
        container.innerHTML = '';

        if (filtered.length === 0) {
            container.innerHTML = '<div class="card-glass p-20 text-center"><span class="material-symbols-outlined text-6xl text-slate-300">search_off</span><p class="text-slate-500 font-bold mt-4 uppercase tracking-widest">No se encontraron equipos</p></div>';
            return;
        }

        const groups = {};
        filtered.forEach(a => {
            if (!groups[a.groupClass]) groups[a.groupClass] = {
                equipos: [],
                total: 0,
                valor: 0
            };
            groups[a.groupClass].equipos.push(a);
            groups[a.groupClass].total++;
            groups[a.groupClass].valor += parseFloat(a.costo || 0);
        });

        const template = document.getElementById('groupTemplate');
        Object.keys(groups).forEach(className => {
            const data = groups[className];
            const clone = template.content.cloneNode(true);
            const card = clone.querySelector('.card-glass');

            const config = (<?= json_encode($claseColors) ?>[className] || <?= json_encode($defaultColor) ?>);
            clone.querySelector('.group-icon').innerText = config.icon;
            clone.querySelector('.group-title').innerText = className;
            clone.querySelector('.group-count').innerText = data.total + ' Equipos';
            clone.querySelector('.group-investment').innerText = '$' + new Intl.NumberFormat('de-DE').format(data.valor);

            const tbody = clone.querySelector('.group-table-body');
            data.equipos.slice(0, 15).forEach(a => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-medical-blue/5 transition-colors';

                const critBadgeClass = (<?= json_encode($critBadge) ?>[a.normalizedCrit] || 'bg-slate-100 text-slate-400');
                const critText = (<?= json_encode($critLabel) ?>[a.normalizedCrit] || '-');

                tr.innerHTML = `
                    <td class="px-6 py-4"><span class="text-xs font-black text-medical-blue hover:underline cursor-pointer">#${a.id}</span></td>
                    <td class="px-6 py-4">
                        <p class="text-[11px] font-black text-[var(--text-main)]">${a.name}</p>
                        <p class="text-[9px] text-slate-500 font-bold uppercase">${a.brand} · ${a.model}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase ${a.status === 'OPERATIVE' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'}">
                            ${a.status === 'OPERATIVE' ? 'Operativo' : 'Inactivo'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center"><span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase ${critBadgeClass}">${critText}</span></td>
                    <td class="px-6 py-4 text-center">
                        <a href="?page=asset&id=${a.id}" class="inline-flex items-center gap-2 px-4 py-2 bg-panel-dark text-text-muted rounded-xl text-[9px] font-black uppercase hover:border hover:border-medical-blue/30 transition-all">
                            Ficha Técnica <span class="material-symbols-outlined text-xs">arrow_forward</span>
                        </a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            container.appendChild(clone);
        });
    }

    function toggleGroup(el) {
        const content = el.nextElementSibling;
        const chevron = el.querySelector('.chevron');
        const isOpen = !content.classList.contains('hidden');
        content.classList.toggle('hidden');
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function animateValue(id, target) {
        const el = document.getElementById(id);
        if (!el) return;
        const start = parseInt(el.innerText) || 0;
        const duration = 500;
        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const val = Math.floor(progress * (target - start) + start);
            el.innerText = val;
            if (progress < 1) window.requestAnimationFrame(step);
        }
        window.requestAnimationFrame(step);
    }
</script>