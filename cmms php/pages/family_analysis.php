<?php
// pages/family_analysis.php
// Clasificación de equipos: Monitoreo / No Monitoreo
// Criticidad: Crítico / Relevante / No Aplica

require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

// Datos agrupados por clasificación (campo riesgo_ge)
$clasesData    = getAssetsByClase();         // [Monitoreo, No Monitoreo, ...]
$riesgoData    = getAssetsByRiesgoBiomedico(); // [Alto, Medio, Bajo, N/A]
$selectedClase = $_GET['clase_filter'] ?? '';
$allClases     = array_column($clasesData, 'clase');

$viewData = $selectedClase
    ? array_filter($clasesData, fn($c) => $c['clase'] === $selectedClase)
    : $clasesData;

// KPIs globales
$totalAssets     = array_sum(array_column($clasesData, 'total'));
$totalOperativos = array_sum(array_column($clasesData, 'operativos'));
$totalCriticos   = array_sum(array_column($clasesData, 'criticos'));
$totalValor      = array_sum(array_column($clasesData, 'valor_total'));
$totalObsoletos  = array_sum(array_column($clasesData, 'obsoletos'));
$disponibilidad  = $totalAssets > 0 ? round(($totalOperativos / $totalAssets) * 100, 1) : 0;

// Mapa criticidad DB → label UI
$critLabel  = ['CRITICAL' => 'Crítico',   'RELEVANT' => 'Relevante', 'LOW' => 'No Aplica'];
$critBadge  = ['CRITICAL' => 'bg-red-500/10 text-red-400', 'RELEVANT' => 'bg-amber-500/10 text-amber-400', 'LOW' => 'bg-slate-500/10 text-slate-400'];

// Colores por clasificación
$claseColors = [
    'Monitoreo'       => ['hex' => '#3b82f6', 'badge' => 'bg-blue-500/10 text-blue-400',   'icon' => 'monitor_heart'],
    'No Monitoreo'    => ['hex' => '#10b981', 'badge' => 'bg-emerald-500/10 text-emerald-400', 'icon' => 'build'],
    'Sin Clasificar'  => ['hex' => '#64748b', 'badge' => 'bg-slate-500/10 text-slate-400', 'icon' => 'device_unknown'],
];
$defaultColor = ['hex' => '#64748b', 'badge' => 'bg-slate-500/10 text-slate-400', 'icon' => 'devices'];

