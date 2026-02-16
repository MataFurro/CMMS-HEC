<?php

/**
 * pages/messenger_requests.php
 * Integración de reportes desde el Mensajero Satélite (SQLite).
 */

if (!canViewDashboard()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1></div>";
    return;
}

// ── Backend Provider ──
require_once __DIR__ . '/../backend/providers/WorkOrderProvider.php';

// Ruta a la base de datos del Mensajero (Consolidada)
$msDbPath = __DIR__ . '/../API Mail/database/messenger.db';

try {
    if (!file_exists($msDbPath)) {
        throw new Exception("La base de datos del Mensajero no se encuentra.");
    }

    $db = new PDO('sqlite:' . $msDbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── Manejar Acción: Crear OT ──
    if (isset($_POST['action']) && $_POST['action'] === 'create_ot' && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];

        $stmt_get = $db->prepare("SELECT * FROM reports WHERE id = :id");
        $stmt_get->execute([':id' => $requestId]);
        $report = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if ($report) {
            createWorkOrderFromRequest([
                'asset_id' => $report['serie'],
                'asset_name' => $report['servicio'],
                'problem' => $report['texto'],
                'priority' => 'Alta',
                'ms_email' => $report['email'],
                'ms_request_id' => $report['id'] // Pasamos el ID para el feedback loop
            ]);

            $stmt = $db->prepare("UPDATE reports SET status = 'Procesado' WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
        }

        echo "<script>window.location.href = '?page=messenger_requests&success=1';</script>";
        exit;
    }

    $requests = $db->query("SELECT * FROM reports WHERE status = 'Pendiente' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $requests = [];
    $error = $e->getMessage();
}
?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <nav class="flex items-center gap-2 mb-4">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue bg-medical-blue/10 px-2 py-0.5 rounded">Mensajería</span>
                <span class="material-symbols-outlined text-xs text-slate-600">chevron_right</span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Solicitudes Clínicas</span>
            </nav>
            <h1 class="text-4xl font-black text-white tracking-tight flex items-center gap-4">
                Solicitudes Pendientes
                <span class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">mail</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg font-medium italic opacity-80">Reportes entrantes desde el anexo satélite de servicios clínicos.</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-2xl flex items-center gap-4 text-emerald-500 shadow-xl shadow-emerald-500/5 animate-in slide-in-from-top-4 duration-300">
            <span class="material-symbols-outlined text-4xl">task_alt</span>
            <div>
                <p class="font-black uppercase tracking-widest text-sm">Orden de Trabajo Generada</p>
                <p class="text-emerald-500/80 text-xs mt-1">La solicitud ha sido procesada y vinculada al sistema central de mantenimiento.</p>
            </div>
            <button onclick="window.location.href='?page=work_orders'" class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all">Ver Órdenes</button>
        </div>
    <?php endif; ?>

    <div class="card-glass overflow-hidden shadow-2xl border border-white/5">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10 uppercase text-[10px] font-black tracking-widest text-slate-500">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Servicio / Equipo</th>
                    <th class="px-6 py-4">Serie</th>
                    <th class="px-6 py-4">Descripción de Falla</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($requests as $r): ?>
                    <tr class="hover:bg-white/5 transition-all text-sm border-b border-white/5">
                        <td class="px-6 py-4 font-black text-slate-400">#<?= $r['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-white"><?= htmlspecialchars($r['equipo'] ?? 'N/A') ?></span>
                                <span class="text-xs text-slate-500"><?= htmlspecialchars($r['servicio']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-medical-blue"><?= htmlspecialchars($r['serie']) ?></td>
                        <td class="px-6 py-4 text-slate-400 max-w-xs truncate" title="<?= htmlspecialchars($r['texto']) ?>">
                            <?= htmlspecialchars($r['texto']) ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?= htmlspecialchars($r['email']) ?>
                            <?php if (!empty($r['imagen_path'])): ?>
                                <div class="mt-1">
                                    <span class="text-[10px] uppercase bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded border border-slate-700 flex items-center w-fit gap-1">
                                        <span class="material-symbols-outlined text-[10px]">image</span> Foto
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_ot">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="p-2 bg-medical-blue/10 text-medical-blue rounded-lg hover:bg-medical-blue/20 transition-all shadow-lg hover:shadow-medical-blue/10 border border-medical-blue/20" title="Crear Orden de Trabajo">
                                        <span class="material-symbols-outlined text-xl">add_task</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-700 mb-4 block">inbox</span>
                            <p class="text-slate-500 font-bold">No hay nuevas solicitudes en la bandeja.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>