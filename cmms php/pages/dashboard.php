<?php
// pages/dashboard.php

// ── Control de Acceso ──
if (!canViewDashboard()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1><p class='text-[var(--text-muted)] mt-2'>Los técnicos no tienen acceso al Dashboard.</p></div>";
    return;
}

// Importar funciones de métricas de confiabilidad
require_once __DIR__ . '/../includes/reliability_metrics.php';
require_once __DIR__ . '/../Backend/Services/CatalogService.php';

// ── Backend Providers ──
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
require_once __DIR__ . '/../Backend/Providers/EventProvider.php';

// --- FILTRADO POR CLASE (CROSS-FILTERING) ---
$selectedClass = $_GET['class'] ?? 'all';

// --- CONFIGURACIÓN DE CACHÉ ---
$cacheEnabled = true;
$cacheFile = __DIR__ . '/../storage/reliability_cache.json';
$cacheTime = 3600; // 1 hora
$isCached = false;

// --- DATOS DESDE PROVIDERS ---
$assetsEntities = getAllAssets();
$assets = array_map(function ($a) {
    return $a instanceof \Backend\Models\AssetEntity ? $a->toArray() : (array)$a;
}, $assetsEntities);

// Aplicar filtro de clase si no es "all"
if ($selectedClass !== 'all') {
    $assets = array_filter($assets, function ($a) use ($selectedClass) {
        return ($a['riesgo_ge'] ?? ($a['criticality'] ?? 'Sin Clase')) === $selectedClass;
    });
}

$otCorrectivas = getCorrectiveWorkOrders();
$technicians = getTechnicianProductivity();
$recentEvents = getRecentEvents();

// --- CÁLCULO DINÁMICO DE MÉTRICAS - OPTIMIZADO CON CACHÉ ---
if ($cacheEnabled && $selectedClass === 'all' && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if ($cacheData) {
        $reliabilityByFamily = $cacheData['reliabilityByFamily'] ?? [];
        $claseGroups = $cacheData['claseGroups'] ?? [];
        $isCached = true;
    }
}
$totalEquipos = count($assets);

// Equipos por estado - OPTIMIZADO: pasar $assets
$statusCounts = countAssetsByStatus($assets);
$equiposOperativos = $statusCounts['operative'];
$equiposMantenimiento = $statusCounts['maintenance'];
$equiposNoOperativos = $statusCounts['no_operative'];
$equiposConObservaciones = $statusCounts['with_obs'];

// Estadísticas financieras - OPTIMIZADO: pasar $assets
$financialStats = getFinancialStats($assets);

$woCounts = countWorkOrdersByStatus();
$totalOT = $woCounts['total'];
$otTerminadas = $woCounts['Terminada'];
$otEnCurso = $woCounts['En Curso'] ?? 0;
$otEnEspera = $woCounts['En Espera'] ?? 0;

$otPorTipo = countWorkOrdersByType();

