<?php
// pages/dashboard.php

// ── Control de Acceso ──
if (!canViewDashboard()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1><p class='text-[var(--text-muted)] mt-2'>Los técnicos no tienen acceso al Dashboard.</p></div>";
    return;
}

// Importar funciones de métricas de confiabilidad
require_once __DIR__ . '/../includes/reliability_metrics.php';

// ── Backend Providers ──
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
require_once __DIR__ . '/../Backend/Providers/EventProvider.php';

// --- DATOS DESDE PROVIDERS ---
$assets = getAllAssets();
$otCorrectivas = getCorrectiveWorkOrders();
$technicians = getTechnicianProductivity();
$recentEvents = getRecentEvents();
$financialStats = getFinancialStats();

// --- CÁLCULO DINÁMICO DE MÉTRICAS ---
$totalEquipos = count($assets);

// Equipos por estado
$statusCounts = countAssetsByStatus();
$equiposOperativos = $statusCounts['operative'];
$equiposMantenimiento = $statusCounts['maintenance'];
$equiposNoOperativos = $statusCounts['no_operative'];
$equiposConObservaciones = $statusCounts['with_obs'];

// Equipos por criticidad
$critCounts = countAssetsByCriticality();
$equiposCriticos = $critCounts['CRITICAL'];
$equiposRelevantes = $critCounts['RELEVANT'];

// --- CÁLCULO DE MÉTRICAS CLÍNICAS 2.0 ---
$totalAcquisitionValue = $financialStats['valor_inventario'];
$totalMaintenanceCost = $financialStats['costo_mantenimiento_anual'];
$cosr = $totalAcquisitionValue > 0 ? ($totalMaintenanceCost / $totalAcquisitionValue) * 100 : 0;

// Riesgo de Capital
$equiposRiesgoCapital = getCapitalRiskCount();

// Órdenes de trabajo
$woCounts = countWorkOrdersByStatus();
$totalOT = $woCounts['total'];
$otTerminadas = $woCounts['Terminada'];
$otEnProceso = $woCounts['En Proceso'];
$otPendientes = $woCounts['Pendiente'];

$otPorTipo = countWorkOrdersByType();

// --- CÁLCULO DE MÉTRICAS POR CLASE PARA EL GRÁFICO GLOBAL ---
$claseGroups = [];
foreach ($assets as $asset) {
    // Agrupar por clase de riesgo biomédico (I, IIA, IIB, III)
    $claveGrupo = $asset['clase_riesgo'] ?? ($asset['criticality'] ?? 'Sin Clase');
    if (!isset($claseGroups[$claveGrupo])) {
        $claseGroups[$claveGrupo] = [
            'count'      => 0,
            'mtbf_sum'   => 0,
            'mttr_sum'   => 0,
            'valid_mtbf' => 0
        ];
    }

    $mtbf = calcularMTBF($asset['id'], $otCorrectivas);
    $mttr = calcularMTTR($asset['id'], $otCorrectivas);

    $claseGroups[$claveGrupo]['count']++;
    $claseGroups[$claveGrupo]['mttr_sum'] += $mttr;
    if ($mtbf !== null) {
        $claseGroups[$claveGrupo]['mtbf_sum'] += $mtbf;
        $claseGroups[$claveGrupo]['valid_mtbf']++;
    }
}

$reliabilityByFamily = [];
foreach ($claseGroups as $nombre => $datos) {
    $reliabilityByFamily[] = [
        'name' => 'Clase ' . $nombre,
        'mtbf' => $datos['valid_mtbf'] > 0 ? round($datos['mtbf_sum'] / $datos['valid_mtbf'], 1) : 0,
        'mttr' => $datos['count'] > 0 ? round($datos['mttr_sum'] / $datos['count'], 1) : 0
    ];
}
// Limitar a 8 clases para el gráfico
$reliabilityByFamily = array_slice($reliabilityByFamily, 0, 8);

// --- CÁLCULO DE MÉTRICAS DE CONFIABILIDAD (WEIBULL) ---
$metricasGlobales = calcularMetricasGlobales($assets, $otCorrectivas);

// --- GENERAR DATOS PARA LA CURVA DE PROBABILIDAD DE FALLA ACUMULADA F(t) ---
$mtbf_global = $metricasGlobales['mtbf_promedio'] > 0 ? $metricasGlobales['mtbf_promedio'] : 30;
$beta = DEFAULT_BETA_WEIBULL;
$eta = $mtbf_global / gamma_approx(1 + 1 / $beta);

