<?php
// pages/financial_analysis.php

require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

require_once __DIR__ . '/../Backend/Providers/ExcelProvider.php';

use function Backend\Providers\exportFinancialReportToCsv;

// --- EXPORT LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'export_minsal' && canModify()) {
    exportFinancialReportToCsv();
}

$stats = getFinancialStats();
$impactData = getDowntimeImpact(); // Top 5 áreas
$waterfallData = getUptimeWaterfallData();
$waveData = getAvailabilityLossWaveData();
$clinicalImpact = getClinicalImpactStats();

// Datos de tendencia (Simulados por ahora, pero basados en el valor real)
$valBase = $stats['valor_inventario'] ?? 0;
$depreciacion_data = [
    ['mes' => 'Ene', 'lineal' => $valBase * 0.05, 'ajustada' => ($valBase * 0.05) + (($stats['penalizacion_pm'] ?? 0) * 0.2)],
    ['mes' => 'Feb', 'lineal' => $valBase * 0.05, 'ajustada' => ($valBase * 0.05) + (($stats['penalizacion_pm'] ?? 0) * 0.5)],
    ['mes' => 'Mar', 'lineal' => $valBase * 0.05, 'ajustada' => ($valBase * 0.05) + (($stats['penalizacion_pm'] ?? 0) * 0.8)],
    ['mes' => 'Abr', 'lineal' => $valBase * 0.05, 'ajustada' => ($valBase * 0.05) + ($stats['penalizacion_pm'] ?? 0)],
];

// Metodología TINC (Fórmula simplificada para la vista)
$formula_tinc = "Veq = Vo - [Pu + (At * Pt) + (At * Pm) + (At * Pv) + Ps + Pi]";
?>

