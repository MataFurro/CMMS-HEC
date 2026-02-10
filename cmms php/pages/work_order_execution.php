<?php
// pages/work_order_execution.php

$id = $_GET['id'] ?? 'OT-2024-UNKNOWN';
$isCompleted = ($id === 'OT-2024-0742'); // Mock Logic

$attachments = [
    ['name' => 'Protocolo_Seguridad_Electrica_IEC62353.pdf', 'type' => 'pdf', 'size' => '1.2 MB', 'date' => '10/05/2024'],
    ['name' => 'Certificado_Calibracion_Fluke.pdf', 'type' => 'pdf', 'size' => '840 KB', 'date' => '10/05/2024'],
    ['name' => 'Captura_Falla_Sensor.jpg', 'type' => 'image', 'size' => '2.1 MB', 'date' => '10/05/2024']
];

$referenceDocs = [
    ['name' => 'Manual_Servicio_PB840.pdf', 'category' => 'Referencia Técnica'],
    ['name' => 'Protocolo_Preventivo_Standard.pdf', 'category' => 'Checklist Guía']
];

$checks = ['Seguridad Eléctrica (Fugas)', 'Inspección Visual Chasis', 'Prueba de Batería', 'Calibración Sensores O2'];
?>

<div class="max-w-[1200px] mx-auto space-y-10 animate-in fade-in duration-500">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black mb-3">
                <span>Gestión Técnica</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <a href="?page=work_orders" class="hover:text-medical-blue transition-colors">Órdenes</a>
                <span class="material-symbols-outlined text-sm text-slate-700">chevron_right</span>
                <span class="text-medical-blue"><?= $isCompleted ? 'Reporte Final' : 'Ejecución y Cierre' ?></span>
            </nav>
            <div class="flex items-center gap-4">
                <h1 class="text-4xl font-bold tracking-tight text-white">Orden Técnica #<?= $id ?></h1>
                <?php
                $statusClass = $isCompleted
                    ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30'
                    : 'bg-amber-500/10 text-amber-500 border-amber-500/30 shadow-[0_0_12px_rgba(245,158,11,0.1)]';
                $dotClass = $isCompleted ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-amber-500 animate-pulse';
                ?>
                <span class="px-4 py-1.5 border rounded-2xl text-[10px] font-black uppercase tracking-wider flex items-center gap-2 <?= $statusClass ?>">
                    <span class="size-2 rounded-full <?= $dotClass ?>"></span>
                    <?= $isCompleted ? 'FINALIZADA' : 'EN EJECUCIÓN' ?>
                </span>
            </div>
            <p class="text-slate-400 mt-2 text-lg">
                <?= $isCompleted ? 'Mantenimiento Preventivo Anual' : 'Mantenimiento Correctivo' ?> · Ventilador Mecánico Puritan Bennett 840
            </p>
        </div>
        <div class="flex gap-4">
            <a href="?page=asset&id=PB-840-00122" class="px-6 py-3 bg-white/5 border border-slate-700/50 text-slate-300 rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-white/10 transition-all">
                <span class="material-symbols-outlined text-xl">history</span>
                Ficha del Activo
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-8">
            <!-- Protocolos de Seguridad -->
            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                    <span class="material-symbols-outlined text-8xl">verified_user</span>
                </div>
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                            <span class="material-symbols-outlined font-variation-fill">fact_check</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Pruebas Normativas</h3>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Validación de seguridad eléctrica y funcional</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($checks as $check): ?>
                        <div class="flex items-center gap-4 p-5 border rounded-2xl transition-all <?= $isCompleted ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-white/5 border-slate-700/50 hover:border-medical-blue/30' ?>">
                            <span class="material-symbols-outlined font-black <?= $isCompleted ? 'text-emerald-500' : 'text-slate-700' ?>">
                                <?= $isCompleted ? 'check_circle' : 'radio_button_unchecked' ?>
                            </span>
                            <span class="text-sm font-bold <?= $isCompleted ? 'text-slate-200' : 'text-slate-400' ?>"><?= $check ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Documentación y Archivos Adjuntos -->
            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                            <span class="material-symbols-outlined font-variation-fill">attachment</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Evidencia y Adjuntos</h3>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Protocolos firmados y capturas de pantalla</p>
                        </div>
                    </div>
                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                        <button class="flex items-center gap-2 text-[10px] font-black text-medical-blue uppercase tracking-widest hover:bg-medical-blue/10 px-4 py-2 rounded-xl border border-medical-blue/30 transition-all">
                            <span class="material-symbols-outlined text-sm">cloud_upload</span>
                            Cargar Evidencia
                        </button>
                    <?php endif; ?>
                </div>

                <div class="space-y-3">
                    <?php
                    $displayFiles = $isCompleted ? $attachments : array_slice($attachments, 0, 1);
                    foreach ($displayFiles as $file):
                        $isPdf = $file['type'] === 'pdf';
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-slate-700/50 rounded-2xl group hover:border-medical-blue/30 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="p-2 rounded-lg <?= $isPdf ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-medical-blue/10 text-medical-blue border border-medical-blue/20' ?>">
                                    <span class="material-symbols-outlined"><?= $isPdf ? 'picture_as_pdf' : 'image' ?></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-200 group-hover:text-white transition-colors"><?= $file['name'] ?></p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?= $file['size'] ?> · <?= $file['date'] ?></p>
                                </div>
                            </div>
                            <button class="p-2 text-slate-500 hover:text-medical-blue transition-colors">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                        <div class="p-8 border-2 border-dashed border-slate-700/50 rounded-2xl flex flex-col items-center justify-center text-slate-600 gap-2 hover:border-medical-blue/50 hover:text-medical-blue transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-3xl">upload_file</span>
                            <p class="text-[10px] font-black uppercase tracking-widest">Arrastra evidencia técnica aquí</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informe de Intervención -->
            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                        <span class="material-symbols-outlined font-variation-fill">edit_note</span>
                    </div>
                    <h3 class="text-xl font-bold text-white">Informe Final de Intervención</h3>
                </div>
                <?php if ($isCompleted): ?>
                    <div class="p-6 bg-slate-900/50 border border-slate-700/50 rounded-2xl text-sm text-slate-300 leading-relaxed italic relative">
                        <span class="material-symbols-outlined absolute -top-3 -left-3 text-medical-blue text-4xl opacity-20">format_quote</span>
                        "Se realiza mantenimiento preventivo anual según cronograma institucional. Se verifican parámetros de seguridad eléctrica bajo norma IEC 62353 con resultados satisfactorios. Se procede a la recalibración de sensores de flujo y reemplazo de kits de mantenimiento semestral. El equipo se entrega en condiciones óptimas de operación a cargo de la jefatura de UCI."
                    </div>
                <?php else: ?>
                    <textarea
                        class="w-full bg-slate-900 border border-slate-700/50 rounded-2xl p-6 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[180px] text-slate-300 placeholder:text-slate-700 font-medium"
                        placeholder="Describa el trabajo realizado, hallazgos técnicos, repuestos reemplazados y observaciones finales..."
                        <?= isReadOnly() ? 'readonly' : '' ?>></textarea>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-4 space-y-8">
            <div class="bg-medical-surface p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Referencia Técnica del Equipo</h4>
                <div class="space-y-3">
                    <?php foreach ($referenceDocs as $doc): ?>
                        <button class="w-full flex items-center gap-3 p-3 bg-white/5 border border-transparent hover:border-medical-blue/30 rounded-xl text-left transition-all group">
                            <span class="material-symbols-outlined text-medical-blue opacity-50 group-hover:opacity-100">description</span>
                            <div>
                                <p class="text-[11px] font-bold text-slate-300 group-hover:text-white transition-colors"><?= $doc['name'] ?></p>
                                <p class="text-[9px] text-slate-600 font-bold uppercase"><?= $doc['category'] ?></p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl sticky top-28">
                <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-8">Información de Cierre</h3>

                <div class="space-y-6">
                    <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Técnico Responsable</p>
                        <div class="flex items-center gap-4">
                            <div class="size-10 rounded-full bg-medical-blue/20 border border-medical-blue/30 flex items-center justify-center font-black text-medical-blue">AM</div>
                            <div>
                                <p class="text-sm font-bold text-white">Ana Muñoz</p>
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Ing. Electromedicina</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($isCompleted): ?>
                        <div class="space-y-4">
                            <div class="p-5 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl text-center shadow-[inset_0_0_12px_rgba(16,185,129,0.05)]">
                                <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Condición Final</span>
                                <p class="text-xl font-bold text-white mt-1">Conforme / Operativo</p>
                            </div>
                            <button class="w-full py-4 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all flex items-center justify-center gap-3 text-xs">
                                <span class="material-symbols-outlined text-xl">print</span>
                                <span>Imprimir Reporte OT</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php if (canCompleteWorkOrder()): ?>
                                <button onclick="window.location.href='?page=work_orders'" class="w-full py-4 bg-emerald-500 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-emerald-500/20 hover:bg-emerald-500/90 transition-all flex items-center justify-center gap-3 text-xs">
                                    <span class="material-symbols-outlined text-xl">verified</span>
                                    <span>Finalizar e Informar</span>
                                </button>
                            <?php endif; ?>
                            <?php if (canExecuteWorkOrder()): ?>
                                <button class="w-full py-4 bg-white/5 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/10 transition-all flex items-center justify-center gap-3 text-xs">
                                    <span class="material-symbols-outlined text-xl">save</span>
                                    <span>Guardar Borrador</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>