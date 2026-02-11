<?php
// pages/dashboard.php

// Importar funciones de métricas de confiabilidad
require_once __DIR__ . '/../includes/reliability_metrics.php';

// --- IMPORTAR DATOS DEL INVENTARIO ---
// En producción, esto vendría de la base de datos
// Por ahora, replicamos el array de inventory.php
$assets = [
    [
        'id' => 'PB-840-00122',
        'name' => 'Ventilador Mecánico',
        'brand' => 'Puritan Bennett',
        'model' => '840',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE',
        'acquisitionCost' => 45000,
        'purchaseYear' => 2018,
        'totalUsefulLife' => 10,
        'yearsRemaining' => 4,
        'funcion' => 10,
        'riesgo' => 5,
        'mantenimiento' => 4
    ],
    [
        'id' => 'AL-8015-994',
        'name' => 'Bomba de Infusión',
        'brand' => 'Alaris',
        'model' => '8015',
        'criticality' => 'RELEVANT',
        'status' => 'MAINTENANCE',
        'acquisitionCost' => 3500,
        'purchaseYear' => 2021,
        'totalUsefulLife' => 7,
        'yearsRemaining' => 4,
        'funcion' => 8,
        'riesgo' => 4,
        'mantenimiento' => 3
    ],
    [
        'id' => 'ZL-X-44211',
        'name' => 'Desfibrilador',
        'brand' => 'Zoll',
        'model' => 'Series X',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE',
        'acquisitionCost' => 18000,
        'purchaseYear' => 2023,
        'totalUsefulLife' => 8,
        'yearsRemaining' => 7,
        'funcion' => 10,
        'riesgo' => 5,
        'mantenimiento' => 3
    ],
    [
        'id' => 'ECG-2024-001',
        'name' => 'Electrocardiógrafo',
        'brand' => 'Philips',
        'model' => 'PageWriter TC70',
        'criticality' => 'RELEVANT',
        'status' => 'OPERATIVE_WITH_OBS',
        'acquisitionCost' => 12000,
        'purchaseYear' => 2014,
        'totalUsefulLife' => 12,
        'yearsRemaining' => 2,
        'funcion' => 5,
        'riesgo' => 2,
        'mantenimiento' => 3
    ]
];

// Datos de OT correctivas (en producción vendría de BD)
$otCorrectivas = [
    // Ventilador PB-840-00122
    ['equipo_id' => 'PB-840-00122', 'fecha' => '2023-06-15', 'duracion_horas' => 2.5],
    ['equipo_id' => 'PB-840-00122', 'fecha' => '2024-01-20', 'duracion_horas' => 3.0],

    // Bomba AL-8015-994
    ['equipo_id' => 'AL-8015-994', 'fecha' => '2023-08-10', 'duracion_horas' => 1.5],
    ['equipo_id' => 'AL-8015-994', 'fecha' => '2024-02-05', 'duracion_horas' => 2.0],

    // Desfibrilador ZL-X-44211
    ['equipo_id' => 'ZL-X-44211', 'fecha' => '2023-05-20', 'duracion_horas' => 4.0],
    ['equipo_id' => 'ZL-X-44211', 'fecha' => '2024-01-15', 'duracion_horas' => 3.5],

    // ECG
    ['equipo_id' => 'ECG-2024-001', 'fecha' => '2023-09-10', 'duracion_horas' => 1.0],
    ['equipo_id' => 'ECG-2024-001', 'fecha' => '2024-02-01', 'duracion_horas' => 1.5]
];


// --- CÁLCULO DINÁMICO DE MÉTRICAS ---

// Total de equipos
$totalEquipos = count($assets);

// Equipos por estado (usando array_filter)
$equiposOperativos = count(array_filter($assets, fn($a) => $a['status'] === STATUS_OPERATIVE));
$equiposMantenimiento = count(array_filter($assets, fn($a) => $a['status'] === STATUS_MAINTENANCE));
$equiposNoOperativos = count(array_filter($assets, fn($a) => $a['status'] === STATUS_NO_OPERATIVE));
$equiposConObservaciones = count(array_filter($assets, fn($a) => $a['status'] === STATUS_OPERATIVE_WITH_OBS));