function gamma_approx($n)
{
    return sqrt(2 * M_PI / $n) * pow($n / exp(1), $n);
}

$puntosCurva = [];
$labelsCurva = [];
for ($t = 0; $t <= 90; $t += 5) {
    $labelsCurva[] = $t . " d";
    // Si eta es <= 0 (no hay fallas), el riesgo es 0 o mínimo
    if ($eta > 0) {
        $puntosCurva[] = round((1 - exp(-pow($t / $eta, $beta))) * 100, 1);
    } else {
        $puntosCurva[] = 0.1;
    }
}

// Calcular GE para equipos críticos
$equiposCriticosGE = count(array_filter($assets, fn($a) => calcularGE($a) >= 12));

// --- OBTENER EQUIPOS DE ALTO RIESGO (DRILL-DOWN) ---
$highRiskAssets = getTopRiskAssets(5);

// KPIs calculados dinámicamente
$kpiCards = [
    [
        'label' => 'Valor Inventario',
        'value' => '$' . number_format($totalAcquisitionValue, 0, ',', '.') . ' CLP',
        'trend' => 'CAPEX',
        'color' => 'border-l-medical-blue',
        'icon' => 'inventory_2',
        'sub' => 'Valorización de Activos'
    ],
    [
        'label' => 'Continuidad Operativa',
        'value' => round($mtbf_global, 1) . ' d',
        'trend' => '$\\beta=' . $beta . '$',
        'color' => 'border-l-emerald-500',
        'icon' => 'timeline',
        'sub' => 'MTBF (Weibull)'
    ],
    [
        'label' => 'COSR',
        'value' => round($cosr, 1) . '%',
        'trend' => 'Meta < 7%',
        'color' => $cosr < 7 ? 'border-l-emerald-500' : 'border-l-amber-500',
        'icon' => 'payments',
        'sub' => 'Costo de Servicio'
    ],
    [
        'label' => 'Disponibilidad',
        'value' => $metricasGlobales['disponibilidad_promedio'] > 0 ? round($metricasGlobales['disponibilidad_promedio'] * 100, 1) . '%' : 'N/A',
        'trend' => 'Uptime Clínico',
        'color' => $metricasGlobales['disponibilidad_promedio'] >= 0.95 ? 'border-l-emerald-500' : 'border-l-red-500',
        'icon' => 'health_and_safety',
        'sub' => 'Operatividad Real'
    ],
    [
        'label' => 'Vencidos Operativos',
        'value' => getExpiredOperativeCount(),
        'trend' => 'Audit Contable',
        'color' => getExpiredOperativeCount() > 0 ? 'border-l-amber-500' : 'border-l-slate-400',
        'icon' => 'history_toggle_off',
        'sub' => 'Vida Útil Excedida'
    ],
    [
        'label' => 'Adherencia',
        'value' => getAdherenceRate() . '%',
        'trend' => 'Meta > 90%',
        'color' => 'border-l-indigo-500',
        'icon' => 'check_circle',
        'sub' => 'Cierre de OT'
    ]
];

// Identificación de datos insuficientes para visualizaciones
$hasAssets = $totalEquipos > 0;
$hasOTs = $totalOT > 0;
$hasCorrectives = count($otCorrectivas) > 0;
$hasFinancialData = $totalAcquisitionValue > 0;

// Datos para gráfico de estado de equipos
$estadoEquiposData = [
    ['name' => 'Operativos', 'value' => $equiposOperativos, 'color' => '#10b981'],
    ['name' => 'Mantenimiento', 'value' => $equiposMantenimiento, 'color' => '#f59e0b'],
    ['name' => 'Con Observaciones', 'value' => $equiposConObservaciones, 'color' => '#eab308']
];

if ($equiposNoOperativos > 0) {
    $estadoEquiposData[] = ['name' => 'No Operativos', 'value' => $equiposNoOperativos, 'color' => '#ef4444'];
}

// Datos para gráfico de criticidad
$criticidadData = [
    ['name' => 'Críticos', 'value' => $equiposCriticos, 'color' => '#ef4444'],
    ['name' => 'Relevantes', 'value' => $equiposRelevantes, 'color' => '#0ea5e9']
];

