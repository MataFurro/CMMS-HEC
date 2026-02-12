<?php
// pages/financial_analysis.php

// Mock Financial Data (Basado en NotebookLM)
$stats = [
    'valorInventario' => 148500, // Suma aproximada de los equipos en inventory.php
    'valorReposicion' => 185000,
    'costoMantenimientoAnual' => 12500,
    'tco_avg' => 4500,
    'obsolescencia_proxima' => 2,
    'roi_contratos' => 25,
];

$depreciacion_data = [
    ['mes' => 'Ene', 'lineal' => 5000, 'ajustada' => 5200],
    ['mes' => 'Feb', 'lineal' => 5000, 'ajustada' => 5500],
    ['mes' => 'Mar', 'lineal' => 5000, 'ajustada' => 6100],
    ['mes' => 'Abr', 'lineal' => 5000, 'ajustada' => 5900],
];

// Metodología TINC (Fórmula simplificada para la vista)
$formula_tinc = "Veq = Vo - [Pu + (At * Pt) + (At * Pm) + (At * Pv) + Ps + Pi]";
?>

<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black mb-3 text-shadow-sm">
                <span>Estrategia</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-medical-blue">Análisis Financiero</span>
            </nav>
            <h1 class="text-4xl font-black text-white tracking-tight flex items-center gap-4">
                Control de Activos y Capital
                <span class="px-3 py-1 bg-medical-blue/10 text-medical-blue text-xs rounded-lg border border-medical-blue/20">FY 2026</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg font-medium">Gestión del Ciclo de Vida Económico y Costo Total de Propiedad (TCO).</p>
        </div>

        <div class="flex gap-3">
            <button class="px-6 py-3 bg-white/5 border border-slate-700/50 text-slate-300 rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-white/10 transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">file_download</span>
                Exportar Reporte MINSAL
            </button>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card-glass p-6 group hover:border-medical-blue/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-emerald-500/10 text-emerald-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">account_balance</span>
                </div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Valor Inventario</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-white">$<?= number_format($stats['valorInventario']) ?></span>
                <span class="text-xs font-bold text-emerald-500 mt-1">USD (Valorización Actual)</span>
            </div>
        </div>

        <div class="card-glass p-6 group hover:border-amber-500/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">trending_down</span>
                </div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Costo de Mantenimiento</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-white">$<?= number_format($stats['costoMantenimientoAnual']) ?></span>
                <span class="text-xs font-bold text-amber-500 mt-1">Vea. vs Valor Activo: 6.8%</span>
            </div>
        </div>

        <div class="card-glass p-6 group hover:border-medical-blue/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-medical-blue/10 text-medical-blue rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">equalizer</span>
                </div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">TCO Promedio</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-white">$<?= number_format($stats['tco_avg']) ?></span>
                <span class="text-xs font-bold text-medical-blue mt-1">Per cápita / Tecnología Crítica</span>
            </div>
        </div>

        <div class="card-glass p-6 group hover:border-red-500/30 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-red-500/10 text-red-500 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">event_busy</span>
                </div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Equipos a Reponer</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-white"><?= $stats['obsolescencia_proxima'] ?></span>
                <span class="text-xs font-bold text-red-500 mt-1">Ciclo de vida finalizado (2026)</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Depreciación Ajustada (Metodología TINC) -->
        <div class="lg:col-span-2 card-glass p-8 space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-3">
                        <span class="material-symbols-outlined text-medical-blue">calculate</span>
                        Depreciación Ajustada (Metodología TINC)
                    </h3>
                    <p class="text-slate-400 text-sm mt-1">Cálculo de valor residual basado en uso, fallas e incidentes adversos.</p>
                </div>
            </div>

            <div class="p-6 bg-slate-900/50 rounded-2xl border border-slate-700/50 font-mono text-xs text-medical-blue text-center shadow-inner">
                <?= $formula_tinc ?>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between text-xs font-black uppercase tracking-[0.2em] text-slate-500 px-4">
                    <span>Variable de Ajuste</span>
                    <span>Impacto Financiero</span>
                </div>

                <div class="space-y-3">
                    <div class="p-4 bg-white/5 rounded-xl border border-slate-700/50 flex items-center justify-between group hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-amber-500">warning</span>
                            <span class="text-sm font-bold text-slate-300">Falta de Mantenimientos (Pm)</span>
                        </div>
                        <span class="text-sm font-black text-amber-500">- $4,500</span>
                    </div>
                    <div class="p-4 bg-white/5 rounded-xl border border-slate-700/50 flex items-center justify-between group hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-red-500">emergency</span>
                            <span class="text-sm font-bold text-slate-300">Eventos Adversos (Pi)</span>
                        </div>
                        <span class="text-sm font-black text-red-500">- $12,800</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Costo de Inactividad -->
        <div class="card-glass p-8 space-y-6">
            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                <span class="material-symbols-outlined text-red-500">timer_off</span>
                Impacto de Downtime
            </h3>

            <div class="space-y-8">
                <div class="text-center py-6 bg-red-500/5 rounded-2xl border border-red-500/10">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Pérdida por Inactividad Acumulada</p>
                    <h4 class="text-4xl font-black text-white">$156,400</h4>
                    <p class="text-xs font-bold text-red-500 mt-1">+12% vs periodo anterior</p>
                </div>

                <div class="space-y-4">
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-700 pb-2">Top Áreas con Riesgo Financiero</h4>
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-slate-300">Pabellón Principal</span>
                                <span class="text-white">$85k/mes</span>
                            </div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-red-500 h-full w-[85%] rounded-full shadow-[0_0_8px_rgba(239,68,68,0.5)]"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-slate-300">UCI Adultos</span>
                                <span class="text-white">$42k/mes</span>
                            </div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full w-[45%] rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROI y Contratos -->
    <div class="card-glass p-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="p-2.5 bg-emerald-500/10 text-emerald-500 rounded-xl border border-emerald-500/20">
                <span class="material-symbols-outlined">handshake</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white uppercase tracking-tight">Evaluación de Contratos y ROI</h3>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">Análisis Rent vs Buy y Outsourcing</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-6 bg-white/5 rounded-2xl border border-emerald-500/20 shadow-xl relative group overflow-hidden">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-125 transition-transform duration-700">
                    <span class="material-symbols-outlined text-8xl text-emerald-500">trending_up</span>
                </div>
                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Eficiencia In-House</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-white">67%</span>
                    <span class="text-emerald-500 font-bold">Ahorro</span>
                </div>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">Reducción de costos comparativa frente a servicios subrogados de terceros.</p>
            </div>

            <div class="p-6 bg-white/5 rounded-2xl border border-slate-700/50">
                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Garantía Activa</h4>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-white">142</span>
                    <span class="text-slate-500 text-xs font-bold">Activos</span>
                </div>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">Costo evitado por uso efectivo de pólizas de fabricante vigente.</p>
            </div>

            <div class="p-6 bg-gradient-to-br from-medical-blue/20 to-cyan-500/5 rounded-2xl border border-medical-blue/30 lg:col-span-1">
                <h4 class="text-xs font-black text-medical-blue uppercase tracking-widest mb-4">Presupuesto Ejecutado</h4>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="text-4xl font-black text-white">82%</span>
                    <span class="text-slate-500 text-xs font-bold">/ Q1-Q2</span>
                </div>
                <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="bg-medical-blue h-full w-[82%] rounded-full shadow-[0_0_12px_rgba(37,99,235,0.4)]"></div>
                </div>
            </div>
        </div>
    </div>
</div>