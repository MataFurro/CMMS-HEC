<?php
// pages/dashboard.php

// --- MOCK DATA (Converted from mockData.ts) ---

$technicians = [
    ['name' => 'Mario Gómez', 'role' => 'Especialista UCI', 'initial' => 'MG', 'stats' => ['done' => 50, 'progress' => 25, 'pending' => 25], 'total' => 28, 'capacity' => 85, 'trend' => 'trending_up', 'trendColor' => 'text-amber-400'],
    ['name' => 'Pablo Rojas', 'role' => 'Electromedicina', 'initial' => 'PR', 'stats' => ['done' => 30, 'progress' => 50, 'pending' => 20], 'total' => 20, 'capacity' => 95, 'trend' => 'warning', 'trendColor' => 'text-red-500'],
    ['name' => 'Ana Muñoz', 'role' => 'Imagenología', 'initial' => 'AM', 'stats' => ['done' => 60, 'progress' => 20, 'pending' => 20], 'total' => 15, 'capacity' => 62, 'trend' => 'check_circle', 'trendColor' => 'text-emerald-400'],
    ['name' => 'Jorge Vera', 'role' => 'Apoyo Crítico', 'initial' => 'JV', 'stats' => ['done' => 27, 'progress' => 27, 'pending' => 46], 'total' => 22, 'capacity' => 74, 'trend' => 'equalizer', 'trendColor' => 'text-slate-400'],
];

$trendData = [
    ['name' => 'Ene', 'mtbf' => 680, 'mttr' => 4.2],
    ['name' => 'Feb', 'mtbf' => 710, 'mttr' => 3.8],
    ['name' => 'Mar', 'mtbf' => 690, 'mttr' => 4.0],
    ['name' => 'Abr', 'mtbf' => 730, 'mttr' => 3.5],
    ['name' => 'May', 'mtbf' => 720, 'mttr' => 3.4],
];

$statusData = [
    ['name' => 'Finalizadas', 'value' => 45, 'color' => '#10b981'],
    ['name' => 'En Ejecución', 'value' => 30, 'color' => '#f59e0b'],
    ['name' => 'Pendientes', 'value' => 25, 'color' => '#94a3b8'],
];

$kpiCards = [
    ['label' => 'MTBF Global', 'value' => '720 hrs', 'trend' => '+5%', 'color' => 'border-l-medical-blue', 'icon' => 'timeline', 'sub' => 'Disponibilidad'],
    ['label' => 'MTTR Global', 'value' => '3.4 hrs', 'trend' => '-2.1h', 'color' => 'border-l-amber-500', 'icon' => 'timer', 'sub' => 'Respuesta'],
    ['label' => 'Cumplimiento PMP', 'value' => '94.2%', 'trend' => 'Meta 90%', 'color' => 'border-l-emerald-500', 'icon' => 'check_circle', 'sub' => 'Planificación'],
    ['label' => 'Fuera de Servicio', 'value' => '12', 'trend' => 'Crítico', 'color' => 'border-l-red-500', 'icon' => 'warning', 'sub' => 'Baja Técnica'],
    ['label' => 'Gasto Repuestos', 'value' => '$12.4M', 'trend' => 'Acumulado', 'color' => 'border-l-slate-400', 'icon' => 'payments', 'sub' => 'Presupuesto'],
];

$recentEvents = [
    [
        'id' => 'OT-2023-4582',
        'title' => 'OT #2023-4582 Finalizada',
        'subtitle' => 'Ventilador Mecánico - UCI Adultos | Técnico: Mario Gómez',
        'time' => 'Hace 15 min',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ],
    [
        'id' => 'OT-REV-NEW',
        'title' => 'Nueva Falla Reportada (Correctivo)',
        'subtitle' => 'Equipo de Rayos X Portátil - Urgencias | Asignado: Pablo Rojas',
        'time' => 'Hace 45 min',
        'type' => 'warning',
        'colorClass' => 'amber-500'
    ]
];

