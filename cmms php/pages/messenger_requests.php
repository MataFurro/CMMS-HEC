<?php

/**
 * pages/messenger_requests.php
 * Integración de reportes desde el Mensajero Satélite (SQLite).
 */

// ── Control de Acceso ──
if (!canViewDashboard()) {
    echo "<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-500'>Acceso Denegado</h1></div>";
    return;
}

// Ruta a la base de datos del Mensajero (Ajustar según ubicación real)
$msDbPath = __DIR__ . '/../../API Mail/database/messenger.db';

try {
    if (!file_exists($msDbPath)) {
        throw new Exception("La base de datos del Mensajero no se encuentra en: " . $msDbPath);
    }

    $db = new PDO('sqlite:' . $msDbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── Manejar Acción: Crear OT ──
    if (isset($_POST['action']) && $_POST['action'] === 'create_ot' && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];
        // Cambiar estado a 'Procesado' para que desaparezca de la lista activa
        $stmt = $db->prepare("UPDATE reports SET status = 'Procesado' WHERE id = :id");
        $stmt->execute([':id' => $requestId]);

        // Aquí se podría redirigir a la página de creación de OT con los datos precargados
        // header('Location: ?page=work_order_opening&serie=' . $_POST['serie']); 
        // Por ahora, solo refrescamos la lista
        echo "<script>window.location.href = '?page=messenger_requests';</script>";
        exit;
    }

    // Solo traer solicitudes PENDIENTES
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

    <?php if (isset($error)): ?>
        <div class="card-glass border-l-4 border-l-red-500 p-4">
            <p class="text-red-400 font-bold">Error de Conexión:</p>
            <p class="text-slate-400 text-sm"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="card-glass overflow-hidden shadow-2xl border border-white/5">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10 uppercase text-[10px] font-black tracking-widest text-slate-500">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Servicio / Equipo</th>
                    <th class="px-6 py-4">Descripción de Falla</th>
                    <th class="px-6 py-4">Evidencia</th>
                    <th class="px-6 py-4">Estado</th>
                    <th class="px-6 py-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($requests as $r): ?>
                    <tr class="hover:bg-white/5 transition-all text-sm">
                        <td class="px-6 py-4 font-black">#<?= $r['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-white"><?= $r['servicio'] ?></div>
                            <div class="text-medical-blue text-xs font-mono"><?= $r['serie'] ?></div>
                        </td>
                        <td class="px-6 py-4 text-slate-400 line-clamp-2 max-w-xs">
                            <?= $r['texto'] ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($r['imagen_path']): ?>
                                <img src="../API Mail/uploads/<?= $r['imagen_path'] ?>" class="w-12 h-12 rounded-lg object-cover border border-white/10 hover:scale-150 transition-transform cursor-pointer">
                            <?php else: ?>
                                <span class="text-slate-600 italic">Sin foto</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <form method="POST" onsubmit="return confirm('¿Crear Orden de Trabajo para esta solicitud?');">
                                    <input type="hidden" name="action" value="create_ot">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="serie" value="<?= $r['serie'] ?>">
                                    <input type="hidden" name="email" value="<?= $r['email'] ?>">
                                    <button type="submit" class="p-2 bg-medical-blue/10 text-medical-blue rounded-lg hover:bg-medical-blue/20 transition-all" title="Generar OT">
                                        <span class="material-symbols-outlined text-xl">add_task</span>
                                    </button>
                                </form>
                                <button class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg hover:bg-emerald-500/20 transition-all" title="Ver Detalle">
                                    <span class="material-symbols-outlined text-xl">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-700 mb-4 block">inbox</span>
                            <p class="text-slate-500 font-bold">No hay nuevas solicitudes en la bandeja.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>