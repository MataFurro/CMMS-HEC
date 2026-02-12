<?php
// pages/work_orders.php

// ── Backend Provider ──
require_once __DIR__ . '/../backend/providers/WorkOrderProvider.php';

$orders = getAllWorkOrders();
$stats = getWorkOrderStats();
?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 mb-4">
                <span
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue bg-medical-blue/10 px-2 py-0.5 rounded">Operaciones</span>
                <span class="material-symbols-outlined text-xs text-slate-600">chevron_right</span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Gestión de
                    Mantenimiento</span>
            </nav>
            <h1 class="text-4xl font-black text-white tracking-tight flex items-center gap-4">
                Órdenes de Trabajo
                <span class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">task_alt</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg font-medium italic opacity-80">Seguimiento y ejecución de
                intervenciones técnicas en tiempo real.</p>
        </div>
        <?php if (canModify()): ?>
            <a href="?page=work_order_opening"
                class="group h-12 px-8 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-medical-blue/90 flex items-center gap-3 transition-all shadow-xl shadow-medical-blue/20 active:scale-95">
                <span class="material-symbols-outlined text-xl transition-transform group-hover:rotate-12">add_circle</span>
                Generar Nueva Orden
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="card-glass p-4 mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Tipo</label>
                <select id="filter-tipo"
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                    <option value="">Todos</option>
                    <option value="Preventiva">Preventiva</option>
                    <option value="Correctiva">Correctiva</option>
                    <option value="Calibración">Calibración</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Estado</label>
                <select id="filter-estado"
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                    <option value="">Todos</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Terminada">Terminada</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Desde</label>
                <input type="date" id="filter-desde"
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Hasta</label>
                <input type="date" id="filter-hasta"
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="applyFilters()"
                class="px-4 py-2 bg-medical-blue text-white rounded-lg font-bold hover:bg-medical-blue/90 transition-all text-sm uppercase tracking-wider">
                Filtrar
            </button>
            <button onclick="clearFilters()"
                class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg font-bold hover:bg-slate-600 transition-all text-sm uppercase tracking-wider">
                Limpiar
            </button>
        </div>
    </div>

    <!-- Stats Row con Diseño Premium -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card-glass p-0 overflow-hidden group hover:border-medical-blue/30 transition-all duration-300">
            <div class="p-6 flex items-center gap-6">
                <div
                    class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-500 shadow-inner group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">pending_actions</span>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Pendientes</p>
                    <p class="text-3xl font-black text-white mt-1">
                        <?= str_pad($stats['PENDING'] ?? 0, 2, '0', STR_PAD_LEFT) ?> <span
                            class="text-[10px] font-medium text-slate-600">unids</span></p>
                </div>
            </div>
            <div class="h-1 w-full bg-slate-800">
                <div class="h-full bg-blue-500 w-[20%] shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
            </div>
        </div>

        <div class="card-glass p-0 overflow-hidden group hover:border-amber-500/30 transition-all duration-300">
            <div class="p-6 flex items-center gap-6">
                <div
                    class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 shadow-inner group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">engineering</span>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">En Proceso</p>
                    <p class="text-3xl font-black text-white mt-1">
                        <?= str_pad($stats['IN_PROGRESS'] ?? 0, 2, '0', STR_PAD_LEFT) ?> <span
                            class="text-[10px] font-medium text-slate-600">unids</span></p>
                </div>
            </div>
            <div class="h-1 w-full bg-slate-800">
                <div class="h-full bg-amber-500 w-[10%] shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
            </div>
        </div>

        <div class="card-glass p-0 overflow-hidden group hover:border-emerald-500/30 transition-all duration-300">
            <div class="p-6 flex items-center gap-6">
                <div
                    class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 shadow-inner group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">check_circle</span>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Terminadas</p>
                    <p class="text-3xl font-black text-white mt-1">
                        <?= str_pad($stats['COMPLETED'] ?? 0, 2, '0', STR_PAD_LEFT) ?> <span
                            class="text-[10px] font-medium text-slate-600">unids</span></p>
                </div>
            </div>
            <div class="h-1 w-full bg-slate-800">
                <div class="h-full bg-emerald-500 w-[70%] shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
            </div>
        </div>

        <div class="card-glass p-0 overflow-hidden group hover:border-red-500/30 transition-all duration-300">
            <div class="p-6 flex items-center gap-6">
                <div
                    class="w-14 h-14 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 shadow-inner group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">emergency_home</span>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Críticas Hoy</p>
                    <p class="text-3xl font-black text-white mt-1">
                        <?= str_pad($stats['CRITICAL_TODAY'] ?? 0, 2, '0', STR_PAD_LEFT) ?> <span
                            class="text-[10px] font-medium text-slate-600">unids</span></p>
                </div>
            </div>
            <div class="h-1 w-full bg-slate-800">
                <div class="h-full bg-red-500 w-[5%] shadow-[0_0_10px_rgba(239,68,68,0.5)]"></div>
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            const tipo = document.getElementById('filter-tipo').value;
            const estado = document.getElementById('filter-estado').value;
            const desde = document.getElementById('filter-desde').value;
            const hasta = document.getElementById('filter-hasta').value;

            const rows = document.querySelectorAll('.ot-row');

            rows.forEach(row => {
                let show = true;

                if (tipo && row.dataset.tipo !== tipo) show = false;
                if (estado && row.dataset.estado !== estado) show = false;
                if (desde && row.dataset.fecha < desde) show = false;
                if (hasta && row.dataset.fecha > hasta) show = false;

                row.style.display = show ? '' : 'none';
            });
        }

        function clearFilters() {
            document.getElementById('filter-tipo').value = '';
            document.getElementById('filter-estado').value = '';
            document.getElementById('filter-desde').value = '';
            document.getElementById('filter-hasta').value = '';

            document.querySelectorAll('.ot-row').forEach(row => {
                row.style.display = '';
            });
        }
    </script>

    <!-- Table -->
    <div class="card-glass overflow-hidden shadow-xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-slate-700/50">
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">fingerprint</span>
                            ID Orden
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                            Activo
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">category</span>
                            Tipo
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">priority_high</span>
                            Prioridad
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">state_managed</span>
                            Estado
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">engineering</span>
                            Técnico
                        </div>
                    </th>
                    <th class="px-6 py-4 text-xs font-black uppercase tracking-wider text-slate-500 text-right">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php foreach ($orders as $ot): ?>
                    <tr class="ot-row hover:bg-white/5 transition-colors" data-tipo="<?= $ot['type'] ?>"
                        data-estado="<?= $ot['status'] ?>" data-fecha="<?= $ot['date'] ?>">
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
                            <span
                                class="px-3 py-1 rounded-full text-[10px] font-black uppercase border <?= $statusClass ?>">
                                <?= $ot['status'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-slate-500 text-sm">person</span>
                                <span class="text-xs text-slate-300 font-bold"><?= $ot['tech'] ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="?page=work_order_execution&id=<?= $ot['id'] ?>"
                                    class="p-2 bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-all border border-slate-700/50"
                                    title="Ver Detalles">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </a>
                                <?php if ($ot['status'] !== 'Terminada' && canExecuteWorkOrder()): ?>
                                    <a href="?page=work_order_execution&id=<?= $ot['id'] ?>&action=complete"
                                        class="px-4 py-2 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg text-[10px] font-black uppercase hover:bg-emerald-500 hover:text-white transition-all shadow-lg shadow-emerald-500/5">
                                        EJECUTAR
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>