// PHP Logic for derived data
$techComparisonData = array_map(function ($t) {
    return [
        'name' => explode(' ', $t['name'])[0],
        'cerradas' => round($t['total'] * ($t['stats']['done'] / 100)),
        'pendientes' => round($t['total'] * (($t['stats']['progress'] + $t['stats']['pending']) / 100))
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
        <div class="flex gap-3">
            <button class="h-11 px-6 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-800 flex items-center gap-2 transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">file_download</span>
                Exportar Reporte
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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

        <!-- MTBF/MTTR Chart -->
        <div class="lg:col-span-8 card-glass p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Historial de Confiabilidad</h3>
                    <p class="text-xs text-slate-500 font-medium">Relación entre MTBF (hrs) y MTTR (hrs) - Últimos 5 meses</p>
                </div>
            </div>
            <div class="h-[300px] w-full">
                <canvas id="mtbfChart"></canvas>
            </div>
        </div>

        <!-- Status Donut -->
        <div class="lg:col-span-4 card-glass p-8">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-6">Estado de OTs</h3>
            <div class="h-[200px] w-full relative">
                <canvas id="statusChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-black text-white">82</span>
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Activos</span>
                </div>
            </div>
            <div class="space-y-3 mt-4">
                <?php foreach ($statusData as $s): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="size-2 rounded-full" style="background-color: <?= $s['color'] ?>;"></div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase"><?= $s['name'] ?></span>
                        </div>
                        <span class="text-[10px] font-black text-white"><?= $s['value'] ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Technicians Workload -->
        <div class="lg:col-span-7 card-glass p-8">
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Carga Laboral</h3>
                    <p class="text-xs text-slate-500 font-medium">Distribución de OTs y uso de capacidad</p>
                </div>
            </div>
            <div class="space-y-10">
                <?php foreach ($technicians as $tech): ?>
                    <div class="grid grid-cols-12 items-center gap-6 group">
                        <div class="col-span-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-medical-blue/20 flex items-center justify-center text-medical-blue font-black text-xs border border-medical-blue/30 group-hover:scale-105 transition-transform">
                                <?= $tech['initial'] ?>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold text-white leading-none"><?= $tech['name'] ?></p>
                                <p class="text-[9px] text-slate-500 font-bold uppercase mt-1.5 tracking-wider"><?= $tech['role'] ?></p>
                            </div>
                        </div>
                        <div class="col-span-6">
                            <div class="h-2 w-full bg-slate-900 rounded-full overflow-hidden flex">
                                <div class="bg-medical-blue h-full" style="width: <?= $tech['stats']['done'] ?>%"></div>
                                <div class="bg-amber-500 h-full" style="width: <?= $tech['stats']['progress'] ?>%"></div>
                                <div class="bg-slate-700 h-full" style="width: <?= $tech['stats']['pending'] ?>%"></div>
                            </div>
                        </div>
                        <div class="col-span-3 text-right">
                            <span class="text-lg font-black <?= $tech['capacity'] > 90 ? 'text-red-500' : 'text-emerald-400' ?>">
                                <?= $tech['capacity'] ?>%
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tech Comparison (Bar Chart) -->
        <div class="lg:col-span-5 card-glass p-8">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-8">Efectividad Técnica</h3>
            <div class="h-[300px] w-full">
                <canvas id="techChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="card-glass p-8">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-8">Eventos Recientes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
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
    // Chart Configuration
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 10
                    }
                }
            },
            y: {
                grid: {
                    color: '#334155'
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 10
                    }
                },
                border: {
                    display: false
                }
            }
        }
    };

    // 1. MTBF Chart (Area)
    new Chart(document.getElementById('mtbfChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendData, 'name')) ?>,
            datasets: [{
                label: 'MTBF',
                data: <?= json_encode(array_column($trendData, 'mtbf')) ?>,
                borderColor: '#0ea5e9', // medical-blue
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 0
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });

    // 2. Status Chart (Doughnut)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($statusData, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($statusData, 'value')) ?>,
                backgroundColor: <?= json_encode(array_column($statusData, 'color')) ?>,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // 3. Tech Chart (Bar)
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($techComparisonData, 'name')) ?>,
            datasets: [{
                    label: 'Cerradas',
                    data: <?= json_encode(array_column($techComparisonData, 'cerradas')) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                },
                {
                    label: 'Pendientes',
                    data: <?= json_encode(array_column($techComparisonData, 'pendientes')) ?>,
                    backgroundColor: '#334155',
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