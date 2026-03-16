<?php
// pages/bulk_management.php

require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
require_once __DIR__ . '/../Backend/Providers/BulkProvider.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

// ── Guard ──────────────────────────────────────────────────────────────
if (!isChiefEngineer()) {
    echo "<script>window.location.href='?page=dashboard';</script>";
    exit;
}

// ── AJAX / POST handler ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken()) {
        echo json_encode(['success' => 0, 'errors' => ['Token de seguridad inválido o caducado. Recarga la página.']]);
        exit;
    }

    $ids    = array_map('intval', $_POST['selected_ids'] ?? []);
    $action = $_POST['bulk_action'] ?? '';
    $result = ['success' => 0, 'errors' => []];

    switch ($action) {
        case 'update_field':
            $result = bulkUpdateField($ids, $_POST['field'] ?? '', $_POST['value'] ?? '');
            break;
        case 'create_maintenance':
            $repeat = max(1, min(12, (int)($_POST['repeat'] ?? 1)));
            $success = 0;
            $errors  = [];
            for ($i = 0; $i < $repeat; $i++) {
                $r = bulkCreateMaintenanceOrders($ids, [
                    'description'    => $_POST['description']    ?? 'Mantenimiento preventivo masivo.',
                    'technician_id'  => !empty($_POST['technician_id'])  ? (int)$_POST['technician_id']  : null,
                    'scheduled_date' => $_POST['scheduled_date'] ?? date('Y-m-d'),
                    'priority'       => $_POST['priority']        ?? 'MEDIUM',
                ]);
                $success += $r['success'];
                $errors   = array_merge($errors, $r['errors']);
            }
            $result = ['success' => $success, 'errors' => $errors];
            break;
        case 'reassign_technician':
            $result = bulkReassignTechnician($ids, !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null);
            break;
        case 'delete':
            $result = bulkDeleteAssets($ids, $_POST['auth_password'] ?? '', $_POST['retirement_reason'] ?? '');
            break;
        case 'undo':
            // Deshacer: detecta tipo de undo guardado en sesión
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!empty($_SESSION['bulk_undo_delete'])) {
                $result = bulkRestoreAssets();
            } elseif (!empty($_SESSION['bulk_undo'])) {
                $result = bulkUndo();
            } elseif (!empty($_SESSION['bulk_undo_orders'])) {
                $result = bulkUndoOrders();
            } else {
                $result = ['success' => 0, 'errors' => ['No hay operación reciente para deshacer.']];
            }
            break;
    }
    echo json_encode($result);
    exit;
}

// ── Datos página ────────────────────────────────────────────────────────
$search      = trim($_GET['search']      ?? '');
$critFilter  = $_GET['criticality']      ?? '';
$statFilter  = $_GET['status']           ?? '';
$claseFilter = $_GET['clase']            ?? '';
$page_num    = max(1, (int)($_GET['p']   ?? 1));
$limit       = 60;
$offset      = ($page_num - 1) * $limit;

$assets      = getBulkAssets($search, $critFilter, $statFilter, $claseFilter, $limit, $offset);
$totalAssets = countBulkAssets($search, $critFilter, $statFilter, $claseFilter);
$totalPages  = max(1, ceil($totalAssets / $limit));
$technicians = getActiveTechnicians();

// KPIs globales
$kpiAll      = countBulkAssets('', '', '', '');
$kpiCrit     = countBulkAssets('', 'CRITICAL', '', '');
$kpiRel      = countBulkAssets('', 'RELEVANT', '', '');
$kpiLow      = countBulkAssets('', 'LOW', '', '');

// Clases disponibles (riesgo_ge) para filtro
$clasesDisponibles = [];
try {
    $dbTmp = \Backend\Core\DatabaseService::getInstance();
    $stmtC = $dbTmp->query("SELECT DISTINCT riesgo_ge FROM assets WHERE riesgo_ge IS NOT NULL AND riesgo_ge<>'' ORDER BY riesgo_ge");
    $clasesDisponibles = $stmtC->fetchAll(\PDO::FETCH_COLUMN);
} catch (\Exception $e) {
    $clasesDisponibles = [];
}

