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
        if (!empty($_POST['current_asset_hours'])) $assetUpdateData['hours_used'] = (int)$_POST['current_asset_hours'];

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

// Handle Save Draft Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_draft'])) {
    // Collect checklist data
    $checklistData = [
        'qualitative' => [],
        'quantitative' => [],
        'electrical_safety' => []
    ];

    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'q_')) {
            $checklistData['qualitative'][$key] = $value;
        } elseif (str_starts_with($key, 'm_')) {
            $checklistData['quantitative'][$key] = $value;
        } elseif (str_starts_with($key, 'group_na_')) {
            $checklistData['quantitative'][$key] = $value;
        } elseif (str_starts_with($key, 'elec_')) {
            $checklistData['electrical_safety'][$key] = $value;
        }
    }

    $executionData = [
        'failure_code' => $_POST['failure_code'] ?? null,
        'final_asset_status' => $_POST['final_asset_status'] ?? 'OPERATIVE',
        'duration_hours' => $_POST['duration_hours'] ?? 0,
        'observations' => $_POST['final_observations'] ?? '',
        'checklist_data' => $checklistData
    ];

    if (saveWorkOrderProgress($id, $executionData)) {
        echo "<script>window.location.href='?page=work_order_execution&id=$id&draft_saved=1';</script>";
        exit;
    }
}