// Datos para gráficos
$claseLabels = json_encode(array_column($clasesData, 'clase'));
$claseHexes  = json_encode(array_map(fn($c) => ($claseColors[$c['clase']] ?? $defaultColor)['hex'], $clasesData));
$claseTotal  = json_encode(array_column($clasesData, 'total'));
$riesgoLabels = json_encode(array_keys($riesgoData));
$riesgoValues = json_encode(array_values($riesgoData));
$riesgoHex    = json_encode(['#ef4444', '#f59e0b', '#10b981', '#64748b']);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text-main)] tracking-tight">Clasificación de Equipos</h1>
            <p class="text-xs text-[var(--text-muted)] mt-1 uppercase tracking-wider font-bold">Monitoreo · No Monitoreo · Criticidad</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="page" value="family_analysis">
            <select name="clase_filter" onchange="this.form.submit()"
                class="h-11 px-4 bg-slate-900 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold focus:ring-2 focus:ring-medical-blue outline-none">
                <option value="">Todas las Clases</option>
                <?php foreach ($allClases as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $selectedClase === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card-glass p-5 border-l-4 border-l-medical-blue">
            <span class="material-symbols-outlined text-[var(--text-muted)] text-lg">devices</span>
            <p class="stat-label mt-2 text-[var(--text-muted)]">Total Equipos</p>
            <h3 class="stat-value text-[var(--text-main)]"><?= $totalAssets ?></h3>
        </div>
        <div class="card-glass p-5 border-l-4 border-l-emerald-500">
            <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
            <p class="stat-label mt-2 text-[var(--text-muted)]">Operativos</p>
            <h3 class="stat-value text-[var(--text-main)]"><?= $totalOperativos ?></h3>
            <p class="text-[10px] text-[var(--text-muted)] mt-1 italic"><?= $disponibilidad ?>% disponibilidad</p>
        </div>
        <div class="card-glass p-5 border-l-4 border-l-red-500">
            <span class="material-symbols-outlined text-red-500 text-lg">warning</span>
            <p class="stat-label mt-2 text-[var(--text-muted)]">Críticos</p>
            <h3 class="stat-value text-[var(--text-main)]"><?= $totalCriticos ?></h3>
        </div>
        <div class="card-glass p-5 border-l-4 border-l-amber-500">
            <span class="material-symbols-outlined text-amber-500 text-lg">history_toggle_off</span>
            <p class="stat-label mt-2 text-[var(--text-muted)]">Obsoletos</p>
            <h3 class="stat-value text-[var(--text-main)]"><?= $totalObsoletos ?></h3>
        </div>
        <div class="card-glass p-5 border-l-4 border-l-indigo-500">
            <span class="material-symbols-outlined text-indigo-500 text-lg">payments</span>
            <p class="stat-label mt-2 text-[var(--text-muted)]">Valor Conservación</p>
            <h3 class="stat-value text-[var(--text-main)]">$<?= number_format($totalValor, 0, ',', '.') ?></h3>
            <p class="text-[10px] text-[var(--text-muted)] mt-1 italic">Pesos Chilenos (CLP)</p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-glass p-6">
            <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest mb-4">Equipos por Clasificación</h3>
            <?php if (count($clasesData) > 0): ?>
                <div class="relative h-56"><canvas id="claseChart"></canvas></div>
            <?php else: ?>
                <div class="h-56 flex flex-col items-center justify-center text-slate-600">
                    <span class="material-symbols-outlined text-5xl mb-2">inventory_2</span>
                    <p class="text-sm font-bold">Sin datos</p>
                    <p class="text-xs mt-1">Ejecuta el poblamiento de datos primero</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-glass p-6">
            <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest mb-4">Nivel de Riesgo Biomédico</h3>
            <?php if ($totalAssets > 0): ?>
                <div class="relative h-56"><canvas id="riesgoChart"></canvas></div>
            <?php else: ?>
                <div class="h-56 flex items-center justify-center text-slate-600">
                    <span class="material-symbols-outlined text-5xl">analytics</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tablas por Grupo -->
    <div class="space-y-4">
        <?php if (count($viewData) > 0): ?>
            <?php foreach ($viewData as $grupo): ?>
                <?php $colors = $claseColors[$grupo['clase']] ?? $defaultColor; ?>
                <div class="card-glass overflow-hidden">
                    <!-- Encabezado del grupo -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 cursor-pointer hover:bg-slate-800/30 transition-colors"
                        onclick="toggleGrupo('<?= htmlspecialchars($grupo['clase']) ?>')">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                style="background:<?= $colors['hex'] ?>22;border:1px solid <?= $colors['hex'] ?>44">
                                <span class="material-symbols-outlined text-lg" style="color:<?= $colors['hex'] ?>"><?= $colors['icon'] ?></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-black text-[var(--text-main)] text-lg"><?= htmlspecialchars($grupo['clase']) ?></h3>
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded <?= $colors['badge'] ?>">
                                        <?= $grupo['total'] ?> equipo<?= $grupo['total'] !== 1 ? 's' : '' ?>
                                    </span>
                                </div>
                                <p class="text-xs text-[var(--text-muted)] mt-0.5">
                                    <?= $grupo['operativos'] ?> operativos &bull;
                                    <?= $grupo['criticos'] ?> críticos &bull;
                                    $<?= number_format($grupo['valor_total'], 0, ',', '.') ?> CLP
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <?php $disp = $grupo['total'] > 0 ? round(($grupo['operativos'] / $grupo['total']) * 100) : 0; ?>
                            <div class="hidden md:block text-right">
                                <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-wider">Disponibilidad</p>
                                <p class="text-xl font-black <?= $disp >= 80 ? 'text-emerald-400' : ($disp >= 60 ? 'text-amber-400' : 'text-red-400') ?>"><?= $disp ?>%</p>
                            </div>
                            <span class="material-symbols-outlined text-slate-500 transition-transform duration-200"
                                id="arrow-<?= htmlspecialchars($grupo['clase']) ?>">expand_more</span>
                        </div>
                    </div>

                    <!-- Tabla equipos -->
                    <div id="grupo-<?= htmlspecialchars($grupo['clase']) ?>" class="hidden overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[9px] font-black text-slate-500 uppercase tracking-wider bg-slate-900/50">
                                    <th class="px-4 py-2 text-left">Equipo</th>
                                    <th class="px-4 py-2 text-left hidden md:table-cell">Marca / Modelo</th>
                                    <th class="px-4 py-2 text-left hidden md:table-cell">Ubicación</th>
                                    <th class="px-4 py-2 text-center">Estado</th>
                                    <th class="px-4 py-2 text-center">Criticidad</th>
                                    <th class="px-4 py-2 text-right hidden md:table-cell">Costo</th>
                                    <th class="px-4 py-2 text-center">Vida Útil</th>
                                    <th class="px-4 py-2 text-center">·</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/20">
                                <?php foreach ($grupo['equipos'] as $eq): ?>
                                    <?php
                                    $stMap = [
                                        'OPERATIVE'            => ['Operativo',   'text-emerald-400 bg-emerald-500/10'],
                                        'MAINTENANCE'          => ['En Mantto.',  'text-amber-400 bg-amber-500/10'],
                                        'NO_OPERATIVE'         => ['No Operativo', 'text-red-400 bg-red-500/10'],
                                        'OPERATIVE_WITH_OBS'   => ['Con Obs.',    'text-yellow-400 bg-yellow-500/10'],
                                    ];
                                    $st  = $stMap[$eq['status']] ?? [$eq['status'], 'text-slate-400 bg-slate-500/10'];
                                    $cl  = $critLabel[$eq['criticality']] ?? $eq['criticality'];
                                    $cb  = $critBadge[$eq['criticality']] ?? 'bg-slate-500/10 text-slate-400';
                                    $vc  = $eq['vida_util'] > 30 ? 'text-emerald-400' : ($eq['vida_util'] > 10 ? 'text-amber-400' : 'text-red-400');
                                    ?>
                                    <tr class="hover:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-[var(--text-main)] leading-tight"><?= htmlspecialchars($eq['name']) ?></p>
                                            <p class="text-[var(--text-muted)] text-[10px]"><?= htmlspecialchars($eq['id']) ?></p>
                                        </td>
                                        <td class="px-4 py-3 hidden md:table-cell">
                                            <p class="text-[var(--text-main)]"><?= htmlspecialchars($eq['brand']) ?></p>
                                            <p class="text-[var(--text-muted)] text-[10px]"><?= htmlspecialchars($eq['model']) ?></p>
                                        </td>
                                        <td class="px-4 py-3 hidden md:table-cell text-[var(--text-muted)]"><?= htmlspecialchars($eq['location']) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded font-black text-[9px] <?= $st[1] ?>"><?= $st[0] ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded font-black text-[9px] <?= $cb ?>"><?= $cl ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-right hidden md:table-cell text-[var(--text-main)] font-mono">
                                            $<?= number_format($eq['costo'], 0, ',', '.') ?>
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold <?= $vc ?>"><?= $eq['vida_util'] ?>%</td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="?page=asset&id=<?= urlencode($eq['id']) ?>"
                                                class="text-medical-blue hover:text-blue-400 font-bold text-[10px] uppercase tracking-wider transition-colors">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="card-glass p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-700 mb-4 block">inventory</span>
                <h3 class="text-lg font-black text-slate-500">Sin equipos clasificados</h3>
                <p class="text-sm text-slate-600 mt-2">Ejecuta el poblamiento de datos para ver la clasificación.</p>
                <a href="../populate_dashboard_data.php" class="inline-block mt-4 px-6 py-3 bg-medical-blue rounded-xl text-white font-bold text-sm hover:bg-blue-600 transition-all">
                    Poblar Datos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleGrupo(grupo) {
        const el = document.getElementById('grupo-' + grupo);
        const arr = document.getElementById('arrow-' + grupo);
        if (!el) return;
        el.classList.toggle('hidden');
        if (arr) arr.style.transform = el.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }

    <?php if (count($clasesData) > 0): ?>
            (function() {
                const ctx = document.getElementById('claseChart');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= $claseLabels ?>,
                        datasets: [{
                            label: 'Equipos',
                            data: <?= $claseTotal ?>,
                            backgroundColor: <?= $claseHexes ?>.map(c => c + '33'),
                            borderColor: <?= $claseHexes ?>,
                            borderWidth: 2,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#94a3b8',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#94a3b8',
                                    stepSize: 1
                                },
                                grid: {
                                    color: '#1e293b'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            })();
    <?php endif; ?>

    <?php if ($totalAssets > 0): ?>
            (function() {
                const ctx = document.getElementById('riesgoChart');
                if (!ctx) return;
                const labels = <?= $riesgoLabels ?>;
                const data = <?= $riesgoValues ?>;
                const colors = <?= $riesgoHex ?>;
                const idx = data.map((v, i) => v > 0 ? i : -1).filter(i => i >= 0);
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: idx.map(i => labels[i]),
                        datasets: [{
                            data: idx.map(i => data[i]),
                            backgroundColor: idx.map(i => colors[i] + '99'),
                            borderColor: idx.map(i => colors[i]),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#94a3b8',
                                    font: {
                                        size: 11,
                                        weight: 'bold'
                                    },
                                    padding: 12
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            })();
    <?php endif; ?>
</script>