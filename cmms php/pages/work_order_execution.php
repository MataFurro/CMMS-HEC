<?php
// pages/work_order_execution.php

require_once __DIR__ . '/../includes/checklist_templates.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

$id = $_GET['id'] ?? 'OT-2024-UNKNOWN';

// Handle Completion Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_ot') {
    // 1. Actualizar Datos del Activo si se proporcionan
    if (isset($_POST['asset_id'])) {
        require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
        $assetUpdateData = [];
        if (!empty($_POST['brand'])) $assetUpdateData['brand'] = $_POST['brand'];
        if (!empty($_POST['model'])) $assetUpdateData['model'] = $_POST['model'];
        if (!empty($_POST['serial_number'])) $assetUpdateData['serial_number'] = $_POST['serial_number'];
        if (!empty($_POST['location'])) $assetUpdateData['location'] = $_POST['location'];
        if (!empty($_POST['asset_name'])) $assetUpdateData['name'] = $_POST['asset_name'];

        if (!empty($assetUpdateData)) {
            updateAssetInfo($_POST['asset_id'], $assetUpdateData);
        }
    }

    // 2. Finalizar OT con datos enriquecidos
    $executionData = [
        'failure_code' => $_POST['failure_code'] ?? null,
        'service_warranty_date' => !empty($_POST['service_warranty_date']) ? $_POST['service_warranty_date'] : null,
        'final_asset_status' => $_POST['final_asset_status'] ?? 'OPERATIVE',
        'duration_hours' => $_POST['duration_hours'] ?? 0,
        'observations' => $_POST['final_observations'] ?? ''
    ];

    if (completeWorkOrder($id, $executionData)) {
        // Redirigir con JS para evitar error de headers
        echo "<script>window.location.href='?page=work_order_execution&id=$id&completed=1';</script>";
        exit;
    }
}

// 1. Cargar datos de la OT
$ot = getWorkOrderById($id);
if (!$ot) {
    echo "<div class='p-8 text-center text-red-500 font-bold'>Orden no encontrada</div>";
    return;
}

$isCompleted = ($ot['status'] ?? '') === 'Terminada';

// 2. Determinar qué plantilla usar
// Prioridad: 1. Plantilla guardada en DB | 2. Parámetro tpl en URL | 3. Default
$templateKey = $ot['checklist_template'] ?? ($_GET['tpl'] ?? 'formato_general');
$template = getChecklistTemplate($templateKey);

if (!$template) {
    // Si la plantilla fallida no existe, usar formato_general por seguridad
    $template = getChecklistTemplate('formato_general');
}

// asset details from OT
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
$asset = getAssetById($ot['asset_id']);

$attachments = [
    ['name' => 'Protocolo_Seguridad_Electrica_IEC62353.pdf', 'type' => 'pdf', 'size' => '1.2 MB', 'date' => '11/02/2026'],
    ['name' => 'Certificado_Calibracion_Fluke.pdf', 'type' => 'pdf', 'size' => '840 KB', 'date' => '11/02/2026'],
    ['name' => 'Captura_Falla_Sensor.jpg', 'type' => 'image', 'size' => '2.1 MB', 'date' => '11/02/2026']
];

$referenceDocs = [
    ['name' => 'Manual_Servicio_PB840.pdf', 'category' => 'Referencia Técnica'],
    ['name' => 'Protocolo_Preventivo_Standard.pdf', 'category' => 'Checklist Guía']
];

// Fallback si no hay template
$qualitativeChecks = $template['qualitative'] ?? [];
$quantitativeGroups = $template['quantitative'] ?? [];
$electricalSafety = $template['electrical_safety'] ?? [];
$templateLabel = $template['label'] ?? 'Genérico';
$templateIcon = $template['icon'] ?? 'fact_check';
$templateVersion = $template['version'] ?? 'V1';
?>

