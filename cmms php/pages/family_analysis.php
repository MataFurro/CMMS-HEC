<?php
// pages/family_analysis.php
// Análisis de equipos por familia - Métricas de uso, vida útil, fallas y tiempo fuera

require_once __DIR__ . '/../backend/providers/AssetProvider.php';

$families = getAssetFamilies();

// Tendencia de fallas por mes (últimos 6 meses)
$failureTrend = [
    ['month' => 'Ago', 'Ventilación' => 3, 'Imagenología' => 2, 'Monitorización' => 4, 'Infusión' => 5, 'Desfibrilación' => 1],
    ['month' => 'Sep', 'Ventilación' => 2, 'Imagenología' => 1, 'Monitorización' => 3, 'Infusión' => 4, 'Desfibrilación' => 0],
    ['month' => 'Oct', 'Ventilación' => 4, 'Imagenología' => 2, 'Monitorización' => 2, 'Infusión' => 3, 'Desfibrilación' => 1],
    ['month' => 'Nov', 'Ventilación' => 1, 'Imagenología' => 1, 'Monitorización' => 3, 'Infusión' => 5, 'Desfibrilación' => 0],
    ['month' => 'Dic', 'Ventilación' => 2, 'Imagenología' => 1, 'Monitorización' => 2, 'Infusión' => 3, 'Desfibrilación' => 1],
    ['month' => 'Ene', 'Ventilación' => 0, 'Imagenología' => 1, 'Monitorización' => 1, 'Infusión' => 2, 'Desfibrilación' => 1]
];

// Calcular totales
$totalAssets = array_sum(array_column($families, 'total_assets'));
$totalHours = array_sum(array_column($families, 'hours_used'));
$totalFailures = array_sum(array_column($families, 'total_failures'));
$avgAvailability = count($families) > 0 ? round(array_sum(array_column($families, 'availability')) / count($families), 1) : 0;

// Familia con más fallas
$maxFailuresFamily = array_reduce($families, function ($carry, $item) {
    return (!$carry || $item['total_failures'] > $carry['total_failures']) ? $item : $carry;
});

