<?php
// pages/work_order_execution.php

require_once __DIR__ . '/../includes/checklist_templates.php';
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

$id = $_GET['id'] ?? 'OT-2024-UNKNOWN';

// 1. Cargar datos de la OT al inicio para que estén disponibles en los handlers
$ot = getWorkOrderById($id);
if (!$ot) {
    echo "<div class='p-8 text-center'>
        <p class='text-red-500 font-bold text-lg'>Orden no encontrada o error de conexión</p>
        <p class='text-slate-400 text-sm mt-2'>ID buscado: <code class='bg-slate-800 px-2 py-1 rounded'>" . htmlspecialchars($id) . "</code></p>
        <p class='text-slate-500 text-xs mt-4'>Verifique que la base de datos esté activa (XAMPP) y que la OT exista en la tabla <code>work_orders</code>.</p>
        <a href='?page=work_orders' class='mt-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg font-bold text-sm'>← Volver a OTs</a>
    </div>";
    return;
}

// 2. Centralized POST Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        die("<div class='min-h-screen flex items-center justify-center bg-medical-dark p-8'><div class='bg-medical-surface p-8 rounded-3xl border border-red-500/30 text-center max-w-lg shadow-2xl'><span class='material-symbols-outlined text-6xl text-red-500 mb-4 block'>security</span><h2 class='text-2xl font-black text-red-500 mb-2'>Token de seguridad inválido (CSRF)</h2><p class='text-slate-400 mb-6'>La sesión ha expirado o la petición es ilegítima. Por favor, vuelva atrás y recargue la página.</p><a href='?page=work_order_execution&id=$id' class='inline-block px-6 py-3 bg-medical-blue text-white rounded-xl font-bold'>Recargar Página</a></div></div>");
    }

    // A. Relevamiento de datos de ejecución (Común para Guardar, Finalizar y Pausar)
    $checklistData = [
        'qualitative' => [],
        'quantitative' => [],
        'electrical_safety' => []
    ];

    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'q_')) {
            $checklistData['qualitative'][$key] = $value;
        } elseif (str_starts_with($key, 'm_') || str_starts_with($key, 'n_') || str_starts_with($key, 'group_na_')) {
            $checklistData['quantitative'][$key] = $value;
        } elseif (str_starts_with($key, 'elec_')) {
            $checklistData['electrical_safety'][$key] = $value;
        }
    }

    $executionData = [
        'failure_code' => $_POST['failure_code'] ?? null,
        'service_warranty_date' => !empty($_POST['service_warranty_date']) ? $_POST['service_warranty_date'] : null,
        'final_asset_status' => $_POST['final_asset_status'] ?? 'OPERATIVE',
        'duration_hours' => $_POST['duration_hours'] ?? 0,
        'observations' => $_POST['final_observations'] ?? '',
        'handover_confirmed_by' => $_POST['handover_confirmed_by'] ?? null,
        'handover_location' => $_POST['handover_location'] ?? null,
        'checklist_data' => $checklistData
    ];

    // B. Handle Actions
    $action = $_POST['action'] ?? null;

    // 1. Finalizar OT
    if ($action === 'complete_ot') {
        // Actualizar datos del activo si se proporcionan
        if (isset($_POST['asset_id'])) {
            require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
            $assetUpdateData = [];
            if (!empty($_POST['brand'])) $assetUpdateData['brand'] = $_POST['brand'];
            if (!empty($_POST['model'])) $assetUpdateData['model'] = $_POST['model'];
            if (!empty($_POST['serial_number'])) $assetUpdateData['serial_number'] = $_POST['serial_number'];
            if (!empty($_POST['location'])) $assetUpdateData['location'] = $_POST['location'];
            if (!empty($_POST['asset_name'])) $assetUpdateData['name'] = $_POST['asset_name'];
            if (!empty($_POST['current_asset_hours'])) $assetUpdateData['hours_used'] = (int)$_POST['current_asset_hours'];
            if (!empty($assetUpdateData)) updateAssetInfo($_POST['asset_id'], $assetUpdateData);
        }

        $unifyOtIds = $_POST['unify_ot_ids'] ?? [];

        if (completeWorkOrder($id, $executionData, $unifyOtIds)) {
            echo "<script>window.location.href = '?page=work_order_execution&id=$id&completed=1';</script>";
            exit;
        }
    }

    // 2. Guardar Borrador — UPDATE directo, sin queries extras
    if ($action === 'save_draft') {
        try {
            $db = \Backend\Core\DatabaseService::getInstance();
            $stmt = $db->prepare("UPDATE work_orders SET 
                observations = :obs, 
                checklist_data = :checklist, 
                failure_code = :fc, 
                duration_hours = :dh, 
                final_asset_status = :fas, 
                service_warranty_date = :swd,
                updated_at = NOW() 
                WHERE id = :id");
            $stmt->execute([
                ':obs' => $executionData['observations'] ?? '',
                ':checklist' => json_encode($executionData['checklist_data']),
                ':fc' => $executionData['failure_code'],
                ':dh' => $executionData['duration_hours'] ?? 0,
                ':fas' => $executionData['final_asset_status'] ?? 'OPERATIVE',
                ':swd' => $executionData['service_warranty_date'],
                ':id' => $id
            ]);
            echo "<script>window.location.href = '?page=work_order_execution&id=$id&draft_saved=1';</script>";
            exit;
        } catch (\Exception $e) {
            // Si falla, continúa cargando la página con el error visible
        }
    }

    // 3. Pausar OT (Stall) -> Ahora guarda progreso antes de cambiar estado
    if ($action === 'stall_ot') {
        // Primero guardamos lo que el técnico lleva hecho
        saveWorkOrderProgress($id, $executionData);

        // Luego pausamos formalmente
        if (stallWorkOrderByCoordination($id, $_POST['stall_reason'] ?? 'Sin motivo especificado')) {
            echo "<script>window.location.href = '?page=work_order_execution&id=$id&stalled=1';</script>";
            exit;
        }
    }

    // 4. Cancelar OT
    if ($action === 'cancel_ot') {
        if (cancelWorkOrder($id, $_POST['cancel_reason'] ?? 'Cancelada por el técnico')) {
            echo "<script>window.location.href = '?page=work_order_execution&id=$id&cancelled=1';</script>";
            exit;
        }
    }

    // 5. Reanudar OT
    if ($action === 'resume_ot') {
        if (resumeStalledWorkOrder($id)) {
            echo "<script>window.location.href = '?page=work_order_execution&id=$id&resumed=1';</script>";
            exit;
        }
    }
}

