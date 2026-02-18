<?php
/**
 * pages/messenger_requests.php
 * Integración de reportes desde MySQL.
 */

// ── Control de Acceso ──
if (!canViewDashboard()) {
    echo "<div class='p-8 text-center text-red-500 font-bold'>Acceso Denegado</div>";
    return;
}

require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();

    // Cargar solicitudes pendientes
    $stmt = $db->query("SELECT * FROM messenger_reports WHERE status = 'Pendiente' ORDER BY created_at DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error al cargar solicitudes: " . $e->getMessage();
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
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="card-glass border-l-4 border-l-red-500 p-4">
            <p class="text-red-400 font-bold">Error del Sistema:</p>
            <p class="text-slate-400 text-sm"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="card-glass overflow-hidden shadow-2xl border border-white/5">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-white/5 border-b border-white/10 text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Servicio / Equipo</th>
                    <th class="px-6 py-4">Falla Reportada</th>
                    <th class="px-6 py-4 text-center">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($requests as $r): ?>
                    <tr class="hover:bg-white/5 transition-all text-sm border-b border-white/5">
                        <td class="px-6 py-4 font-black text-medical-blue">#<?= $r['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="text-white font-bold"><?= htmlspecialchars($r['asset_name']) ?></div>
                            <div class="text-[10px] text-slate-500 font-mono"><?= $r['asset_id'] ?></div>
                        </td>
                        <td class="px-6 py-4 text-slate-400 max-w-xs truncate" title="<?= htmlspecialchars($r['texto']) ?>">
                            <?= htmlspecialchars($r['texto']) ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="?page=work_order_opening&from_request=<?= $r['id'] ?>" 
                               class="inline-flex p-2.5 bg-medical-blue text-white rounded-xl hover:bg-medical-blue/80 shadow-lg shadow-medical-blue/20 transition-all active:scale-95"
                               title="Abrir Formulario de OT">
                                <span class="material-symbols-outlined">add_task</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-700 mb-4 block">inbox</span>
                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">No hay nuevas solicitudes en la bandeja.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
