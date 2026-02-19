<?php

/**
 * pages/messenger_requests.php
 * Integración de reportes desde MySQL (Migrado desde SQLite).
 */

// ── Control de Acceso ──
if (!canViewDashboard()) {
    echo "<div class='p-8 text-center text-red-500 font-bold'>Acceso Denegado</div>";
    return;
}

// ── Backend Providers & Core ──
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();

    // ── Manejar Acción: Crear OT ──
    if (isset($_POST['action']) && $_POST['action'] === 'create_ot' && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];

        // 1. Obtener datos del reporte antes de marcarlo como procesado
        $stmt_get = $db->prepare("SELECT * FROM messenger_reports WHERE id = :id");
        $stmt_get->execute([':id' => $requestId]);
        $report = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if ($report) {
            // 2. Crear OT Real en el sistema
            createWorkOrderFromRequest([
                'asset_id' => $report['serie'] ?? $report['asset_id'] ?? 'S/N',
                'asset_name' => $report['servicio'] ?? $report['asset_name'] ?? 'Equipo Desconocido',
                'problem' => $report['texto'],
                'priority' => 'Alta',
                'ms_email' => $report['email'],
                'ms_request_id' => $report['id']
            ]);

            // 3. Cambiar estado a 'Procesado'
            $stmt = $db->prepare("UPDATE messenger_reports SET status = 'Procesado' WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
        }

        echo "<script>window.location.href = '?page=messenger_requests&success=1';</script>";
        exit;
    }

    // Solo traer solicitudes PENDIENTES
    $requests = $db->query("SELECT * FROM messenger_reports WHERE status = 'Pendiente' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $requests = [];
    $error = "Error de Conexión MySQL (Módulo Mensajería): " . $e->getMessage();
}
?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 mb-4">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue bg-medical-blue/10 px-2 py-0.5 rounded">Mensajería Profesional</span>
                <span class="material-symbols-outlined text-xs text-slate-600">chevron_right</span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Solicitudes Clínicas</span>
            </nav>
            <h1 class="text-4xl font-black text-white tracking-tight flex items-center gap-4">
                Solicitudes Pendientes
                <span class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">mail</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg font-medium italic opacity-80">Integración directa MySQL v4.2 PRO.</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-2xl flex items-center gap-4 text-emerald-500 shadow-xl shadow-emerald-500/5 animate-in slide-in-from-top-4 duration-300">
            <span class="material-symbols-outlined text-4xl">task_alt</span>
            <div>
                <p class="font-black uppercase tracking-widest text-sm">Orden de Trabajo Generada</p>
                <p class="text-emerald-500/80 text-xs mt-1">La solicitud ha sido procesada y vinculada al sistema central MySQL.</p>
            </div>
            <button onclick="window.location.href='?page=work_orders'" class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all">Ver Órdenes</button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="card-glass border-l-4 border-l-red-500 p-4">
            <p class="text-red-400 font-bold">Resumen del Estado:</p>
            <p class="text-slate-400 text-sm"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="card-glass overflow-hidden shadow-2xl border border-white/5">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-white/5 border-b border-white/10 uppercase text-[10px] font-black tracking-widest text-slate-500">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Servicio / Equipo</th>
                    <th class="px-6 py-4">Descripción de Falla</th>
                    <th class="px-6 py-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($requests as $r): ?>
                    <tr class="hover:bg-white/5 transition-all text-sm border-b border-white/5">
                        <td class="px-6 py-4 font-black">#<?= $r['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-white"><?= htmlspecialchars($r['servicio'] ?? $r['asset_name'] ?? 'N/A') ?></div>
                            <div class="text-medical-blue text-xs font-mono"><?= htmlspecialchars($r['serie'] ?? $r['asset_id'] ?? 'S/N') ?></div>
                        </td>
                        <td class="px-6 py-4 text-slate-400 line-clamp-2 max-w-xs" title="<?= htmlspecialchars($r['texto']) ?>">
                            <?= htmlspecialchars($r['texto']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <a href="?page=work_order_opening&from_request=<?= $r['id'] ?>"
                                    class="p-2 bg-medical-blue/10 text-medical-blue rounded-lg hover:bg-medical-blue/20 transition-all border border-medical-blue/20 shadow-lg"
                                    title="Abrir Formulario de OT">
                                    <span class="material-symbols-outlined text-xl">add_task</span>
                                </a>
                                <form method="POST" onsubmit="return confirm('¿Crear Orden de Trabajo directa para esta solicitud?');" class="inline">
                                    <input type="hidden" name="action" value="create_ot">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg hover:bg-emerald-500/20 transition-all border border-emerald-500/20 shadow-lg" title="Cierre Directo">
                                        <span class="material-symbols-outlined text-xl">bolt</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-700 mb-4 block">inbox</span>
                            <p class="text-slate-500 font-bold">No hay nuevas solicitudes en la bandeja central MySQL.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>