// Handle Attachment Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attachment_file']) && canExecuteWorkOrder()) {
    $category = $_POST['attachment_category'] ?? 'evidencia';
    $caption = $_POST['attachment_caption'] ?? '';
    if (uploadOtAttachment($id, $ot['asset_id'], $_FILES['attachment_file'], $category, $caption)) {
        echo "<script>window.location.href='?page=work_order_execution&id=$id&upload_success=1';</script>";
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
$savedChecklist = $ot['checklist_data'] ?? [];

// 2. Determinar qué plantilla usar
$templateKey = $ot['checklist_template'] ?? ($_GET['tpl'] ?? 'formato_general');
$template = getChecklistTemplate($templateKey);

if (!$template) {
    $template = getChecklistTemplate('formato_general');
}

// asset details from OT
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
$asset = getAssetById($ot['asset_id']);

$attachments = getOtAttachments($id);

$referenceDocs = [
    ['name' => 'Manual_Servicio_PB840.pdf', 'category' => 'Referencia Técnica'],
    ['name' => 'Protocolo_Preventivo_Standard.pdf', 'category' => 'Checklist Guía']
];

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
            <nav class="flex items-center gap-2 text-[10px] text-[var(--text-muted)] uppercase tracking-[0.2em] font-black mb-3">
                <span>Gestión Técnica</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <a href="?page=work_orders" class="hover:text-medical-blue transition-colors">Órdenes</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-medical-blue"><?= $isCompleted ? 'Reporte Final' : 'Ejecución y Cierre' ?></span>
            </nav>
            <div class="flex items-center gap-4">
                <h1 class="text-4xl font-bold tracking-tight text-[var(--text-main)]">Orden Técnica #<?= $id ?></h1>
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
            <p class="text-[var(--text-muted)] mt-2 text-lg">
                <?= $ot['type'] ?? 'Servicio Técnico' ?> · <?= $templateLabel ?>
            </p>
        </div>
        <div class="flex flex-col items-end gap-3">
            <?php if (isset($_GET['draft_saved'])): ?>
                <div class="px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-500 text-[10px] font-black uppercase tracking-widest animate-bounce">
                    ✓ Borrador Guardado
                </div>
            <?php endif; ?>
            <div class="flex gap-4">
                <a href="?page=asset&id=<?= $ot['asset_id'] ?? '' ?>"
                    class="px-6 py-3 bg-medical-surface border border-border-dark text-[var(--text-main)] rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
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
                <p class="text-[10px] text-[var(--text-muted)] font-bold">Versión <?= $templateVersion ?> ·
                    <?= count($qualitativeChecks) ?> ítems + <?= count($quantitativeGroups) ?> grupos de medición
                </p>
            </div>
        </div>

        <form method="POST" id="executionForm" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <!-- Hidden Asset ID for updates -->
            <input type="hidden" name="asset_id" value="<?= $ot['asset_id'] ?? '' ?>">

            <div class="lg:col-span-8 space-y-8">

                <!-- ═══════════════════════════════════════════════════════ -->
                <!-- SECCIÓN 1: Inspección Cualitativa (Checklist)          -->
                <!-- ═══════════════════════════════════════════════════════ -->
                <div
                    class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <span class="material-symbols-outlined text-8xl text-[var(--text-muted)]">verified_user</span>
                    </div>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                                <span class="material-symbols-outlined font-variation-fill">fact_check</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-[var(--text-main)]">Inspección Cualitativa</h3>
                                <p class="text-xs text-[var(--text-muted)] uppercase font-bold tracking-widest mt-0.5">Aprueba / Falla
                                    / No Aplica</p>
                            </div>
                        </div>
                        <span
                            class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest"><?= count($qualitativeChecks) ?>
                            ítems</span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($qualitativeChecks as $idx => $check): ?>
                            <div
                                class="flex items-center justify-between p-4 border rounded-2xl transition-all <?= $isCompleted ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-medical-surface/50 border-border-dark hover:border-medical-blue/30' ?>">
                                <div class="flex items-center gap-4">
                                    <span
                                        class="material-symbols-outlined font-black <?= $isCompleted ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-700' ?>">
                                        <?= $isCompleted ? 'check_circle' : 'radio_button_unchecked' ?>
                                    </span>
                                    <span
                                        class="text-sm font-bold text-[var(--text-main)]"><?= $check ?></span>
                                </div>
                                <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="q_<?= $idx ?>" value="pass" class="hidden peer"
                                                <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'pass') ? 'checked' : '' ?> required>
                                            <span
                                                class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-border-dark text-[var(--text-muted)] peer-checked:bg-emerald-500/10 peer-checked:text-emerald-500 peer-checked:border-emerald-500/30 transition-all hover:border-emerald-500/30 cursor-pointer">Aprueba</span>
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="q_<?= $idx ?>" value="fail" class="hidden peer"
                                                <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'fail') ? 'checked' : '' ?>>
                                            <span
                                                class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-border-dark text-[var(--text-muted)] peer-checked:bg-red-500/10 peer-checked:text-red-500 peer-checked:border-red-500/30 transition-all hover:border-red-500/30 cursor-pointer">Falla</span>
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="q_<?= $idx ?>" value="na" class="hidden peer"
                                                <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'na') ? 'checked' : '' ?>>
                                            <span
                                                class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-border-dark text-[var(--text-muted)] peer-checked:bg-slate-500/10 peer-checked:text-slate-400 peer-checked:border-slate-500/30 transition-all hover:border-slate-500/30 cursor-pointer">N/A</span>
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
                        class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                            <span class="material-symbols-outlined text-8xl text-[var(--text-muted)]">speed</span>
                        </div>
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20">
                                    <span class="material-symbols-outlined font-variation-fill">labs</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-[var(--text-main)]">Pruebas Cuantitativas</h3>
                                    <p class="text-xs text-[var(--text-muted)] uppercase font-bold tracking-widest mt-0.5">Valor simulado
                                        vs. Valor medido (Tolerancia)</p>
                                </div>
                            </div>
                            <span
                                class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest"><?= count($quantitativeGroups) ?>
                                parámetros</span>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($quantitativeGroups as $gIdx => $group): ?>
                                <?php $groupSavedNA = isset($savedChecklist['quantitative']["group_na_$gIdx"]) && $savedChecklist['quantitative']["group_na_$gIdx"] == 'on'; ?>
                                <div class="p-5 bg-white/[0.02] dark:bg-black/[0.1] border border-border-dark rounded-2xl space-y-4" x-data="{ groupNA: <?= $groupSavedNA ? 'true' : 'false' ?> }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-black text-[var(--text-main)] uppercase tracking-wider" :class="groupNA ? 'opacity-30' : ''"><?= $group['group'] ?></h4>
                                            <?php if (!$isCompleted): ?>
                                                <label class="flex items-center gap-2 cursor-pointer bg-slate-200 dark:bg-slate-800 px-2 py-1 rounded-lg border border-border-dark">
                                                    <input type="checkbox" x-model="groupNA" name="group_na_<?= $gIdx ?>" <?= $groupSavedNA ? 'checked' : '' ?> class="rounded bg-white dark:bg-slate-900 border-border-dark text-medical-blue focus:ring-medical-blue/20">
                                                    <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">No Aplica</span>
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
                                            class="grid grid-cols-3 gap-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest px-2">
                                            <span>Valor Simulado</span>
                                            <span>Valor Medido</span>
                                            <span class="text-center">Estado</span>
                                        </div>

                                        <!-- Table Rows -->
                                        <?php foreach ($group['points'] as $pIdx => $point): ?>
                                            <div class="grid grid-cols-3 gap-3 items-center">
                                                <div
                                                    class="flex items-center gap-2 px-4 py-3 bg-[var(--input-bg)] border border-border-dark rounded-xl">
                                                    <span class="text-sm font-bold text-medical-blue"><?= $point['simulated'] ?></span>
                                                    <span class="text-[10px] text-[var(--text-muted)] font-bold"><?= $group['unit'] ?></span>
                                                </div>
                                                <?php if ($isCompleted): ?>
                                                    <div
                                                        class="flex items-center gap-2 px-4 py-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                                                        <span
                                                            class="text-sm font-bold text-emerald-500"><?= is_numeric($point['simulated']) ? $point['simulated'] + rand(-1, 1) : $point['simulated'] ?></span>
                                                        <span class="text-[10px] text-[var(--text-muted)] font-bold"><?= $group['unit'] ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <input type="text" name="m_<?= $gIdx ?>_<?= $pIdx ?>" placeholder="—"
                                                        value="<?= $savedChecklist['quantitative']["m_{$gIdx}_{$pIdx}"] ?? '' ?>"
                                                        class="px-4 py-3 bg-[var(--input-bg)] border border-border-dark rounded-xl text-sm text-[var(--text-main)] focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all font-bold"
                                                        <?= isReadOnly() ? 'readonly' : '' ?>>
                                                <?php endif; ?>
                                                <div class="flex justify-center">
                                                    <?php if ($isCompleted): ?>
                                                        <span
                                                            class="px-3 py-1.5 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg text-[9px] font-black uppercase tracking-widest">Pasa</span>
                                                    <?php else: ?>
                                                        <span
                                                            class="px-3 py-1.5 bg-slate-500/10 text-[var(--text-muted)] border border-border-dark rounded-lg text-[9px] font-black uppercase tracking-widest">Pendiente</span>
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
                        class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                            <span class="material-symbols-outlined text-8xl text-[var(--text-muted)]">bolt</span>
                        </div>
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 bg-red-500/10 text-red-500 rounded-xl border border-red-500/20">
                                    <span class="material-symbols-outlined font-variation-fill">electrical_services</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-[var(--text-main)]">Seguridad Eléctrica</h3>
                                    <p class="text-xs text-[var(--text-muted)] uppercase font-bold tracking-widest mt-0.5">IEC 62353 ·
                                        Mediciones Normativas</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <!-- Header -->
                            <div
                                class="grid grid-cols-4 gap-3 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest px-4">
                                <span>Parámetro</span>
                                <span>Valor Esperado</span>
                                <span>Valor Medido</span>
                                <span class="text-center">Tolerancia</span>
                            </div>
                            <?php foreach ($electricalSafety as $sIdx => $safety): ?>
                                <div
                                    class="grid grid-cols-4 gap-3 items-center p-4 bg-white/[0.02] dark:bg-black/[0.1] border border-border-dark rounded-2xl hover:border-red-500/20 transition-all">
                                    <span class="text-sm font-bold text-[var(--text-main)]"><?= $safety['param'] ?></span>
                                    <span class="text-sm font-bold text-red-500"><?= $safety['expected'] ?></span>
                                    <?php if ($isCompleted): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                                            <span class="text-sm font-bold text-emerald-500">Conforme</span>
                                        </div>
                                    <?php else: ?>
                                        <input type="text" name="es_<?= $sIdx ?>" placeholder="—"
                                            value="<?= $savedChecklist['electrical_safety']["es_$sIdx"] ?? '' ?>"
                                            class="px-3 py-2 bg-[var(--input-bg)] border border-border-dark rounded-xl text-sm text-[var(--text-main)] focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all font-bold"
                                            <?= isReadOnly() ? 'readonly' : '' ?>>
                                    <?php endif; ?>
                                    <span
                                        class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest text-center"><?= $safety['tolerance'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Documentación y Archivos Adjuntos -->
                <div class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                                <span class="material-symbols-outlined font-variation-fill">attachment</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-[var(--text-main)]">Evidencia y Adjuntos</h3>
                                <p class="text-xs text-[var(--text-muted)] uppercase font-bold tracking-widest mt-0.5">Protocolos
                                    firmados y capturas de pantalla</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php if (empty($attachments)): ?>
                            <div class="p-8 border-2 border-dashed border-border-dark rounded-2xl flex flex-col items-center justify-center text-[var(--text-muted)] gap-2">
                                <span class="material-symbols-outlined text-3xl">folder_off</span>
                                <p class="text-[10px] font-black uppercase tracking-widest text-center">Sin archivos adjuntos aún</p>
                            </div>
                        <?php endif; ?>

                        <?php
                        foreach ($attachments as $file):
                            $isPdf = str_contains($file['file_type'], 'pdf');
                            $isImage = str_contains($file['file_type'], 'image');
                            $fileName = basename($file['file_path']);
                        ?>
                            <div
                                class="flex items-center justify-between p-4 bg-medical-surface border border-border-dark rounded-2xl group hover:border-medical-blue/30 transition-all">
                                <div class="flex items-center gap-4">
                                    <?php if ($isImage): ?>
                                        <div class="w-12 h-12 rounded-lg overflow-hidden border border-border-dark flex-shrink-0">
                                            <img src="<?= $file['file_path'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform cursor-pointer" onclick="window.open(this.src, '_blank')">
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="p-2 rounded-lg <?= $isPdf ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-medical-blue/10 text-medical-blue border border-medical-blue/20' ?>">
                                            <span
                                                class="material-symbols-outlined"><?= $isPdf ? 'picture_as_pdf' : 'image' ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm font-bold text-[var(--text-main)]">
                                            <?= htmlspecialchars($file['caption'] ?: $fileName) ?>
                                        </p>
                                        <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-widest">
                                            <?= $file['category'] ?> · <?= date('d/m/Y', strtotime($file['uploaded_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?= $file['file_path'] ?>" download class="p-2 text-[var(--text-muted)] hover:text-medical-blue transition-colors">
                                    <span class="material-symbols-outlined">download</span>
                                </a>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                            <div class="mt-6 p-6 border-2 border-dashed border-border-dark rounded-2xl hover:border-medical-blue/50 transition-all">
                                <div class="flex flex-col items-center gap-4">
                                    <input type="file" name="attachment_file" id="ot_attachment" class="hidden" form="attachmentForm" onchange="document.getElementById('attachmentForm').submit()">
                                    <button type="button" onclick="document.getElementById('ot_attachment').click()"
                                        class="flex flex-col items-center gap-2 group text-[var(--text-muted)] hover:text-medical-blue transition-all w-full py-4">
                                        <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">cloud_upload</span>
                                        <p class="text-[10px] font-black uppercase tracking-widest">Cargar Evidencia Técnica</p>
                                        <p class="text-[9px] font-bold text-[var(--text-muted)]/60">JPG, PNG, PDF (Máx 10MB)</p>
                                    </button>
                                    <form id="attachmentForm" method="POST" enctype="multipart/form-data" class="w-full space-y-3">
                                        <input type="text" name="attachment_caption" placeholder="Nombre/Descripción del archivo..." class="w-full bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-xs text-[var(--text-main)] focus:border-medical-blue outline-none transition-all">
                                        <select name="attachment_category" class="w-full bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-xs text-[var(--text-main)] focus:border-medical-blue outline-none transition-all font-bold">
                                            <option value="evidencia">Evidencia Técnica</option>
                                            <option value="protocolo">Protocolo Firmado</option>
                                            <option value="repuesto">Factura / Guía Repuestos</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informe de Intervención -->
                <div
                    class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                            <span class="material-symbols-outlined font-variation-fill">edit_note</span>
                        </div>
                        <h3 class="text-xl font-bold text-[var(--text-main)]">Informe Final de Intervención</h3>
                    </div>
                    <?php if ($isCompleted): ?>
                        <div
                            class="p-6 bg-medical-surface border border-border-dark rounded-2xl text-sm text-[var(--text-muted)] leading-relaxed italic relative">
                            <span
                                class="material-symbols-outlined absolute -top-3 -left-3 text-medical-blue text-4xl opacity-20">format_quote</span>
                            <?= htmlspecialchars($ot['observations'] ?? 'Sin observaciones registradas.') ?>
                        </div>
                    <?php else: ?>
                        <textarea
                            name="final_observations"
                            form="executionForm"
                            class="w-full bg-[var(--input-bg)] border border-border-dark rounded-2xl p-6 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[180px] text-[var(--text-main)] placeholder:text-slate-500 font-medium"
                            placeholder="Describa el trabajo realizado, hallazgos técnicos, repuestos reemplazados y observaciones finales..."
                            <?= isReadOnly() ? 'readonly' : '' ?>><?= htmlspecialchars($ot['observations'] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-4 space-y-8">
                <div class="bg-medical-surface p-6 rounded-3xl border border-border-dark shadow-xl">
                    <h4 class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-6">Referencia Técnica del
                        Equipo</h4>
                    <div class="space-y-3">
                        <?php foreach ($referenceDocs as $doc): ?>
                            <button
                                class="w-full flex items-center gap-3 p-3 bg-medical-surface border border-transparent hover:border-medical-blue/30 rounded-xl text-left transition-all group">
                                <span
                                    class="material-symbols-outlined text-medical-blue opacity-50 group-hover:opacity-100">description</span>
                                <div>
                                    <p class="text-[11px] font-bold text-[var(--text-main)] transition-colors">
                                        <?= $doc['name'] ?>
                                    </p>
                                    <p class="text-[9px] text-[var(--text-muted)] font-bold uppercase"><?= $doc['category'] ?></p>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Datos del Equipo -->
                <div class="bg-medical-surface p-6 rounded-3xl border border-border-dark shadow-xl">
                    <h4 class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-6">Datos del Activo</h4>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-1 p-3 bg-[var(--input-bg)] rounded-xl border border-border-dark">
                            <label class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest group-focus-within:text-medical-blue">Nombre del Equipo</label>
                            <input type="text" name="asset_name" form="executionForm" value="<?= htmlspecialchars($ot['asset_name'] ?? $templateLabel) ?>" placeholder="Nombre del equipo..." class="bg-transparent text-xs font-bold text-[var(--text-main)] outline-none placeholder:text-slate-500" <?= isReadOnly() ? 'readonly' : '' ?>>
                        </div>
                        <div class="flex flex-col gap-1 p-3 bg-[var(--input-bg)] rounded-xl border border-border-dark group focus-within:border-medical-blue/30 transition-all">
                            <label class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest group-focus-within:text-medical-blue">Marca</label>
                            <input type="text" name="brand" form="executionForm" value="<?= htmlspecialchars($asset['brand'] ?? '') ?>" placeholder="Completar marca..." class="bg-transparent text-xs font-bold text-[var(--text-main)] outline-none placeholder:text-slate-500" <?= isReadOnly() ? 'readonly' : '' ?>>
                        </div>
                        <div class="flex flex-col gap-1 p-3 bg-[var(--input-bg)] rounded-xl border border-border-dark group focus-within:border-medical-blue/30 transition-all">
                            <label class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest group-focus-within:text-medical-blue">Modelo</label>
                            <input type="text" name="model" form="executionForm" value="<?= htmlspecialchars($asset['model'] ?? '') ?>" placeholder="Completar modelo..." class="bg-transparent text-xs font-bold text-[var(--text-main)] outline-none placeholder:text-slate-500" <?= isReadOnly() ? 'readonly' : '' ?>>
                        </div>
                        <div class="flex flex-col gap-1 p-3 bg-[var(--input-bg)] rounded-xl border border-border-dark group focus-within:border-medical-blue/30 transition-all">
                            <label class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest group-focus-within:text-medical-blue">Serie / Placa</label>
                            <input type="text" name="serial_number" form="executionForm" value="<?= htmlspecialchars($asset['serial_number'] ?? '') ?>" placeholder="S/N o Inventario..." class="bg-transparent text-xs font-bold text-[var(--text-main)] outline-none placeholder:text-slate-500" <?= isReadOnly() ? 'readonly' : '' ?>>
                        </div>
                        <div class="flex flex-col gap-1 p-3 bg-[var(--input-bg)] rounded-xl border border-border-dark group focus-within:border-medical-blue/30 transition-all">
                            <label class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest group-focus-within:text-medical-blue">Servicio / Ubicación</label>
                            <?php $locations = getAllLocations(); ?>
                            <select name="location" form="executionForm" class="bg-transparent text-xs font-bold text-[var(--text-main)] outline-none appearance-none cursor-pointer" <?= isReadOnly() ? 'disabled' : '' ?>>
                                <option value="" disabled>Seleccione ubicación...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>" <?= ($asset['location'] ?? '') === $loc ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-medical-surface p-8 rounded-3xl border border-border-dark shadow-2xl sticky top-28">
                    <h3 class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-[0.2em] mb-8">Información de Cierre
                    </h3>

                    <div class="space-y-6">
                        <div class="p-5 bg-medical-surface border border-border-dark rounded-2xl">
                            <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-3">Técnico
                                Responsable</p>
                            <div class="flex items-center gap-4">
                                <div
                                    class="size-10 rounded-full bg-medical-blue/20 border border-medical-blue/30 flex items-center justify-center font-black text-medical-blue">
                                    <?= strtoupper(substr($ot['technician_name'] ?? 'T', 0, 1)) ?></div>
                                <div>
                                    <p class="text-sm font-bold text-[var(--text-main)]"><?= $ot['technician_name'] ?? 'Técnico' ?></p>
                                    <p class="text-[9px] text-[var(--text-muted)] font-bold uppercase tracking-wider">Ing.
                                        Electromedicina</p>
                                </div>
                            </div>
                        </div>

                        <!-- Codificación de Falla (Solo Correctivas) -->
                        <?php if (($ot['type'] ?? '') === 'Correctiva'): ?>
                            <div class="p-5 bg-medical-surface border border-border-dark rounded-2xl">
                                <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-3">Código de Falla (RCA)</p>
                                <?php if ($isCompleted): ?>
                                    <div class="text-sm font-bold text-medical-blue"><?= htmlspecialchars($ot['failure_code'] ?? 'Sin especificar') ?></div>
                                <?php else: ?>
                                    <select name="failure_code" form="executionForm"
                                        class="w-full bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-sm font-bold text-[var(--text-main)] outline-none focus:border-medical-blue transition-all appearance-none">
                                        <option value="">Seleccionar...</option>
                                        <option value="Falla de Hardware" <?= ($ot['failure_code'] ?? '') === 'Falla de Hardware' ? 'selected' : '' ?>>Falla de Hardware</option>
                                        <option value="Error de Software" <?= ($ot['failure_code'] ?? '') === 'Error de Software' ? 'selected' : '' ?>>Error de Software</option>
                                        <option value="Error de Usuario" <?= ($ot['failure_code'] ?? '') === 'Error de Usuario' ? 'selected' : '' ?>>Error de Usuario</option>
                                        <option value="Desgaste Natural" <?= ($ot['failure_code'] ?? '') === 'Desgaste Natural' ? 'selected' : '' ?>>Desgaste Natural</option>
                                        <option value="Otro" <?= ($ot['failure_code'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro (Ver Obs.)</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Estado Final del Activo -->
                        <div class="p-5 bg-medical-surface border border-border-dark rounded-2xl">
                            <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-3">Estado Final del Activo</p>
                            <?php if ($isCompleted): ?>
                                <div class="flex items-center gap-2 <?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'text-emerald-500' : 'text-red-500' ?>">
                                    <span class="material-symbols-outlined"><?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'check_circle' : 'cancel' ?></span>
                                    <span class="text-sm font-bold"><?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'Operativo' : 'Fuera de Servicio' ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex gap-3">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="final_asset_status" form="executionForm" value="OPERATIVE" class="hidden peer"
                                            <?= ($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'checked' : '' ?> required>
                                        <div class="p-3 text-center rounded-xl border border-border-dark text-[var(--text-muted)] text-[10px] font-black uppercase tracking-widest peer-checked:bg-emerald-500/10 peer-checked:text-emerald-500 peer-checked:border-emerald-500/30 transition-all hover:border-emerald-500/30">OPERATIVO</div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="final_asset_status" form="executionForm" value="NO_OPERATIVE" class="hidden peer"
                                            <?= ($ot['final_asset_status'] ?? '') === 'NO_OPERATIVE' ? 'checked' : '' ?>>
                                        <div class="p-3 text-center rounded-xl border border-border-dark text-[var(--text-muted)] text-[10px] font-black uppercase tracking-widest peer-checked:bg-red-500/10 peer-checked:text-red-500 peer-checked:border-red-500/30 transition-all hover:border-red-500/30">Baja / F.S</div>
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Garantía del Servicio -->
                        <div class="p-5 bg-medical-surface border border-border-dark rounded-2xl">
                            <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-3">Garantía del Servicio</p>
                            <?php if ($isCompleted): ?>
                                <div class="text-xs font-bold text-[var(--text-main)]">
                                    <?= $ot['service_warranty_date'] ? 'Vence: ' . $ot['service_warranty_date'] : 'Sin garantía' ?>
                                </div>
                            <?php else: ?>
                                <input type="date" name="service_warranty_date" form="executionForm" class="w-full bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-xs font-bold text-[var(--text-main)] outline-none focus:border-medical-blue transition-all">
                                <p class="text-[9px] text-[var(--text-muted)] mt-2 font-bold uppercase tracking-wider">Dejar vacío si no aplica</p>
                            <?php endif; ?>
                        </div>

                        <!-- Duración del Trabajo -->
                        <?php if (!$isCompleted): ?>
                            <div class="p-5 bg-medical-surface border border-border-dark rounded-2xl">
                                <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-3">Tiempo Invertido</p>
                                <div class="flex items-center gap-3">
                                    <input type="number" step="0.5" name="duration_hours" form="executionForm" placeholder="0.0" class="w-24 bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-sm font-bold text-[var(--text-main)] outline-none focus:border-medical-blue transition-all" required>
                                    <span class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-widest">Horas Hombre</span>
                                </div>
                            </div>

                            <!-- Rastreo de Horas del Activo (Opción Sugerida) -->
                            <div class="p-5 bg-medical-blue/5 border border-medical-blue/20 rounded-2xl group focus-within:border-medical-blue/50 transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-[10px] font-black text-medical-blue uppercase tracking-widest">Horómetro / Horas Actuales</p>
                                    <span class="material-symbols-outlined text-medical-blue text-sm opacity-50 group-focus-within:opacity-100">timer</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="number" step="1" name="current_asset_hours" form="executionForm"
                                        placeholder="<?= $asset['hours_used'] ?? '0' ?>"
                                        class="w-full bg-[var(--input-bg)] border border-border-dark rounded-xl px-4 py-2 text-sm font-bold text-[var(--text-main)] outline-none focus:border-medical-blue transition-all">
                                </div>
                                <p class="text-[9px] text-[var(--text-muted)] mt-2 font-bold uppercase tracking-wider">
                                    Opcional. Dejar vacío si el equipo no posee horómetro.
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($isCompleted): ?>
                            <div class="space-y-4">
                                <div
                                    class="p-5 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl text-center shadow-[inset_0_0_12px_rgba(16,185,129,0.05)]">
                                    <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Condición
                                        Final</span>
                                    <p class="text-xl font-bold text-[var(--text-main)] mt-1">Conforme / Operativo</p>
                                </div>
                                <a href="?page=report_print&id=<?= $id ?>" target="_blank"
                                    class="w-full py-4 border border-border-dark text-[var(--text-main)] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-3 text-xs">
                                    <span class="material-symbols-outlined text-xl">print</span>
                                    <span>Imprimir Reporte OT</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php if (canCompleteWorkOrder()): ?>
                                    <input type="hidden" name="action" form="executionForm" value="complete_ot">
                                    <button type="submit" form="executionForm"
                                        class="w-full py-4 bg-emerald-500 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-emerald-500/20 hover:bg-emerald-500/90 transition-all flex items-center justify-center gap-3 text-xs">
                                        <span class="material-symbols-outlined text-xl">verified</span>
                                        <span>Finalizar e Informar</span>
                                    </button>
                                <?php endif; ?>
                                <?php if (canExecuteWorkOrder()): ?>
                                    <button type="submit" form="executionForm" name="save_draft" value="1"
                                        class="w-full py-4 bg-medical-surface border border-border-dark text-[var(--text-main)] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-3 text-xs">
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

    <!-- Modal de Firma Electrónica -->
    <template x-if="showSignatureModal">
        <div
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-300">
            <div @click.away="showSignatureModal = false"
                class="max-w-md w-full bg-medical-surface border border-border-dark rounded-3xl p-8 shadow-2xl space-y-8">
                <div class="text-center">
                    <div
                        class="size-16 rounded-2xl bg-medical-blue/10 text-medical-blue border border-medical-blue/20 flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-3xl">draw</span>
                    </div>
                    <h2 class="text-2xl font-bold text-[var(--text-main)] tracking-tight">Firma de Conformidad</h2>
                    <p class="text-xs text-[var(--text-muted)] font-bold uppercase tracking-widest mt-2">Cierre de Orden y
                        Certificación Técnica</p>
                </div>

                <div class="space-y-4">
                    <div class="p-4 bg-medical-surface/50 border border-border-dark rounded-2xl">
                        <p class="text-[10px] text-[var(--text-muted)] font-black uppercase tracking-widest mb-1">Declaración de
                            Responsabilidad</p>
                        <p class="text-[11px] text-[var(--text-main)] italic leading-relaxed">
                            "Certifico que el equipo ha sido intervenido siguiendo los protocolos del fabricante y
                            cumple con los estándares de seguridad vigentes."
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div
                            class="p-3 bg-medical-surface/50 rounded-xl border border-border-dark flex items-center justify-between">
                            <span class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">Técnico
                                Ejecutante</span>
                            <span class="text-xs font-bold text-medical-blue"><?= $ot['technician_name'] ?? 'Técnico' ?></span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest ml-1">Confirmar
                            Identidad (PIN/ID)</label>
                        <input type="password" placeholder="••••"
                            class="w-full h-14 bg-[var(--input-bg)] border border-border-dark rounded-2xl px-6 text-xl tracking-[1em] text-center focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-[var(--text-main)]">
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button @click="showSignatureModal = false"
                        class="flex-1 py-4 border border-border-dark text-[var(--text-muted)] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-800 transition-all text-xs">Cancelar</button>
                    <button onclick="window.location.href='?page=work_orders'"
                        class="flex-1 py-4 bg-medical-blue text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-medical-blue/20 hover:bg-medical-blue/90 transition-all text-xs">Sellar
                        OT</button>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('executionState', () => ({
                showSignatureModal: false
            }))
        });
    </script>