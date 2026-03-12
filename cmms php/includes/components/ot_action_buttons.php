<?php
// includes/components/ot_action_buttons.php
// Botones de acción para la ejecución de OT.
// Requiere: $isCompleted, $ot, canExecuteWorkOrder() en scope.
?>

<?php if (canExecuteWorkOrder()): ?>
    <?php if (($ot['status'] ?? '') === 'En Espera'): ?>
        <!-- BOTÓN REANUDAR -->
        <button type="submit" form="executionForm" formnovalidate
            @click="formAction = 'resume_ot'; $nextTick(() => { isSubmitting = true; })"
            :disabled="isSubmitting"
            class="w-full py-5 rounded-3xl text-[12px] font-black uppercase tracking-[0.2em] text-white bg-emerald-500 hover:bg-emerald-600 hover:scale-[1.02] active:scale-95 shadow-2xl shadow-emerald-500/20 transition-all flex items-center justify-center gap-4 outline-none border-none cursor-pointer mb-4 disabled:opacity-50">
            <template x-if="!isSubmitting">
                <div class="flex items-center gap-3">
                    <span>Reanudar Ejecución</span>
                    <span class="material-symbols-outlined text-xl font-variation-fill">play_circle</span>
                </div>
            </template>
            <template x-if="isSubmitting">
                <div class="flex items-center gap-3">
                    <span>Procesando...</span>
                    <span class="material-symbols-outlined text-xl animate-spin">progress_activity</span>
                </div>
            </template>
        </button>

        <button type="submit" form="executionForm" formnovalidate
            @click="
                if(!confirm('¿Está seguro de que desea CANCELAR esta Orden Técnica?')) {
                    $event.preventDefault();
                    return;
                }
                formAction = 'cancel_ot'; 
                $nextTick(() => { isSubmitting = true; });"
            :disabled="isSubmitting"
            class="w-full py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-red-500 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 transition-all flex items-center justify-center gap-2 outline-none cursor-pointer">
            <span class="material-symbols-outlined text-sm">cancel</span>
            <span>Cancelar OT</span>
        </button>

    <?php elseif (!$isCompleted): ?>
        <!-- BOTÓN FINALIZAR -->
        <button type="submit" form="executionForm"
            @click="
                if(!isCompleting) { 
                    isCompleting = true; 
                    $event.preventDefault(); 
                } else {
                    const allRadios = document.querySelectorAll('input[name^=\'q_\']');
                    const uniqueNames = [...new Set([...allRadios].map(r => r.name))];
                    const checkedNames = [...new Set([...document.querySelectorAll('input[name^=\'q_\']:checked')].map(r => r.name))];
                    const missing = uniqueNames.length - checkedNames.length;
                    
                    if(missing > 0) {
                        showValidationWarning = true;
                        validationMissing = missing;
                        $event.preventDefault();
                        return;
                    }
                    showValidationWarning = false;
                    formAction = 'complete_ot'; 
                    $nextTick(() => { isSubmitting = true; }); 
                }
            "
            :disabled="isSubmitting"
            :class="isCompleting ? 'bg-emerald-600 shadow-emerald-600/20' : 'bg-medical-blue shadow-medical-blue/20'"
            class="w-full py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white hover:scale-[1.02] active:scale-95 shadow-xl transition-all flex items-center justify-center gap-3 outline-none border-none cursor-pointer mb-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="!isSubmitting">
                <div class="flex items-center gap-3">
                    <span x-text="isCompleting ? 'Confirmar Finalización' : 'Finalizar OT'"></span>
                    <span class="material-symbols-outlined text-sm" x-text="isCompleting ? 'check_circle' : 'rocket_launch'"></span>
                </div>
            </template>
            <template x-if="isSubmitting">
                <div class="flex items-center gap-3">
                    <span>Procesando...</span>
                    <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                </div>
            </template>
        </button>

        <!-- Aviso de validación -->
        <template x-if="showValidationWarning">
            <div class="mb-3 p-4 bg-red-500/10 border border-red-500/30 rounded-2xl flex items-start gap-3 animate-in fade-in slide-in-from-top duration-300">
                <span class="material-symbols-outlined text-red-500 text-xl mt-0.5">warning</span>
                <div>
                    <p class="text-xs font-bold text-red-500">No se puede finalizar</p>
                    <p class="text-[10px] text-[var(--text-muted)] mt-1">
                        Para completar la OT se necesita llenar <strong class="text-red-500" x-text="validationMissing"></strong> campo(s) pendiente(s).
                    </p>
                </div>
                <button type="button" @click="showValidationWarning = false" class="ml-auto text-[var(--text-muted)] hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        </template>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <!-- BOTÓN GUARDAR -->
            <button type="submit" form="executionForm" formnovalidate
                @click="formAction = 'save_draft'; $nextTick(() => { isSubmitting = true; })"
                :disabled="isSubmitting"
                class="py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-[var(--text-main)] bg-[var(--input-bg)] border border-[var(--border-color)] hover:bg-black/5 hover:border-medical-blue/50 transition-all outline-none cursor-pointer flex flex-col items-center justify-center gap-1 disabled:opacity-50">
                <template x-if="!isSubmitting">
                    <div class="flex flex-col items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xl text-medical-blue/70">save</span>
                        <span>Guardar</span>
                    </div>
                </template>
                <template x-if="isSubmitting">
                    <div class="flex flex-col items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xl animate-spin">progress_activity</span>
                        <span>...</span>
                    </div>
                </template>
            </button>

            <!-- BOTÓN PAUSAR -->
            <button type="submit" form="executionForm" formnovalidate
                @click="stallReason = 'Pausada por el técnico'; formAction = 'stall_ot'; $nextTick(() => { isSubmitting = true; })"
                :disabled="isSubmitting"
                class="py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-amber-600 bg-amber-500/10 hover:bg-amber-500/20 transition-all border border-amber-500/20 outline-none cursor-pointer flex flex-col items-center justify-center gap-1 disabled:opacity-50">
                <span class="material-symbols-outlined text-xl">pause_circle</span>
                <span>Pausar</span>
            </button>
        </div>

        <button type="submit" form="executionForm" formnovalidate
            @click="
                if(!confirm('¿Está seguro de que desea CANCELAR esta Orden Técnica?')) {
                    $event.preventDefault();
                    return;
                }
                formAction = 'cancel_ot'; 
                $nextTick(() => { isSubmitting = true; });"
            :disabled="isSubmitting"
            class="w-full py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-red-500 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 transition-all flex items-center justify-center gap-2 outline-none cursor-pointer">
            <span class="material-symbols-outlined text-sm">cancel</span>
            <span>Cancelar OT</span>
        </button>
    <?php endif; ?>
<?php endif; ?>