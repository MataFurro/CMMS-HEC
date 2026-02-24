<?php
// pages/accreditation_dashboard.php

if (!canViewDashboard()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1></div>";
    return;
}

require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

// --- DATA COLLECTION FOR ACCREDITATION ---
$assets = getAllAssets();
$criticalAssets = array_filter($assets, fn($a) => ($a['criticality'] ?? '') === 'CRITICAL');
$totalCritical = count($criticalAssets);

// Simulation of Maintenance Plan Compliance (GCL 1.2)
$plannedPMs = 150; // In a real system, this comes from a calendar table
$executedPMs = 142;
$compliancePM = ($plannedPMs > 0) ? round(($executedPMs / $plannedPMs) * 100, 1) : 100;

// Tecnovigilancia (Recalls)
$recalls = [];
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $recalls = $db->query("SELECT r.*, a.name as asset_name FROM asset_recalls r JOIN assets a ON r.asset_id = a.id ORDER BY recall_date DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
}

$activeRecalls = count(array_filter($recalls, fn($r) => !($r['resolved'] ?? false)));

// Uptime Clinical Metric
$avgUptime = 98.4; // %

?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text-main)] tracking-tight">Panel de Acreditación</h1>
            <p class="text-xs text-[var(--text-muted)] mt-1 uppercase tracking-wider font-bold">Cumplimiento Estándares SIS-Q · GCL 1.2 / GCL 2.2</p>
        </div>
        <div class="flex gap-3">
            <button class="h-11 px-6 bg-medical-blue text-white rounded-xl text-sm font-bold shadow-lg shadow-medical-blue/20 hover:scale-105 transition-all active:scale-95 flex items-center gap-2">
                <span class="material-symbols-outlined text-xl">verified</span>
                Generar Informe Foliado
            </button>
        </div>
    </div>

    <!-- Main Accreditation Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- GCL 1.2: Mantenimiento Preventivo -->
        <div class="card-glass p-8 flex flex-col items-center justify-center text-center space-y-4">
            <div class="relative size-40">
                <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="3"></circle>
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-medical-blue" stroke-width="3"
                        stroke-dasharray="<?= $compliancePM ?>, 100" stroke-linecap="round"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-black text-[var(--text-main)]"><?= $compliancePM ?>%</span>
                    <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest">Ejecución PM</span>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-widest">GCL 1.2 - Preventivos</h3>
                <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase mt-1">Cumplimiento Plan Anual Equipos Críticos</p>
            </div>
            <div class="px-4 py-2 <?= $compliancePM >= 90 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500' ?> rounded-lg border border-current/20 text-[10px] font-black uppercase">
                <?= $compliancePM >= 90 ? '✓ CUMPLE ESTÁNDAR' : '✗ BAJO EL UMBRAL' ?>
            </div>
        </div>

        <!-- Disponibilidad y Uptime -->
        <div class="card-glass p-8 flex flex-col items-center justify-center text-center space-y-4">
            <div class="size-40 flex items-center justify-center">
                <span class="material-symbols-outlined text-8xl text-emerald-500 opacity-20 absolute">health_and_safety</span>
                <div class="relative">
                    <span class="text-5xl font-black text-emerald-500"><?= $avgUptime ?></span>
                    <span class="text-xl font-black text-emerald-500">%</span>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-widest">Disponibilidad Técnica</h3>
                <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase mt-1">Uptime Promedio Equipos Soporte Vital</p>
            </div>
            <div class="text-[10px] font-bold text-[var(--text-muted)]">Meta Institucional: > 95%</div>
        </div>

        <!-- GCL 2.2: Tecnovigilancia -->
        <div class="card-glass p-8 flex flex-col items-center justify-center text-center space-y-4">
            <div class="size-40 flex items-center justify-center">
                <div class="relative">
                    <span class="material-symbols-outlined text-8xl <?= $activeRecalls > 0 ? 'text-red-500 animate-pulse' : 'text-slate-400' ?>">campaign</span>
                    <?php if ($activeRecalls > 0): ?>
                        <div class="absolute -top-2 -right-2 size-8 bg-red-500 text-white rounded-full flex items-center justify-center font-black text-sm border-4 border-medical-surface">
                            <?= $activeRecalls ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-widest">GCL 2.2 - Tecnovigilancia</h3>
                <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase mt-1">Eventos Adversos y Alertas Sanitarias</p>
            </div>
            <div class="px-4 py-2 <?= $activeRecalls == 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' ?> rounded-lg border border-current/20 text-[10px] font-black uppercase">
                <?= $activeRecalls == 0 ? 'Sin Alertas Activas' : 'Alertas por Resolver' ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Detalle de Recalls / Alertas Sanitarias -->
        <div class="card-glass p-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-red-500/10 text-red-500 rounded-2xl border border-red-500/20">
                    <span class="material-symbols-outlined font-variation-fill">warning</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-[var(--text-main)]">Alertas de Tecnovigilancia Recientes</h3>
                    <p class="text-[10px] text-[var(--text-muted)] font-black uppercase tracking-widest">Monitoreo de Safety Recalls (ISP/FDA)</p>
                </div>
            </div>

            <div class="space-y-4">
                <?php if (empty($recalls)): ?>
                    <p class="text-center py-8 text-[var(--text-muted)] text-xs font-bold uppercase tracking-widest">No hay registros de alertas sanitarias</p>
                <?php else: ?>
                    <?php foreach ($recalls as $recall): ?>
                        <div class="p-4 bg-medical-surface border border-border-dark rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="size-2 rounded-full <?= $recall['resolved'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                <div>
                                    <p class="text-sm font-bold text-[var(--text-main)]"><?= htmlspecialchars($recall['asset_name']) ?></p>
                                    <p class="text-[10px] text-[var(--text-muted)] font-bold"><?= $recall['reason'] ?></p>
                                </div>
                            </div>
                            <span class="text-[9px] font-black text-[var(--text-muted)] uppercase"><?= date('d M Y', strtotime($recall['recall_date'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evidencia de Capacitación y Hoja de Vida -->
        <div class="card-glass p-8 border-dashed border-2">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-2xl border border-indigo-500/20">
                    <span class="material-symbols-outlined font-variation-fill">school</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-[var(--text-main)]">Capacitaciones y Títulos</h3>
                    <p class="text-[10px] text-[var(--text-muted)] font-black uppercase tracking-widest">Registro de Competencias del Personal</p>
                </div>
            </div>

            <div class="flex flex-col items-center justify-center py-12 text-center space-y-4">
                <span class="material-symbols-outlined text-6xl text-slate-300">construction</span>
                <p class="text-xs text-[var(--text-muted)] font-bold uppercase tracking-widest">Módulo en Desarrollo</p>
                <p class="text-[10px] text-[var(--text-muted)] max-w-xs italic">Próximamente: Integración con base de datos de RRHH para validación de competencias técnicas en mantenimiento.</p>
            </div>
        </div>
    </div>
</div>