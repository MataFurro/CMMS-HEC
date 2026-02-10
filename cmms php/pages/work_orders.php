<?php
// pages/work_orders.php

// Mock Data for Work Orders
$orders = [
    [
        'id' => 'OT-2023-4582',
        'asset' => 'Ventilador Mecánico',
        'type' => 'Preventiva',
        'status' => 'Terminada',
        'tech' => 'Mario Gómez',
        'date' => '2023-10-15',
        'priority' => 'Alta'
    ],
    [
        'id' => 'OT-2023-4583',
        'asset' => 'Bomba de Infusión',
        'type' => 'Correctiva',
        'status' => 'En Proceso',
        'tech' => 'Pablo Rojas',
        'date' => '2023-10-16',
        'priority' => 'Media'
    ],
    [
        'id' => 'OT-2023-4584',
        'asset' => 'Monitor Multiparámetro',
        'type' => 'Calibración',
        'status' => 'Pendiente',
        'tech' => 'Ana Muñoz',
        'date' => '2023-10-20',
        'priority' => 'Baja'
    ]
];
?>

<div class="space-y-6">
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Órdenes de Trabajo</h1>
            <p class="text-slate-400 text-sm mt-1">Gestión y seguimiento de mantenimientos.</p>
        </div>
    </div>
    <?php if ($_SESSION['user_role'] !== 'Auditor'): ?>
        <a href="?page=work_order_opening" class="h-10 px-6 bg-medical-blue text-white rounded-xl font-bold hover:bg-medical-blue/90 flex items-center gap-2 transition-all shadow-lg shadow-medical-blue/20">
            <span class="material-symbols-outlined text-lg">add</span>
            Nueva Orden
        </a>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="card-glass p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500">
            <span class="material-symbols-outlined text-2xl">pending_actions</span>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">5</p>
            <p class="text-xs text-slate-500 font-bold uppercase">Pendientes</p>
        </div>
    </div>
    <div class="card-glass p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-500">
            <span class="material-symbols-outlined text-2xl">engineering</span>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">2</p>
            <p class="text-xs text-slate-500 font-bold uppercase">En Proceso</p>
        </div>
    </div>
    <div class="card-glass p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
            <span class="material-symbols-outlined text-2xl">check_circle</span>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">128</p>
            <p class="text-xs text-slate-500 font-bold uppercase">Terminadas (Mes)</p>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card-glass overflow-hidden shadow-xl">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-white/5 border-b border-slate-700/50">
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">ID Orden</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">Activo</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">Tipo</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-center">Prioridad</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-center">Estado</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">Técnico</th>
                <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            <?php foreach ($orders as $ot): ?>
                <tr class="hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4 font-mono text-sm text-medical-blue font-bold"><?= $ot['id'] ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-white"><?= $ot['asset'] ?></td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-300 uppercase"><?= $ot['type'] ?></td>
                    <td class="px-6 py-4 text-center">
                        <?php
                        $prioClass = match ($ot['priority']) {
                            'Alta' => 'text-red-500 bg-red-500/10 border-red-500/20',
                            'Media' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
                            default => 'text-slate-400 bg-slate-500/10 border-slate-500/20'
                        };
                        ?>
                        <span class="px-2 py-1 rounded text-[10px] font-black uppercase border <?= $prioClass ?>">
                            <?= $ot['priority'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php
                        $statusClass = match ($ot['status']) {
                            'Terminada' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                            'En Proceso' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                            'Pendiente' => 'text-slate-400 bg-slate-500/10 border-slate-500/20',
                            default => ''
                        };
                        ?>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase border <?= $statusClass ?>">
                            <?= $ot['status'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-400 font-bold"><?= $ot['tech'] ?></td>
                    <td class="px-6 py-4 text-right">
                        <a href="?page=work_order_execution&id=<?= $ot['id'] ?>" class="text-slate-400 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </a>
                        <?php if ($ot['status'] === 'En Proceso' && $_SESSION['user_role'] !== 'Auditor'): ?>
                            <a href="?page=work_order_execution&id=<?= $ot['id'] ?>&action=complete" class="ml-2 px-4 py-2 bg-emerald-500 text-white rounded-lg text-xs font-black uppercase hover:bg-emerald-600 transition-colors shadow-lg shadow-emerald-500/20" title="Completar Orden">
                                COMPLETAR
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>