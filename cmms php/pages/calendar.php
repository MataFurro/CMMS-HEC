<?php
// pages/calendar.php

$view = $_GET['view'] ?? 'month';

$weekDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

// Mock Data
$annualWorkload = [
    ['month' => 'Enero', 'ots' => 12],
    ['month' => 'Febrero', 'ots' => 8],
    ['month' => 'Marzo', 'ots' => 15],
    ['month' => 'Abril', 'ots' => 10],
    ['month' => 'Mayo', 'ots' => 22],
    ['month' => 'Junio', 'ots' => 14],
    ['month' => 'Julio', 'ots' => 9],
    ['month' => 'Agosto', 'ots' => 11],
    ['month' => 'Septiembre', 'ots' => 18],
    ['month' => 'Octubre', 'ots' => 13],
    ['month' => 'Noviembre', 'ots' => 7],
    ['month' => 'Diciembre', 'ots' => 5],
];

// Day Events Mock
$dayEvents = [
    ['time' => '09:30', 'title' => 'Mantenimiento Preventivo', 'equipment' => 'Monitor Multiparámetro', 'tech' => 'Mario Lagos', 'status' => 'PENDING', 'color' => 'medical-blue'],
    ['time' => '11:45', 'title' => 'Calibración de Sensores', 'equipment' => 'Incubadora Neonatal', 'tech' => 'Ana Muñoz', 'status' => 'IN_PROGRESS', 'color' => 'amber-500'],
    ['time' => '15:20', 'title' => 'Correctivo Urgente', 'equipment' => 'Desfibrilador Zoll', 'tech' => 'Carlos Ruiz', 'status' => 'PENDING', 'color' => 'danger'],
    ['time' => '17:00', 'title' => 'Ronda de Inspección', 'equipment' => 'Servicio de Urgencias', 'tech' => 'Equipo Biomédico', 'status' => 'PENDING', 'color' => 'slate-500'],
];

