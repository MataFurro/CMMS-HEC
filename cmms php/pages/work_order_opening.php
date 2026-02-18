<?php
// pages/work_order_opening.php

require_once __DIR__ . '/../includes/checklist_templates.php';

// Verificar permisos - Solo Ingeniero/Admin puede crear órdenes
if (!canModify()) {
    echo "<script>window.location.href='?page=work_orders';</script>";
    exit;
}

$templateOptions = listChecklistTemplates();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Doble verificación de permisos en backend
    if (!canModify()) {
        die('Acceso denegado. Usted no cuenta con permisos suficientes para esta operación.');
    }

    require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';

    // 1. Capturar datos del formulario
    $assetId = $_POST['asset_id'] ?? '';
    $type = $_POST['type'] ?? 'Correctivo';
    $priority = $_POST['priority'] ?? 'Media';
    $description = $_POST['description'] ?? '';
    $fromRequestId = $_POST['from_request_id'] ?? null;
    $checklistTemplate = $_POST['checklist_template'] ?? 'formato_general';

    // Mapear tipo a los valores del ENUM de la DB (MySQL usa Correctiva/Preventiva/Calibracion)
    $dbType = match ($type) {
        'Correctivo' => 'Correctiva',
        'Preventivo' => 'Preventiva',
        'Calibración' => 'Calibración',
        'Instalación' => 'Instalación',
        default => 'Correctiva'
    };

    $newId = createWorkOrder([
        'asset_id' => $assetId,
        'type' => $dbType,
        'priority' => $priority,
        'observations' => $description,
        'status' => 'Pendiente',
        'ms_request_id' => $fromRequestId,
        'checklist_template' => $checklistTemplate
    ]);

    // 2. Si viene de una solicitud de mensajería, marcarla como procesada
    if ($fromRequestId) {
        try {
            require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
            $db = \Backend\Core\DatabaseService::getInstance();
            $stmt = $db->prepare("UPDATE messenger_reports SET status = 'Procesado' WHERE id = :id");
            $stmt->execute([':id' => $fromRequestId]);
        } catch (Exception $e) {
            // Log error but don't stop execution
            \Backend\Core\LoggerService::error("No se pudo cerrar reporte SMS despues de crear OT", ["id" => $fromRequestId, "error" => $e->getMessage()]);
        }
    }

    // Éxito Real
    echo "<script>alert('Orden de Trabajo generada exitosamente en MySQL. ID: $newId'); window.location.href='?page=work_orders';</script>";
    exit;
}

// ── Pre-carga de datos desde SMS ──
$pre_asset_id = '';
$pre_description = '';
$from_request_id = null;

if (isset($_GET['from_request'])) {
    $from_request_id = (int)$_GET['from_request'];
    try {
        require_once __DIR__ . '/../Backend/Core/DatabaseService.php';
        $db = \Backend\Core\DatabaseService::getInstance();
        $stmt = $db->prepare("SELECT * FROM messenger_reports WHERE id = :id");
        $stmt->execute([':id' => $from_request_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($report) {
            $pre_asset_id = $report['asset_id'];
            $pre_description = "REPORTE DESDE SMS:\n" . $report['texto'];
        }
    } catch (Exception $e) {
        $error_preload = "Error al pre-cargar datos: " . $e->getMessage();
    }
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

    <form method="POST"
        x-data="{ otType: 'Correctivo' }"
        class="card-glass p-8 space-y-8 shadow-2xl relative overflow-hidden">
        <!-- Hidden field for SMS association -->
        <input type="hidden" name="from_request_id" value="<?= $from_request_id ?>">

        <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 blur-3xl rounded-full -mr-16 -mt-16"></div>

        <div class="grid grid-cols-1 gap-8 relative z-10">
            <!-- Asset Selection -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Activo Afectado</h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">ID del Activo (ID o Serie)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">qr_code</span>
                        <input name="asset_id" required value="<?= htmlspecialchars($pre_asset_id) ?>" placeholder="Ej: 500000012105..." class="w-full bg-slate-900 border border-slate-700/50 rounded-xl pl-12 pr-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white" />
                    </div>
                    <p class="text-[10px] text-slate-500 italic">Conexión Real: El ID vinculará la OT con el inventario central.</p>
                </div>
            </div>

            <!-- OT Details -->
            <div class="space-y-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Detalles de la Solicitud</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tipo de Orden</label>
                        <select required name="type" x-model="otType" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
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

                <!-- Selector de Plantilla de Checklist (Siempre Visible) -->
                <div x-transition class="space-y-2 pt-4 border-t border-slate-700/30">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-medical-blue text-sm">fact_check</span>
                        Plantilla de Lista de Chequeo
                    </label>
                    <select name="checklist_template" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none">
                        <option value="">-- Seleccionar Plantilla --</option>
                        <?php foreach (listChecklistTemplates() as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-slate-500 italic flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">info</span>
                        La plantilla define los ítems de inspección y mediciones que el técnico debe completar.
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción del Problema / Solicitud</label>
                    <textarea required name="description" class="w-full bg-slate-900 border border-slate-700/50 rounded-xl p-4 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[120px] text-white placeholder:text-slate-600" placeholder="Describa la falla, código de error o motivo del mantenimiento..."><?= htmlspecialchars($pre_description) ?></textarea>
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
                            <?php
                            require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
                            foreach (getActiveTechnicians() as $tech):
                            ?>
                                <option value="<?= htmlspecialchars($tech['name']) ?>"><?= htmlspecialchars($tech['name']) ?></option>
                            <?php endforeach; ?>
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