// Equipos por criticidad
$equiposCriticos = count(array_filter($assets, fn($a) => $a['criticality'] === 'CRITICAL'));
$equiposRelevantes = count(array_filter($assets, fn($a) => $a['criticality'] === 'RELEVANT'));

// Técnicos reales del sistema (de login.php)
$technicians = [
    [
        'name' => 'Ana Muñoz',
        'role' => 'Ingeniero Sr.',
        'initial' => 'AM',
        'xp' => 5420,
        'level' => 18,
        'badges' => ['military_tech', 'psychology', 'workspace_premium'],
        'otTerminadas' => 22,
        'trend' => 'up'
    ],
    [
        'name' => 'Mario Gómez',
        'role' => 'Especialista',
        'initial' => 'MG',
        'xp' => 4150,
        'level' => 14,
        'badges' => ['bolt', 'verified'],
        'otTerminadas' => 12,
        'trend' => 'up'
    ],
    [
        'name' => 'Pablo Rojas',
        'role' => 'Técnico de Campo',
        'initial' => 'PR',
        'xp' => 2840,
        'level' => 9,
        'badges' => ['schedule'],
        'otTerminadas' => 8,
        'trend' => 'stable'
    ]
];

// Re-ordenar por XP para el ranking de gamificación
usort($technicians, fn($a, $b) => $b['xp'] - $a['xp']);

// --- CÁLCULO DE MÉTRICAS CLÍNICAS 2.0 ---
$totalAcquisitionValue = array_sum(array_column($assets, 'acquisitionCost'));
$totalMaintenanceCost = 12500; // Mock de costos de mantenimiento acumulados
$cosr = $totalAcquisitionValue > 0 ? ($totalMaintenanceCost / $totalAcquisitionValue) * 100 : 0;

// Riesgo de Capital (Equipos a renovar pronto)
$equiposRiesgoCapital = count(array_filter($assets, fn($a) => ($a['yearsRemaining'] / $a['totalUsefulLife']) < 0.2));

// Órdenes de trabajo
$totalOT = 4;
$otTerminadas = 3;
$otEnProceso = 1;
$otPendientes = 0;

$otPorTipo = [
    'Preventivo' => 2,
    'Correctivo' => 1,
    'Calibración' => 1
];

// --- CÁLCULO DE MÉTRICAS POR FAMILIA PARA EL GRÁFICO GLOBAL ---
$familias = [];
foreach ($assets as $asset) {
    if (!isset($familias[$asset['name']])) {
        $familias[$asset['name']] = [
            'count' => 0,
            'mtbf_sum' => 0,
            'mttr_sum' => 0,
            'valid_mtbf' => 0
        ];
    }

    $mtbf = calcularMTBF($asset['id'], $otCorrectivas);
    $mttr = calcularMTTR($asset['id'], $otCorrectivas);

    $familias[$asset['name']]['count']++;
    $familias[$asset['name']]['mttr_sum'] += $mttr;
    if ($mtbf !== null) {
        $familias[$asset['name']]['mtbf_sum'] += $mtbf;
        $familias[$asset['name']]['valid_mtbf']++;
    }
}

$reliabilityByFamily = [];
foreach ($familias as $nombre => $datos) {
    $reliabilityByFamily[] = [
        'name' => $nombre,
        'mtbf' => $datos['valid_mtbf'] > 0 ? round($datos['mtbf_sum'] / $datos['valid_mtbf'], 1) : 0,
        'mttr' => round($datos['mttr_sum'] / $datos['count'], 1)
    ];
}

// --- CÁLCULO DE MÉTRICAS DE CONFIABILIDAD (WEIBULL) ---

// Calcular métricas globales
$metricasGlobales = calcularMetricasGlobales($assets, $otCorrectivas);

// --- GENERAR DATOS PARA LA CURVA DE PROBABILIDAD DE FALLA ACUMULADA F(t) ---
// F(t) = 1 - e^-(t/eta)^beta (Distribución Weibull)
$mtbf_global = $metricasGlobales['mtbf_promedio'] > 0 ? $metricasGlobales['mtbf_promedio'] : 30;
$beta = 1.45; // Simulación de fase de desgaste inicial
$eta = $mtbf_global / gamma_approx(1 + 1 / $beta);

function gamma_approx($n)
{
    // Aproximación de Stirling para la función Gamma en la UI
    return sqrt(2 * M_PI / $n) * pow($n / exp(1), $n);
}