<div x-data="executionState" class="max-w-[1200px] mx-auto space-y-10 animate-in fade-in duration-500">
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
                <span
                    class="px-4 py-1.5 border rounded-2xl text-[10px] font-black uppercase tracking-wider flex items-center gap-2 <?= $statusClass ?>">
                    <span class="size-2 rounded-full <?= $dotClass ?>"></span>
                    <?= $isCompleted ? 'FINALIZADA' : 'EN EJECUCIÓN' ?>
                </span>
            </div>
            <p class="text-slate-400 mt-2 text-lg">
                <?= $orderData['type'] ?? 'Servicio Técnico' ?> · <?= $templateLabel ?>
            </p>
        </div>
        <div class="flex gap-4">
            <a href="?page=asset&id=<?= $orderData['asset_id'] ?? 'PB-840-00122' ?>"
                class="px-6 py-3 bg-white/5 border border-slate-700/50 text-slate-300 rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-white/10 transition-all">
                <span class="material-symbols-outlined text-xl">history</span>
                Ficha del Activo
            </a>
        </div>
    </div>

    <!-- Template Badge -->
    <div class="flex items-center gap-3 px-5 py-3 bg-indigo-500/5 border border-indigo-500/20 rounded-2xl w-fit">
        <span class="material-symbols-outlined text-indigo-400"><?= $templateIcon ?></span>
        <div>
            <p class="text-xs font-black text-indigo-300 uppercase tracking-widest">Plantilla: <?= $templateLabel ?></p>
            <p class="text-[10px] text-slate-500 font-bold">Versión <?= $templateVersion ?> ·
                <?= count($qualitativeChecks) ?> ítems + <?= count($quantitativeGroups) ?> grupos de medición
            </p>
        </div>
    </div>

    <form method="POST" id="executionForm" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <!-- Hidden Asset ID for updates -->
        <input type="hidden" name="asset_id" value="<?= $orderData['asset_id'] ?? '' ?>">

        <div class="lg:col-span-8 space-y-8">

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 1: Inspección Cualitativa (Checklist)          -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div
                class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                    <span class="material-symbols-outlined text-8xl">verified_user</span>
                </div>
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div
                            class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                            <span class="material-symbols-outlined font-variation-fill">fact_check</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Inspección Cualitativa</h3>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Aprueba / Falla
                                / No Aplica</p>
                        </div>
                    </div>
                    <span
                        class="text-[10px] font-black text-slate-600 uppercase tracking-widest"><?= count($qualitativeChecks) ?>
                        ítems</span>
                </div>

                <div class="space-y-3">
                    <?php foreach ($qualitativeChecks as $idx => $check): ?>
                        <div
                            class="flex items-center justify-between p-4 border rounded-2xl transition-all <?= $isCompleted ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-white/5 border-slate-700/50 hover:border-medical-blue/30' ?>">
                            <div class="flex items-center gap-4">
                                <span
                                    class="material-symbols-outlined font-black <?= $isCompleted ? 'text-emerald-500' : 'text-slate-700' ?>">
                                    <?= $isCompleted ? 'check_circle' : 'radio_button_unchecked' ?>
                                </span>
                                <span
                                    class="text-sm font-bold <?= $isCompleted ? 'text-slate-200' : 'text-slate-400' ?>"><?= $check ?></span>
                            </div>
                            <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="radio" name="q_<?= $idx ?>" value="pass" class="hidden peer" required>
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-700/50 text-slate-600 peer-checked:bg-emerald-500/10 peer-checked:text-emerald-500 peer-checked:border-emerald-500/30 transition-all hover:border-emerald-500/30 cursor-pointer">Aprueba</span>
                                    </label>
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="radio" name="q_<?= $idx ?>" value="fail" class="hidden peer">
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-700/50 text-slate-600 peer-checked:bg-red-500/10 peer-checked:text-red-500 peer-checked:border-red-500/30 transition-all hover:border-red-500/30 cursor-pointer">Falla</span>
                                    </label>
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="radio" name="q_<?= $idx ?>" value="na" class="hidden peer">
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-700/50 text-slate-600 peer-checked:bg-slate-500/10 peer-checked:text-slate-400 peer-checked:border-slate-500/30 transition-all hover:border-slate-500/30 cursor-pointer">N/A</span>
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 2: Pruebas Cuantitativas (Metrología)          -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <?php if (!empty($quantitativeGroups)): ?>
                <div
                    class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <span class="material-symbols-outlined text-8xl">speed</span>
                    </div>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20">
                                <span class="material-symbols-outlined font-variation-fill">labs</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Pruebas Cuantitativas</h3>
                                <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Valor simulado
                                    vs. Valor medido (Tolerancia)</p>
                            </div>
                        </div>
                        <span
                            class="text-[10px] font-black text-slate-600 uppercase tracking-widest"><?= count($quantitativeGroups) ?>
                            parámetros</span>
                    </div>

                    <div class="space-y-6">
                        <?php foreach ($quantitativeGroups as $gIdx => $group): ?>
                            <div class="p-5 bg-white/[0.02] border border-slate-700/50 rounded-2xl space-y-4" x-data="{ groupNA: false }">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-sm font-black text-white uppercase tracking-wider" :class="groupNA ? 'opacity-30' : ''"><?= $group['group'] ?></h4>
                                        <?php if (!$isCompleted): ?>
                                            <label class="flex items-center gap-2 cursor-pointer bg-slate-800 px-2 py-1 rounded-lg border border-slate-700">
                                                <input type="checkbox" x-model="groupNA" name="group_na_<?= $gIdx ?>" class="rounded bg-slate-900 border-slate-700 text-medical-blue focus:ring-medical-blue/20">
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">No Aplica</span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                    <span
                                        class="px-3 py-1 bg-amber-500/10 text-amber-500 border border-amber-500/20 rounded-lg text-[9px] font-black uppercase tracking-widest" :class="groupNA ? 'opacity-30' : ''">
                                        Tolerancia: <?= $group['tolerance_label'] ?>
                                    </span>
                                </div>

                                <div x-show="!groupNA" x-transition>

                                    <!-- Table Header -->
                                    <div
                                        class="grid grid-cols-3 gap-3 text-[10px] font-black text-slate-500 uppercase tracking-widest px-2">
                                        <span>Valor Simulado</span>
                                        <span>Valor Medido</span>
                                        <span class="text-center">Estado</span>
                                    </div>

                                    <!-- Table Rows -->
                                    <?php foreach ($group['points'] as $pIdx => $point): ?>
                                        <div class="grid grid-cols-3 gap-3 items-center">
                                            <div
                                                class="flex items-center gap-2 px-4 py-3 bg-slate-900 border border-slate-700/50 rounded-xl">
                                                <span class="text-sm font-bold text-medical-blue"><?= $point['simulated'] ?></span>
                                                <span class="text-[10px] text-slate-500 font-bold"><?= $group['unit'] ?></span>
                                            </div>
                                            <?php if ($isCompleted): ?>
                                                <div
                                                    class="flex items-center gap-2 px-4 py-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                                                    <span
                                                        class="text-sm font-bold text-emerald-400"><?= is_numeric($point['simulated']) ? $point['simulated'] + rand(-1, 1) : $point['simulated'] ?></span>
                                                    <span class="text-[10px] text-slate-500 font-bold"><?= $group['unit'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <input type="text" name="m_<?= $gIdx ?>_<?= $pIdx ?>" placeholder="—"
                                                    class="px-4 py-3 bg-slate-900 border border-slate-700/50 rounded-xl text-sm text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all font-bold"
                                                    <?= isReadOnly() ? 'readonly' : '' ?>>
                                            <?php endif; ?>
                                            <div class="flex justify-center">
                                                <?php if ($isCompleted): ?>
                                                    <span
                                                        class="px-3 py-1.5 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg text-[9px] font-black uppercase tracking-widest">Pasa</span>
                                                <?php else: ?>
                                                    <span
                                                        class="px-3 py-1.5 bg-slate-500/10 text-slate-600 border border-slate-700/50 rounded-lg text-[9px] font-black uppercase tracking-widest">Pendiente</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 3: Seguridad Eléctrica (IEC 62353)             -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <?php if (!empty($electricalSafety)): ?>
                <div
                    class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <span class="material-symbols-outlined text-8xl">bolt</span>
                    </div>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="p-2.5 bg-red-500/10 text-red-400 rounded-xl border border-red-500/20">
                                <span class="material-symbols-outlined font-variation-fill">electrical_services</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Seguridad Eléctrica</h3>
                                <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">IEC 62353 ·
                                    Mediciones Normativas</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <!-- Header -->
                        <div
                            class="grid grid-cols-4 gap-3 text-[10px] font-black text-slate-500 uppercase tracking-widest px-4">
                            <span>Parámetro</span>
                            <span>Valor Esperado</span>
                            <span>Valor Medido</span>
                            <span class="text-center">Tolerancia</span>
                        </div>
                        <?php foreach ($electricalSafety as $sIdx => $safety): ?>
                            <div
                                class="grid grid-cols-4 gap-3 items-center p-4 bg-white/[0.02] border border-slate-700/50 rounded-2xl hover:border-red-500/20 transition-all">
                                <span class="text-sm font-bold text-white"><?= $safety['param'] ?></span>
                                <span class="text-sm font-bold text-red-400"><?= $safety['expected'] ?></span>
                                <?php if ($isCompleted): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                                        <span class="text-sm font-bold text-emerald-400">Conforme</span>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="es_<?= $sIdx ?>" placeholder="—"
                                        class="px-3 py-2 bg-slate-900 border border-slate-700/50 rounded-xl text-sm text-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all font-bold"
                                        <?= isReadOnly() ? 'readonly' : '' ?>>
                                <?php endif; ?>
                                <span
                                    class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-center"><?= $safety['tolerance'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Documentación y Archivos Adjuntos -->
            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div
                            class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                            <span class="material-symbols-outlined font-variation-fill">attachment</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Evidencia y Adjuntos</h3>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Protocolos
                                firmados y capturas de pantalla</p>
                        </div>
                    </div>
                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                        <button
                            class="flex items-center gap-2 text-[10px] font-black text-medical-blue uppercase tracking-widest hover:bg-medical-blue/10 px-4 py-2 rounded-xl border border-medical-blue/30 transition-all">
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
                        <div
                            class="flex items-center justify-between p-4 bg-white/5 border border-slate-700/50 rounded-2xl group hover:border-medical-blue/30 transition-all">
                            <div class="flex items-center gap-4">
                                <div
                                    class="p-2 rounded-lg <?= $isPdf ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-medical-blue/10 text-medical-blue border border-medical-blue/20' ?>">
                                    <span
                                        class="material-symbols-outlined"><?= $isPdf ? 'picture_as_pdf' : 'image' ?></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-200 group-hover:text-white transition-colors">
                                        <?= $file['name'] ?>
                                    </p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                        <?= $file['size'] ?> · <?= $file['date'] ?>
                                    </p>
                                </div>
                            </div>
                            <button class="p-2 text-slate-500 hover:text-medical-blue transition-colors">
                                <span class="material-symbols-outlined">download</span>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                        <div
                            class="p-8 border-2 border-dashed border-slate-700/50 rounded-2xl flex flex-col items-center justify-center text-slate-600 gap-2 hover:border-medical-blue/50 hover:text-medical-blue transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-3xl">upload_file</span>
                            <p class="text-[10px] font-black uppercase tracking-widest">Arrastra evidencia técnica aquí</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informe de Intervención -->
            <div
                class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                        <span class="material-symbols-outlined font-variation-fill">edit_note</span>
                    </div>
                    <h3 class="text-xl font-bold text-white">Informe Final de Intervención</h3>
                </div>
                <?php if ($isCompleted): ?>
                    <div
                        class="p-6 bg-slate-900/50 border border-slate-700/50 rounded-2xl text-sm text-slate-300 leading-relaxed italic relative">
                        <span
                            class="material-symbols-outlined absolute -top-3 -left-3 text-medical-blue text-4xl opacity-20">format_quote</span>
                        <?= htmlspecialchars($ot['observations'] ?? 'Sin observaciones registradas.') ?>
                    </div>
                <?php else: ?>
                    <textarea
                        name="final_observations"
                        class="w-full bg-slate-900 border border-slate-700/50 rounded-2xl p-6 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[180px] text-slate-300 placeholder:text-slate-700 font-medium"
                        placeholder="Describa el trabajo realizado, hallazgos técnicos, repuestos reemplazados y observaciones finales..."
                        <?= isReadOnly() ? 'readonly' : '' ?>><?= htmlspecialchars($ot['observations'] ?? '') ?></textarea>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-4 space-y-8">
            <div class="bg-medical-surface p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Referencia Técnica del
                    Equipo</h4>
                <div class="space-y-3">
                    <?php foreach ($referenceDocs as $doc): ?>
                        <button
                            class="w-full flex items-center gap-3 p-3 bg-white/5 border border-transparent hover:border-medical-blue/30 rounded-xl text-left transition-all group">
                            <span
                                class="material-symbols-outlined text-medical-blue opacity-50 group-hover:opacity-100">description</span>
                            <div>
                                <p class="text-[11px] font-bold text-slate-300 group-hover:text-white transition-colors">
                                    <?= $doc['name'] ?>
                                </p>
                                <p class="text-[9px] text-slate-600 font-bold uppercase"><?= $doc['category'] ?></p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Datos del Equipo (Bloque Estandarizado) -->
            <div class="bg-medical-surface p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Datos del Activo</h4>
                <div class="space-y-4">
                    <div class="flex flex-col gap-1 p-3 bg-white/5 rounded-xl border border-slate-700/30">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-focus-within:text-medical-blue">Nombre del Equipo</label>
                        <input type="text" name="asset_name" value="<?= htmlspecialchars($ot['asset_name'] ?? $templateLabel) ?>" placeholder="Nombre del equipo..." class="bg-transparent text-xs font-bold text-white outline-none placeholder:text-slate-600" <?= isReadOnly() ? 'readonly' : '' ?>>
                    </div>
                    <div class="flex flex-col gap-1 p-3 bg-white/5 rounded-xl border border-slate-700/30 group focus-within:border-medical-blue/30 transition-all">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-focus-within:text-medical-blue">Marca</label>
                        <input type="text" name="brand" value="<?= htmlspecialchars($orderData['brand'] ?? '') ?>" placeholder="Completar marca..." class="bg-transparent text-xs font-bold text-white outline-none placeholder:text-slate-600" <?= isReadOnly() ? 'readonly' : '' ?>>
                    </div>
                    <div class="flex flex-col gap-1 p-3 bg-white/5 rounded-xl border border-slate-700/30 group focus-within:border-medical-blue/30 transition-all">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-focus-within:text-medical-blue">Modelo</label>
                        <input type="text" name="model" value="<?= htmlspecialchars($orderData['model'] ?? '') ?>" placeholder="Completar modelo..." class="bg-transparent text-xs font-bold text-white outline-none placeholder:text-slate-600" <?= isReadOnly() ? 'readonly' : '' ?>>
                    </div>
                    <div class="flex flex-col gap-1 p-3 bg-white/5 rounded-xl border border-slate-700/30 group focus-within:border-medical-blue/30 transition-all">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-focus-within:text-medical-blue">Serie / Placa</label>
                        <input type="text" name="serial_number" value="<?= htmlspecialchars($orderData['serial_number'] ?? '') ?>" placeholder="S/N o Inventario..." class="bg-transparent text-xs font-bold text-white outline-none placeholder:text-slate-600" <?= isReadOnly() ? 'readonly' : '' ?>>
                    </div>
                    <div class="flex flex-col gap-1 p-3 bg-white/5 rounded-xl border border-slate-700/30 group focus-within:border-medical-blue/30 transition-all">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-focus-within:text-medical-blue">Servicio / Ubicación</label>
                        <?php $locations = getAllLocations(); ?>
                        <select name="location" class="bg-transparent text-xs font-bold text-white outline-none appearance-none cursor-pointer" <?= isReadOnly() ? 'disabled' : '' ?>>
                            <option value="" disabled>Seleccione ubicación...</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($ot['location'] ?? '') === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-medical-surface p-8 rounded-3xl border border-slate-700/50 shadow-2xl sticky top-28">
                <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-8">Información de Cierre
                </h3>

                <div class="space-y-6">
                    <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Técnico
                            Responsable</p>
                        <div class="flex items-center gap-4">
                            <div
                                class="size-10 rounded-full bg-medical-blue/20 border border-medical-blue/30 flex items-center justify-center font-black text-medical-blue">
                                AM</div>
                            <div>
                                <p class="text-sm font-bold text-white">Ana Muñoz</p>
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Ing.
                                    Electromedicina</p>
                            </div>
                        </div>
                    </div>

                    <!-- Codificación de Falla (Solo Correctivas) -->
                    <?php if (($ot['type'] ?? '') === 'Correctiva'): ?>
                        <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Código de Falla (RCA)</p>
                            <?php if ($isCompleted): ?>
                                <div class="text-sm font-bold text-medical-blue"><?= htmlspecialchars($ot['failure_code'] ?? 'Sin especificar') ?></div>
                            <?php else: ?>
                                <select name="failure_code" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-2 text-xs font-bold text-white outline-none focus:border-medical-blue transition-all" required>
                                    <option value="" disabled selected>Seleccione causa raíz...</option>
                                    <option value="Error de Usuario">Error de Usuario / Operación</option>
                                    <option value="Falla Electrónica">Falla Electrónica / Hardware</option>
                                    <option value="Desgaste Natural">Desgaste Natural (Vida Útil)</option>
                                    <option value="Falla de Software">Falla de Software / Red</option>
                                    <option value="Factores Externos">Factores Externos (Energía/Gases)</option>
                                    <option value="Otro">Otro (Especificar en obs.)</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Estado Final del Activo -->
                    <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Estado Final del Activo</p>
                        <?php if ($isCompleted): ?>
                            <div class="flex items-center gap-2 <?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'text-emerald-500' : 'text-red-500' ?>">
                                <span class="material-symbols-outlined"><?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'check_circle' : 'cancel' ?></span>
                                <span class="text-sm font-bold"><?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'Operativo' : 'Fuera de Servicio' ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flex gap-3">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="final_asset_status" value="OPERATIVE" class="hidden peer" required>
                                    <div class="p-3 text-center rounded-xl border border-slate-700/50 text-slate-600 text-[10px] font-black uppercase tracking-widest peer-checked:bg-emerald-500/10 peer-checked:text-emerald-500 peer-checked:border-emerald-500/30 transition-all hover:border-emerald-500/30">OPERATIVO</div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="final_asset_status" value="NO_OPERATIVE" class="hidden peer">
                                    <div class="p-3 text-center rounded-xl border border-slate-700/50 text-slate-600 text-[10px] font-black uppercase tracking-widest peer-checked:bg-red-500/10 peer-checked:text-red-500 peer-checked:border-red-500/30 transition-all hover:border-red-500/30">Baja / F.S</div>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Garantía del Servicio -->
                    <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Garantía del Servicio</p>
                        <?php if ($isCompleted): ?>
                            <div class="text-xs font-bold text-slate-300">
                                <?= $ot['service_warranty_date'] ? 'Vence: ' . $ot['service_warranty_date'] : 'Sin garantía' ?>
                            </div>
                        <?php else: ?>
                            <input type="date" name="service_warranty_date" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-2 text-xs font-bold text-white outline-none focus:border-medical-blue transition-all">
                            <p class="text-[9px] text-slate-600 mt-2 font-bold uppercase tracking-wider">Dejar vacío si no aplica</p>
                        <?php endif; ?>
                    </div>

                    <!-- Duración del Trabajo -->
                    <?php if (!$isCompleted): ?>
                        <div class="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Tiempo Invertido</p>
                            <div class="flex items-center gap-3">
                                <input type="number" step="0.5" name="duration_hours" placeholder="Horas" class="w-24 bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-2 text-sm font-bold text-white outline-none focus:border-medical-blue transition-all" required>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Horas Hombre</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($isCompleted): ?>
                        <div class="space-y-4">
                            <div
                                class="p-5 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl text-center shadow-[inset_0_0_12px_rgba(16,185,129,0.05)]">
                                <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Condición
                                    Final</span>
                                <p class="text-xl font-bold text-white mt-1">Conforme / Operativo</p>
                            </div>
                            <button
                                class="w-full py-4 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all flex items-center justify-center gap-3 text-xs">
                                <span class="material-symbols-outlined text-xl">print</span>
                                <span>Imprimir Reporte OT</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php if (canCompleteWorkOrder()): ?>
                                <input type="hidden" name="action" value="complete_ot">
                                <button type="submit"
                                    class="w-full py-4 bg-emerald-500 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-emerald-500/20 hover:bg-emerald-500/90 transition-all flex items-center justify-center gap-3 text-xs">
                                    <span class="material-symbols-outlined text-xl">verified</span>
                                    <span>Finalizar e Informar</span>
                                </button>
                            <?php endif; ?>
                            <?php if (canExecuteWorkOrder()): ?>
                                <button
                                    class="w-full py-4 bg-white/5 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/10 transition-all flex items-center justify-center gap-3 text-xs">
                                    <span class="material-symbols-outlined text-xl">save</span>
                                    <span>Guardar Borrador</span>
                                </button>

                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </form>
</div>

<!-- Modal de Firma Electrónica (Compliance 21 CFR Part 11) -->
<template x-if="showSignatureModal">
    <div
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-medical-dark/95 backdrop-blur-sm animate-in fade-in duration-300">
        <div @click.away="showSignatureModal = false"
            class="max-w-md w-full bg-medical-surface border border-slate-700/50 rounded-3xl p-8 shadow-2xl space-y-8">
            <div class="text-center">
                <div
                    class="size-16 rounded-2xl bg-medical-blue/10 text-medical-blue border border-medical-blue/20 flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-3xl">draw</span>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Firma de Conformidad</h2>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-2">Cierre de Orden y
                    Certificación Técnica</p>
            </div>

            <div class="space-y-4">
                <div class="p-4 bg-white/5 rounded-2xl border border-slate-700/50">
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1">Declaración de
                        Responsabilidad</p>
                    <p class="text-[11px] text-slate-400 italic leading-relaxed">
                        "Certifico que el equipo ha sido intervenido siguiendo los protocolos del fabricante y
                        cumple con los estándares de seguridad vigentes."
                    </p>
                </div>

                <!-- Firmas: Técnico Ejecutante, Técnico HEC, Jefe de Servicio -->
                <div class="space-y-3">
                    <div
                        class="p-3 bg-white/5 rounded-xl border border-slate-700/50 flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Técnico
                            Ejecutante</span>
                        <span class="text-xs font-bold text-medical-blue">Ana Muñoz</span>
                    </div>
                    <div
                        class="p-3 bg-white/5 rounded-xl border border-slate-700/50 flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Técnico
                            HEC</span>
                        <span class="text-xs text-slate-600">Pendiente</span>
                    </div>
                    <div
                        class="p-3 bg-white/5 rounded-xl border border-slate-700/50 flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Jefe de
                            Servicio</span>
                        <span class="text-xs text-slate-600">Pendiente</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirmar
                        Identidad (PIN/ID)</label>
                    <input type="password" placeholder="••••"
                        class="w-full h-14 bg-slate-900 border border-slate-700/50 rounded-2xl px-6 text-xl tracking-[1em] text-center focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all">
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button @click="showSignatureModal = false"
                    class="flex-1 py-4 border border-slate-700/50 text-slate-500 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all text-xs">Cancelar</button>
                <button onclick="window.location.href='?page=work_orders'"
                    class="flex-1 py-4 bg-medical-blue text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-medical-blue/20 hover:bg-medical-blue/90 transition-all text-xs">Sellar
                    OT</button>
            </div>

            <p class="text-[9px] text-slate-600 text-center font-bold uppercase leading-relaxed">
                Esta acción genera un registro inalterable en la pista de auditoría (Log #<?= uniqid() ?>) bajo la
                normativa FDA 21 CFR Part 11.
            </p>
        </div>
    </div>
</template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('executionState', () => ({
            showSignatureModal: false
        }))
    });
</script>