?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-white flex items-center gap-3">
                Agenda Técnica 2024
                <span class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">event_upcoming</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg">Cronograma maestro sincronizado con el módulo de Órdenes de Trabajo.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex bg-white/5 border border-border-dark p-1.5 rounded-2xl">
                <?php
                $views = [
                    ['id' => 'year', 'label' => 'Año'],
                    ['id' => 'month', 'label' => 'Mes'],
                    ['id' => 'week', 'label' => 'Semana'],
                    ['id' => 'day', 'label' => 'Día']
                ];
                foreach ($views as $v):
                    $isActive = $view === $v['id'];
                    $class = $isActive
                        ? 'bg-medical-blue text-white shadow-lg shadow-medical-blue/20'
                        : 'text-slate-500 hover:text-white';
                ?>
                    <a href="?page=calendar&view=<?= $v['id'] ?>" class="px-5 py-2 text-xs font-bold rounded-xl transition-all <?= $class ?>">
                        <?= $v['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <a href="?page=work_orders" class="flex items-center gap-3 px-6 py-3 border border-slate-700 text-slate-300 rounded-2xl hover:bg-white/5 transition-all font-bold text-sm">
                <span class="material-symbols-outlined text-xl">settings_applications</span>
                <span>Manejo de OTs</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar Legend (Common) -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-panel-dark p-6 rounded-3xl border border-border-dark shadow-xl">
                <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-6 px-1 text-center">Leyenda de Estados</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                        <div class="size-3 rounded-full bg-danger"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Correctivos Críticos</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                        <div class="size-3 rounded-full bg-medical-blue"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Preventivos Programados</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                        <div class="size-3 rounded-full bg-amber-500"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Calibraciones Pendientes</span>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-border-dark space-y-4">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Filtro por Servicio</p>
                    <div class="space-y-3">
                        <?php foreach (['UCI Adultos', 'Pabellón Central', 'Urgencias'] as $loc): ?>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" checked class="size-5 rounded-lg border-border-dark bg-background-dark text-medical-blue focus:ring-0" />
                                <span class="text-xs text-slate-400 group-hover:text-white font-bold uppercase tracking-wider transition-colors"><?= $loc ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="bg-medical-blue/5 border border-medical-blue/20 p-6 rounded-3xl flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-medical-blue text-3xl mb-3">sync</span>
                <h4 class="text-[10px] font-black text-medical-blue uppercase tracking-widest mb-2">Sincronización Activa</h4>
                <p class="text-xs font-bold text-white">Actualizado en tiempo real</p>
            </div>
        </div>

        <!-- Main Content Area based on View -->
        <div class="lg:col-span-9">

            <!-- MONTH VIEW -->
            <?php if ($view === 'month'): ?>
                <div class="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-300">
                    <div class="grid grid-cols-7 border-b border-border-dark bg-white/5">
                        <?php foreach ($weekDays as $day): ?>
                            <div class="py-5 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                <?= substr($day, 0, 2) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid grid-cols-7 auto-rows-[140px]">
                        <?php
                        // Mock month logic: 35 days grid
                        for ($i = -2; $i <= 32; $i++):
                            $isCurrentMonth = $i > 0 && $i <= 31;
                            $isToday = $i === 14;
                        ?>
                            <div class="p-3 border-r border-b border-border-dark transition-all hover:bg-white/[0.02] flex flex-col relative group <?= !$isCurrentMonth ? 'opacity-10 bg-slate-900/50' : '' ?> <?= $isToday ? 'bg-medical-blue/5' : '' ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-black font-mono <?= $isToday ? 'text-medical-blue' : 'text-slate-500' ?>">
                                        <?= $i <= 0 ? 30 + $i : ($i > 31 ? $i - 31 : $i) ?>
                                    </span>
                                    <?php if ($isToday): ?>
                                        <span class="size-2 rounded-full bg-medical-blue shadow-[0_0_12px_rgba(14,165,233,0.5)]"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1.5 overflow-hidden">
                                    <?php if ($i === 5): ?>
                                        <div class="px-2 py-1 bg-danger/10 border-l-2 border-danger rounded text-[9px] font-black text-danger truncate uppercase">OT #2024-0890</div>
                                    <?php elseif ($i === 12 || $i === 22): ?>
                                        <div class="px-2 py-1 bg-medical-blue/10 border-l-2 border-medical-blue rounded text-[9px] font-black text-medical-blue truncate uppercase">OT #2024-0885</div>
                                    <?php elseif ($i === 15): ?>
                                        <div class="px-2 py-1 bg-amber-500/10 border-l-2 border-amber-500 rounded text-[9px] font-black text-amber-500 truncate uppercase">OT #2024-0891</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- YEAR VIEW -->
            <?php elseif ($view === 'year'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-in fade-in zoom-in-95 duration-300">
                    <?php foreach ($annualWorkload as $idx => $item): ?>
                        <div class="bg-panel-dark rounded-3xl border border-border-dark p-6 hover:border-medical-blue/40 transition-all group relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="text-sm font-black text-white uppercase tracking-widest"><?= $item['month'] ?></h4>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded <?= $item['ots'] > 15 ? 'bg-danger/10 text-danger' : 'bg-medical-blue/10 text-medical-blue' ?>">
                                    <?= $item['ots'] ?> OTs
                                </span>
                            </div>
                            <div class="grid grid-cols-7 gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                <?php for ($d = 0; $d < 28; $d++): ?>
                                    <div class="aspect-square rounded-[2px] <?= ($d + $idx) % 7 === 0 ? 'bg-medical-blue/40' : 'bg-slate-800' ?>"></div>
                                <?php endfor; ?>
                            </div>
                            <button class="absolute inset-0 bg-medical-blue/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none group-hover:pointer-events-auto">
                                <span class="text-[10px] font-black text-medical-blue uppercase tracking-widest bg-medical-dark px-4 py-2 rounded-xl border border-medical-blue/30 shadow-2xl">Ver Detalle</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- WEEK VIEW -->
            <?php elseif ($view === 'week'): ?>
                <div class="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-border-dark bg-white/5">
                        <div class="py-4 border-r border-border-dark"></div>
                        <?php foreach ($weekDays as $idx => $day): ?>
                            <div class="py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-r border-border-dark/30">
                                <?= $day ?> <span class="block text-white opacity-40 mt-1">1<?= $idx + 3 ?> May</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="h-[600px] overflow-y-auto custom-scrollbar">
                        <?php foreach ($hours as $hour): ?>
                            <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-border-dark/30">
                                <div class="py-6 px-3 text-[10px] font-mono text-slate-600 border-r border-border-dark text-right font-bold uppercase"><?= $hour ?></div>
                                <?php foreach ($weekDays as $idx => $day): ?>
                                    <div class="border-r border-border-dark/30 relative min-h-[80px] hover:bg-white/[0.01] transition-colors">
                                        <?php if ($idx === 0 && $hour === '10:00'): ?>
                                            <div class="absolute inset-x-1 top-2 p-3 bg-medical-blue/20 border-l-4 border-medical-blue rounded-xl z-10 shadow-lg shadow-medical-blue/10">
                                                <p class="text-[9px] font-black text-white uppercase leading-none">OT #2024-0892</p>
                                                <p class="text-[8px] text-medical-blue font-bold mt-1 uppercase truncate">V. Mecánico PB840</p>
                                            </div>
                                        <?php elseif ($idx === 2 && $hour === '14:00'): ?>
                                            <div class="absolute inset-x-1 top-2 p-3 bg-danger/20 border-l-4 border-danger rounded-xl z-10">
                                                <p class="text-[9px] font-black text-white uppercase leading-none">URGENCIA CRÍTICA</p>
                                                <p class="text-[8px] text-danger font-bold mt-1 uppercase truncate">Rayos X Portátil</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- DAY VIEW -->
            <?php elseif ($view === 'day'): ?>
                <div class="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <div class="p-8 border-b border-border-dark bg-white/5 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white uppercase tracking-tight">Jueves, 14 de Mayo, 2024</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">4 Intervenciones Programadas para hoy</p>
                        </div>
                        <div class="flex items-center gap-4 bg-emerald-500/5 px-4 py-2 rounded-2xl border border-emerald-500/20">
                            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest text-center">Técnicos de Turno: 4/5 Disponibles</span>
                        </div>
                    </div>
                    <div class="p-8 space-y-8 h-[500px] overflow-y-auto custom-scrollbar">
                        <?php foreach ($dayEvents as $event): ?>
                            <div class="flex gap-8 group">
                                <div class="w-20 pt-1 shrink-0">
                                    <span class="text-lg font-mono font-black text-white"><?= $event['time'] ?></span>
                                    <div class="mt-2 h-full w-px bg-slate-800 mx-auto opacity-50 group-last:hidden"></div>
                                </div>
                                <div class="flex-1 card-glass p-6 border-l-4 border-l-<?= $event['color'] == 'medical-blue' ? 'medical-blue' : ($event['color'] == 'amber-500' ? 'amber-500' : ($event['color'] == 'danger' ? 'danger' : 'slate-500')) ?> hover:bg-white/[0.03] transition-all">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-white text-base"><?= $event['title'] ?></h4>
                                            <p class="text-sm text-slate-400 font-medium mt-1"><?= $event['equipment'] ?></p>
                                        </div>
                                        <?php
                                        $statusLabel = $event['status'] === 'IN_PROGRESS' ? 'En Curso' : 'Programada';
                                        $bgClass = match ($event['color']) {
                                            'medical-blue' => 'bg-medical-blue/10 text-medical-blue border-medical-blue/20',
                                            'amber-500' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                            'danger' => 'bg-danger/10 text-danger border-danger/20',
                                            default => 'bg-slate-500/10 text-slate-500 border-slate-500/20'
                                        };
                                        ?>
                                        <span class="text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest <?= $bgClass ?> border">
                                            <?= $statusLabel ?>
                                        </span>
                                    </div>
                                    <div class="mt-4 flex items-center gap-6">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-slate-500 text-sm">person</span>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase"><?= $event['tech'] ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-slate-500 text-sm">location_on</span>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase">Sector A - Piso 2</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6 flex justify-between items-center px-4 opacity-50">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">© BioCMMS Agenda - Sincronizado con v4.2 Pro</p>
                <div class="flex gap-4">
                    <span class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase"><span class="size-2 rounded-full bg-slate-700"></span> Feriado</span>
                    <span class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase"><span class="size-2 rounded-full bg-medical-blue"></span> Programado</span>
                </div>
            </div>
        </div>
    </div>
</div>