// Familia con más uso
$maxUsageFamily = array_reduce($families, function ($carry, $item) {
    return (!$carry || $item['hours_used'] > $carry['hours_used']) ? $item : $carry;
});

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Análisis por Familia</h1>
            <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider font-bold">Métricas agregadas por tipo de
                equipo</p>
        </div>
        <div class="flex gap-3">
            <button
                class="h-11 px-6 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-800 flex items-center gap-2 transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">file_download</span>
                Exportar Análisis
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card-glass p-5 border-l-4 border-l-medical-blue">
            <div class="flex justify-between items-start mb-2">
                <span class="material-symbols-outlined text-slate-400 text-lg">category</span>
                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-500">
                    <?= count($families) ?> Familias
                </span>
            </div>
            <p class="stat-label">Total de Activos</p>
            <h3 class="stat-value"><?= $totalAssets ?></h3>
            <p class="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium">Equipos registrados</p>
        </div>

        <div class="card-glass p-5 border-l-4 border-l-amber-500">
            <div class="flex justify-between items-start mb-2">
                <span class="material-symbols-outlined text-slate-400 text-lg">schedule</span>
                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-amber-500/10 text-amber-500">
                    <?= $maxUsageFamily['name'] ?? 'N/A' ?>
                </span>
            </div>
            <p class="stat-label">Horas de Uso Total</p>
            <h3 class="stat-value"><?= number_format($totalHours) ?> hrs</h3>
            <p class="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium">Acumulado</p>
        </div>

        <div class="card-glass p-5 border-l-4 border-l-red-500">
            <div class="flex justify-between items-start mb-2">
                <span class="material-symbols-outlined text-slate-400 text-lg">warning</span>
                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-red-500/10 text-red-500">
                    <?= $maxFailuresFamily['name'] ?? 'N/A' ?>
                </span>
            </div>
            <p class="stat-label">Fallas Totales</p>
            <h3 class="stat-value"><?= $totalFailures ?></h3>
            <p class="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium">Últimos 6 meses</p>
        </div>

        <div class="card-glass p-5 border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-start mb-2">
                <span class="material-symbols-outlined text-slate-400 text-lg">check_circle</span>
                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-500">
                    Meta 95%
                </span>
            </div>
            <p class="stat-label">Disponibilidad Promedio</p>
            <h3 class="stat-value"><?= $avgAvailability ?>%</h3>
            <p class="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium">Todas las familias</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Horas de Uso (Bar Chart) -->
        <div class="lg:col-span-6 card-glass p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Horas de Uso por Familia</h3>
                    <p class="text-xs text-slate-500 font-medium">Tiempo acumulado de operación</p>
                </div>
            </div>
            <div class="h-[300px] w-full">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <!-- Métricas Múltiples (Radar Chart) -->
        <div class="lg:col-span-6 card-glass p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Perfil de Rendimiento</h3>
                    <p class="text-xs text-slate-500 font-medium">Vida útil vs Disponibilidad</p>
                </div>
            </div>
            <div class="h-[300px] w-full flex items-center justify-center">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Tendencia de Fallas (Line Chart) -->
        <div class="lg:col-span-12 card-glass p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tendencia de Fallas</h3>
                    <p class="text-xs text-slate-500 font-medium">Evolución mensual por familia - Últimos 6 meses</p>
                </div>
            </div>
            <div class="h-[300px] w-full">
                <canvas id="failureChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla Comparativa -->
    <div class="card-glass overflow-hidden shadow-2xl">
        <div class="p-8 border-b border-slate-700/50">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tabla Comparativa Detallada</h3>
            <p class="text-xs text-slate-500 font-medium mt-1">Todas las métricas por familia</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-slate-700/50">
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Familia
                        </th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">
                            Activos</th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">
                            Hrs Uso</th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">
                            Vida Útil</th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">
                            Fallas</th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">
                            Tiempo Fuera</th>
                        <th
                            class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">
                            Disponibilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php foreach ($families as $family): ?>
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                        style="background-color: <?= $family['color'] ?>20; border: 1px solid <?= $family['color'] ?>40;">
                                        <span class="material-symbols-outlined text-xl"
                                            style="color: <?= $family['color'] ?>;"><?= $family['icon'] ?></span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-base"><?= $family['name'] ?></p>
                                        <p class="text-xs text-slate-500 uppercase font-semibold mt-0.5">Familia de equipos
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span class="text-lg font-black text-white"><?= $family['total_assets'] ?></span>
                            </td>
                            <td class="px-6 py-6 text-right">
                                <span
                                    class="text-base font-bold text-slate-200"><?= number_format($family['hours_used']) ?></span>
                                <span class="text-xs text-slate-500 ml-1">hrs</span>
                            </td>
                            <td class="px-6 py-6">
                                <div class="w-32 mx-auto">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-[10px] font-black text-slate-500"><?= $family['avg_life_remaining'] ?>%</span>
                                        <span class="text-[9px] text-slate-600 font-bold uppercase">Restante</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden border border-white/5">
                                        <div class="h-full <?= $family['avg_life_remaining'] > 60 ? 'bg-emerald-500' : ($family['avg_life_remaining'] > 40 ? 'bg-amber-500' : 'bg-red-500') ?>"
                                            style="width: <?= $family['avg_life_remaining'] ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-black uppercase border <?= $family['total_failures'] > 15 ? 'bg-red-500/10 text-red-500 border-red-500/30' : 'bg-amber-500/10 text-amber-500 border-amber-500/30' ?>">
                                    <span class="material-symbols-outlined text-sm">warning</span>
                                    <?= $family['total_failures'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-6 text-right">
                                <span class="text-base font-bold text-slate-200"><?= $family['downtime'] ?></span>
                                <span class="text-xs text-slate-500 ml-1">hrs</span>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span
                                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-xl text-xs font-black uppercase border <?= $family['availability'] >= 95 ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30' : 'bg-amber-500/10 text-amber-500 border-amber-500/30' ?>">
                                    <span
                                        class="size-2 rounded-full <?= $family['availability'] >= 95 ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-amber-500' ?>"></span>
                                    <?= $family['availability'] ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    // 1. Usage Chart (Horizontal Bar)
    new Chart(document.getElementById('usageChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($families, 'name')) ?>,
            datasets: [{
                label: 'Horas de Uso',
                data: <?= json_encode(array_column($families, 'hours_used')) ?>,
                backgroundColor: <?= json_encode(array_column($families, 'color')) ?>,
                borderRadius: 8,
                barThickness: 32
            }]
        },
        options: {
            ...commonOptions,
            indexAxis: 'y',
            scales: {
                x: {
                    grid: {
                        color: '#334155'
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b'
                    }
                }
            }
        }
    });

    // 2. Radar Chart (Performance Profile)
    new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: <?= json_encode(array_column($families, 'name')) ?>,
            datasets: [{
                label: 'Vida Útil %',
                data: <?= json_encode(array_column($families, 'avg_life_remaining')) ?>,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                borderWidth: 2
            }, {
                label: 'Disponibilidad %',
                data: <?= json_encode(array_column($families, 'availability')) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                r: {
                    grid: {
                        color: '#334155'
                    },
                    angleLines: {
                        color: '#334155'
                    },
                    pointLabels: {
                        color: '#64748b',
                        font: {
                            size: 10
                        }
                    },
                    ticks: {
                        color: '#64748b',
                        backdropColor: 'transparent'
                    }
                }
            }
        }
    });

    // 3. Failure Trend Chart (Multi-line)
    new Chart(document.getElementById('failureChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($failureTrend, 'month')) ?>,
            datasets: [
                <?php
                $datasets = [];
                foreach ($families as $family) {
                    $datasets[] = [
                        'label' => $family['name'],
                        'data' => array_column($failureTrend, $family['name']),
                        'borderColor' => $family['color'],
                        'backgroundColor' => $family['color'] . '20',
                        'tension' => 0.4,
                        'borderWidth' => 2,
                        'pointRadius' => 4,
                        'pointHoverRadius' => 6
                    ];
                }
                echo json_encode($datasets);
                ?>
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        padding: 15
                    }
                }
            }
        }
    });
</script>