<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav
                class="flex items-center gap-2 text-[10px] text-text-muted uppercase tracking-[0.2em] font-black mb-3 text-shadow-sm">
                <span>Estrategia</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-medical-blue">Impacto Clínico</span>
            </nav>
            <h1 class="text-4xl font-black text-text-main tracking-tight flex items-center gap-4">
                Análisis de Impacto Clínico y Operativo
            </h1>
            <p class="text-text-muted mt-2 text-lg font-medium">Gestión de Continuidad de Servicio y Disponibilidad Tecnológica.</p>
        </div>

        <div class="flex gap-3">
            <a href="?page=financial_analysis&action=export" download="reporte_minsal_financiero.csv" target="_blank" rel="noopener noreferrer" data-turbo="false" hx-disable
                class="h-12 flex items-center gap-3 px-6 bg-white/80 dark:bg-white/10 backdrop-blur-xl text-text-main dark:text-white border border-border-color dark:border-white/10 rounded-2xl hover:border-medical-blue/50 hover:bg-medical-blue/5 dark:hover:bg-white/20 transition-all duration-300 font-bold shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.3)] active:scale-95 group">
                <span class="material-symbols-outlined text-xl text-medical-blue group-hover:-translate-y-0.5 transition-transform">download</span>
                <span class="text-xs uppercase tracking-widest text-left leading-tight">Exportar Reporte<br><span class="opacity-70 text-[10px]">MINSAL (CSV)</span></span>
            </a>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card-glass p-6 border border-[var(--border-color)]/30 group-hover:border-medical-blue transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-emerald-500/10 text-emerald-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">account_balance</span>
                </div>
                <span class="text-[10px] font-black text-text-muted uppercase tracking-widest leading-none">Valor
                    Inventario</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-text-main">$<?= number_format($stats['valor_inventario'] ?? 0, 0, ',', '.') ?></span>
                <span class="text-xs font-bold text-emerald-500 mt-1">CLP (Valorización de Activos)</span>
            </div>
        </div>

        <div class="card-glass p-6 group hover:border-amber-500/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">trending_down</span>
                </div>
                <span class="text-[10px] font-black text-text-muted uppercase tracking-widest leading-none">Costo de
                    Mantenimiento</span>
            </div>
            <div class="flex flex-col">
                <span
                    class="text-3xl font-black text-text-main">$<?= number_format($stats['costo_mantenimiento_anual'] ?? 0, 0, ',', '.') ?></span>
                <span class="text-xs font-bold text-amber-500 mt-1">CLP Anual (Presupuesto Estimado)</span>
            </div>
        </div>

        <div class="card-glass p-6 border border-[var(--border-color)]/30 group-hover:border-medical-blue transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-medical-blue/10 text-medical-blue rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">health_and_safety</span>
                </div>
                <span class="text-[10px] font-black text-text-muted uppercase tracking-widest leading-none">Disponibilidad Clínica</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-text-main"><?= $clinicalImpact['clinical_availability'] ?>%</span>
                <span class="text-xs font-bold text-medical-blue mt-1">Uptime Promedio Parque Crítico</span>
            </div>
        </div>

        <div class="card-glass p-6 group hover:border-red-500/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-red-500/10 text-red-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">event_busy</span>
                </div>
                <span class="text-[10px] font-black text-text-muted uppercase tracking-widest leading-none">Equipos a
                    Reponer</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-text-main"><?= $stats['obsolescencia_proxima'] ?></span>
                <span class="text-xs font-bold text-red-500 mt-1">Ciclo de vida finalizado (2026)</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Cascada de Uptime (Erosión de Disponibilidad) -->
        <div class="card-glass p-8 relative overflow-hidden h-[450px]">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <span class="material-symbols-outlined text-8xl text-emerald-500">timer</span>
            </div>
            <div class="mb-6">
                <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-500">waterfall_chart</span>
                    Cascada de Disponibilidad
                </h3>
                <p class="text-[var(--text-muted)] text-[10px] font-bold uppercase tracking-widest mt-1">Erosión del Uptime Teórico</p>
            </div>
            <div class="h-[280px]">
                <canvas id="waterfallChart"></canvas>
            </div>
        </div>

        <!-- Ola de Obsolescencia: Riesgo Operativo -->
        <div class="card-glass p-8 relative overflow-hidden h-[450px]">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <span class="material-symbols-outlined text-8xl text-blue-500">waves</span>
            </div>
            <div class="mb-6">
                <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-[0.2em] flex items-center gap-3">
                    <span class="material-symbols-outlined text-blue-500">tide</span>
                    Riesgo de Obsolescencia
                </h3>
                <p class="text-[var(--text-muted)] text-[10px] font-bold uppercase tracking-widest mt-1">Pérdida Proyectada de Disponibilidad 2024-2030</p>
            </div>
            <div class="h-[280px]">
                <canvas id="waveChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Depreciación Ajustada (Metodología TINC) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 card-glass p-8 space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-text-main flex items-center gap-3">
                        <span class="material-symbols-outlined text-medical-blue">calculate</span>
                        Depreciación Ajustada (Metodología TINC)
                    </h3>
                    <p class="text-text-muted text-sm mt-1">Valor residual basado en horas operativas y fallas.</p>
                </div>
            </div>

            <div class="p-6 bg-[var(--input-bg)] rounded-2xl border border-[var(--border-color)] font-mono text-xs text-medical-blue text-center shadow-inner">
                <?= $formula_tinc ?>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between text-xs font-black uppercase tracking-[0.2em] text-text-muted px-4">
                    <span>Variable de Tiempo</span>
                    <span>Impacto en Disponibilidad</span>
                </div>

                <div class="space-y-3">
                    <div class="p-4 bg-panel-dark rounded-xl border border-[var(--border-color)] flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-amber-500">warning</span>
                            <span class="text-sm font-bold text-text-main">Mantenimientos Pendientes</span>
                        </div>
                        <span class="text-sm font-black text-amber-500"><?= $stats['obsolescencia_proxima'] ?> Equipos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impacto de Downtime -->
        <div class="card-glass p-8 space-y-6">
            <h3 class="text-xl font-bold text-text-main flex items-center gap-3">
                <span class="material-symbols-outlined text-red-500">timer_off</span>
                Top Downtime por Área
            </h3>
            <div class="space-y-4">
                <?php if (!empty($impactData['areas'])): ?>
                    <?php foreach ($impactData['areas'] as $area):
                        $pct = min(($area['hours'] / 100) * 100, 100);
                    ?>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-text-muted"><?= $area['area'] ?></span>
                                <span class="text-text-main"><?= number_format($area['hours'], 1) ?>h</span>
                            </div>
                            <div class="w-full bg-[var(--input-bg)] h-1.5 rounded-full overflow-hidden">
                                <div class="bg-red-500 h-full rounded-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-text-muted text-center py-10 italic">Sin reportes de inactividad</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Robust Chart Initialization
    window.addEventListener('load', function() {
        if (typeof Chart === 'undefined') {
            console.error('BioCMMS Error: Chart.js failed to load. Please check your internet connection.');
            return;
        }

        // Uptime Waterfall Chart
        (function() {
            const canvas = document.getElementById('waterfallChart');
            if (!canvas) return;

            const rawData = <?= json_encode($waterfallData) ?>;
            if (!rawData || !Array.isArray(rawData)) {
                console.warn('BioCMMS Warn: No waterfall data available.');
                return;
            }
            const labels = rawData.map(d => d.label);
            const data = rawData.map(d => d.value);

            const bgColors = rawData.map(d => {
                if (d.type === 'base') return '#10b98199';
                if (d.type === 'total') return '#3b82f6';
                return '#ef444499';
            });

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: bgColors,
                        borderColor: bgColors.map(c => c.replace('99', '')),
                        borderWidth: 1,
                        borderRadius: 4
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
                                color: 'var(--text-muted)',
                                font: {
                                    size: 9,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                color: 'var(--text-muted)'
                            },
                            grid: {
                                color: 'var(--border-color)'
                            }
                        }
                    }
                }
            });
        })();

        // Obsolescence Wave Chart
        (function() {
            const ctx = document.getElementById('waveChart');
            if (!ctx) return;

            const waveData = <?= json_encode($waveData) ?>;
            const years = Object.keys(waveData);
            const counts = years.map(y => waveData[y].expired_count);
            const loss = years.map(y => waveData[y].uptime_loss_risk);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [{
                            label: 'Equipos Vencidos',
                            data: counts,
                            backgroundColor: '#3b82f633',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Riesgo Perdida Uptime (%)',
                            data: loss,
                            type: 'line',
                            borderColor: '#f59e0b',
                            borderWidth: 2,
                            pointRadius: 3,
                            fill: false,
                            yAxisID: 'y1',
                            tension: 0.4,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            position: 'left',
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        y1: {
                            position: 'right',
                            min: 0,
                            ticks: {
                                color: '#f59e0b'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        })();
    }); // End Window Load
</script>