<?php
// pages/service_request_review.php - Engineer view to process solicitudes

if ($_SESSION['user_role'] !== 'Ingeniero') {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1></div>";
    return;
}

// ── Backend Provider ──
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

// Mock Data for Requests
$requests = [
    [
        'id' => 'SOL-2026-0045',
        'asset_name' => 'Monitor Multiparámetro',
        'asset_id' => 'MON-B450-44',
        'client' => 'Dr. Solicitante',
        'problem' => 'Pantalla parpadea y se apaga aleatoriamente durante el uso.',
        'date' => '2026-02-11 14:10',
        'priority' => 'Alta',
        'tech_suggested' => 'Mario Gómez (Senior)'
    ],
    [
        'id' => 'SOL-2026-0042',
        'asset_name' => 'Bomba de Infusión',
        'asset_id' => 'BOM-INF-12',
        'client' => 'Enf. Unidades Críticas',
        'problem' => 'Error de oclusión persistente sin obstrucción visible.',
        'date' => '2026-02-11 09:30',
        'priority' => 'Media',
        'tech_suggested' => 'Pablo Rojas (Especialista)'
    ]
];

// Mock Success & Execution Plan
$convertedId = '';
if (isset($_POST['action']) && $_POST['action'] === 'convert' && isset($_POST['req_id'])) {
    $reqId = $_POST['req_id'];

    // Buscar la solicitud para extraer datos
    foreach ($requests as $req) {
        if ($req['id'] === $reqId) {
            $convertedId = createWorkOrderFromRequest([
                'asset_id' => $req['asset_id'],
                'asset_name' => $req['asset_name'],
                'problem' => $_POST['diagnosis'] ?? $req['problem'],
                'priority' => $req['priority'],
                'tech' => $_POST['tech'] ?? $req['tech_suggested'],
                'type' => $_POST['intervention_type'] ?? 'Mantenimiento Correctivo'
            ]);
            break;
        }
    }
}
?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-black text-white tracking-tight">Revisión de Solicitudes</h1>
            <p class="text-slate-400 font-medium">Validación técnica y conversión a Órdenes de Trabajo.</p>
        </div>
        <div class="flex gap-4">
            <div class="card-glass px-4 py-2 flex items-center gap-3">
                <span class="text-2xl font-black text-medical-blue"><?= count($requests) ?></span>
                <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Pendientes</span>
            </div>
        </div>
    </div>

    <?php if ($convertedId): ?>
        <div class="bg-blue-500/10 border border-blue-500/20 p-6 rounded-2xl flex items-center gap-4 text-blue-500 shadow-xl shadow-blue-500/5 animate-in slide-in-from-top-4 duration-300">
            <span class="material-symbols-outlined text-4xl">task_alt</span>
            <div>
                <p class="font-black uppercase tracking-widest text-sm">Solicitud Convertida a OT</p>
                <p class="text-blue-500/80 text-xs mt-1">La solicitud ha sido procesada con éxito. ID Generado: <span class="font-mono font-bold"><?= $convertedId ?></span>.</p>
            </div>
            <button onclick="window.location.href='?page=work_orders'" class="ml-auto px-4 py-2 bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Ver Órdenes</button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($requests as $req): ?>
            <div class="card-glass overflow-hidden group hover:border-medical-blue/30 transition-all duration-300">
                <div class="p-6 flex flex-col md:flex-row gap-8">
                    <!-- Basic Info -->
                    <div class="md:w-1/3 space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-slate-800 text-slate-500 text-[10px] font-black rounded uppercase border border-slate-700/50"><?= $req['id'] ?></span>
                            <span class="text-xs text-slate-500 font-bold"><?= $req['date'] ?></span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white"><?= $req['asset_name'] ?></h3>
                            <p class="text-xs text-medical-blue font-bold tracking-widest uppercase mt-1"><?= $req['asset_id'] ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <img src="https://i.pravatar.cc/150?u=doc" class="size-6 rounded-full border border-slate-700" alt="Client">
                            <p class="text-xs text-slate-400 font-semibold"><?= $req['client'] ?></p>
                        </div>
                    </div>

                    <!-- Problem Description -->
                    <div class="flex-1 bg-slate-900/40 rounded-2xl p-6 border border-slate-800/50">
                        <label class="text-[10px] font-black uppercase text-slate-600 tracking-widest mb-3 block">Problema Reportado</label>
                        <p class="text-sm text-slate-300 leading-relaxed font-medium italic">"<?= $req['problem'] ?>"</p>

                        <!-- Priority Badge -->
                        <div class="mt-4">
                            <?php $pClass = $req['priority'] === 'Alta' ? 'text-red-500 bg-red-500/10 border-red-500/20' : 'text-amber-500 bg-amber-500/10 border-amber-500/20'; ?>
                            <span class="px-2 py-1 rounded text-[9px] font-black uppercase border <?= $pClass ?>">Urgencia: <?= $req['priority'] ?></span>
                        </div>
                    </div>

                    <!-- Action Panel (Engineering Input) -->
                    <div class="md:w-1/3 flex flex-col justify-center gap-4">
                        <button onclick="document.getElementById('modal-<?= $req['id'] ?>').classList.remove('hidden')" class="w-full h-12 bg-medical-blue text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-medical-blue/90 flex items-center justify-center gap-2 transition-all shadow-lg shadow-medical-blue/10">
                            <span class="material-symbols-outlined text-lg">edit_note</span>
                            Completar Antecedentes
                        </button>
                    </div>
                </div>

                <!-- Simulation Modal (Hidden by default) -->
                <div id="modal-<?= $req['id'] ?>" class="hidden bg-slate-950/80 backdrop-blur-md p-8 border-t border-slate-800 animate-in fade-in duration-300">
                    <form method="POST" class="max-w-2xl mx-auto space-y-6">
                        <input type="hidden" name="action" value="convert">
                        <input type="hidden" name="req_id" value="<?= $req['id'] ?>">
                        <h4 class="text-lg font-black text-white uppercase tracking-tight flex items-center gap-3">
                            <span class="material-symbols-outlined text-medical-blue">engineering</span>
                            Análisis Técnico Sugerido
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-2 block">Antecedentes y Diagnóstico Preliminar</label>
                                <textarea name="diagnosis" placeholder="Ingeniero: Agregue antecedentes técnicos aquí..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-medical-blue h-24 resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-2 block">Asignar Técnico</label>
                                    <select name="tech" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-medical-blue">
                                        <option>Mario Gómez (Senior)</option>
                                        <option>Pablo Rojas (Especialista)</option>
                                        <option>Ana Muñoz (Calibración)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-2 block">Tipo de Intervención</label>
                                    <select name="intervention_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-medical-blue">
                                        <option>Mantenimiento Correctivo</option>
                                        <option>Revisión Técnica</option>
                                        <option>Validación Operativa</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" onclick="document.getElementById('modal-<?= $req['id'] ?>').classList.add('hidden')" class="px-6 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest">Cancelar</button>
                            <button type="submit" class="px-8 py-2.5 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 shadow-lg shadow-emerald-500/20">
                                Emitir Orden de Trabajo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>