// --- CÁLCULO DE MÉTRICAS POR CLASE (ESPECIALIDAD) - OPTIMIZADO ---
if (!$isCached) {
    // Agrupar OTs por activo una sola vez en el Dashboard
    $otsPorActivo = [];
    foreach ($otCorrectivas as $ot) {
        if (!empty($ot['asset_id'])) {
            $otsPorActivo[$ot['asset_id']][] = $ot;
        }
    }

    $claseGroups = [];
    foreach ($assets as $asset) {
        // Agrupar por Especialidad (riesgo_ge) para coincidir con el Excel importado
        $claveGrupo = $asset['riesgo_ge'] ?? ($asset['criticality'] ?? 'Sin Clase');
        if (!isset($claseGroups[$claveGrupo])) {
            $claseGroups[$claveGrupo] = [
                'count'      => 0,
                'mtbf_sum'   => 0,
                'mttr_sum'   => 0,
                'valid_mtbf' => 0
            ];
        }

        $otsActivo = $otsPorActivo[$asset['id']] ?? [];
        $mtbf = calcularMTBF_Internal($asset['id'], $otsActivo);
        $mttr = calcularMTTR_Internal($asset['id'], $otsActivo);

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

    if ($cacheEnabled && $selectedClass === 'all') {
        @file_put_contents($cacheFile, json_encode([
            'reliabilityByFamily' => $reliabilityByFamily,
            'claseGroups' => $claseGroups
        ]));
    }
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

// Equipos por criticidad - OPTIMIZADO: pasar $assets
$critCounts = countAssetsByCriticality($assets);
$equiposCriticos = $critCounts['CRITICAL'];
$equiposRelevantes = $critCounts['RELEVANT'];

// --- CÁLCULO DE MÉTRICAS CLÍNICAS 2.0 ---
$totalAcquisitionValue = $financialStats['valor_inventario'] ?? 0;
$totalMaintenanceCost = $financialStats['costo_mantenimiento_anual'] ?? 0;
$cosr = $totalAcquisitionValue > 0 ? ($totalMaintenanceCost / $totalAcquisitionValue) * 100 : 0;

// KPIs calculados dinámicamente
$kpiCards = [
    [
        'label' => 'Disponibilidad',
        'value' => $metricasGlobales['disponibilidad_promedio'] > 0 ? round($metricasGlobales['disponibilidad_promedio'] * 100, 1) . '%' : 'N/A',
        'trend' => 'Uptime Clínico',
        'color' => $metricasGlobales['disponibilidad_promedio'] >= 0.95 ? 'border-l-emerald-500' : 'border-l-red-500',
        'icon' => 'health_and_safety',
        'sub' => 'Operatividad Real'
    ],
    [
        'label' => 'Fuera de Servicio',
        'value' => $equiposNoOperativos,
        'trend' => 'Inactividad',
        'color' => $equiposNoOperativos > 0 ? 'border-l-red-500' : 'border-l-emerald-500',
        'icon' => 'error',
        'sub' => 'Requieren Intervención'
    ],
    [
        'label' => 'Tiempo de Retorno',
        'value' => round($metricasGlobales['mttr_promedio'], 1) . ' h',
        'trend' => 'Meta < 4h',
        'color' => $metricasGlobales['mttr_promedio'] <= 4 ? 'border-l-emerald-500' : 'border-l-amber-500',
        'icon' => 'pacing',
        'sub' => 'MTTR Promedio'
    ],
    [
        'label' => 'Cobertura PM',
        'value' => getPMComplianceRate() . '%',
        'trend' => 'Cumplimiento',
        'color' => 'border-l-indigo-500',
        'icon' => 'event_available',
        'sub' => 'Mora: $' . number_format($financialStats['mantenimiento_mora'], 0, ',', '.') . ' CLP'
    ],
    [
        'label' => 'Valor Inventario',
        'value' => '$' . number_format($totalAcquisitionValue / 1000000, 1, ',', '.') . 'M',
        'trend' => 'CLP (Millones)',
        'color' => 'border-l-medical-blue',
        'icon' => 'inventory_2',
        'sub' => 'Valorización CAPEX'
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
    ['name' => 'Relevantes', 'value' => $equiposRelevantes, 'color' => '#0ea5e9'],
    ['name' => 'No Aplica', 'value' => $critCounts['LOW'] ?? 0, 'color' => '#94a3b8']
];

// Datos para gráfico de OT por tipo
$otPorTipoData = [
    ['name' => 'Preventivo', 'value' => $otPorTipo['Preventiva'] ?? 0],
    ['name' => 'Correctivo', 'value' => $otPorTipo['Correctiva'] ?? 0],
    ['name' => 'Calibración', 'value' => $otPorTipo['Calibración'] ?? 0]
];

// Datos de técnicos para gráfico
$techComparisonData = array_map(function ($t) {
    // Si el nombre empieza con Téc. o Ing., tomar la segunda palabra para más claridad
    $parts = explode(' ', $t['name']);
    $displayName = (count($parts) > 1 && in_array($parts[0], ['Téc.', 'Ing.', 'Técnico', 'Ingeniero'])) ? $parts[1] : $parts[0];

    return [
        'name' => $displayName,
        'terminadas' => $t['ot_terminadas']
    ];
}, $technicians);

// --- MÉTRICAS OPERATIVAS (FLOW 10) ---
$pmCoverage = getPMCoverageStats();
$mttrTrend = getMTTREvolutionData();
?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <?php
    $headerActions = '';
    if (canModify()) {
        $headerActions = '
            <button class="h-11 px-6 border border-border-color text-text-main rounded-xl text-sm font-bold hover:bg-medical-blue/10 flex items-center gap-2 transition-all active:scale-95 bg-medical-surface">
                <span class="material-symbols-outlined text-xl">file_download</span>
                Exportar Reporte
            </button>';
    }

    $preTitle = 'BioCMMS Engine';
    $title = SIDEBAR_DASHBOARD;
    $subTitle = 'Vista General Operativa';
    $icon = 'dashboard';
    $description = 'Análisis predictivo y gestión táctica del equipamiento biomédico.';
    $actions = $headerActions;
    include __DIR__ . '/../includes/components/header_master.php';
    ?>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <?php
        $kpiList = [
            [
                'label' => 'Continuidad',
                'value' => round($mtbf_global, 1) . ' d',
                'subValue' => 'MTBF',
                'icon' => 'timeline',
                'colorClass' => 'emerald-500',
                'trend' => 'Weibull'
            ],
            [
                'label' => 'COSR',
                'value' => round($cosr, 1) . '%',
                'subValue' => 'Mant.',
                'icon' => 'payments',
                'colorClass' => $cosr < 7 ? 'emerald-500' : 'amber-500',
                'trend' => 'Meta < 7%'
            ],
            [
                'label' => 'Uptime',
                'value' => $metricasGlobales['disponibilidad_promedio'] > 0 ? round($metricasGlobales['disponibilidad_promedio'] * 100, 1) . '%' : 'N/A',
                'subValue' => 'Real',
                'icon' => 'health_and_safety',
                'colorClass' => $metricasGlobales['disponibilidad_promedio'] >= 0.95 ? 'emerald-500' : 'red-500',
                'trend' => 'Clínico'
            ],
            [
                'label' => 'Vencidos',
                'value' => getExpiredOperativeCount(),
                'subValue' => 'Exced.',
                'icon' => 'history_toggle_off',
                'colorClass' => getExpiredOperativeCount() > 0 ? 'amber-500' : 'slate-400',
                'trend' => 'Audit'
            ],
            [
                'label' => 'Adherencia',
                'value' => getAdherenceRate() . '%',
                'subValue' => 'Mora',
                'icon' => 'check_circle',
                'colorClass' => 'indigo-500',
                'trend' => 'Meta > 90%'
            ]
        ];

        foreach ($kpiList as $k) {
            $label = $k['label'] ?? '';
            $value = $k['value'] ?? '';
            $subValue = $k['subValue'] ?? '';
            $icon = $k['icon'] ?? '';
            $colorClass = $k['colorClass'] ?? '';
            $trend = $k['trend'] ?? '';
            $description = $k['description'] ?? '';
            $extraClasses = $k['extraClasses'] ?? '';
            include __DIR__ . '/../includes/components/metric_card.php';
        }
        ?>
    </div>

    <!-- Control Operativo (Flow 10) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-glass p-6 border-l-4 border-l-indigo-500">
            <h3 class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">event_repeat</span>
                Cobertura de Mantenimiento Preventivo
            </h3>
            <div class="h-64">
                <canvas id="pmCoverageChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="p-2 bg-emerald-500/5 rounded-xl border border-emerald-500/10">
                    <p class="text-[9px] text-emerald-500 font-bold uppercase">Al Día</p>
                    <p class="text-xs font-black text-emerald-500"><?= $pmCoverage['al_dia'] ?></p>
                </div>
                <div class="p-2 bg-amber-500/5 rounded-xl border border-amber-500/10">
                    <p class="text-[9px] text-amber-500 font-bold uppercase">Atrasado</p>
                    <p class="text-xs font-black text-amber-500"><?= $pmCoverage['atrasado'] ?></p>
                </div>
                <div class="p-2 bg-slate-500/5 rounded-xl border border-slate-500/10">
                    <p class="text-[9px] text-slate-500 font-bold uppercase">Sin Plan</p>
                    <p class="text-xs font-black text-slate-500"><?= $pmCoverage['sin_plan'] ?></p>
                </div>
            </div>
        </div>
        <div class="card-glass p-6 border-l-4 border-l-medical-blue">
            <h3 class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">trending_up</span>
                Evolución de MTTR (Tiempo de Retorno)
            </h3>
            <div class="h-64">
                <canvas id="mttrTrendChart"></canvas>
            </div>
            <p class="text-[9px] text-text-muted italic mt-4 text-center uppercase tracking-widest">
                Media histórica de horas de reparación (Últimos 6 meses)
            </p>
        </div>
    </div>

    <!-- Distribution Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card-glass p-6">
            <h3 class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4">Estado del Parque</h3>
            <div class="h-44">
                <canvas id="estadoEquiposChart"></canvas>
            </div>
        </div>
        <div class="card-glass p-6">
            <h3 class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4">Criticidad de Activos</h3>
            <div class="h-44">
                <canvas id="criticidadChart"></canvas>
            </div>
        </div>
        <div class="card-glass p-6">
            <h3 class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4">Distribución de OTs</h3>
            <div class="h-44">
                <canvas id="otPorTipoChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts & Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Curva de Falla Weibull (Confiabilidad) -->
        <div class="lg:col-span-12 card-glass p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none overflow-hidden select-none">
                <span class="material-symbols-outlined text-6xl text-[var(--text-muted)]">analytics</span>
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
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-[var(--border-color)]">
                                <span class="text-[var(--text-muted)] font-bold uppercase tracking-widest">PROBABILIDAD FALLA (30D)</span>
                                <span class="text-amber-500 font-black"><?= $hasCorrectives ? round((1 - exp(-pow(30 / $eta, $beta))) * 100, 1) . '%' : '---' ?></span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-[var(--border-color)]">
                                <span class="text-[var(--text-muted)] font-bold uppercase tracking-widest">PROBABILIDAD FALLA (60D)</span>
                                <span class="text-red-500 font-black"><?= $hasCorrectives ? round((1 - exp(-pow(60 / $eta, $beta))) * 100, 1) . '%' : '---' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 rounded-2xl bg-medical-surface border border-[var(--border-color)]">
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
                        <tr class="border-b border-[var(--border-color)]/50">
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
                                <tr class="group hover:bg-medical-blue/5 transition-all">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="size-8 rounded-lg bg-medical-surface border border-[var(--border-color)] flex items-center justify-center">
                                                <span class="material-symbols-outlined text-sm text-medical-blue">medical_services</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-[var(--text-main)]"><?= $hrAsset['name'] ?></p>
                                                <p class="text-[9px] text-[var(--text-muted)] font-bold uppercase italic"><?= $hrAsset['id'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="px-2 py-0.5 rounded-md bg-medical-surface border border-[var(--border-color)] text-[10px] font-bold text-[var(--text-muted)] italic">
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
        <div class="lg:col-span-12 card-glass p-8 shadow-2xl relative overflow-hidden group border border-[var(--border-color)]">
            <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity overflow-hidden select-none">
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
                        class="px-3 py-1 bg-medical-surface rounded-lg text-[10px] font-black text-[var(--text-muted)] border border-[var(--border-color)] uppercase tracking-tighter">Semana
                        Actual</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                foreach ($technicians as $index => $tech):
                    $techCapacity = $tech['capacity'] ?? 0;
                    $statusColor = $techCapacity > 90 ? 'text-red-500' : ($techCapacity > 70 ? 'text-amber-500' : 'text-emerald-500');
                    $progressBarColor = $techCapacity > 90 ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]' : ($techCapacity > 70 ? 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]' : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]');

                    // Gamificación: Badge para top performance (Ej: SLA > 85% y MTTR < 4h)
                    $isTopPerformer = ($tech['sla_compliance'] >= 85 && $tech['mttr'] <= 4);
                ?>
                    <div
                        class="relative hover:bg-medical-blue/5 p-5 rounded-3xl transition-all border border-[var(--border-color)] bg-medical-surface shadow-sm group">

                        <?php if ($isTopPerformer): ?>
                            <div class="absolute -top-2 -right-2 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[8px] font-black px-2 py-1 rounded-full shadow-lg z-10 animate-bounce uppercase tracking-tighter">
                                <i class="fas fa-bolt mr-1"></i>Alta Eficiencia
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-10 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center text-white font-black text-sm shadow-xl group-hover:scale-110 transition-transform">
                                    <?= substr($tech['name'], 0, 1) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-black text-[var(--text-main)] truncate w-24"><?= $tech['name'] ?></h4>
                                    <p class="text-[8px] text-[var(--text-muted)] font-bold uppercase truncate"><?= $tech['specialty'] ?? 'Técnico Biomédico' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black <?= $statusColor ?>"><?= $techCapacity ?>%</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div class="p-2 bg-black/5 rounded-xl border border-white/5">
                                <p class="text-[7px] text-text-muted font-bold uppercase">MTTR (Media)</p>
                                <p class="text-xs font-black text-text-main"><?= $tech['mttr'] ?>h</p>
                            </div>
                            <div class="p-2 bg-black/5 rounded-xl border border-white/5">
                                <p class="text-[7px] text-text-muted font-bold uppercase">SLA (Calidad)</p>
                                <p class="text-xs font-black text-emerald-500"><?= $tech['sla_compliance'] ?>%</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="h-1.5 w-full bg-slate-200 dark:bg-white/5 rounded-full overflow-hidden border border-[var(--border-color)]">
                                <div class="h-full <?= $progressBarColor ?> transition-all duration-1000" style="width: <?= $techCapacity ?>%"></div>
                            </div>
                            <div class="flex justify-between items-center text-[8px] font-black uppercase tracking-tighter">
                                <span class="text-text-muted">Activas: <span class="text-text-main"><?= $tech['active'] ?? 0 ?></span></span>
                                <span class="text-text-muted">Cerradas: <span class="text-emerald-500"><?= $tech['ot_terminadas'] ?></span></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 pt-6 border-t border-[var(--border-color)] flex items-center justify-between">
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

        <!-- Matriz de Efectividad (Radar Chart) -->
        <div class="lg:col-span-12 card-glass p-8 group overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-text-main uppercase tracking-[0.2em]">Efectividad Técnica</h3>
                    <p class="text-[10px] text-text-muted font-bold uppercase tracking-widest mt-1">Comparativa de Desempeño</p>
                </div>
                <!-- Comparison Toggles -->
                <div class="flex bg-medical-dark p-1 rounded-xl border border-border-color" id="techCompareToggles">
                    <button onclick="setTechView('all')" id="btn-view-all" class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-tighter transition-all bg-medical-blue text-white shadow-lg shadow-medical-blue/20">Todos</button>
                    <button onclick="setTechView('duo')" id="btn-view-duo" class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-tighter transition-all text-text-muted hover:text-text-main">Dúo</button>
                    <button onclick="setTechView('solo')" id="btn-view-solo" class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-tighter transition-all text-text-muted hover:text-text-main">Solo</button>
                </div>
            </div>

            <div class="h-[400px] flex items-center justify-center relative">
                <canvas id="techChart"></canvas>

                <!-- Overlay for specific tech selection (Dynamic) -->
                <div id="techSelectorOverlay" class="absolute inset-0 bg-medical-surface/80 backdrop-blur-sm flex flex-col items-center justify-center gap-4 opacity-0 pointer-events-none transition-opacity duration-300 z-20 rounded-2xl">
                    <p class="text-xs font-black text-text-main uppercase tracking-widest">Selecciona Técnicos para Comparar</p>
                    <div class="flex flex-wrap justify-center gap-2 px-8">
                        <?php
                        $topTechsList = array_slice($technicians, 0, 5); // Allow selecting from top 5
                        foreach ($topTechsList as $idx => $t): ?>
                            <button onclick="toggleTechSelection(<?= $idx ?>)" data-idx="<?= $idx ?>"
                                class="tech-select-pill px-4 py-2 rounded-xl border border-border-color bg-medical-dark text-[10px] font-bold text-text-muted hover:border-medical-blue transition-all">
                                <?= htmlspecialchars($t['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <button onclick="applyTechSelection()" class="mt-4 px-8 py-2 bg-medical-blue text-white rounded-xl text-[10px] font-black uppercase shadow-lg shadow-medical-blue/20 transform hover:scale-105 active:scale-95 transition-all">Comparar Ahora</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // --- State Management ---
    let techViewMode = 'all'; // all, duo, solo
    let selectedTechIndices = [];
    const allTechDataRaw = <?= json_encode($technicians) ?>;

    // --- Equipment Class Cross-Filtering ---
    function updateDashboardFilters() {
        const selectedClass = document.getElementById('masterClassFilter').value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('class', selectedClass);
        window.location.href = currentUrl.toString();
    }

    // --- Technician Comparison Logic ---
    function setTechView(mode) {
        techViewMode = mode;

        // Update UI Toggles
        const btns = ['all', 'duo', 'solo'];
        btns.forEach(b => {
            const el = document.getElementById(`btn-view-${b}`);
            if (b === mode) {
                el.classList.add('bg-medical-blue', 'text-white', 'shadow-lg', 'shadow-medical-blue/20');
                el.classList.remove('text-text-muted', 'hover:text-text-main');
            } else {
                el.classList.remove('bg-medical-blue', 'text-white', 'shadow-lg', 'shadow-medical-blue/20');
                el.classList.add('text-text-muted', 'hover:text-text-main');
            }
        });

        if (mode === 'all') {
            hideTechSelector();
            updateTechChart(allTechDataRaw.slice(0, 3));
        } else {
            showTechSelector();
        }
    }

    function showTechSelector() {
        const overlay = document.getElementById('techSelectorOverlay');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        selectedTechIndices = [];
        updatePills();
    }

    function hideTechSelector() {
        const overlay = document.getElementById('techSelectorOverlay');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.classList.remove('opacity-100', 'pointer-events-auto');
    }

    function toggleTechSelection(idx) {
        const limit = techViewMode === 'duo' ? 2 : 1;
        const pos = selectedTechIndices.indexOf(idx);

        if (pos > -1) {
            selectedTechIndices.splice(pos, 1);
        } else {
            if (selectedTechIndices.length >= limit) {
                selectedTechIndices.shift();
            }
            selectedTechIndices.push(idx);
        }
        updatePills();
    }

    function updatePills() {
        document.querySelectorAll('.tech-select-pill').forEach(pill => {
            const idx = parseInt(pill.dataset.idx);
            if (selectedTechIndices.includes(idx)) {
                pill.classList.add('border-medical-blue', 'bg-medical-blue/10', 'text-medical-blue');
                pill.classList.remove('text-text-muted', 'bg-medical-dark');
            } else {
                pill.classList.remove('border-medical-blue', 'bg-medical-blue/10', 'text-medical-blue');
                pill.classList.add('text-text-muted', 'bg-medical-dark');
            }
        });
    }

    function applyTechSelection() {
        const limit = techViewMode === 'duo' ? 2 : 1;
        if (selectedTechIndices.length < limit) {
            alert(`Selecciona exactamente ${limit} técnico(s) para comparar.`);
            return;
        }

        const filteredData = allTechDataRaw.filter((_, idx) => selectedTechIndices.includes(idx));
        hideTechSelector();
        updateTechChart(filteredData);
    }

    function updateTechChart(data) {
        const ctx = document.getElementById('techChart');
        if (!ctx) return;

        let chart = Chart.getChart('techChart');
        const colors = [{
                r: 59,
                g: 130,
                b: 246
            },
            {
                r: 16,
                g: 185,
                b: 129
            },
            {
                r: 245,
                g: 158,
                b: 11
            }
        ];

        const datasets = data.map((t, i) => {
            const speed = Math.max(0, Math.min(100, 100 - (t.mttr * 10)));
            const volume = Math.max(0, Math.min(100, (t.ot_terminadas / 20) * 100));
            const sla = t.sla_compliance || 0;
            const pro = t.prev_ratio || 0;
            const avail = t.capacity || 0;
            const color = colors[i % colors.length];

            return {
                label: t.name,
                data: [speed, volume, sla, pro, avail],
                backgroundColor: `rgba(${color.r}, ${color.g}, ${color.b}, 0.2)`,
                borderColor: `rgb(${color.r}, ${color.g}, ${color.b})`,
                borderWidth: 3,
                pointBackgroundColor: `rgb(${color.r}, ${color.g}, ${color.b})`,
                pointBorderColor: '#fff',
                pointRadius: 4,
                fill: true
            };
        });

        if (chart) {
            chart.data.datasets = datasets;
            chart.update();
        } else {
            const isDark = document.documentElement.classList.contains('dark');
            const mainText = isDark ? '#f1f5f9' : '#0f172a';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Velocidad', 'Volumen', 'SLA', 'Proactividad', 'Disponibilidad'],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                color: isDark ? '#cbd5e1' : '#334155',
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                },
                                padding: 15
                            }
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                color: gridColor
                            },
                            grid: {
                                color: gridColor
                            },
                            pointLabels: {
                                color: mainText,
                                font: {
                                    size: 12,
                                    weight: '800'
                                }
                            },
                            ticks: {
                                display: false,
                                stepSize: 20
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });
        }
    }
    window.addEventListener('load', function() {
        console.log("BioCMMS: Iniciando dashboard analytics...");
        if (typeof Chart === 'undefined') {
            console.error('BioCMMS Error: Chart.js failed to load.');
            return;
        }

        // Detección de tema para colores de alto contraste
        const isDark = document.documentElement.classList.contains('dark');
        const mainText = isDark ? '#f1f5f9' : '#0f172a'; // Slate 900 for Light
        const mutedText = isDark ? '#cbd5e1' : '#334155'; // Slate 700 for Light
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

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

        // 0.1 Cobertura de Mantenimiento Preventivo (Doughnut) - FLOW 10
        try {
            const ctxPM = document.getElementById('pmCoverageChart');
            if (ctxPM) {
                new Chart(ctxPM, {
                    type: 'doughnut',
                    data: {
                        labels: ['Al Día', 'Atrasado', 'Sin Plan'],
                        datasets: [{
                            data: [<?= $pmCoverage['al_dia'] ?>, <?= $pmCoverage['atrasado'] ?>, <?= $pmCoverage['sin_plan'] ?>],
                            backgroundColor: ['#10b981', '#f59e0b', '#94a3b8'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: Object.assign({}, commonOptions, {
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    color: mutedText,
                                    font: { size: 10, weight: 'bold' }
                                }
                            }
                        }
                    })
                });
            }
        } catch (e) { console.warn("Error en pmCoverageChart:", e); }

        // 0.2 Evolución de MTTR (Line Chart) - FLOW 10
        try {
            const ctxMTTR = document.getElementById('mttrTrendChart');
            if (ctxMTTR) {
                new Chart(ctxMTTR, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_column($mttrTrend, 'mes')) ?>,
                        datasets: [{
                            label: 'MTTR (Hrs)',
                            data: <?= json_encode(array_column($mttrTrend, 'mttr')) ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#3b82f6'
                        }]
                    },
                    options: Object.assign({}, commonOptions, {
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: mutedText, font: { weight: 'bold' } },
                                title: { display: true, text: 'Horas', color: mutedText, font: { size: 10 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: mutedText, font: { weight: 'bold' } }
                            }
                        }
                    })
                });
            }
        } catch (e) { console.warn("Error en mttrTrendChart:", e); }

        // 0. Curva de Probabilidad de Falla Exponencial F(t)
        try {
            const ctx0 = document.getElementById('reliabilityCurveChart');
            if (ctx0) {
                <?php if ($hasCorrectives): ?>
                    new Chart(ctx0, {
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
                        options: Object.assign({}, commonOptions, {
                            scales: {
                                x: {
                                    grid: {
                                        color: gridColor
                                    },
                                    ticks: {
                                        color: mutedText,
                                        font: {
                                            weight: 'bold',
                                            size: 10
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Tiempo Transcurrido (Días)',
                                        color: mutedText,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y: {
                                    min: 0,
                                    max: 100,
                                    grid: {
                                        color: gridColor
                                    },
                                    ticks: {
                                        color: mutedText,
                                        callback: function(value) {
                                            return value + '%'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Probabilidad de Ocurrencia',
                                        color: mutedText,
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
                        })
                    });
                <?php endif; ?>
            }
        } catch (e) {
            console.warn("Error en reliabilityCurveChart:", e);
        }


        // 4. Efectividad Técnica (Radar) - Inicialización
        updateTechChart(allTechDataRaw.slice(0, 3));

        // 5. Estado de Equipos (Doughnut)
        try {
            const ctx5 = document.getElementById('estadoEquiposChart');
            if (ctx5) {
                new Chart(ctx5, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_column($estadoEquiposData, 'name')) ?>,
                        datasets: [{
                            data: <?= json_encode(array_column($estadoEquiposData, 'value')) ?>,
                            backgroundColor: <?= json_encode(array_column($estadoEquiposData, 'color')) ?>,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: Object.assign({}, commonOptions, {
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        onClick: (e, activeEls) => {
                            if (activeEls.length > 0) {
                                const index = activeEls[0].index;
                                const label = e.chart.data.labels[index];
                                const mapping = {
                                    'Operativos': 'OPERATIVE',
                                    'Mantenimiento': 'MAINTENANCE',
                                    'No Operativos': 'NO_OPERATIVE',
                                    'Con Observaciones': 'OPERATIVE_WITH_OBS'
                                };
                                const status = mapping[label] || 'ALL';
                                window.location.href = '?page=inventory&status=' + status;
                            }
                        },
                        onHover: (event, chartElement) => {
                            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                        }
                    })
                });
            }
        } catch (e) {
            console.warn("Error en estadoEquiposChart:", e);
        }

        // 6. Criticidad (Pie)
        try {
            const ctx6 = document.getElementById('criticidadChart');
            if (ctx6) {
                new Chart(ctx6, {
                    type: 'pie',
                    data: {
                        labels: <?= json_encode(array_column($criticidadData, 'name')) ?>,
                        datasets: [{
                            data: <?= json_encode(array_column($criticidadData, 'value')) ?>,
                            backgroundColor: <?= json_encode(array_column($criticidadData, 'color')) ?>,
                            borderWidth: 0
                        }]
                    },
                    options: Object.assign({}, commonOptions, {
                        onClick: (e, activeEls) => {
                            if (activeEls.length > 0) {
                                const index = activeEls[0].index;
                                const label = e.chart.data.labels[index];
                                const mapping = {
                                    'Críticos': 'CRITICAL',
                                    'Relevantes': 'RELEVANT',
                                    'Baja': 'LOW',
                                    'No Aplica': 'NA'
                                };
                                const crit = mapping[label] || 'ALL';
                                window.location.href = '?page=inventory&criticality=' + crit;
                            }
                        },
                        onHover: (event, chartElement) => {
                            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                        }
                    })
                });
            }
        } catch (e) {
            console.warn("Error en criticidadChart:", e);
        }

        // 7. OTs por Tipo (Horizontal Bar)
        try {
            const ctx7 = document.getElementById('otPorTipoChart');
            if (ctx7) {
                new Chart(ctx7, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($otPorTipoData, 'name')) ?>,
                        datasets: [{
                            label: 'Cantidad',
                            data: <?= json_encode(array_column($otPorTipoData, 'value')) ?>,
                            backgroundColor: '#0ea5e9',
                            borderRadius: 6
                        }]
                    },
                    options: Object.assign({}, commonOptions, {
                        indexAxis: 'y',
                        onClick: (e, activeEls) => {
                            if (activeEls.length > 0) {
                                const index = activeEls[0].index;
                                const label = e.chart.data.labels[index];
                                window.location.href = '?page=work_orders&search=' + encodeURIComponent(label);
                            }
                        },
                        onHover: (event, chartElement) => {
                            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: mainText,
                                    font: {
                                        size: 10,
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    })
                });
            }
        } catch (e) {
            console.warn("Error en otPorTipoChart:", e);
        }

    });
</script>