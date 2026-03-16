<?php
// pages/retired_assets.php
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/BulkProvider.php';

if (!isChiefEngineer()) {
    echo "<script>window.location.href='?page=dashboard';</script>";
    exit;
}

// Handler para restaurar / finalizar (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken()) {
        echo json_encode(['success' => 0, 'errors' => ['Token de seguridad inválido o caducado. Recarga la página.']]);
        exit;
    }

    $id = $_POST['id']; // ID can be string (e.g. PB-840-...)
    $res = ['success' => 0];

    if ($_POST['action'] === 'restore') {
        $res = bulkRestoreAssets([$id]);
    } else if ($_POST['action'] === 'finalize') {
        $res = finalizeAssetNow($id);
    }

    echo json_encode($res);
    exit;
}

// Ejecutar limpieza automática al cargar esta página
finalizePendingRetirements();

// Obtener equipos retirados o en trámite
$db = \Backend\Core\DatabaseService::getInstance();
$sql = "SELECT * FROM assets WHERE en_uso = 0 OR status IN ('PENDING_RETIREMENT', 'RETIRED') ORDER BY retirement_requested_at DESC, updated_at DESC";
$retired = $db->query($sql)->fetchAll();
?>

<div class="w-full p-4 lg:p-6 space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-slate-400 text-3xl">skull</span>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-text-main">Cementerio de Equipos</h1>
                    <p class="text-text-muted font-bold text-sm opacity-70 italic">Historial de activos retirados y bajas en trámite</p>
                </div>
            </div>
        </div>
        <a href="?page=bulk_management" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-medical-dark border border-border-dark text-text-muted hover:text-white transition-all text-xs font-black uppercase tracking-widest">
            <span class="material-symbols-outlined text-sm">inventory_2</span> Volver a Gestión
        </a>
    </div>

    <!-- Info Banner -->
    <div class="p-6 rounded-3xl bg-amber-500/10 border border-amber-500/20 flex gap-4 items-start">
        <span class="material-symbols-outlined text-amber-500 text-3xl mt-1">history_toggle_off</span>
        <div>
            <h3 class="text-amber-500 font-black text-lg">Acerca del Registro de Bajas</h3>
            <p class="text-text-muted text-sm leading-relaxed max-w-3xl">
                Los equipos aquí listados han sido retirados del servicio activo. Según las normativas <strong>ISO/FDA</strong>, los registros de mantenimiento deben conservarse incluso después de la baja.
                Los equipos marcados como <span class="text-orange-400 font-bold uppercase">Baja en Trámite</span> tienen un periodo de gracia de 24h para ser restaurados antes de su retiro permanente.
            </p>
        </div>
    </div>

    <!-- List -->
    <div class="bg-medical-surface border border-border-dark rounded-3xl overflow-hidden shadow-2xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-medical-dark/50 border-b border-border-dark">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-text-muted">Equipo / ID</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-text-muted">Estado Actual</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-text-muted">Fecha Solicitud</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-text-muted">Motivo de Baja</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-text-muted text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-dark/30">
                <?php if (empty($retired)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center text-text-muted opacity-40 font-bold italic">No hay equipos registrados en el cementerio.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($retired as $a):
                        $isPending = ($a['status'] === 'PENDING_RETIREMENT');
                        $timeLeft = "";
                        if ($isPending && $a['retirement_requested_at']) {
                            $requestedAt = strtotime($a['retirement_requested_at']);
                            $deadline = $requestedAt + (24 * 3600);
                            $diff = $deadline - time();
                            if ($diff > 0) {
                                $hours = floor($diff / 3600);
                                $mins = floor(($diff % 3600) / 60);
                                $timeLeft = "Restan {$hours}h {$mins}m";
                            } else {
                                $timeLeft = "Expirado (Procesando...)";
                            }
                        }
                    ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-slate-500">devices_other</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-black text-text-main"><?= htmlspecialchars($a['name']) ?></p>
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-medical-blue/10 text-medical-blue font-bold uppercase tracking-widest border border-medical-blue/20">
                                                <?= htmlspecialchars($a['hec_id'] ?: 'S/ID') ?>
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-text-muted opacity-60">INV: <?= htmlspecialchars($a['inventory_id'] ?: $a['id']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <?php if ($isPending): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[10px] font-black uppercase w-fit">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span> Baja en Trámite
                                        </span>
                                        <span class="text-[9px] font-bold text-orange-500/60 uppercase tracking-widest ml-1"><?= $timeLeft ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-500/10 text-slate-400 border border-slate-500/20 text-[10px] font-black uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Retirado
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-xs text-text-muted font-bold">
                                <?= $a['retirement_requested_at'] ? date('d/m/Y H:i', strtotime($a['retirement_requested_at'])) : '—' ?>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-xs text-text-muted max-w-xs leading-relaxed italic opacity-80">
                                    "<?= htmlspecialchars($a['retirement_reason'] ?: 'Sin motivo especificado') ?>"
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="restoreAsset('<?= addslashes($a['id']) ?>', '<?= addslashes($a['name']) ?>')"
                                        class="px-4 py-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-emerald-500 hover:text-black transition-all">
                                        Restaurar
                                    </button>
                                    <a href="?page=asset&id=<?= urlencode($a['id']) ?>"
                                        class="px-4 py-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-blue-500 hover:text-black transition-all"
                                        title="Ver Historial">
                                        Historial
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    async function restoreAsset(id, name) {
        if (typeof Swal === 'undefined') {
            if (!confirm(`¿Deseas restaurar el equipo "${name}" al inventario activo?`)) return;
            await apiCall('restore', id, 'Equipo restaurado con éxito.');
            return;
        }

        const result = await Swal.fire({
            title: '¿Restaurar equipo?',
            text: `¿Deseas restaurar "${name}" al inventario activo?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            await apiCall('restore', id, 'Equipo restaurado con éxito.');
        }
    }

    async function finalizeNow(id, name) {
        if (typeof Swal === 'undefined') {
            if (!confirm(`¿Deseas finalizar la baja de "${name}" ahora mismo?`)) return;
            await apiCall('finalize', id, 'Equipo dado de baja permanentemente.');
            return;
        }

        const result = await Swal.fire({
            title: '¿Finalizar baja ahora?',
            text: `¿Deseas desactivar "${name}" permanentemente?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, finalizar ahora',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            await apiCall('finalize', id, 'Equipo dado de baja permanentemente.');
        }
    }

    async function apiCall(action, id, successMsg) {
        const body = new URLSearchParams();
        body.append('action', action);
        body.append('id', id);
        body.append('csrf_token', '<?= generateCsrfToken() ?>');

        try {
            const r = await fetch('?page=retired_assets', {
                method: 'POST',
                body
            });
            const j = await r.json();
            if (j.success > 0) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: j.message || successMsg,
                        confirmButtonColor: '#10b981'
                    });
                } else {
                    alert(successMsg);
                }
                location.reload();
            } else {
                const errorText = j.errors?.join(' · ') || 'Operación fallida.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorText
                    });
                } else {
                    alert('Error: ' + errorText);
                }
            }
        } catch (e) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: e.message
                });
            } else {
                alert('Error de conexión: ' + e.message);
            }
        }
    }
</script>