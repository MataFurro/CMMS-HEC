<?php
// pages/new_asset.php

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, sanitize and save to DB
    // $data = $_POST;
    // saveAsset($data);

    // Simulate success
    echo "<script>alert('Activo registrado exitosamente (Simulado)'); window.location.href='?page=inventory';</script>";
}
?>

<div class="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                <span class="material-symbols-outlined text-medical-blue text-3xl">add_circle</span>
                Registro de Nuevo Activo
            </h1>
            <p class="text-slate-400 mt-1 text-sm uppercase tracking-widest font-semibold opacity-70">
                Completar datos técnicos según norma CMMS HEC
            </p>
        </div>
        <a href="?page=inventory" class="p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Volver
        </a>
    </div>

    <form method="POST" class="card-glass p-8 space-y-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-medical-blue/5 blur-3xl rounded-full -mr-16 -mt-16"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            <!-- Identification Section -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Identificación de Equipo</h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">ID Inventario</label>
                    <input required name="id" placeholder="Ej: PB-840-00122" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nombre del Equipo</label>
                    <input required name="name" placeholder="Ej: Ventilador Mecánico" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Marca</label>
                        <input required name="brand" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Modelo</label>
                        <input required name="model" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bajo Plan de Mantenimiento</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="underMaintenancePlan" value="1" checked class="size-4 accent-medical-blue" />
                            <span class="text-sm text-slate-300">Sí</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="underMaintenancePlan" value="0" class="size-4 accent-medical-blue" />
                            <span class="text-sm text-slate-300">No</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Technical Specs Section -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Especificaciones Técnicas</h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ubicación</label>
                    <select required name="location" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                        <option value="">Seleccionar Ubicación</option>
                        <option value="UCI Adultos">UCI Adultos</option>
                        <option value="Urgencias">Urgencias</option>
                        <option value="Pabellón Central">Pabellón Central</option>
                        <option value="Imagenología">Imagenología</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Criticidad</label>
                    <select required name="criticality" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                        <option value="CRITICAL">CRITICAL</option>
                        <option value="RELEVANT" selected>RELEVANT</option>
                        <option value="LOW">LOW</option>
                        <option value="NA">NA</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Garantía (Proveedor)</label>
                        <input name="warranty" placeholder="Ej: Medtronic Chile" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Vencimiento Garantía</label>
                        <input type="date" name="warrantyExpiration" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Vida Útil (%)</label>
                        <input type="number" name="usefulLife" value="100" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Años Restantes</label>
                        <input type="number" name="yearsRemaining" value="10" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-700/50 flex justify-end gap-4">
            <a href="?page=inventory" class="px-8 py-3 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-bold uppercase tracking-wider text-center">
                Cancelar
            </a>
            <button type="submit" class="px-10 py-3 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20 active:scale-95 flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">save</span>
                Guardar Activo
            </button>
        </div>
    </form>
</div>