$puntosCurva = [];
$labelsCurva = [];
for ($t = 0; $t <= 90; $t += 5) {
    $labelsCurva[] = $t . " d";
    $puntosCurva[] = round((1 - exp(-pow($t / $eta, $beta))) * 100, 1);
}

// Calcular GE para equipos críticos
$equiposCriticosGE = count(array_filter($assets, fn($a) => calcularGE($a) >= 12));

// KPIs calculados dinámicamente con enfoque Clínico 2.0
$kpiCards = [
    [
        'label' => 'Valor Inventario',
        'value' => '$' . number_format($totalAcquisitionValue / 1000, 0) . 'k',
        'trend' => 'CAPEX',
        'color' => 'border-l-medical-blue',
        'icon' => 'inventory_2',
        'sub' => 'Activos Totales'
    ],
    [
        'label' => 'MTBF (Weibull)',
        'value' => round($mtbf_global, 1) . ' d',
        'trend' => '$\beta=' . $beta . '$',
        'color' => 'border-l-emerald-500',
        'icon' => 'timeline',
        'sub' => 'Fase de Desgaste'
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
        'label' => 'Riesgo Capital',
        'value' => $equiposRiesgoCapital,
        'trend' => 'Obsolescencia',
        'color' => $equiposRiesgoCapital > 0 ? 'border-l-red-500' : 'border-l-slate-400',
        'icon' => 'error_med',
        'sub' => 'Equipos < 20% Vida'
    ],
    [
        'label' => 'Adherencia',
        'value' => '92%',
        'trend' => 'Gamification',
        'color' => 'border-l-indigo-500',
        'icon' => 'sports_esports',
        'sub' => 'Cierre de OT'
    ]
];

// Datos para gráfico de estado de equipos (calculados dinámicamente)
$estadoEquiposData = [
    ['name' => 'Operativos', 'value' => $equiposOperativos, 'color' => '#10b981'],
    ['name' => 'Mantenimiento', 'value' => $equiposMantenimiento, 'color' => '#f59e0b'],
    ['name' => 'Con Observaciones', 'value' => $equiposConObservaciones, 'color' => '#eab308']
];

// Solo incluir "No Operativos" si hay alguno
if ($equiposNoOperativos > 0) {
    $estadoEquiposData[] = ['name' => 'No Operativos', 'value' => $equiposNoOperativos, 'color' => '#ef4444'];
}

// Datos para gráfico de criticidad (calculados dinámicamente)
$criticidadData = [
    ['name' => 'Críticos', 'value' => $equiposCriticos, 'color' => '#ef4444'],
    ['name' => 'Relevantes', 'value' => $equiposRelevantes, 'color' => '#0ea5e9']
];

// Datos para gráfico de OT por tipo
$otPorTipoData = [
    ['name' => 'Preventivo', 'value' => $otPorTipo['Preventivo']],
    ['name' => 'Correctivo', 'value' => $otPorTipo['Correctivo']],
    ['name' => 'Calibración', 'value' => $otPorTipo['Calibración']]
];

// Eventos recientes (basados en OT reales)
$recentEvents = [
    [
        'id' => 'OT-2024-001',
        'title' => 'OT-2024-001 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Preventivo | Téc. Mario Gómez',
        'time' => 'Hace 15 días',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ],
    [
        'id' => 'OT-2024-015',
        'title' => 'OT-2024-015 En Proceso',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Correctivo | Téc. Pablo Rojas',
        'time' => 'Hace 5 días',
        'type' => 'warning',
        'colorClass' => 'amber-500'
    ],
    [
        'id' => 'OT-2023-089',
        'title' => 'OT-2023-089 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Calibración | Téc. Ana Muñoz',
        'time' => 'Hace 2 meses',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ],
    [
        'id' => 'OT-2023-045',
        'title' => 'OT-2023-045 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Preventivo | Téc. Mario Gómez',
        'time' => 'Hace 5 meses',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ]
];