// --- ESTADO DE COMPLETITUD ---
$statusValue = ($ot['status'] instanceof \Backend\Models\WorkOrderStatus) ? $ot['status']->value : (string)($ot['status'] ?? 'En Curso');
$isCompleted = ($statusValue === 'Terminada' || $statusValue === 'Cancelada' || isset($_GET['completed']));
$savedChecklist = $ot['checklist_data'] ?? [];

// 2. Obtener datos del equipo (Asset)
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
$asset = getAssetById($ot['asset_id']);

// 3. Determinar qué plantilla usar
$templateKey = $ot['checklist_template'] ?? ($_GET['tpl'] ?? null);

// Detección automática (Smart Mapping) basada en el nombre del equipo
if (!$templateKey) {
    $assetName = mb_strtolower($asset['name'] ?? '', 'UTF-8');
    if (strpos($assetName, 'ventilador') !== false) {
        $templateKey = 'ventilador_mecanico';
    } elseif (strpos($assetName, 'bomba de infus') !== false) {
        $templateKey = 'bomba_infusion';
    } elseif (strpos($assetName, 'desfibrilador') !== false) {
        $templateKey = 'monitor_desfibrilador';
    } elseif (strpos($assetName, 'electrocardiógrafo') !== false || strpos($assetName, 'electrocardiografo') !== false) {
        $templateKey = 'electrocardiografo';
    } elseif (strpos($assetName, 'monitor') !== false) {
        $templateKey = 'monitor_signos_vitales';
    } else {
        $templateKey = 'formato_general';
    }
}

$template = getChecklistTemplate($templateKey);

if (!$template) {
    $template = getChecklistTemplate('formato_general');
}

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