// Datos para gráfico de OT por tipo
$otPorTipoData = [
    ['name' => 'Preventivo', 'value' => $otPorTipo['Preventiva'] ?? 0],
    ['name' => 'Correctivo', 'value' => $otPorTipo['Correctiva'] ?? 0],
    ['name' => 'Calibración', 'value' => $otPorTipo['Calibración'] ?? 0]
];

// Datos de técnicos para gráfico
$techComparisonData = array_map(function ($t) {
    return [
        'name' => explode(' ', $t['name'])[0],
        'terminadas' => $t['ot_terminadas']
    ];
}, $technicians);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Interno -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text-main)] tracking-tight"><?= SIDEBAR_DASHBOARD ?></h1>
            <p class="text-xs text-[var(--text-muted)] mt-1 uppercase tracking-wider font-bold">Vista General Operativa</p>
        </div>
        <?php if (canModify()): ?>
            <div class="flex gap-3">
                <button
                    class="h-11 px-6 border border-border-dark text-[var(--text-muted)] rounded-xl text-sm font-bold hover:bg-slate-200 dark:hover:bg-slate-800 flex items-center gap-2 transition-all active:scale-95 bg-medical-surface">
                    <span class="material-symbols-outlined text-xl">file_download</span>
                    Exportar Reporte
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <?php foreach ($kpiCards as $idx => $kpi): ?>
            <div class="card-glass p-5 border-l-4 <?= $kpi['color'] ?>">
                <div class="flex justify-between items-start mb-2">
                    <span
                        class="material-symbols-outlined text-[var(--text-muted)] text-lg font-variation-fill"><?= $kpi['icon'] ?></span>
                    <span
                        class="text-[9px] font-black px-2 py-0.5 rounded <?= $idx === 3 ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500' ?>">
                        <?= $kpi['trend'] ?>
                    </span>
                </div>
                <p class="stat-label"><?= $kpi['label'] ?></p>
                <h3 class="stat-value text-[var(--text-main)]"><?= $kpi['value'] ?></h3>
                <p class="text-[10px] text-[var(--text-muted)] mt-1 italic tracking-tight font-medium"><?= $kpi['sub'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts & Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Curva de Falla Weibull (Confiabilidad) -->
        <div class="lg:col-span-12 card-glass p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                <span class="material-symbols-outlined text-9xl text-[var(--text-muted)]">analytics</span>
            </div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em]">Modelado de Fallas (Weibull)
                    </h3>
                    <p class="text-xs text-[var(--text-muted)] font-bold uppercase tracking-widest mt-1">
                        Probabilidad Acumulada $F(t) = 1 - e^{-(t/\eta)^\beta}$
                    </p>
                </div>
                <div class="flex gap-4">
                    <div class="px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                        <span class="text-[10px] font-black text-amber-500 uppercase">Beta: <?= $beta ?>
                            (Desgaste)</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <div class="md:col-span-8 h-[350px] flex items-center justify-center">
                    <?php if ($hasCorrectives): ?>
                        <canvas id="reliabilityCurveChart"></canvas>
                    <?php else: ?>
                        <div class="text-center space-y-4">
                            <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-700">query_stats</span>
                            <p class="text-xs text-[var(--text-muted)] font-bold uppercase tracking-widest">Sin datos históricos de fallas para modelar curva</p>
                            <p class="text-[10px] text-[var(--text-muted)] max-w-xs mx-auto italic">Se requieren Órdenes de Trabajo correctivas finalizadas para generar el pronóstico Weibull.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="md:col-span-4 space-y-6">
                    <div class="card-glass <?= $hasCorrectives ? 'bg-amber-500/5 border-amber-500/10' : 'opacity-50 grayscale' ?> p-6 border">
                        <h4 class="text-[11px] font-black text-amber-500 uppercase mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                            Pronóstico de Confiabilidad
                        </h4>
                        <p class="text-sm text-[var(--text-main)] leading-relaxed mb-4">
                            <?php if ($hasCorrectives): ?>
                                Predicción basada en fase de <b>desgaste inicial</b> ($\beta > 1$) para el parque tecnológico crítico.
                            <?php else: ?>
                                Pendiente de datos de intervención técnica para análisis descriptivo.
                            <?php endif; ?>
                        </p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-border-dark">
                                <span class="text-[var(--text-muted)] font-bold uppercase tracking-widest">PROBABILIDAD FALLA (30D)</span>
                                <span class="text-amber-500 font-black"><?= $hasCorrectives ? round((1 - exp(-pow(30 / $eta, $beta))) * 100, 1) . '%' : '---' ?></span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-border-dark">
                                <span class="text-[var(--text-muted)] font-bold uppercase tracking-widest">PROBABILIDAD FALLA (60D)</span>
                                <span class="text-red-500 font-black"><?= $hasCorrectives ? round((1 - exp(-pow(60 / $eta, $beta))) * 100, 1) . '%' : '---' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 rounded-2xl bg-medical-surface border border-border-dark">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-medical-blue text-sm">lightbulb</span>
                            <p class="text-[10px] text-medical-blue font-black uppercase tracking-widest">Recomendación
                                Clínico 2.0</p>
                        </div>
                        <p class="text-[11px] text-[var(--text-muted)] italic">"Dada la pendiente de la curva, se sugiere
                            adelantar mantenimientos preventivos en ventilación mecánica para mitigar el riesgo de
                            correctivos en el próximo trimestre."</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drill-down: Equipos en Riesgo Crítico -->
        <div class="lg:col-span-12 card-glass p-8 border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-500">report_problem</span>
                        Equipos con Alto Riesgo de Falla (Drill-down)
                    </h3>
                    <p class="text-[var(--text-muted)] text-[10px] font-bold uppercase tracking-widest mt-1">Identificación específica basada en modelo predictivo</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-border-dark/50">
                            <th class="py-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">Activo</th>
                            <th class="py-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest text-center">Ubicación</th>
                            <th class="py-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest text-center">Días en Servicio</th>
                            <th class="py-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest text-right">Riesgo (30D)</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-dark/30">
                        <?php if (empty($highRiskAssets)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[var(--text-muted)] text-[10px] font-bold uppercase tracking-widest italic">No se detectaron riesgos críticos inmediatos</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($highRiskAssets as $hrAsset):
                                $riskColor = $hrAsset['failure_prob'] > 15 ? 'text-red-500' : ($hrAsset['failure_prob'] > 10 ? 'text-amber-500' : 'text-emerald-500');
                            ?>
                                <tr class="group hover:bg-slate-200/20 transition-all">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="size-8 rounded-lg bg-medical-surface border border-border-dark flex items-center justify-center">
                                                <span class="material-symbols-outlined text-sm text-medical-blue">medical_services</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-[var(--text-main)]"><?= $hrAsset['name'] ?></p>
                                                <p class="text-[9px] text-[var(--text-muted)] font-bold uppercase italic"><?= $hrAsset['id'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="px-2 py-0.5 rounded-md bg-medical-surface border border-border-dark text-[10px] font-bold text-[var(--text-muted)] italic">
                                            <?= $hrAsset['location'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="text-xs font-bold text-[var(--text-main)]"><?= $hrAsset['days_in_service'] ?> días</span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <span class="text-xs font-black <?= $riskColor ?>"><?= round($hrAsset['failure_prob'], 1) ?>%</span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <a href="?page=asset&id=<?= $hrAsset['id'] ?>" class="p-2 hover:bg-medical-blue/10 rounded-lg text-medical-blue transition-all inline-flex items-center">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distribución de Carga de Trabajo Técnicos -->
        <div class="lg:col-span-12 card-glass p-8 shadow-2xl relative overflow-hidden group border border-border-dark">
            <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                <span class="material-symbols-outlined text-6xl text-[var(--text-muted)]">engineering</span>
            </div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] italic flex items-center gap-3">
                        <span class="size-2 bg-medical-blue rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                        Carga de Trabajo del Equipo
                    </h3>
                    <p class="text-[var(--text-muted)] text-[10px] font-bold uppercase tracking-widest mt-1">Análisis de capacidad
                        y OTs activas</p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="px-3 py-1 bg-medical-surface rounded-lg text-[10px] font-black text-[var(--text-muted)] border border-border-dark uppercase tracking-tighter">Semana
                        Actual</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                foreach ($technicians as $tech):
                    $techCapacity = $tech['capacity'] ?? 0;
                    $statusColor = $techCapacity > 90 ? 'text-red-500' : ($techCapacity > 70 ? 'text-amber-500' : 'text-emerald-500');
                    $progressBarColor = $techCapacity > 90 ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]' : ($techCapacity > 70 ? 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]' : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]');
                ?>
                    <div
                        class="relative hover:bg-slate-200/30 dark:hover:bg-white/5 p-5 rounded-2xl transition-all border border-border-dark bg-medical-surface shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-10 rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center text-white font-black text-sm shadow-xl">
                                    <?= $tech['initial'] ?? '?' ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-black text-[var(--text-main)] truncate w-24"><?= $tech['name'] ?></h4>
                                    <p class="text-[8px] text-[var(--text-muted)] font-bold uppercase truncate"><?= $tech['role'] ?? 'Técnico Biomédico' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black <?= $statusColor ?>"><?= $tech['capacity'] ?>%</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="h-1.5 w-full bg-slate-200 dark:bg-white/5 rounded-full overflow-hidden border border-border-dark">
                                <div class="h-full <?= $progressBarColor ?> transition-all duration-1000"
                                    style="width: <?= $tech['capacity'] ?>%"></div>
                            </div>
                            <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-tighter">
                                <span class="text-[var(--text-muted)]">Pendientes: <span
                                        class="text-medical-blue"><?= $tech['active'] ?? 0 ?></span></span>
                                <span class="text-[var(--text-muted)]">Cerradas: <span
                                        class="text-emerald-500"><?= $tech['completed'] ?? 0 ?></span></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 pt-6 border-t border-border-dark flex items-center justify-between">
                <div class="flex items-center gap-2 group cursor-pointer text-medical-blue">
                    <span
                        class="text-[10px] font-black uppercase tracking-[0.2em] group-hover:underline transition-all">Balancear
                        Carga Equitativa</span>
                    <span
                        class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">balance</span>
                </div>
                <div class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest italic">Saturación Media:
                    <?= getWorkloadSaturation() ?>%
                </div>
            </div>
        </div>

        <div class="lg:col-span-12 card-glass p-8">
            <h3 class="text-sm font-bold text-[var(--text-main)] uppercase tracking-wider mb-8">Efectividad Técnica</h3>
            <div class="h-[300px] w-full flex items-center justify-center">
                <?php if ($hasOTs): ?>
                    <canvas id="techChart"></canvas>
                <?php else: ?>
                    <div class="text-center opacity-50">
                        <span class="material-symbols-outlined text-4xl mb-2">trending_flat</span>
                        <p class="text-[10px] font-black uppercase tracking-widest">Sin registros de productividad</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Eventos Recientes -->
    <div class="card-glass p-8">
        <h3 class="text-sm font-bold text-[var(--text-main)] uppercase tracking-wider mb-8">Historial de Órdenes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($recentEvents as $event): ?>
                <div
                    class="relative pl-10 before:content-[''] before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-border-dark">
                    <div
                        class="absolute left-1 top-1 w-4 h-4 rounded-full bg-<?= $event['color_class'] ?> ring-4 ring-medical-surface shadow-xl shadow-<?= $event['color_class'] ?>/20">
                    </div>
                    <p class="text-sm font-bold text-[var(--text-main)]"><?= $event['title'] ?></p>
                    <p class="text-xs text-[var(--text-muted)] mt-1"><?= $event['subtitle'] ?></p>
                    <p class="text-[10px] text-[var(--text-muted)] font-black uppercase tracking-tighter mt-2"><?= $event['time'] ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Configuración común
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    };

    // 0. Curva de Probabilidad de Falla Exponencial F(t)
    <?php if ($hasCorrectives): ?>
        new Chart(document.getElementById('reliabilityCurveChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labelsCurva) ?>,
                datasets: [{
                    label: 'Probabilidad de Falla F(t) (%)',
                    data: <?= json_encode($puntosCurva) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(100, 116, 139, 0.05)'
                        },
                        ticks: {
                            color: 'var(--text-muted)',
                            font: {
                                weight: 'bold',
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: 'Tiempo Transcurrido (Días)',
                            color: 'var(--text-muted)',
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: 'rgba(100, 116, 139, 0.1)'
                        },
                        ticks: {
                            color: 'var(--text-muted)',
                            callback: function(value) {
                                return value + '%'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Probabilidad de Ocurrencia',
                            color: 'var(--text-muted)',
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Riesgo de Falla: ' + context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    // 4. Técnicos (Bar)
    <?php if ($hasOTs): ?>
        new Chart(document.getElementById('techChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($techComparisonData, 'name')) ?>,
                datasets: [{
                    label: 'Productividad',
                    data: <?= json_encode(array_column($techComparisonData, 'terminadas')) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'var(--text-muted)'
                        }
                    },
                    y: {
                        display: false
                    }
                }
            }
        });
    <?php endif; ?>
</script>