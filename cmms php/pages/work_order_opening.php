<?php
// pages/work_order_opening.php

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, save to DB
    // $data = $_POST;
    // createWorkOrder($data);

    // Simulate success
    echo "<script>alert('Orden de Trabajo generada exitosamente (Simulado). ID: OT-2024-NEW'); window.location.href='?page=work_orders';</script>";
}
?>

<div class="max-w-3xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                <span className="material-symbols-outlined text-medical-blue text-3xl">add_task</span>
                Nueva Orden de Trabajo
            </h1>
            <p class="text-slate-400 mt-1 text-sm uppercase tracking-widest font-semibold opacity-70">
                Apertura de Solicitud de Mantenimiento
            </p>
        </div>
        <a href="?page=work_orders" class="p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Volver
        </a>
    </div>

    <form method="POST" class="card-glass p-8 space-y-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 blur-3xl rounded-full -mr-16 -mt-16"></div>

        <div class="grid grid-cols-1 gap-8 relative z-10">
            <!-- Asset Selection -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Activo Afectado</h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Buscar Activo (ID o Nombre)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">search</span>
                        <input name="asset_search" placeholder="Ej: Ventilador UCI-04..." class="w-full bg-slate-900 border border-slate-700/50 rounded-xl pl-12 pr-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                    <p class="text-[10px] text-slate-500 italic">Simulación: Escribe cualquier ID para continuar.</p>
                </div>
            </div>

            <!-- OT Details -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Detalles de la Solicitud</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tipo de Orden</label>
                        <select required name="type" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                            <option value="Correctivo">Correctivo (Falla)</option>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Calibración">Calibración</option>
                            <option value="Instalación">Instalación</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Prioridad</label>
                        <select required name="priority" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                            <option value="Alta">Alta (Crítico)</option>
                            <option value="Media" selected>Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción del Problema / Solicitud</label>
                    <textarea required name="description" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl p-4 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[120px] text-white placeholder:text-slate-600" placeholder="Describa la falla, código de error o motivo del mantenimiento..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Solicitante</label>
                        <input name="requestor" value="<?= $_SESSION['user_name'] ?? '' ?>" readonly class="w-full bg-slate-800 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-slate-400 cursor-not-allowed" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Asignar a (Opcional)</label>
                        <select name="assigned_tech" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                            <option value="">-- Sin Asignar --</option>
                            <option value="Mario Gómez">Mario Gómez</option>
                            <option value="Pablo Rojas">Pablo Rojas</option>
                            <option value="Ana Muñoz">Ana Muñoz</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-700/50 flex justify-end gap-4">
            <a href="?page=work_orders" class="px-8 py-3 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-bold uppercase tracking-wider text-center">
                Cancelar
            </a>
            <button type="submit" class="px-10 py-3 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20 active:scale-95 flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">send</span>
                Generar Orden
            </button>
        </div>
    </form>
</div>