$critMap = [
    'CRITICAL' => ['label' => 'EMC · Crítico',   'dot' => 'bg-red-500',    'pill' => 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/30'],
    'RELEVANT' => ['label' => 'EMR · Relevante',  'dot' => 'bg-amber-500',  'pill' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30'],
    'LOW'      => ['label' => 'No Aplica',         'dot' => 'bg-slate-500',  'pill' => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/30'],
];

$statMap = [
    'OPERATIVE'           => ['label' => 'Operativo',          'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30'],
    'MAINTENANCE'         => ['label' => 'En Mantención',      'dot' => 'bg-amber-500',   'pill' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30'],
    'NO_OPERATIVE'        => ['label' => 'Fuera de Servicio',  'dot' => 'bg-red-500',     'pill' => 'bg-red-500/10 text-red-700 dark:text-red-400 border border-red-500/30'],
    'OPERATIVE_WITH_OBS'  => ['label' => 'Operativo con Obs.', 'dot' => 'bg-yellow-500',  'pill' => 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30'],
    'PENDING_RETIREMENT'  => ['label' => 'Baja en Trámite', 'dot' => 'bg-orange-500', 'pill' => 'bg-orange-500/20 text-orange-400 border border-orange-500/30 font-black'],
];
?>

<!-- ═══════════════════════════════ STYLES ════════════════════════════════ -->
<style>
    /* ── Full-screen admin override ─────────────────────────────────────── */
    .bm-page {
        --gold: #f59e0b;
        --gold-low: rgba(245, 158, 11, .08);
    }

    /* Header banner */
    .bm-banner {
        background: linear-gradient(120deg, var(--medical-blue) 0%, var(--panel-dark) 100%);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
    }

    .dark .bm-banner {
        background: linear-gradient(120deg, #05060e 0%, #0b1220 55%, #0d1a2e 100%);
        border-color: rgba(245, 158, 11, .12);
    }

    .bm-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 50% 100% at 100% 50%, rgba(245, 158, 11, .06) 0%, transparent 70%);
        pointer-events: none;
    }

    .bm-banner-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(245, 158, 11, .04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(245, 158, 11, .04) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }

    /* Back button */
    .bm-back {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem 1rem;
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 999px;
        color: var(--text-muted);
        font-size: .7rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .12em;
        transition: all .15s;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .bm-back:hover {
        background: var(--medical-dark);
        color: var(--text-main);
        border-color: var(--medical-blue);
    }

    .dark .bm-back {
        background: rgba(255, 255, 255, .04);
        border-color: var(--border-color);
        color: var(--text-muted);
        opacity: 0.8;
    }

    .dark .bm-back:hover {
        background: rgba(255, 255, 255, .08);
        color: var(--text-main);
        border-color: var(--medical-blue);
        opacity: 1;
    }

    /* KPI strip */
    .bm-kpi {
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.125rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: .25rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .dark .bm-kpi {
        background: rgba(255, 255, 255, .025);
        border-color: rgba(255, 255, 255, .07);
        box-shadow: none;
    }

    .bm-kpi-val {
        font-size: 2rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -.02em;
    }

    /* Filtros */
    .bm-filters {
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .dark .bm-filters {
        background: #09101e;
        border-color: rgba(255, 255, 255, .07);
        box-shadow: none;
    }

    .bm-input {
        height: 2.5rem;
        padding: .5rem .875rem;
        background: var(--medical-dark);
        border: 1px solid var(--border-color);
        border-radius: .75rem;
        color: var(--text-main);
        font-size: .8rem;
        font-weight: 600;
        outline: none;
        width: 100%;
        transition: border-color .15s;
    }

    .dark .bm-input {
        background: rgba(255, 255, 255, .04);
        border-color: rgba(255, 255, 255, .1);
    }

    .bm-input:focus {
        border-color: var(--medical-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .bm-input::placeholder {
        color: var(--text-muted);
        opacity: 0.8 !important;
    }

    .bm-select {
        appearance: none;
        cursor: pointer;
    }

    .dark .bm-select option,
    .dark .dr-input option {
        background-color: #0f172a;
        color: #f1f5f9;
    }

    /* Acciones quick */
    .bm-act {
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all .3s cubic-bezier(.4, 0, .2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .bm-act:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .15);
        border-color: rgba(245, 158, 11, .4);
    }

    .dark .bm-act {
        background: rgba(255, 255, 255, .025);
        border-color: rgba(255, 255, 255, .07);
        box-shadow: none;
    }

    .dark .bm-act:hover {
        background: rgba(255, 255, 255, .05);
        box-shadow: 0 14px 30px rgba(0, 0, 0, .3);
    }

    .bm-act-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Tabla */
    .bm-table-wrap {
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1);
    }

    .dark .bm-table-wrap {
        background: #070c18;
        border-color: rgba(255, 255, 255, .07);
        box-shadow: none;
    }

    .bm-table thead th {
        background: var(--medical-dark);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-muted);
        font-size: .62rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .14em;
        padding: .875rem 1rem;
    }

    .dark .bm-table thead th {
        background: rgba(245, 158, 11, .04);
        border-color: rgba(245, 158, 11, .1);
        color: rgba(245, 158, 11, .6);
    }

    .bm-row {
        border-bottom: 1px solid var(--border-color);
        transition: background .1s;
    }

    .dark .bm-row {
        border-color: rgba(255, 255, 255, .04);
    }

    .bm-row:hover {
        background: var(--medical-dark);
    }

    .dark .bm-row:hover {
        background: rgba(245, 158, 11, .03);
    }

    .bm-row.sel {
        background: rgba(59, 130, 246, 0.12) !important;
        box-shadow: inset 4px 0 0 0 #3b82f6;
    }

    .dark .bm-row.sel {
        background: rgba(245, 158, 11, 0.22) !important;
        /* High contrast amber tint */
        box-shadow: inset 8px 0 0 0 #f59e0b;
        /* Strong selection indicator */
    }

    .bm-row.sel td {
        border-bottom-color: rgba(245, 158, 11, 0.3) !important;
    }

    .bm-row.sel td {
        color: var(--text-main) !important;
    }

    .dark .bm-row.sel td {
        color: #fff !important;
        /* Crystal clear contrast */
        text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .dark .bm-row.sel p.text-text-muted {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    /* Custom Checkbox Logic */
    .asset-checkbox,
    #selectAll {
        appearance: none;
        -webkit-appearance: none;
        width: 1.15rem;
        height: 1.15rem;
        border: 2px solid #64748b;
        border-radius: 0.35rem;
        background: transparent;
        cursor: pointer;
        display: inline-grid;
        place-content: center;
        transition: all 0.2s;
        vertical-align: middle;
        margin: 0;
    }

    .dark .asset-checkbox,
    .dark #selectAll {
        border-color: rgba(255, 255, 255, 0.2);
    }

    .asset-checkbox::before,
    #selectAll::before {
        content: "";
        width: 0.65rem;
        height: 0.65rem;
        clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        transform: scale(0);
        transform-origin: bottom left;
        transition: 120ms transform ease-in-out;
        box-shadow: inset 1rem 1rem #000;
        background-color: CanvasText;
    }

    .asset-checkbox:checked,
    #selectAll:checked {
        background: #f59e0b !important;
        border-color: #f59e0b !important;
        box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
    }

    .asset-checkbox:checked::before,
    #selectAll:checked::before {
        transform: scale(1);
        background-color: #000 !important;
        /* Pure black check on amber background */
        box-shadow: none;
    }

    .dark .asset-checkbox:checked::before,
    .dark #selectAll:checked::before {
        background-color: #000 !important;
        /* Keep it black on amber for max contrast */
    }

    .asset-checkbox:hover,
    #selectAll:hover {
        border-color: #f59e0b;
    }

    .bm-row td {
        padding: .75rem 1rem;
        color: var(--text-muted);
        font-size: .8rem;
        vertical-align: middle;
    }

    .dark .bm-row td {
        color: var(--text-muted);
    }

    .bm-row:last-child {
        border-bottom: none;
    }

    /* Pill floating selection bar */
    .sel-pill {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: var(--medical-surface);
        border: 1px solid var(--medical-blue);
        border-radius: 999px;
        padding: .625rem 1rem;
        display: flex;
        align-items: center;
        gap: .625rem;
        z-index: 60;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        opacity: 0;
        pointer-events: none;
        transition: all .25s cubic-bezier(.4, 0, .2, 1);
        white-space: nowrap;
    }

    .dark .sel-pill {
        background: #09101e;
        border-color: rgba(245, 158, 11, .3);
        box-shadow: 0 0 50px rgba(245, 158, 11, .06), 0 20px 50px rgba(0, 0, 0, .5);
    }

    .sel-pill.visible {
        opacity: 1;
        pointer-events: all;
        transform: translateX(-50%) translateY(0);
    }

    .sel-btn {
        display: flex;
        align-items: center;
        gap: .375rem;
        padding: .4rem .875rem;
        border-radius: 999px;
        font-size: .65rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        border: 1px solid transparent;
        cursor: pointer;
        transition: filter .15s;
    }

    .sel-btn:hover {
        filter: brightness(1.25);
    }

    /* Drawer */
    .bm-drawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100%;
        width: min(520px, 100%);
        background: var(--medical-surface);
        border-left: 1px solid var(--border-color);
        z-index: 70;
        transform: translateX(100%);
        transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        display: flex;
        flex-direction: column;
        box-shadow: -20px 0 60px rgba(0, 0, 0, 0.1);
    }

    .dark .bm-drawer {
        background: #09101e;
        border-color: rgba(245, 158, 11, .15);
        box-shadow: -20px 0 60px rgba(0, 0, 0, 0.5);
    }

    .bm-drawer.open {
        transform: translateX(0);
    }

    .dr-input {
        width: 100%;
        padding: .75rem 1rem;
        background: var(--medical-dark);
        border: 1px solid var(--border-color);
        border-radius: .75rem;
        color: var(--text-main);
        font-size: .875rem;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }

    .dark .dr-input {
        background: rgba(255, 255, 255, .04);
        border-color: rgba(255, 255, 255, .1);
    }

    .dr-input:focus {
        border-color: var(--medical-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .dr-label {
        display: block;
        font-size: .62rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--text-muted);
        margin-bottom: .5rem;
        opacity: 0.6;
    }

    .dark .dr-label {
        color: rgba(255, 255, 255, .3);
        opacity: 1;
    }

    /* Toast */
    .bm-toast {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        min-width: 320px;
        background: var(--medical-surface);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        z-index: 80;
        transform: translateY(-16px);
        opacity: 0;
        transition: all .3s cubic-bezier(.4, 0, .2, 1);
        pointer-events: none;
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .dark .bm-toast {
        background: #09101e;
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }

    .bm-toast.show {
        transform: translateY(0);
        opacity: 1;
        pointer-events: all;
    }

    /* Confirm modal */
    .bm-confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .7);
        backdrop-filter: blur(4px);
        z-index: 90;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s;
    }

    .bm-confirm-overlay.open {
        opacity: 1;
        pointer-events: all;
    }

    .bm-confirm-box {
        background: var(--medical-surface);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.75rem;
        width: min(420px, calc(100vw - 2rem));
        box-shadow: 0 30px 80px rgba(0, 0, 0, .2);
        transform: scale(.95) translateY(8px);
        transition: transform .2s cubic-bezier(.4, 0, .2, 1);
    }

    .dark .bm-confirm-box {
        background: #0d1424;
        border-color: rgba(245, 158, 11, .25);
        box-shadow: 0 30px 80px rgba(0, 0, 0, .6), 0 0 0 1px rgba(245, 158, 11, .08) inset;
    }

    .bm-confirm-overlay.open .bm-confirm-box {
        transform: scale(1) translateY(0);
    }
</style>

<div class="bm-page space-y-6 pb-28">

    <!-- ════════════════════ BANNER ════════════════════════════════════════ -->
    <div class="bm-banner p-8 md:p-10">
        <div class="bm-banner-grid"></div>

        <div class="relative z-10">
            <!-- back + badge -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <a href="?page=dashboard" class="bm-back">
                    <span class="material-symbols-outlined text-base">arrow_back</span>Volver al sistema
                </a>
                <span class="h-7 px-3 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Acceso exclusivo — Jefatura Biomédica
                </span>
                <span class="hidden md:flex items-center gap-1.5 text-text-muted text-[10px] font-bold uppercase tracking-wider opacity-60">
                    <span class="material-symbols-outlined text-sm">lock</span>
                    CHIEF_ENGINEER ONLY
                </span>
            </div>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
                <!-- Title -->
                <div>
                    <h1 class="text-3xl md:text-5xl font-extrabold text-text-main dark:text-white tracking-tight leading-none">
                        Consola de<br>
                        <span class="text-amber-600 dark:text-amber-400">Gestión Masiva</span>
                    </h1>
                    <p class="text-text-muted dark:text-slate-400 text-sm mt-4 max-w-lg leading-relaxed">
                        Módulo de control total del inventario biomédico. Edita campos, programa mantenciones,
                        ajusta frecuencias y reasigna técnicos a lotes completos de activos — sin restricciones.
                    </p>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 shrink-0">
                    <?php
                    $kpiList = [
                        ['val' => $kpiAll,  'label' => 'Total activos', 'color' => 'text-blue-400'],
                        ['val' => $kpiCrit, 'label' => 'EMC Críticos',  'color' => 'text-red-400'],
                        ['val' => $kpiRel,  'label' => 'EMR Relevantes', 'color' => 'text-amber-400'],
                        ['val' => $kpiLow,  'label' => 'No Aplica',     'color' => 'text-slate-400'],
                    ];
                    foreach ($kpiList as $k): ?>
                        <div class="bm-kpi">
                            <span class="bm-kpi-val <?= $k['color'] ?>"><?= number_format($k['val']) ?></span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-text-muted opacity-60"><?= $k['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ QUICK ACTIONS ════════════════════════════════ -->
    <div>
        <p class="text-[9px] font-black uppercase tracking-[.25em] text-text-muted opacity-60 mb-3 text-center md:text-left">Acciones maestras — selecciona equipos para activar</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <button class="bm-act group" onclick="triggerAction('maintenance')">
                <div class="bm-act-icon bg-blue-500/10 border border-blue-500/20 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300">
                    <span class="material-symbols-outlined text-xl">build_circle</span>
                </div>
                <div class="text-left min-w-0">
                    <p class="text-text-main text-[11px] font-black uppercase tracking-tight truncate">Mantención Masiva</p>
                    <p class="text-text-muted text-[10px] opacity-60">Generar OTs inmediatas</p>
                </div>
            </button>
            <button class="bm-act group" onclick="triggerAction('edit_field')">
                <div class="bm-act-icon bg-amber-500/10 border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-black transition-all duration-300">
                    <span class="material-symbols-outlined text-xl">edit_square</span>
                </div>
                <div class="text-left min-w-0">
                    <p class="text-text-main text-[11px] font-black uppercase tracking-tight truncate">Edición en Lote</p>
                    <p class="text-text-muted text-[10px] opacity-60">Frecuencia, estados y más</p>
                </div>
            </button>
            <button class="bm-act group" onclick="triggerAction('reassign')">
                <div class="bm-act-icon bg-purple-500/10 border border-purple-500/20 group-hover:bg-purple-500 group-hover:text-white transition-all duration-300">
                    <span class="material-symbols-outlined text-xl">supervised_user_circle</span>
                </div>
                <div class="text-left min-w-0">
                    <p class="text-text-main text-[11px] font-black uppercase tracking-tight truncate">Reasignar Técnico</p>
                    <p class="text-text-muted text-[10px] opacity-60">Transferir OTs activas</p>
                </div>
            </button>
            <button class="bm-act group" onclick="triggerAction('delete')">
                <div class="bm-act-icon bg-red-500/10 border border-red-500/20 group-hover:bg-red-500 group-hover:text-white transition-all duration-300">
                    <span class="material-symbols-outlined text-xl">delete_sweep</span>
                </div>
                <div class="text-left min-w-0">
                    <p class="text-text-main text-[11px] font-black uppercase tracking-tight truncate">Retiro Masivo</p>
                    <p class="text-text-muted text-[10px] opacity-60">Baja del inventario</p>
                </div>
            </button>
        </div>
    </div>

    <!-- ════════════════════ FILTROS ══════════════════════════════════════ -->
    <div class="bm-filters">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-amber-600 dark:text-amber-500 text-base">filter_alt</span>
            <p class="text-xs font-black uppercase tracking-widest text-amber-600 dark:text-amber-500">Filtros de búsqueda</p>
        </div>
        <form method="GET" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <input type="hidden" name="page" value="bulk_management">

            <!-- Búsqueda -->
            <div class="relative col-span-2 md:col-span-2">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-base opacity-50">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Nombre, ID inventario, ubicación…" class="bm-input pl-9">
            </div>

            <!-- Clase (riesgo_ge) -->
            <select name="clase" class="bm-input bm-select">
                <option value="">Todas las Clases</option>
                <?php foreach ($clasesDisponibles as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $claseFilter === $c ? 'selected' : '' ?>>📁 <?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Criticidad -->
            <select name="criticality" class="bm-input bm-select">
                <option value="">Toda Criticidad</option>
                <option value="CRITICAL" <?= $critFilter === 'CRITICAL' ? 'selected' : '' ?>>EMC — Crítico</option>
                <option value="RELEVANT" <?= $critFilter === 'RELEVANT' ? 'selected' : '' ?>>EMR — Relevante</option>
                <option value="LOW" <?= $critFilter === 'LOW'     ? 'selected' : '' ?>>No Aplica</option>
            </select>

            <!-- Estado -->
            <select name="status" class="bm-input bm-select">
                <option value="">Todo Estado</option>
                <option value="OPERATIVE" <?= $statFilter === 'OPERATIVE' ? 'selected' : '' ?>>Operativo</option>
                <option value="MAINTENANCE" <?= $statFilter === 'MAINTENANCE' ? 'selected' : '' ?>>En Mantención</option>
                <option value="NO_OPERATIVE" <?= $statFilter === 'NO_OPERATIVE' ? 'selected' : '' ?>>Fuera de Servicio</option>
                <option value="OPERATIVE_WITH_OBS" <?= $statFilter === 'OPERATIVE_WITH_OBS' ? 'selected' : '' ?>>Operativo con Obs.</option>
            </select>

            <!-- Botones -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 h-10 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-amber-500/10">
                    Buscar
                </button>
                <?php if ($search || $critFilter || $statFilter || $claseFilter): ?>
                    <a href="?page=bulk_management" class="h-10 w-10 flex items-center justify-center rounded-xl bg-medical-dark border border-border-dark text-text-muted hover:text-text-main hover:bg-medical-surface transition-all" title="Limpiar filtros">
                        <span class="material-symbols-outlined text-base">close</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($search || $critFilter || $statFilter || $claseFilter): ?>
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-border-dark dark:border-white/5 opacity-80">
                <p class="text-[9px] font-black uppercase tracking-widest text-text-muted self-center">Filtros activos:</p>
                <?php if ($search):     ?><span class="px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-400 text-[9px] font-bold">🔍 "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
                <?php if ($claseFilter): ?><span class="px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-400 text-[9px] font-bold">📁 <?= htmlspecialchars($claseFilter) ?></span><?php endif; ?>
                <?php if ($critFilter && isset($critMap[$critFilter])): ?><span class="px-2.5 py-1 rounded-full text-[9px] font-bold <?= $critMap[$critFilter]['pill'] ?>">⚠ <?= $critMap[$critFilter]['label'] ?></span><?php endif; ?>
                <?php if ($statFilter && isset($statMap[$statFilter])): ?><span class="px-2.5 py-1 rounded-full text-[9px] font-bold <?= $statMap[$statFilter]['pill'] ?>">● <?= $statMap[$statFilter]['label'] ?></span><?php endif; ?>
                <span class="ml-auto text-[9px] font-black text-text-muted dark:text-slate-500"><?= number_format($totalAssets) ?> resultado<?= $totalAssets !== 1 ? 's' : '' ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ════════════════════ TABLA ════════════════════════════════════════ -->
    <div class="bm-table-wrap">
        <!-- Cabecera tabla -->
        <div class="flex items-center gap-4 px-5 py-3.5 border-b border-border-dark opacity-60">
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" id="selectAll" class="w-4 h-4 rounded accent-amber-500">
                <span class="text-[10px] font-black uppercase tracking-widest text-text-muted">Todos</span>
            </label>
            <div id="selBadgeWrap" class="hidden flex items-center bg-amber-500/10 border border-amber-500/20 rounded-full overflow-hidden">
                <span id="selBadge" class="px-2.5 py-1 text-amber-500 dark:text-amber-400 text-[10px] font-black pointer-events-none">0 seleccionados</span>
                <button onclick="clearSel()" class="px-2 py-1 text-amber-500 hover:text-white hover:bg-red-500 transition-colors flex items-center justify-center border-l border-amber-500/20" title="Desmarcar todos">
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </button>
            </div>
            <span class="ml-auto text-[10px] text-text-muted font-bold">
                Página <?= $page_num ?> · <?= count($assets) ?>/<?= number_format($totalAssets) ?> equipos
            </span>
        </div>

        <table class="w-full bm-table">
            <thead>
                <tr>
                    <th class="w-10 text-center">✓</th>
                    <th>ID / Inventario</th>
                    <th>Equipo</th>
                    <th>Clase</th>
                    <th class="text-center">Criticidad</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Plan</th>
                </tr>
            </thead>
            <tbody id="bulkBody">
                <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="7" class="py-20 text-center">
                            <span class="material-symbols-outlined text-5xl text-text-muted opacity-20 block mb-3">search_off</span>
                            <p class="text-text-muted text-xs font-bold uppercase tracking-widest">Sin resultados — ajusta los filtros</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assets as $a):
                        $crit = $critMap[$a['criticality']] ?? ['label' => ($a['criticality'] ?? '-'), 'dot' => 'bg-slate-500', 'pill' => 'bg-slate-600/30 text-slate-400'];
                        $stat = $statMap[$a['status']]      ?? ['label' => ($a['status'] ?? '-'), 'pill' => 'bg-slate-600/30 text-slate-400'];
                    ?>
                        <tr class="bm-row" data-id="<?= $a['id'] ?>">
                            <td class="text-center">
                                <input type="checkbox" class="asset-checkbox w-4 h-4 rounded accent-amber-500 cursor-pointer" value="<?= $a['id'] ?>">
                            </td>
                            <td>
                                <span class="text-[11px] font-black text-amber-400/80"><?= htmlspecialchars($a['inventory_id'] ?? '#' . $a['id']) ?></span>
                            </td>
                            <td>
                                <p class="text-[12px] font-bold text-text-main truncate max-w-[200px]"><?= htmlspecialchars($a['name']) ?></p>
                                <p class="text-[10px] text-text-muted opacity-70 truncate"><?= htmlspecialchars(trim(($a['brand'] ?? '') . ' ' . ($a['model'] ?? ''))) ?></p>
                            </td>
                            <td>
                                <span class="text-[10px] font-bold text-text-muted"><?= htmlspecialchars($a['riesgo_ge'] ?? '—') ?></span>
                                <?php if ($a['location']): ?><p class="text-[10px] text-text-muted opacity-60 truncate max-w-[120px]"><?= htmlspecialchars($a['location']) ?></p><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-black uppercase <?= $crit['pill'] ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $crit['dot'] ?>"></span>
                                    <?= $crit['label'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                $sKey = !empty($a['status']) ? $a['status'] : 'OPERATIVE'; // Fallback consistent with AssetEntity
                                $stat = $statMap[$sKey] ?? ['label' => $sKey, 'dot' => 'bg-slate-500', 'pill' => 'bg-slate-600/10 text-slate-400 border border-slate-500/20'];
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider <?= $stat['pill'] ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $stat['dot'] ?>"></span>
                                    <?= highlight($stat['label'], $search) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($a['under_maintenance_plan']): ?>
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-slate-800 text-lg">radio_button_unchecked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 px-5 py-4 border-t border-border-dark opacity-60">
                <?php
                $qs = http_build_query(['page' => 'bulk_management', 'search' => $search, 'criticality' => $critFilter, 'status' => $statFilter, 'clase' => $claseFilter]);
                for ($i = max(1, $page_num - 4); $i <= min($totalPages, $page_num + 4); $i++): ?>
                    <a href="?<?= $qs ?>&p=<?= $i ?>"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-black transition-all
                <?= $i === $page_num ? 'bg-amber-500 text-black' : 'text-text-muted hover:text-text-main hover:bg-medical-dark' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════════ SELECTION PILL ═══════════════════════════════════════ -->
<div id="selPill" class="sel-pill">
    <div class="flex items-center gap-1.5 pr-3 border-r border-border-dark opacity-40">
        <span class="material-symbols-outlined text-amber-500 text-base">checklist</span>
        <b id="selCount" class="text-amber-500 font-black text-sm">0</b>
        <span class="text-text-muted text-[11px]">equipos</span>
    </div>
    <button class="sel-btn bg-blue-500/20 text-blue-400 border-blue-500/25" onclick="openDrawer('maintenance')">
        <span class="material-symbols-outlined text-sm">build_circle</span>Mantención
    </button>
    <button class="sel-btn bg-amber-500/20 text-amber-400 border-amber-500/25" onclick="openDrawer('edit_field')">
        <span class="material-symbols-outlined text-sm">edit_square</span>Editar
    </button>
    <button class="sel-btn bg-purple-500/20 text-purple-400 border-purple-500/25" onclick="openDrawer('reassign')">
        <span class="material-symbols-outlined text-sm">supervised_user_circle</span>Técnico
    </button>
    <button class="sel-btn bg-rose-500/20 text-rose-400 border-rose-500/25" onclick="openDrawer('delete')" title="Eliminar seleccionados">
        <span class="material-symbols-outlined text-sm">delete_sweep</span>Eliminar
    </button>
    <button class="sel-btn bg-white/5 dark:bg-white/5 text-text-muted border-border-dark opacity-70 hover:opacity-100" onclick="clearSel()" title="Deseleccionar todo">
        <span class="material-symbols-outlined text-sm">deselect</span>
    </button>
</div>

<!-- ════════════════════ DRAWER ═══════════════════════════════════════════════ -->
<div id="drawerBG" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden" onclick="closeDrawer()"></div>
<div id="bmDrawer" class="bm-drawer">
    <div class="flex items-center gap-3 px-6 py-5 border-b border-border-dark opacity-60">
        <div id="drIconWrap" class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
            <span id="drIcon" class="material-symbols-outlined text-xl">tune</span>
        </div>
        <div class="flex-1 min-w-0">
            <h3 id="drTitle" class="text-text-main font-black text-base leading-tight">Acción</h3>
            <p id="drSub" class="text-text-muted text-[11px] font-bold mt-0.5">— equipos seleccionados</p>
        </div>
        <button onclick="closeDrawer()" class="text-text-muted hover:text-text-main transition-colors p-1">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto p-6" id="drBody"></div>
    <div class="p-6 border-t border-border-dark opacity-60 flex gap-3">
        <button onclick="closeDrawer()" class="flex-1 h-12 rounded-xl border border-border-dark text-text-muted text-xs font-black uppercase tracking-widest hover:bg-medical-dark transition-all">Cancelar</button>
        <button id="drConfirm" onclick="askConfirm()"
            class="flex-1 h-12 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-lg shadow-amber-500/15">
            <span class="material-symbols-outlined text-base">bolt</span>Aplicar ahora
        </button>
    </div>
</div>

<!-- ═══════════════ CONFIRM MODAL ══════════════════════════════════════════════ -->
<div id="bmConfirmModal" class="bm-confirm-overlay" onclick="cancelConfirm()" aria-hidden="true">
    <div class="bm-confirm-box" onclick="event.stopPropagation()" role="dialog" aria-modal="true">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center" style="flex-shrink:0">
                <span class="material-symbols-outlined text-amber-400 text-xl">warning</span>
            </div>
            <div>
                <p class="text-text-main font-black text-sm">¿Confirmar operación masiva?</p>
                <p id="cfmSub" class="text-text-muted opacity-60 text-xs mt-0.5"></p>
            </div>
        </div>
        <div id="cfmDetail" class="p-4 rounded-xl bg-medical-dark border border-border-dark text-text-muted text-xs font-mono mb-6 leading-relaxed"></div>
        <div class="flex gap-3 mt-8">
            <button onclick="cancelConfirm()" class="flex-1 h-10 rounded-xl border border-border-dark text-text-muted text-xs font-black uppercase tracking-widest hover:bg-medical-surface transition-all">No, cancelar</button>
            <button id="cfmApplyBtn" onclick="doConfirmedAction()" class="flex-1 h-10 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">check</span>Sí, aplicar
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════ TOAST ════════════════════════════════════════════════ -->
<div id="bmToast" class="bm-toast">
    <div class="flex items-start gap-3 mb-2">
        <span id="toastIco" class="material-symbols-outlined text-2xl" style="flex-shrink:0;margin-top:.125rem">check_circle</span>
        <div class="flex-1 min-w-0">
            <p id="toastT" class="text-text-main font-black text-sm"></p>
            <p id="toastS" class="text-text-muted opacity-60 text-xs mt-0.5"></p>
        </div>
    </div>
    <div id="toastUndoWrap" class="hidden pt-2 border-t border-border-dark opacity-40">
        <button onclick="doUndo()" class="w-full h-8 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-400 text-[10px] font-black uppercase tracking-widest hover:bg-amber-500/25 transition-all flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-sm">undo</span>Deshacer
        </button>
    </div>
</div>

<!-- ════════════════════ SCRIPT ═══════════════════════════════════════════════ -->
<script>
    // ── Persistence & Initialization ─────────────────────────────────────
    const STORAGE_KEY = 'cmms_bulk_selection';
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    let sel = new Set(saved);
    let previousSel = null;
    let curAction = '';

    const pill = document.getElementById('selPill');
    const selCount = document.getElementById('selCount');
    const selBadge = document.getElementById('selBadge');
    const drEl = document.getElementById('bmDrawer');
    const drBG = document.getElementById('drawerBG');

    // Technician options for drawers
    const techOpts = `<option value="">-- Seleccionar técnico --</option>` +
        `<?php foreach ($technicians as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= addslashes(htmlspecialchars($t['name'])) ?></option>
        <?php endforeach; ?>`;

    // UI Initial sync (for checkboxes on the current page)
    function initCheckboxes() {
        document.querySelectorAll('.asset-checkbox').forEach(cb => {
            const id = parseInt(cb.value);
            if (sel.has(id)) {
                cb.checked = true;
                cb.closest('tr').classList.add('sel');
            }
        });
        updateSelectAllCheckbox();
        syncUI();
    }

    function saveSel() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...sel]));
    }

    function updateSelectAllCheckbox() {
        const cbs = document.querySelectorAll('.asset-checkbox');
        const allChecked = cbs.length > 0 && [...cbs].every(c => c.checked);
        document.getElementById('selectAll').checked = allChecked;
    }

    // ── Checkboxes ─────────────────────────────────────────────────────────
    document.querySelectorAll('.asset-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            const id = parseInt(cb.value);
            cb.closest('tr').classList.toggle('sel', cb.checked);
            cb.checked ? sel.add(id) : sel.delete(id);
            saveSel();
            updateSelectAllCheckbox();
            syncUI();
        });
    });

    document.getElementById('selectAll').addEventListener('change', e => {
        if (!e.target.checked && sel.size > 0) previousSel = new Set(sel);

        document.querySelectorAll('.asset-checkbox').forEach(cb => {
            cb.checked = e.target.checked;
            cb.closest('tr').classList.toggle('sel', cb.checked);
            e.target.checked ? sel.add(parseInt(cb.value)) : sel.delete(parseInt(cb.value));
        });
        saveSel();
        syncUI();

        if (!e.target.checked && previousSel) {
            const diff = previousSel.size - sel.size;
            if (diff > 0) {
                showToast('Selección reducida', diff + ' equipo' + (diff !== 1 ? 's' : '') + ' removido' + (diff !== 1 ? 's' : ''), true, true, 'undoSelection()');
            } else {
                previousSel = null;
            }
        }
    });

    initCheckboxes();

    function clearSel() {
        if (sel.size > 0) previousSel = new Set(sel);
        sel.clear();
        saveSel();
        document.querySelectorAll('.asset-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('tr').classList.remove('sel');
        });
        document.getElementById('selectAll').checked = false;
        syncUI();

        if (previousSel && previousSel.size > 0) {
            showToast('Selección borrada', previousSel.size + ' equipo' + (previousSel.size !== 1 ? 's' : '') + ' deseleccionado' + (previousSel.size !== 1 ? 's' : ''), true, true, 'undoSelection()');
        }
    }

    function undoSelection() {
        document.getElementById('bmToast').classList.remove('show');
        if (previousSel && previousSel.size > 0) {
            sel = new Set(previousSel);
            saveSel();
            initCheckboxes(); // re-checks the necessary ones
            previousSel = null;
        }
    }

    function syncUI() {
        const n = sel.size;
        selCount.textContent = n;
        selBadge.textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
        n > 0 ? pill.classList.add('visible') : pill.classList.remove('visible');

        const badgeWrap = document.getElementById('selBadgeWrap');
        if (n > 0) {
            badgeWrap.classList.remove('hidden');
            badgeWrap.classList.add('flex');
        } else {
            badgeWrap.classList.add('hidden');
            badgeWrap.classList.remove('flex');
        }

        document.getElementById('drSub').textContent = n + ' equipo' + (n !== 1 ? 's' : '') + ' seleccionado' + (n !== 1 ? 's' : '');
    }

    // ── Confirm modal ──────────────────────────────────────────────────────
    const LABELS = {
        maintenance: 'Emisión de Mantención Masiva',
        edit_field: 'Modificación de Atributos / Frecuencia',
        reassign: 'Reasignación de Técnico (OTs Activas)',
        delete: 'Solicitud de Baja del Inventario'
    };

    function askConfirm() {
        const n = sel.size;
        if (n === 0) {
            showToast('Selecciona equipos primero', 'Usa los checkboxes de la tabla para elegir el lote', false);
            return;
        }
        const modal = document.getElementById('bmConfirmModal');
        const applyBtn = document.getElementById('cfmApplyBtn');
        const action = LABELS[curAction] || 'Operación masiva';

        if (curAction === 'delete') {
            applyBtn.classList.replace('bg-amber-500', 'bg-red-500');
            applyBtn.classList.replace('hover:bg-amber-400', 'hover:bg-red-400');
            applyBtn.innerHTML = '<span class="material-symbols-outlined text-sm">security</span> Confirmar Borrado';
        } else {
            applyBtn.classList.replace('bg-red-500', 'bg-amber-500');
            applyBtn.classList.replace('hover:bg-red-400', 'hover:bg-amber-400');
            applyBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check</span> Sí, aplicar';
        }

        document.getElementById('cfmSub').textContent = `Acción: ${action}`;
        document.getElementById('cfmDetail').innerHTML =
            `<span style="color:${curAction === 'delete' ? '#ef4444' : '#f59e0b'};font-weight:900">${n}</span> equipo${n!==1?'s':''} seleccionado${n!==1?'s':''} serán afectados.<br>
            <span style="color:var(--text-muted);opacity:0.6;font-size:10px">${curAction === 'delete' ? 'Esta acción moverá los equipos a la papelera. Podrás restaurarlos pronto.' : 'Esta operación no puede deshacerse automáticamente pasados 5 minutos.'}</span>`;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function cancelConfirm() {
        const modal = document.getElementById('bmConfirmModal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function doConfirmedAction() {
        cancelConfirm();
        executeAction();
    }

    // ── Action cards ───────────────────────────────────────────────────
    function triggerAction(type) {
        if (sel.size === 0) {
            showToast('Selecciona equipos primero', 'Usa los checkboxes de la tabla para elegir el lote', false);
            document.querySelector('.bm-table-wrap').style.boxShadow = '0 0 0 2px rgba(245,158,11,.4)';
            setTimeout(() => document.querySelector('.bm-table-wrap').style.boxShadow = '', 1800);
            return;
        }
        openDrawer(type);
    }

    // ── Drawer defs ────────────────────────────────────────────────────────
    const defs = {
        maintenance: {
            icon: 'build_circle',
            wrap: 'bg-blue-500/15 text-blue-400',
            title: 'Mantención Preventiva Masiva',
            html: () => `
        <div class="space-y-5">
            <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-300 text-xs flex gap-2">
                <span class="material-symbols-outlined text-sm shrink-0 mt-0.5">info</span>
                <span>Se creará una OT Preventiva <strong>MP</strong> por cada equipo × el número de veces indicado.</span>
            </div>
            <div>
                <label class="dr-label">Descripción</label>
                <textarea id="m_desc" rows="2" placeholder="Ej: Mantención semestral PMP 2026" class="dr-input resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="dr-label">Fecha programada</label>
                    <input type="date" id="m_date" value="<?= date('Y-m-d') ?>" class="dr-input">
                </div>
                <div>
                    <label class="dr-label">Prioridad</label>
                    <select id="m_prio" class="dr-input">
                        <option value="HIGH">Alta</option>
                        <option value="MEDIUM" selected>Media</option>
                        <option value="LOW">Baja</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="dr-label">Técnico asignado (opcional)</label>
                <select id="m_tech" class="dr-input">${techOpts}</select>
            </div>
        </div>`
        },
        edit_field: {
            icon: 'edit_square',
            wrap: 'bg-amber-500/15 text-amber-400',
            title: 'Modificación de Atributos',
            html: () => `
        <div class="space-y-5">
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs flex gap-2">
                <span class="material-symbols-outlined text-sm shrink-0 mt-0.5">warning</span>
                <span>Sobreescribirá el campo en <strong>todos los equipos seleccionados</strong>. Podrás deshacer la acción durante 5 minutos.</span>
            </div>
            <div>
                <label class="dr-label">Campo a modificar</label>
                <select id="ef_field" onchange="renderVal(this.value)" class="dr-input">
                    <option value="criticality">Criticidad (MINSAL)</option>
                    <option value="status">Estado operativo</option>
                    <option value="maintenance_frequency">Frecuencia de Mantenimiento</option>
                    <option value="under_maintenance_plan">Plan de mantenimiento</option>
                    <option value="annual_maint_cost">Costo Anual de Mantenimiento</option>
                    <option value="ownership">Tipo de propiedad</option>
                    <option value="riesgo_ge">Clase / Especialidad</option>
                    <option value="location">Ubicación / Servicio</option>
                    <option value="sub_location">Sub-ubicación</option>
                    <option value="acquisition_cost">Precio de Adquisición (CLP)</option>
                    <option value="brand">Marca del Equipo</option>
                    <option value="model">Modelo del Equipo</option>
                    <option value="vendor">Proveedor / Servicio Técnico</option>
                    <option value="purchased_year">Año de Adquisición</option>
                </select>
            </div>
            <div>
                <label class="dr-label">Nuevo valor</label>
                <div id="efValWrap">
                    <select id="ef_value" class="dr-input">
                        <option value="CRITICAL">EMC — Crítico (Soporte Vital)</option>
                        <option value="RELEVANT">EMR — Relevante (IM≥12)</option>
                        <option value="LOW">EMI — No Aplica</option>
                    </select>
                </div>
            </div>
        </div>`
        },

        reassign: {
            icon: 'supervised_user_circle',
            wrap: 'bg-purple-500/15 text-purple-400',
            title: 'Reasignación de Operaciones (OTs)',
            html: () => `
        <div class="space-y-5">
            <div class="p-4 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-300 text-xs flex gap-2">
                <span class="material-symbols-outlined text-sm shrink-0 mt-0.5">info</span>
                <span>Se asignará el técnico a las OTs <strong>En Curso</strong> de los equipos seleccionados.</span>
            </div>
            <div>
                <label class="dr-label">Técnico destino</label>
                <select id="re_tech" class="dr-input">${techOpts}</select>
            </div>
        </div>`
        },
        delete: {
            icon: 'delete_sweep',
            wrap: 'bg-red-500/15 text-red-400',
            title: 'Solicitud de Baja del Inventario',
            html: () => `
        <div class="space-y-5">
            <div class="p-5 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs flex gap-3 leading-relaxed">
                <span class="material-symbols-outlined text-xl shrink-0">report_problem</span>
                <div>
                    <strong class="text-white block mb-1 uppercase tracking-wider">¿Retirar de servicio?</strong>
                    Se iniciará el proceso de baja definitiva para los <span class="text-white font-black" id="delCountText"></span> activos seleccionados.
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="dr-label text-red-400">Motivo de la baja (Obligatorio)</label>
                    <textarea id="retirement_reason" rows="3" placeholder="Ej: Equipo obsoleto, daño estructural irreparable, extravío..." class="dr-input resize-none bg-red-500/5 focus:border-red-500/40"></textarea>
                </div>
                
                <div class="pt-4 border-t border-border-dark/30">
                    <label class="dr-label">Autorización Requerida</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-lg group-focus-within:text-amber-500 transition-colors">key</span>
                        <input type="password" id="auth_password" placeholder="Ingresa tu clave de acceso..." class="w-full bg-medical-dark border border-border-dark rounded-xl pl-10 pr-4 py-3 text-xs outline-none focus:border-amber-500/50 transition-all font-bold text-text-main shadow-inner">
                    </div>
                </div>
            </div>

            <p class="text-text-muted text-[10px] font-bold opacity-60 italic">
                * Los equipos pasarán a "Baja en Trámite" por 24h. Podrás restaurarlos desde el "Cementerio de Equipos" si te equivocas.
            </p>
        </div>`
        }
    };

    function openDrawer(type) {
        const d = defs[type];
        if (!d) return;
        curAction = type;
        const iw = document.getElementById('drIconWrap');
        iw.className = 'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ' + d.wrap;
        document.getElementById('drIcon').textContent = d.icon;
        document.getElementById('drTitle').textContent = d.title;
        document.getElementById('drBody').innerHTML = d.html();
        if (type === 'delete') {
            document.getElementById('delCountText').textContent = sel.size;
        }
        syncUI();
        drBG.classList.remove('hidden');
        requestAnimationFrame(() => drEl.classList.add('open'));
    }

    function closeDrawer() {
        drEl.classList.remove('open');
        drBG.classList.add('hidden');
        curAction = '';
    }

    function renderVal(field) {
        const m = {
            criticality: `<select id="ef_value" class="dr-input"><option value="CRITICAL">EMC — Crítico (Soporte Vital)</option><option value="RELEVANT">EMR — Relevante (IM≥12)</option><option value="LOW">EMI — No Aplica</option></select>`,
            status: `<select id="ef_value" class="dr-input"><option value="OPERATIVE">Operativo</option><option value="MAINTENANCE">En Mantención</option><option value="NO_OPERATIVE">Fuera de Servicio</option><option value="OPERATIVE_WITH_OBS">Operativo con Obs.</option></select>`,
            maintenance_frequency: `
                <div class="space-y-4 p-4 rounded-xl bg-rose-500/5 border border-rose-500/10">
                    <p class="text-[10px] text-rose-400 font-bold uppercase tracking-wider mb-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">event_repeat</span> Configuración de Ciclos
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="dr-label !text-[9px]">Intervenciones / Año</label>
                            <input type="number" id="freq_n" min="1" max="12" value="2" class="dr-input text-center font-black !text-lg text-rose-400">
                        </div>
                        <div>
                            <label class="dr-label !text-[9px]">Prioridad OT</label>
                            <select id="freq_prio" class="dr-input !py-2.5">
                                <option value="HIGH">Alta</option>
                                <option value="MEDIUM" selected>Media</option>
                                <option value="LOW">Baja</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="dr-label !text-[9px]">Técnico Asignado</label>
                        <select id="freq_tech" class="dr-input !py-2.5">${techOpts}</select>
                    </div>
                    <div>
                        <label class="dr-label !text-[9px]">Descripción Base</label>
                        <input type="text" id="freq_desc" placeholder="Ej: Preventivo Periódico" class="dr-input !py-2.5">
                    </div>
                    <input type="hidden" id="ef_value" value="FREQ_TOOL_ACTIVE">
                </div>
            `,
            under_maintenance_plan: `<select id="ef_value" class="dr-input"><option value="1">Sí — bajo plan</option><option value="0">No — sin plan</option></select>`,
            ownership: `<select id="ef_value" class="dr-input"><option value="PROPIO">Propio</option><option value="COMODATO">Comodato</option><option value="ARRIENDO">Arriendo</option></select>`,
            riesgo_ge: `<input type="text" id="ef_value" list="clase_list" placeholder="Ej: IMAGENOLOGÍA" class="dr-input"><datalist id="clase_list"><?php foreach ($clasesDisponibles as $c) echo '<option value="' . htmlspecialchars($c) . '">'; ?></datalist>`,
            location: `<input type="text" id="ef_value" placeholder="Ej: UNIDAD DE CUIDADOS INTENSIVOS" class="dr-input">`,
            sub_location: `<input type="text" id="ef_value" placeholder="Ej: SALA 3 - PABELLÓN" class="dr-input">`,
            acquisition_cost: `
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted font-bold">$</span>
                    <input type="number" id="ef_value" min="0" step="1" placeholder="Ej: 1500000" class="dr-input pl-8" oninput="document.getElementById('price_preview').textContent = 'CLP: $' + (parseInt(this.value||0)).toLocaleString('es-CL')">
                </div>
                <p id="price_preview" class="text-[11px] font-bold text-amber-500 mt-2 text-right">CLP: $0</p>
                <div class="mt-4 p-3 rounded-xl bg-medical-dark border border-border-dark/30">
                    <p class="text-[9px] font-black uppercase tracking-widest text-text-muted opacity-60 mb-2">Valores Promedio Referenciales</p>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="setMassValue(450000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">BOMBA INF: $450k</button>
                        <button onclick="setMassValue(750000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">BOMBA JER: $750k</button>
                        <button onclick="setMassValue(1200000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">MONITOR MULT: $1.2M</button>
                        <button onclick="setMassValue(4500000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">ECO GRAF (STD): $4.5M</button>
                        <button onclick="setMassValue(225000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">SIG. VITALES: $225k</button>
                        <button onclick="setMassValue(18000000)" class="px-2 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-bold hover:bg-blue-500/20 transition-all">VENTILADOR: $18M</button>
                    </div>
                </div>
            `,
            annual_maint_cost: `
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted font-bold">$</span>
                    <input type="number" id="ef_value" min="0" step="1" placeholder="Ej: 80000" class="dr-input pl-8">
                </div>
            `,
            brand: `<input type="text" id="ef_value" placeholder="Ej: MINDRAY, PHILIPS, GE" class="dr-input">`,
            model: `<input type="text" id="ef_value" placeholder="Ej: BeneView T8, Infinity V500" class="dr-input">`,
            vendor: `<input type="text" id="ef_value" placeholder="Ej: Proveedor Médico S.A." class="dr-input">`,
            purchased_year: `<input type="number" id="ef_value" min="1980" max="${new Date().getFullYear()}" value="${new Date().getFullYear()}" class="dr-input text-center font-bold">`
        };
        document.getElementById('efValWrap').innerHTML = m[field] || '';
    }

    function setMassValue(val) {
        const input = document.getElementById('ef_value');
        if (input) {
            input.value = val;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // ── Execute ─────────────────────────────────────────────────────────────
    async function executeAction() {
        const btn = document.getElementById('drConfirm');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span> Procesando…';

        const body = new URLSearchParams();
        body.append('csrf_token', '<?= generateCsrfToken() ?>');
        [...sel].forEach(id => body.append('selected_ids[]', id));

        if (curAction === 'maintenance') {
            body.append('bulk_action', 'create_maintenance');
            body.append('description', document.getElementById('m_desc')?.value || '');
            body.append('scheduled_date', document.getElementById('m_date')?.value || '');
            body.append('priority', document.getElementById('m_prio')?.value || 'MEDIUM');
            body.append('technician_id', document.getElementById('m_tech')?.value || '');
            body.append('repeat', '1');
        } else if (curAction === 'freq') {
            body.append('bulk_action', 'create_maintenance');
            body.append('description', document.getElementById('freq_desc')?.value || 'Mantención preventiva periódica');
            body.append('scheduled_date', document.getElementById('freq_date')?.value || '');
            body.append('priority', document.getElementById('freq_prio')?.value || 'MEDIUM');
            body.append('technician_id', document.getElementById('freq_tech')?.value || '');
            body.append('repeat', document.getElementById('freq_n')?.value || '2');
        } else if (curAction === 'edit_field') {
            const field = document.getElementById('ef_field')?.value;
            if (field === 'maintenance_frequency') {
                body.append('bulk_action', 'create_maintenance');
                body.append('description', document.getElementById('freq_desc')?.value || 'Plan de Mantención Preventiva');
                body.append('scheduled_date', new Date().toISOString().split('T')[0]);
                body.append('priority', document.getElementById('freq_prio')?.value || 'MEDIUM');
                body.append('technician_id', document.getElementById('freq_tech')?.value || '');
                body.append('repeat', document.getElementById('freq_n')?.value || '2');
            } else {
                body.append('bulk_action', 'update_field');
                body.append('field', field || '');
                body.append('value', document.getElementById('ef_value')?.value ?? '');
            }
        } else if (curAction === 'reassign') {
            body.append('bulk_action', 'reassign_technician');
            body.append('technician_id', document.getElementById('re_tech')?.value || '');
        } else if (curAction === 'delete') {
            body.append('bulk_action', 'delete');
            body.append('auth_password', document.getElementById('auth_password')?.value || '');
            body.append('retirement_reason', document.getElementById('retirement_reason')?.value || '');
        }

        try {
            const r = await fetch('?page=bulk_management', {
                method: 'POST',
                body
            });
            const j = await r.json();
            const withUndo = !j.errors?.length && ['edit_field', 'maintenance', 'freq', 'delete'].includes(curAction);
            closeDrawer();
            showToast(
                j.success + ' operación' + (j.success !== 1 ? 'es' : '') + ' completada' + (j.success !== 1 ? 's' : ''),
                j.errors?.length ? j.errors.join(' · ') : '✓ Acción ejecutada con éxito',
                !j.errors?.length,
                withUndo
            );
            if (!j.errors?.length) {
                // Clear selection on success for all actions
                sel.clear();
                saveSel();
                // Immediate UI feedback
                document.querySelectorAll('.asset-checkbox').forEach(cb => {
                    cb.checked = false;
                    cb.closest('tr').classList.remove('sel');
                });
                if (document.getElementById('selectAll')) {
                    document.getElementById('selectAll').checked = false;
                }
                syncUI();

                // All actions should reload to refresh state (OTs, status, etc.)
                setTimeout(() => location.reload(), 1600);
            }
        } catch (e) {
            showToast('Error de conexión', e.message, false);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-base">bolt</span>Aplicar ahora';
        }
    }

    // ── Toast (con botón Deshacer) ────────────────────────────────────────
    let _toastTimer;

    function showToast(title, sub, ok, withUndo = false, undoFn = 'doUndo()') {
        const t = document.getElementById('bmToast');
        const ic = document.getElementById('toastIco');
        const uw = document.getElementById('toastUndoWrap');
        document.getElementById('toastT').textContent = title;
        document.getElementById('toastS').textContent = sub;
        ic.textContent = ok ? 'check_circle' : 'error';
        ic.className = 'material-symbols-outlined text-2xl ' + (ok ? 'text-emerald-400' : 'text-red-400');
        ic.style = 'flex-shrink:0;margin-top:.125rem';
        t.style.borderColor = ok ? 'rgba(16,185,129,.3)' : 'rgba(239,68,68,.3)';

        const undoBtn = uw.querySelector('button');
        if (undoBtn) undoBtn.setAttribute('onclick', undoFn);

        withUndo ? uw.classList.remove('hidden') : uw.classList.add('hidden');
        clearTimeout(_toastTimer);
        t.classList.add('show');
        _toastTimer = setTimeout(() => {
            t.classList.remove('show');
            uw.classList.add('hidden');
        }, 6000);
    }

    // ── Deshacer (Ctrl-Z) ─────────────────────────────────────────────────
    async function doUndo() {
        const b = new URLSearchParams();
        b.append('bulk_action', 'undo');
        b.append('csrf_token', '<?= generateCsrfToken() ?>');
        document.getElementById('bmToast').classList.remove('show');
        try {
            const r = await fetch('?page=bulk_management', {
                method: 'POST',
                body: b
            });
            const j = await r.json();
            showToast(
                j.success + ' equipo' + (j.success !== 1 ? 's' : '') + ' restaurado' + (j.success !== 1 ? 's' : ''),
                j.errors?.length ? j.errors.join(' · ') : '✓ Deshacer completado',
                !j.errors?.length,
                false
            );
            setTimeout(() => location.reload(), 1400);
        } catch (e) {
            showToast('Error al deshacer', e.message, false, false);
        }
    }
</script>