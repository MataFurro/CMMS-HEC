<?php
// pages/new_asset.php
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

// Verificar permisos - Solo Ingeniero/Admin puede crear activos
if (!canModify()) {
    header('Location: ?page=inventory');
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canModify()) {
        die('Acceso denegado. Solo Ingeniero/Admin puede crear activos.');
    }

    $result = saveAsset($_POST);

    if ($result) {
        echo "<script>alert('Activo registrado exitosamente.'); window.location.href='?page=inventory';</script>";
    } else {
        $error = "Error al guardar el activo en la base de datos.";
    }
}
?>

<div class="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[var(--text-main)] flex items-center gap-3">
                <span class="material-symbols-outlined text-medical-blue text-3xl">add_circle</span>
                Registro de Nuevo Activo
            </h1>
            <p class="text-[var(--text-muted)] mt-1 text-sm uppercase tracking-widest font-semibold opacity-70">
                Completar datos técnicos según norma CMMS HEC
            </p>
        </div>
        <a href="?page=inventory"
            class="p-2.5 rounded-xl bg-medical-surface border border-border-dark text-[var(--text-muted)] hover:text-medical-blue transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Volver
        </a>
    </div>

    <form method="POST" class="card-glass p-8 space-y-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-medical-blue/5 blur-3xl rounded-full -mr-16 -mt-16"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            <!-- Identification Section -->
            <div class="space-y-6">
                <h3
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">
                    Identificación de Equipo</h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">ID Inventario</label>
                    <input required name="id" placeholder="Ej: PB-840-00122"
                        class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Nombre del Equipo</label>
                    <input required name="name" placeholder="Ej: Ventilador Mecánico"
                        class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Marca</label>
                        <input required name="brand"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Modelo</label>
                        <input required name="model"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">N° de Serie</label>
                        <input required name="serial_number" placeholder="Ej: SN-992031-B"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Pertenencia</label>
                        <select name="ownership"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] appearance-none">
                            <option value="Propio">Propio</option>
                            <option value="Comodato">Comodato</option>
                            <option value="Arriendo">Arriendo</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Costo Adquisición</label>
                        <input type="number" step="0.01" name="acquisition_cost" placeholder="0.00"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Año de Compra</label>
                        <input type="number" name="purchased_year" value="<?= date('Y') ?>"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Bajo Plan de
                        Mantenimiento</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="underMaintenancePlan" value="1" checked
                                class="size-4 accent-medical-blue" />
                            <span class="text-sm text-[var(--text-muted)]">Sí</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="underMaintenancePlan" value="0"
                                class="size-4 accent-medical-blue" />
                            <span class="text-sm text-[var(--text-muted)]">No</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Technical Specs Section -->
            <div class="space-y-6">
                <h3
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">
                    Especificaciones Técnicas</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Ubicación</label>
                        <select required name="location"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] appearance-none">
                            <option value="">Seleccionar Ubicación</option>
                            <?php foreach (getAllLocations() as $loc): ?>
                                <option value="<?= $loc ?>"><?= $loc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Sub-Ubicación</label>
                        <input name="sub_location" placeholder="Ej: Box 04 / Cama 4"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Criticidad</label>
                        <select required name="criticality"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] appearance-none">
                            <option value="CRITICAL">Crítico</option>
                            <option value="RELEVANT" selected>Relevante</option>
                            <option value="LOW">No Aplica</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Familia / Clase</label>
                        <select name="riesgo_ge"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] appearance-none">
                            <option value="">Sin Clase</option>
                            <?php foreach (getCategoryOptions() as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Código UMDNS (Opcional/NA)</label>
                        <input name="codigo_umdns" placeholder="Ej: 17-429"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Fecha Instalación</label>
                        <input type="date" name="fecha_instalacion"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Proveedor</label>
                        <input name="vendor" placeholder="Ej: Draeger Medical"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Vencimiento Garantía</label>
                        <input type="date" name="warranty_expiration"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Vida Útil Total (Años)</label>
                        <input type="number" name="total_useful_life" value="10"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Años Restantes</label>
                        <input type="number" name="years_remaining" value="10"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Estado Inicial</label>
                        <select name="status"
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] appearance-none">
                            <option value="OPERATIVE">OPERATIVO</option>
                            <option value="MAINTENANCE">EN MANTENCIÓN</option>
                            <option value="NO_OPERATIVE">FUERA DE SERVICIO</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">URL Imagen (Opcional)</label>
                        <input name="image_url" placeholder="https://..."
                            class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]" />
                    </div>
                </div>

                <!-- Observations Field Additions -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider block mb-1">Observaciones Generales</label>
                    <textarea name="observations" rows="2"
                        class="w-full bg-medical-surface border border-border-dark rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)] resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-border-dark/50 flex justify-end gap-4">
            <a href="?page=inventory"
                class="px-8 py-3 rounded-2xl text-[var(--text-muted)] hover:text-[var(--text-main)] hover:bg-slate-200/50 dark:hover:bg-slate-800 transition-all text-sm font-bold uppercase tracking-wider text-center">
                Cancelar
            </a>
            <button type="submit"
                class="px-10 py-3 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20 active:scale-95 flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">save</span>
                Guardar Activo
            </button>
        </div>
    </form>
</div>