// Calcular datos para gráfico de técnicos (Gamification focus)
$techComparisonData = array_map(function ($t) {
    return [
        'name' => explode(' ', $t['name'])[0],
        'terminadas' => $t['otTerminadas'],
        'xp' => $t['xp'] / 100 // Escalar XP para visualización en barra
    ];
}, $technicians);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Interno -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight"><?= SIDEBAR_DASHBOARD ?></h1>
            <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider font-bold">Vista General Operativa</p>
        </div>
        <?php if (canModify()): ?>
            <div class="flex gap-3">
                <button class="h-11 px-6 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-800 flex items-center gap-2 transition-all active:scale-95">
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
                    <span class="material-symbols-outlined text-slate-400 text-lg font-variation-fill"><?= $kpi['icon'] ?></span>
                    <span class="text-[9px] font-black px-2 py-0.5 rounded <?= $idx === 3 ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500' ?>">
                        <?= $kpi['trend'] ?>
                    </span>
                </div>
                <p class="stat-label"><?= $kpi['label'] ?></p>
                <h3 class="stat-value"><?= $kpi['value'] ?></h3>
                <p class="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium"><?= $kpi['sub'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts & Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Curva de Falla Weibull (Confiabilidad) -->
        <div class="lg:col-span-12 card-glass p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                <span class="material-symbols-outlined text-9xl">analytics</span>
            </div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-white uppercase tracking-[0.2em]">Modelado de Fallas (Weibull)</h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">
                        Probabilidad Acumulada $F(t) = 1 - e^{-(t/\eta)^\beta}$
                    </p>
                </div>
                <div class="flex gap-4">
                    <div class="px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                        <span class="text-[10px] font-black text-amber-500 uppercase">Beta: <?= $beta ?> (Desgaste)</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <div class="md:col-span-8 h-[350px]">
                    <canvas id="reliabilityCurveChart"></canvas>
                </div>
                <div class="md:col-span-4 space-y-6">
                    <div class="card-glass bg-amber-500/5 p-6 border border-amber-500/10">
                        <h4 class="text-[11px] font-black text-amber-500 uppercase mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                            Pronóstico de Confiabilidad
                        </h4>
                        <p class="text-sm text-slate-300 leading-relaxed mb-4">
                            Predicción basada en fase de <b>desgaste inicial</b> ($\beta > 1$) para el parque tecnológico crítico.
                        </p>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-white/5">
                                <span class="text-slate-500 font-bold uppercase tracking-widest">PROBABILIDAD FALLA (30D)</span>
                                <span class="text-amber-500 font-black"><?= round((1 - exp(-pow(30 / $eta, $beta))) * 100, 1) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] py-2 border-b border-white/5">
                                <span class="text-slate-500 font-bold uppercase tracking-widest">PROBABILIDAD FALLA (60D)</span>
                                <span class="text-red-500 font-black"><?= round((1 - exp(-pow(60 / $eta, $beta))) * 100, 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-700/50">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-medical-blue text-sm">lightbulb</span>
                            <p class="text-[10px] text-medical-blue font-black uppercase tracking-widest">Recomendación Clínico 2.0</p>
                        </div>
                        <p class="text-[11px] text-slate-400 italic">"Dada la pendiente de la curva, se sugiere adelantar mantenimientos preventivos en ventilación mecánica para mitigar el riesgo de correctivos en el próximo trimestre."</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribución de Carga de Trabajo Técnicos -->
        <div class="lg:col-span-12 card-glass p-8 shadow-2xl relative overflow-hidden group border border-white/5">
            <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                <span class="material-symbols-outlined text-6xl">engineering</span>
            </div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-sm font-black text-white uppercase tracking-[0.2em] italic flex items-center gap-3">
                        <span class="size-2 bg-医-blue rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                        Carga de Trabajo del Equipo
                    </h3>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1">Análisis de capacidad y OTs activas</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-white/5 rounded-lg text-[10px] font-black text-slate-400 border border-white/10 uppercase tracking-tighter">Semana Actual</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                $techWorkload = [
                    ['name' => 'Carlos Rodriguez', 'role' => 'Ing. Clínico Sr.', 'active' => 8, 'completed' => 12, 'capacity' => 85, 'initial' => 'CR'],
                    ['name' => 'Ana Martínez', 'role' => 'Técnico Biomédico', 'active' => 3, 'completed' => 15, 'capacity' => 45, 'initial' => 'AM'],
                    ['name' => 'Roberto Paiva', 'role' => 'Ing. Electrónico', 'active' => 11, 'completed' => 5, 'capacity' => 95, 'initial' => 'RP'],
                    ['name' => 'Elena Solís', 'role' => 'Técnico Especialista', 'active' => 5, 'completed' => 10, 'capacity' => 60, 'initial' => 'ES'],
                ];

                foreach ($techWorkload as $tech):
                    $statusColor = $tech['capacity'] > 90 ? 'text-red-500' : ($tech['capacity'] > 70 ? 'text-amber-500' : 'text-emerald-400');
                    $progressBarColor = $tech['capacity'] > 90 ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]' : ($tech['capacity'] > 70 ? 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]' : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]');
                ?>
                    <div class="relative hover:bg-white/5 p-5 rounded-2xl transition-all border border-white/5 hover:border-white/10 bg-white/[0.02]">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-white font-black text-sm shadow-xl">
                                    <?= $tech['initial'] ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-black text-white truncate w-24"><?= $tech['name'] ?></h4>
                                    <p class="text-[8px] text-slate-500 font-bold uppercase truncate"><?= $tech['role'] ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black <?= $statusColor ?>"><?= $tech['capacity'] ?>%</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden border border-white/5">
                                <div class="h-full <?= $progressBarColor ?> transition-all duration-1000" style="width: <?= $tech['capacity'] ?>%"></div>
                            </div>
                            <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-tighter">
                                <span class="text-slate-500">Pendientes: <span class="text-医-blue"><?= $tech['active'] ?></span></span>
                                <span class="text-slate-500">Cerradas: <span class="text-emerald-500"><?= $tech['completed'] ?></span></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-2 group cursor-pointer text-医-blue">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] group-hover:underline transition-all">Balancear Carga Equitativa</span>
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">balance</span>
                </div>
                <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest italic">Saturación Media: 71%</div>
            </div>
        </div>

        <!-- Comparación Técnicos (Bar) -->
        <div class="lg:col-span-5 card-glass p-8">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-8">Efectividad Técnica</h3>
            <div class="h-[300px] w-full">
                <canvas id="techChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Eventos Recientes -->
    <div class="card-glass p-8">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-8">Historial de Órdenes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($recentEvents as $event): ?>
                <div class="relative pl-10 before:content-[''] before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-slate-700/50">
                    <div class="absolute left-1 top-1 w-4 h-4 rounded-full bg-<?= $event['colorClass'] ?> ring-4 ring-medical-dark shadow-xl shadow-<?= $event['colorClass'] ?>/20"></div>
                    <p class="text-sm font-bold text-white"><?= $event['title'] ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= $event['subtitle'] ?></p>
                    <p class="text-[10px] text-slate-600 font-black uppercase tracking-tighter mt-2"><?= $event['time'] ?></p>
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
                        color: '#64748b',
                        font: {
                            weight: 'bold',
                            size: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Tiempo Transcurrido (Días)',
                        color: '#64748b',
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
                        color: '#64748b',
                        callback: function(value) {
                            return value + '%'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Probabilidad de Ocurrencia',
                        color: '#64748b',
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

    // 1. Estado de Equipos (Doughnut)
    new Chart(document.getElementById('estadoChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($estadoEquiposData, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($estadoEquiposData, 'value')) ?>,
                backgroundColor: <?= json_encode(array_column($estadoEquiposData, 'color')) ?>,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            ...commonOptions,
            cutout: '75%'
        }
    });

    // 2. Criticidad (Bar)
    new Chart(document.getElementById('criticidadChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($criticidadData, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($criticidadData, 'value')) ?>,
                backgroundColor: <?= json_encode(array_column($criticidadData, 'color')) ?>,
                borderRadius: 8,
                barThickness: 60
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
                        color: '#64748b',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    display: false
                }
            }
        }
    });

    // 3. OT por Tipo (Doughnut)
    new Chart(document.getElementById('otTipoChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($otPorTipoData, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($otPorTipoData, 'value')) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#0ea5e9'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            ...commonOptions,
            cutout: '75%'
        }
    });

    // 4. Técnicos (Bar)
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($techComparisonData, 'name')) ?>,
            datasets: [{
                    label: 'OT Cerradas',
                    data: <?= json_encode(array_column($techComparisonData, 'terminadas')) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                },
                {
                    label: 'XP Score (x100)',
                    data: <?= json_encode(array_column($techComparisonData, 'xp')) ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                y: {
                    display: false
                }
            }
        }
    });
</script>