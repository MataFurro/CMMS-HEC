<?php
// pages/service_request.php - Client view to create service requests

if (!canRequestService()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1></div>";
    return;
}

// Mock Success Message
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
}

// Sample Assets for Selection
$assets = [
    ['id' => 'PB-840-00122', 'name' => 'Ventilador Mecánico PB840', 'location' => 'UCI Piso 3'],
    ['id' => 'DEF-ZOLL-99', 'name' => 'Desfibrilador ZOLL X', 'location' => 'Pabellón 4'],
    ['id' => 'MON-B450-44', 'name' => 'Monitor B450', 'location' => 'Urgencias'],
];
?>

<div class="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-medical-blue/10 border border-medical-blue/20 flex items-center justify-center text-medical-blue shadow-xl shadow-medical-blue/5">
            <span class="material-symbols-outlined text-3xl font-variation-fill">add_alert</span>
        </div>
        <div>
            <h1 class="text-3xl font-black text-white tracking-tight">Solicitud de Servicio</h1>
            <p class="text-slate-400 font-medium">Reporte de fallas o requerimientos técnicos.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-2xl flex items-center gap-4 text-emerald-500 shadow-xl shadow-emerald-500/5 animate-in zoom-in duration-300">
            <span class="material-symbols-outlined text-4xl">check_circle</span>
            <div>
                <p class="font-black uppercase tracking-widest text-sm">Solicitud Generada Exitosamente</p>
                <p class="text-emerald-500/80 text-xs mt-1">ID Seguimiento: <span class="font-mono">SOL-2026-0045</span>. Un ingeniero revisará su requerimiento en breve.</p>
            </div>
            <button onclick="window.location.href='?page=dashboard'" class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all">Ir al Inicio</button>
        </div>
    <?php endif; ?>

    <form method="POST" class="card-glass p-8 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Asset Selection -->
            <div class="space-y-3">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                    Seleccionar Equipo
                </label>
                <select name="active_id" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-medical-blue focus:ring-4 focus:ring-medical-blue/10 transition-all appearance-none cursor-pointer">
                    <option value="" disabled selected>Elija el equipo con la falla...</option>
                    <?php foreach ($assets as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= $a['name'] ?> (<?= $a['id'] ?>) - <?= $a['location'] ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-slate-500 font-medium italic">Si el equipo no aparece, contacte a soporte técnico directamente.</p>
            </div>

            <!-- Priority -->
            <div class="space-y-3">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">priority_high</span>
                    Urgencia Percibida
                </label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="priority" value="Baja" class="hidden peer">
                        <div class="p-3 text-center border border-slate-700/50 rounded-xl text-slate-500 group-hover:bg-slate-800 peer-checked:bg-slate-700 peer-checked:text-white peer-checked:border-slate-500 transition-all">
                            <p class="text-[10px] font-black uppercase">Baja</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="priority" value="Media" class="hidden peer" checked>
                        <div class="p-3 text-center border border-slate-700/50 rounded-xl text-slate-500 group-hover:bg-amber-500/10 peer-checked:bg-amber-500/20 peer-checked:text-amber-500 peer-checked:border-amber-500/40 transition-all">
                            <p class="text-[10px] font-black uppercase">Media</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="priority" value="Alta" class="hidden peer">
                        <div class="p-3 text-center border border-slate-700/50 rounded-xl text-slate-500 group-hover:bg-red-500/10 peer-checked:bg-red-500/20 peer-checked:text-red-500 peer-checked:border-red-500/40 transition-all">
                            <p class="text-[10px] font-black uppercase">Crítica</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="space-y-3">
            <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">description</span>
                Descripción del Problema
            </label>
            <textarea name="problem" rows="4" required placeholder="Describa brevemente qué sucede con el equipo (ej: no enciende, mensaje de error en pantalla, falla de sensor...)" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-medical-blue focus:ring-4 focus:ring-medical-blue/10 transition-all resize-none"></textarea>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-700/50">
            <div class="flex items-center gap-3 text-slate-500">
                <span class="material-symbols-outlined text-xl">verified_user</span>
                <p class="text-[10px] uppercase font-black tracking-widest">Su identidad digital será vinculada al reporte</p>
            </div>
            <button type="submit" class="h-12 px-10 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-medical-blue/90 flex items-center gap-3 transition-all shadow-xl shadow-medical-blue/20 active:scale-95">
                <span class="material-symbols-outlined text-xl">send</span>
                Enviar Solicitud
            </button>
        </div>
    </form>
</div>