<div x-data="executionState" id="executionState" class="w-full space-y-6 animate-in fade-in duration-500">
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
                $statusValue = $ot['status'] ?? 'En Curso';
                $statusClass = match ($statusValue) {
                    'Terminada' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
                    'En Curso' => 'bg-blue-500/10 text-blue-500 border-blue-500/30 shadow-[0_0_12px_rgba(59,130,246,0.1)]',
                    'En Espera' => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
                    'Cancelada' => 'bg-red-500/10 text-red-500 border-red-500/30',
                    default => 'bg-slate-700/10 text-slate-500 border-slate-700/30'
                };
                $dotClass = match ($statusValue) {
                    'Terminada' => 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]',
                    'En Curso' => 'bg-blue-500 animate-pulse',
                    'En Espera' => 'bg-amber-500',
                    'Cancelada' => 'bg-red-500',
                    default => 'bg-slate-500'
                };
                $statusLabel = match ($statusValue) {
                    'Terminada' => 'FINALIZADA',
                    'En Curso' => 'EN CURSO',
                    'En Espera' => 'EN ESPERA',
                    'Cancelada' => 'CANCELADA',
                    default => strtoupper($statusValue)
                };
                ?>
                <span
                    class="px-4 py-1.5 border rounded-2xl text-[10px] font-black uppercase tracking-wider flex items-center gap-2 <?= $statusClass ?>">
                    <span class="size-2 rounded-full <?= $dotClass ?>"></span>
                    <?= $statusLabel ?>
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
            <div class="flex gap-3">
                <?php if ($isCompleted): ?>
                    <a href="Backend/Exports/generate_ot_pdf.php?id=<?= $id ?>" target="_blank"
                        class="px-6 py-3 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center gap-2 shadow-lg shadow-medical-blue/20 hover:scale-[1.02] transition-all">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        Reporte PDF
                    </a>
                    <button type="button" onclick="window.print()"
                        class="px-6 py-3 bg-medical-surface border border-[var(--border-color)] text-[var(--text-main)] rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center gap-2 hover:bg-medical-blue/10 transition-all print:hidden">
                        <span class="material-symbols-outlined text-sm">print</span>
                        Imprimir
                    </button>
                <?php endif; ?>
                <a href="?page=asset&id=<?= $ot['asset_id'] ?? '' ?>"
                    class="px-6 py-3 bg-medical-surface border border-[var(--border-color)] text-[var(--text-main)] rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-medical-blue/10 transition-all print:hidden">
                    <span class="material-symbols-outlined text-xl">history</span>
                    Ficha del Activo
                </a>
            </div>
        </div>
    </div> <!-- Cierre del Header Flex Container -->

    <!-- Main Layout Grid -->
    <form method="POST" id="executionForm" class="grid grid-cols-1 xl:grid-cols-12 gap-8 w-full p-0 m-0" style="width: 100% !important; max-width: none !important;">
        <?= csrfField() ?>
        <!-- Hidden Asset ID and Stall Reason for updates -->
        <input type="hidden" name="asset_id" value="<?= $ot['asset_id'] ?? '' ?>">
        <input type="hidden" name="stall_reason" x-model="stallReason">
        <input type="hidden" name="action" :value="formAction">

        <!-- CONTENIDO PRINCIPAL -->
        <div class="xl:col-span-8 2xl:col-span-9 space-y-6" style="width: 100% !important; max-width: none !important;">
            <!-- Info Banner (Plantilla + Estado) Movido aquí para mejor flujo -->
            <div class="flex flex-col sm:flex-row items-center gap-4 p-5 bg-medical-blue/5 border border-medical-blue/20 rounded-2xl mb-6">
                <div class="p-3 bg-medical-blue/10 text-medical-blue rounded-2xl">
                    <span class="material-symbols-outlined text-3xl font-variation-fill"><?= $templateIcon ?></span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-[var(--text-main)]"><?= $templateLabel ?></h2>
                        <span class="px-3 py-0.5 bg-medical-blue text-white text-[10px] font-black rounded-lg uppercase tracking-widest">
                            <?= $templateVersion ?>
                        </span>
                    </div>
                    <p class="text-xs text-[var(--text-muted)] font-bold mt-1">
                        <?= count($qualitativeChecks) ?> inspecciones · <?= count($quantitativeGroups) ?> pruebas de metrología
                    </p>
                </div>
                <?php if ($ot['status'] === 'Retrasado por Servicio'): ?>
                    <div class="px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <p class="text-[10px] font-black text-red-500 uppercase tracking-widest">⚠️ STALLED BY SERVICE</p>
                        <p class="text-[9px] text-red-500/70 font-bold"><?= $ot['coordination_stalled_reason'] ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 1: Inspección Cualitativa (Checklist)          -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div
                class="bg-medical-surface p-10 rounded-3xl border border-[var(--border-color)] shadow-[0_4px_24px_rgba(0,0,0,0.02)] relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
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

                <?php
                // Pre-procesar campos personalizados guardados
                $savedCustomQuali = [];
                foreach (($savedChecklist['qualitative'] ?? []) as $k => $v) {
                    if (str_starts_with($k, 'q_custom_label_')) {
                        $id = str_replace('q_custom_label_', '', $k);
                        $savedCustomQuali[] = [
                            'id' => $id,
                            'label' => $v,
                            'value' => $savedChecklist['qualitative']["q_custom_val_$id"] ?? ''
                        ];
                    }
                }
                ?>
                <div class="space-y-3" x-data="{ 
                        newCheck: '', 
                        customChecks: <?= json_encode($savedCustomQuali, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                        addCheck() {
                            if(this.newCheck.trim() === '') return;
                            this.customChecks.push({
                                id: Date.now().toString(),
                                label: this.newCheck.trim(),
                                value: ''
                            });
                            this.newCheck = '';
                            $dispatch('item-added');
                        },
                        removeCheck(idToRemove) {
                            this.customChecks = this.customChecks.filter(c => c.id !== idToRemove);
                            $dispatch('item-removed');
                        }
                    }">
                    <!-- Grid para items cualitativos (Compacto) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($qualitativeChecks as $idx => $check): ?>
                            <div
                                class="flex items-center justify-between p-3 border border-[var(--border-color)] rounded-xl transition-all <?= $isCompleted ? 'bg-emerald-500/5' : 'hover:bg-medical-blue/5' ?>">
                                <div class="flex flex-col w-full">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="material-symbols-outlined font-light text-lg <?= $isCompleted ? 'text-emerald-500' : 'text-text-muted/30' ?>">
                                            <?= $isCompleted ? 'check_circle' : 'radio_button_unchecked' ?>
                                        </span>
                                        <span class="text-xs font-bold text-[var(--text-main)] leading-tight"><?= $check ?></span>
                                    </div>

                                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                                        <div class="flex items-center gap-1 justify-start">
                                            <label class="grow">
                                                <input type="radio" name="q_<?= $idx ?>" value="pass" class="hidden peer" @change="updateProgress()"
                                                    <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'pass') ? 'checked' : '' ?> required>
                                                <span
                                                    class="block text-center px-2 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] border border-[var(--border-color)] peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-sm transition-all cursor-pointer">OK</span>
                                            </label>
                                            <label class="grow">
                                                <input type="radio" name="q_<?= $idx ?>" value="fail" class="hidden peer" @change="updateProgress()"
                                                    <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'fail') ? 'checked' : '' ?>>
                                                <span
                                                    class="block text-center px-2 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] border border-[var(--border-color)] peer-checked:bg-red-500 peer-checked:text-white peer-checked:border-red-600 peer-checked:shadow-sm transition-all cursor-pointer">FALLA</span>
                                            </label>
                                            <label class="grow">
                                                <input type="radio" name="q_<?= $idx ?>" value="na" class="hidden peer" @change="updateProgress()"
                                                    <?= (isset($savedChecklist['qualitative']["q_$idx"]) && $savedChecklist['qualitative']["q_$idx"] === 'na') ? 'checked' : '' ?>>
                                                <span
                                                    class="block text-center px-2 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] border border-[var(--border-color)] peer-checked:bg-slate-600 peer-checked:text-white peer-checked:border-slate-700 peer-checked:shadow-sm transition-all cursor-pointer">N/A</span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Custom Checks Rendered via Alpine -->
                        <template x-for="c in customChecks" :key="c.id">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-2xl transition-all border-medical-blue/30 bg-medical-blue/5 group relative">
                                <input type="hidden" :name="'q_custom_label_' + c.id" :value="c.label">

                                <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                                    <button type="button" @click="removeCheck(c.id)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg" title="Eliminar ítem">
                                        <span class="material-symbols-outlined text-[10px] font-black">close</span>
                                    </button>
                                <?php endif; ?>

                                <div class="flex items-center gap-4 w-full sm:w-1/2">
                                    <span class="material-symbols-outlined font-black text-medical-blue hidden sm:block">bookmark_added</span>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-medical-blue" x-text="c.label"></span>
                                        <span class="text-[8px] uppercase tracking-widest text-medical-blue/60 font-black">Adicional</span>
                                    </div>
                                </div>
                                <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                                    <div class="flex items-center gap-2 justify-end grow mt-3 sm:mt-0">
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" :name="'q_custom_val_' + c.id" value="pass" class="hidden peer" x-model="c.value" required @change="updateProgress()">
                                            <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:shadow-md transition-all cursor-pointer">Aprueba</span>
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" :name="'q_custom_val_' + c.id" value="fail" class="hidden peer" x-model="c.value" @change="updateProgress()">
                                            <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-md transition-all cursor-pointer">Falla</span>
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" :name="'q_custom_val_' + c.id" value="na" class="hidden peer" x-model="c.value" @change="updateProgress()">
                                            <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] bg-[var(--input-bg)] peer-checked:bg-slate-600 peer-checked:text-white peer-checked:shadow-md transition-all cursor-pointer">N/A</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </template>
                    </div>

                    <?php if (!$isCompleted && canExecuteWorkOrder()): ?>
                        <!-- Add Custom Field Input -->
                        <div class="flex items-center gap-3 p-3 mt-4 border border-dashed rounded-xl border-[var(--border-color)] bg-[var(--input-bg)]">
                            <span class="material-symbols-outlined text-[var(--text-muted)] ml-2">add_task</span>
                            <input type="text" x-model="newCheck" @keydown.enter.prevent="addCheck()" placeholder="Escribe para agregar otra revisión cualitativa..." class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-bold text-[var(--text-main)] placeholder:text-[var(--text-muted)]/50 outline-none">
                            <button type="button" @click="addCheck()" :disabled="!newCheck.trim()" class="px-4 py-2 bg-medical-blue/10 text-medical-blue hover:bg-medical-blue/20 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                                Agregar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 2: Mediciones Cuantitativas (Parámetros)     -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div
                class="bg-medical-surface p-10 rounded-3xl border border-[var(--border-color)] shadow-[0_4px_24px_rgba(0,0,0,0.02)] relative overflow-hidden">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl">
                        <span class="material-symbols-outlined text-2xl font-light">straighten</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[var(--text-main)]">Mediciones Técnicas</h3>
                        <p class="text-xs text-[var(--text-muted)]">Registre valores numéricos de funcionamiento</p>
                    </div>
                </div>

                <!-- DEBUG -->
                <?php if (isset($_GET['debug_data'])): ?>
                    <pre class="text-[8px] bg-black text-white p-4 my-4 overflow-auto max-h-64">
                            <?php var_export($quantitativeGroups); ?>
                        </pre>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php foreach ($quantitativeGroups as $idx => $groupData): ?>
                        <?php
                        $groupName = is_array($groupData['group'] ?? null) ? "ERROR" : ($groupData['group'] ?? 'Sin Etiqueta');
                        $points = $groupData['points'] ?? [['simulated' => '']]; // Default to 1 input if no points defined
                        ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-medical-surface border border-[var(--border-color)] rounded-2xl w-full">
                            <!-- Lado izquierdo: Etiqueta -->
                            <div class="flex-1">
                                <label class="text-xs font-black text-[var(--text-main)] uppercase tracking-widest">
                                    <?= htmlspecialchars($groupName) ?>
                                </label>
                                <?php if (isset($groupData['tolerance_label'])): ?>
                                    <p class="text-[9px] text-[var(--text-muted)] font-bold mt-1">Tolerancia: <?= htmlspecialchars($groupData['tolerance_label']) ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Lado derecho: Cajas (Cuadros de valores simulados) -->
                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <?php foreach ($points as $pIdx => $point): ?>
                                    <div class="flex items-center gap-2 bg-[var(--input-bg)] border border-[var(--border-color)] p-1.5 rounded-xl">
                                        <?php if (isset($point['simulated']) && $point['simulated'] !== ''): ?>
                                            <span class="text-[10px] bg-medical-blue/10 text-medical-blue font-bold px-2 py-1 rounded-lg w-12 text-center">
                                                <?= htmlspecialchars($point['simulated']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <input type="number" step="any" name="n_<?= $idx ?>_<?= $pIdx ?>"
                                            value="<?= $savedChecklist['quantitative']["n_{$idx}_{$pIdx}"] ?? '' ?>"
                                            <?php if ($isCompleted || !canExecuteWorkOrder()): ?> disabled <?php endif; ?>
                                            class="w-20 bg-transparent border-none px-2 py-1 text-sm font-bold text-center focus:ring-0 outline-none placeholder:text-[var(--text-muted)]/30"
                                            placeholder="0.00">
                                        <?php if (isset($groupData['unit'])): ?>
                                            <span class="text-[10px] font-black text-[var(--text-muted)] w-8 text-center"><?= htmlspecialchars($groupData['unit']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- SECCIÓN 3: Seguridad Eléctrica (IEC 62353)             -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <?php if (!empty($electricalSafety)): ?>
                <div
                    class="bg-medical-surface p-8 xl:p-12 rounded-3xl border border-[var(--border-color)] shadow-xl relative overflow-hidden">
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
                            class="grid grid-cols-[1.8fr_1fr_1.2fr_0.8fr] gap-4 text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest px-4 mb-2">
                            <span>Parámetro de Seguridad</span>
                            <span class="text-center">Límite</span>
                            <span class="text-center">Medición</span>
                            <span class="text-center">Tolerancia</span>
                        </div>
                        <?php foreach ($electricalSafety as $sIdx => $safety): ?>
                            <div
                                class="grid grid-cols-[1.8fr_1fr_1.2fr_0.8fr] gap-4 items-center p-4 bg-white/[0.02] dark:bg-black/[0.1] border border-[var(--border-color)] rounded-2xl hover:border-red-500/20 transition-all">
                                <span class="text-sm font-bold text-[var(--text-main)]"><?= $safety['param'] ?></span>
                                <span class="text-xs font-bold text-red-500 text-center bg-red-500/10 py-1 rounded-lg border border-red-500/20"><?= htmlspecialchars($safety['expected']) ?></span>
                                <?php if ($isCompleted): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                                        <span class="text-sm font-bold text-emerald-500">Conforme</span>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="es_<?= $sIdx ?>" placeholder="—"
                                        value="<?= $savedChecklist['electrical_safety']["es_$sIdx"] ?? '' ?>"
                                        class="px-3 py-2 bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl text-sm text-[var(--text-main)] focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all font-bold"
                                        <?= isReadOnly() ? 'readonly' : '' ?>>
                                <?php endif; ?>
                                <span
                                    class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest text-center opacity-60"><?= $safety['tolerance'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECCIÓN 3: Observaciones Finales y Repuestos        -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="bg-medical-surface p-10 rounded-3xl border border-[var(--border-color)] shadow-xl relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl">
                        <span class="material-symbols-outlined font-variation-fill">settings_suggest</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[var(--text-main)]">Detalles de Ejecución</h3>
                        <p class="text-xs text-medical-blue font-black uppercase tracking-[0.2em] mt-1">Categorización y Tiempos</p>
                    </div>
                </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Tiempo de Trabajo -->
                        <div class="space-y-3">
                            <label class="block text-[11px] font-black text-[var(--text-main)] uppercase tracking-[0.15em]">Horas de Intervención</label>
                            <div class="relative group">
                                <input type="number" step="0.5" name="duration_hours" value="<?= $ot['duration_hours'] ?? 0 ?>"
                                    class="w-full bg-[var(--input-bg)] border-2 border-[var(--border-color)] group-hover:border-medical-blue/50 rounded-2xl px-5 py-4 text-sm focus:border-medical-blue outline-none transition-all font-bold text-[var(--text-main)]"
                                    <?= isReadOnly() ? 'disabled' : '' ?>>
                                <span class="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-medical-blue/60">HRS</span>
                            </div>
                        </div>

                        <!-- Estado Final -->
                        <div class="space-y-3">
                            <label class="block text-[11px] font-black text-[var(--text-main)] uppercase tracking-[0.15em]">Estado Final del Activo</label>
                            <div class="relative group">
                                <select name="final_asset_status"
                                    class="w-full bg-[var(--input-bg)] border-2 border-[var(--border-color)] group-hover:border-medical-blue/50 rounded-2xl px-5 py-4 text-sm focus:border-medical-blue outline-none transition-all font-bold text-[var(--text-main)] appearance-none"
                                    <?= isReadOnly() ? 'disabled' : '' ?>>
                                    <option value="OPERATIVE" <?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'selected' : '' ?>>OPERATIVO</option>
                                    <option value="DEGRADED" <?= ($ot['final_asset_status'] ?? '') === 'DEGRADED' ? 'selected' : '' ?>>OPERATIVO CON RESTRICCIÓN</option>
                                    <option value="OUT_OF_SERVICE" <?= ($ot['final_asset_status'] ?? '') === 'OUT_OF_SERVICE' ? 'selected' : '' ?>>FUERA DE SERVICIO</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--text-muted)]">expand_more</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Handover Section (Only if completing) -->
            <div x-show="isCompleting" x-transition class="bg-emerald-500/5 p-6 rounded-3xl border border-emerald-500/20 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-emerald-500">handshake</span>
                    <h4 class="text-sm font-black text-emerald-700 uppercase tracking-widest">Entrega de Equipo</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-wider mb-2">Recibido por (Nombre/RUT)</label>
                        <input type="text" name="handover_confirmed_by" placeholder="Personal clínico que recibe..."
                            class="w-full bg-white border border-emerald-500/20 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-wider mb-2">Lugar de Entrega</label>
                        <input type="text" name="handover_location" placeholder="Servicio/Unidad..."
                            class="w-full bg-white border border-emerald-500/20 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 outline-none">
                    </div>
                </div>
            </div>

            <!-- Documentación y Archivos Adjuntos -->
            <div class="bg-medical-surface p-6 rounded-3xl border border-[var(--border-color)] shadow-xl">
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
                        <div class="p-8 border-2 border-dashed border-[var(--border-color)] rounded-3xl bg-[var(--input-bg)] flex flex-col items-center justify-center text-[var(--text-muted)] gap-2">
                            <span class="material-symbols-outlined text-3xl opacity-50">folder_off</span>
                            <p class="text-[10px] font-black uppercase tracking-widest text-center opacity-75">Sin archivos adjuntos aún</p>
                        </div>
                    <?php endif; ?>

                    <?php
                    foreach ($attachments as $file):
                        $isPdf = str_contains($file['file_type'], 'pdf');
                        $isImage = str_contains($file['file_type'], 'image');
                        $fileName = basename($file['file_path']);
                    ?>
                        <div
                            class="flex items-center justify-between p-4 bg-medical-surface border border-[var(--border-color)] rounded-2xl group hover:border-medical-blue/30 transition-all">
                            <div class="flex items-center gap-4">
                                <?php if ($isImage): ?>
                                    <div class="w-12 h-12 rounded-lg overflow-hidden border border-[var(--border-color)] flex-shrink-0">
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
                        <div class="mt-6 p-6 border-2 border-dashed border-[var(--border-color)] rounded-2xl hover:border-medical-blue/50 transition-all">
                            <div class="flex flex-col items-center gap-4">
                                <input type="file" name="attachment_file" id="ot_attachment" class="hidden" form="attachmentForm" onchange="document.getElementById('attachmentForm').submit()">
                                <button type="button" onclick="document.getElementById('ot_attachment').click()"
                                    class="flex flex-col items-center gap-2 group text-[var(--text-muted)] hover:text-medical-blue transition-all w-full py-4">
                                    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">cloud_upload</span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Cargar Evidencia Técnica</p>
                                    <p class="text-[9px] font-bold text-[var(--text-muted)]/60">JPG, PNG, PDF (Máx 10MB)</p>
                                </button>
                                <form id="attachmentForm" method="POST" enctype="multipart/form-data" class="w-full space-y-3">
                                    <input type="text" name="attachment_caption" placeholder="Nombre/Descripción del archivo..." class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-4 py-2 text-xs text-[var(--text-main)] focus:border-medical-blue outline-none transition-all">
                                    <select name="attachment_category" class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-xl px-4 py-2 text-xs text-[var(--text-main)] focus:border-medical-blue outline-none transition-all font-bold">
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
                class="bg-medical-surface p-10 rounded-3xl border border-[var(--border-color)] shadow-xl relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl">
                        <span class="material-symbols-outlined font-variation-fill">edit_note</span>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-main)]">Informe Final de Intervención</h3>
                </div>
                <?php if ($isCompleted): ?>
                    <div
                        class="p-6 bg-medical-surface border border-[var(--border-color)] rounded-2xl text-sm text-[var(--text-muted)] leading-relaxed italic relative">
                        <span
                            class="material-symbols-outlined absolute -top-3 -left-3 text-medical-blue text-4xl opacity-20">format_quote</span>
                        <?= htmlspecialchars($ot['observations'] ?? 'Sin observaciones registradas.') ?>
                    </div>
                <?php else: ?>
                    <textarea
                        name="final_observations"
                        class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-2xl p-6 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[180px] text-[var(--text-main)] placeholder:text-[var(--text-muted)] font-medium"
                        placeholder="Describa el trabajo realizado, hallazgos técnicos, repuestos reemplazados y observaciones finales..."
                        <?= isReadOnly() ? 'readonly' : '' ?>><?= htmlspecialchars($ot['observations'] ?? '') ?></textarea>
                <?php endif; ?>
            </div>
            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- [FLUJO 7] SECCIÓN 7: Unificación de Mantenimiento        -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <?php if (!$isCompleted && ($ot['type'] ?? '') === 'Correctiva'): 
                $pendingPreventives = getPendingPreventivesForAsset($ot['asset_id']);
                if (!empty($pendingPreventives)):
            ?>
            <div class="bg-medical-surface p-10 rounded-3xl border border-medical-blue/30 shadow-xl relative overflow-hidden mb-6">
                <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                    <span class="material-symbols-outlined text-8xl text-medical-blue font-variation-fill">merge</span>
                </div>
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                        <span class="material-symbols-outlined font-variation-fill">inventory_2</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[var(--text-main)]">Unificación de Mantenimiento</h3>
                        <p class="text-xs text-medical-blue font-black uppercase tracking-widest mt-1">Optimización de Intervención</p>
                    </div>
                </div>

                <div class="p-4 bg-medical-blue/5 border border-medical-blue/20 rounded-2xl mb-6">
                    <p class="text-sm text-[var(--text-main)] font-medium leading-relaxed">
                        Existen mantenimientos preventivos pendientes para este equipo. Si ya realizó las tareas preventivas durante esta reparación, puede unificarlas para optimizar la disponibilidad del activo.
                    </p>
                </div>

                <div class="space-y-3">
                    <?php foreach ($pendingPreventives as $prev): ?>
                    <label class="flex items-center justify-between p-4 border border-[var(--border-color)] rounded-2xl hover:bg-medical-blue/5 transition-all cursor-pointer group">
                        <div class="flex items-center gap-4">
                            <input type="checkbox" name="unify_ot_ids[]" value="<?= $prev['id'] ?>" class="size-5 rounded border-medical-blue text-medical-blue focus:ring-medical-blue">
                            <div>
                                <p class="text-sm font-bold text-[var(--text-main)]">Checklist Preventivo #<?= $prev['id'] ?></p>
                                <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-wider">Apertura: <?= $prev['created_date'] ?></p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-[var(--text-muted)] opacity-0 group-hover:opacity-100 transition-all">link</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>

        <!-- SIDEBAR STICKY -->
        <div class="xl:col-span-4 2xl:col-span-3">
            <div class="xl:sticky xl:top-24 space-y-8">
                <!-- ACCIONES PRINCIPALES (Movidas al lateral) -->
                <div class="bg-medical-surface p-6 rounded-3xl border border-medical-blue/30 shadow-2xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-medical-blue/5 to-transparent opacity-50"></div>
                    <h3 class="text-xs font-black text-medical-blue uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">rocket_launch</span>
                        Gestión de Orden
                    </h3>

                    <div class="space-y-3 relative z-10">
                        <?php include __DIR__ . '/../includes/components/ot_action_buttons.php'; ?>
                    </div>
                </div>

                <div class="bg-medical-surface p-6 rounded-3xl border border-[var(--border-color)] shadow-xl overflow-hidden relative group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-medical-blue/5 rounded-full blur-2xl group-hover:bg-medical-blue/10 transition-all"></div>

                    <h3 class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-[0.2em] mb-6">
                        <?= $isCompleted ? 'Resumen de Registro' : 'Estado de Avance' ?>
                    </h3>

                    <!-- Mini Checklist Progress -->
                    <div class="space-y-6">
                        <?php
                        $totalItemsCount = count($qualitativeChecks) + count($savedCustomQuali);
                        $percentage = $isCompleted ? 100 : ($totalItemsCount > 0 ? floor(($completedItemsCount / $totalItemsCount) * 100) : 0);
                        ?>
                        <?php if ($isCompleted): ?>
                            <div class="flex items-center gap-6 py-2">
                                <div class="size-14 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20 shadow-lg shadow-emerald-500/5">
                                    <span class="material-symbols-outlined text-3xl font-variation-fill">verified</span>
                                </div>
                                <div>
                                    <p class="text-3xl font-black text-[var(--text-main)] italic tracking-tighter">100%</p>
                                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.15em] leading-none">REGISTRO COMPLETO</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-end justify-between">
                                <span class="text-4xl font-black text-[var(--text-main)] italic" x-text="Math.floor((checkedCount / totalItems) * 100) + '%'"></span>
                                <span class="text-xs font-bold text-[var(--text-muted)]"><span x-text="checkedCount"></span> / <span x-text="totalItems"></span> Ítems</span>
                            </div>
                            <div class="h-2.5 w-full bg-[var(--input-bg)] rounded-full overflow-hidden">
                                <div class="h-full bg-medical-blue transition-all duration-500" :style="'width: ' + ((checkedCount / totalItems) * 100) + '%'"></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-8 pt-6 border-t border-[var(--border-color)] space-y-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[var(--text-muted)]">Tipo de OT</span>
                            <span class="text-sm font-black text-medical-blue uppercase tracking-tight"><?= $ot['type'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[var(--text-muted)]">Prioridad</span>
                            <?php
                            $prioCol = match($ot['priority'] ?? 'Media') {
                                'Alta' => 'text-red-500 bg-red-500/10 border-red-500/20',
                                'Media' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
                                default => 'text-text-muted bg-slate-500/10 border-slate-500/20'
                            };
                            ?>
                            <span class="px-3 py-1 <?= $prioCol ?> text-[10px] font-black rounded-lg uppercase border tracking-widest"><?= $ot['priority'] ?? 'Media' ?></span>
                        </div>
                    </div>
                </div>

                <!-- Datos Técnicos del Activo (Report Mode - Ampliado) -->
                <div class="bg-medical-surface p-10 rounded-3xl border border-[var(--border-color)] shadow-xl space-y-8 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-[0.04] pointer-events-none transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined text-8xl text-medical-blue">medical_information</span>
                    </div>

                    <div class="flex items-center justify-between mb-2 border-b border-[var(--border-color)]/50 pb-6">
                        <h3 class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-[0.25em]">Ficha del Activo</h3>
                        <span class="material-symbols-outlined text-xl text-medical-blue font-variation-fill">id_card</span>
                    </div>

                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-widest px-1">Equipo / Modelo</label>
                            <p class="text-lg font-black text-[var(--text-main)] italic px-1 leading-tight uppercase tracking-tight">
                                <?= htmlspecialchars($asset['name'] ?? 'N/A') ?> 
                                <span class="text-medical-blue not-italic mx-2 opacity-30 text-base">/</span> 
                                <?= htmlspecialchars($asset['model'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-widest px-1">Marca</label>
                                <p class="text-sm font-bold text-[var(--text-main)] px-1"><?= htmlspecialchars($asset['brand'] ?? 'N/A') ?></p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-widest px-1">ID Inventario</label>
                                <p class="text-sm font-mono font-black text-medical-blue px-1 uppercase tracking-tighter"><?= $asset['inventory_id'] ?? 'N/A' ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-widest px-1">N° Serie</label>
                                <p class="text-sm font-bold text-[var(--text-main)] px-1 uppercase"><?= htmlspecialchars($asset['serial_number'] ?? 'N/A') ?></p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-amber-600 uppercase tracking-widest px-1 italic">Horómetro</label>
                                <p class="text-base font-black text-amber-600 px-1 italic">
                                    <?= (int)($asset['hours_used'] ?? 0) ?> 
                                    <span class="text-[10px] opacity-70 ml-1 font-black">HRS</span>
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-[var(--border-color)]/50">
                            <div class="flex items-center gap-5 bg-medical-blue/5 p-5 rounded-3xl border border-medical-blue/15 shadow-inner shadow-medical-blue/5">
                                <div class="size-12 rounded-2xl bg-medical-blue/10 text-medical-blue flex items-center justify-center border border-medical-blue/20">
                                    <span class="material-symbols-outlined text-2xl">location_on</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-medical-blue/70 uppercase tracking-widest leading-none">Ubicación de Operación</label>
                                    <p class="text-sm font-black text-medical-blue uppercase leading-tight tracking-wide">
                                        <?= htmlspecialchars($asset['location'] ?? 'Sin ubicación') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Sticky bar eliminada a petición del usuario para dejarlo al lado -->

    <!-- Modals -->
    <template x-if="showPostponeModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in zoom-in duration-300">
            <div @click.away="showPostponeModal = false" class="max-w-md w-full bg-medical-surface border border-[var(--border-color)] rounded-3xl p-8 shadow-2xl space-y-6">
                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 flex items-center justify-center">
                        <span class="material-symbols-outlined">pause_circle</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-[var(--text-main)]">Posponer OT</h2>
                        <p class="text-[10px] text-[var(--text-muted)] font-black uppercase tracking-widest">Retraso por Coordinación de Servicio</p>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">Motivo del Retraso</label>
                        <textarea x-model="stallReason" required placeholder="Ej: Equipo en uso por cirugía, No se encuentra personal de servicio, etc..." class="w-full bg-[var(--input-bg)] border border-[var(--border-color)] rounded-2xl p-4 text-sm font-medium text-[var(--text-main)] focus:border-medical-blue transition-all min-h-[100px] outline-none"></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button type="button" @click="showPostponeModal = false" class="flex-1 py-4 border border-[var(--border-color)] text-[var(--text-muted)] font-black uppercase tracking-widest rounded-2xl hover:bg-black/5 transition-all text-xs">Cerrar</button>
                        <button type="submit" @click="formAction = 'stall_ot'; $nextTick(() => { isSubmitting = true; })" form="executionForm" formnovalidate class="flex-1 py-4 bg-red-500 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-red-500/20 hover:bg-red-500/90 transition-all text-xs outline-none border-none cursor-pointer">Confirmar Pausa</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('executionState', () => ({
            showPostponeModal: false,
            isCompleting: false,
            isSubmitting: false,
            showValidationWarning: false,
            validationMissing: 0,
            formAction: '',
            stallReason: '',
            checkedCount: <?= $completedItemsCount ?>,
            totalItems: <?= $totalItems ?> + <?= count($savedCustomQuali) ?>,
            updateProgress() {
                // Selecciona todos los radios q_ que estén marcados
                const radios = document.querySelectorAll('input[name^="q_"]:checked');
                this.checkedCount = radios.length;
            },
            incrementTotal() {
                this.totalItems++;
            },
            decrementTotal() {
                this.totalItems--;
                this.updateProgress(); // Re-count just in case a checked one was removed
            },
            init() {
                console.log('BioCMMS Execution Engine Initialized');
                this.updateProgress();

                // Escuchar eventos de adición/eliminación
                window.addEventListener('item-added', () => this.incrementTotal());
                window.addEventListener('item-removed', () => this.decrementTotal());
            }
        }))
    });
</script>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>