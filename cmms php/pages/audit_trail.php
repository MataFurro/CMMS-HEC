<?php
// pages/audit_trail.php
require_once __DIR__ . '/../Backend/Providers/AuditProvider.php';

use function Backend\Providers\getAssetAuditHistory;

// Obtener todos los logs (podríamos añadir una función getAllAuditLogs si fuera necesario)
// Por ahora simularemos la obtención de los últimos 50 logs de la tabla audit_trail
$db = \Backend\Core\DatabaseService::getInstance();
$stmt = $db->query("SELECT at.*, u.name as user_name FROM audit_trail at LEFT JOIN users u ON at.user_id = u.id ORDER BY at.timestamp DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div>
        <h1 class="text-4xl font-black text-[var(--text-main)] tracking-tight flex items-center gap-4">
            Bitácora de Sistema
            <span class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">history_edu</span>
        </h1>
        <p class="text-[var(--text-muted)] mt-2 text-lg font-medium italic opacity-80">Rastreo de integridad y auditoría de procesos automáticos (Compliance FDA 21 CFR Part 11).</p>
    </div>

    <!-- Filtros Rápidos -->
    <div class="flex gap-4 overflow-x-auto pb-2">
        <button class="px-6 py-2 bg-slate-800 border border-slate-700 rounded-xl text-slate-300 font-bold text-xs uppercase tracking-widest hover:border-medical-blue transition-all">Todos</button>
        <button class="px-6 py-2 bg-slate-800 border border-slate-700 rounded-xl text-slate-300 font-bold text-xs uppercase tracking-widest hover:border-medical-blue transition-all">Cierres Automáticos</button>
        <button class="px-6 py-2 bg-slate-800 border border-slate-700 rounded-xl text-slate-300 font-bold text-xs uppercase tracking-widest hover:border-medical-blue transition-all">Reportes Bio</button>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($logs as $log):
            $details = json_decode($log['details'], true);
            $isSystem = isset($details['agentic_reasoning']) || (isset($log['action']) && strpos($log['action'], 'AUTO') !== false);
        ?>
            <div class="card-glass p-6 border-l-4 <?= $isSystem ? 'border-medical-blue' : 'border-slate-700' ?> group hover:bg-slate-800/40 transition-all">
                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-xl <?= $isSystem ? 'bg-medical-blue/10 text-medical-blue' : 'bg-slate-700/50 text-slate-500' ?> flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl"><?= $isSystem ? 'settings_suggest' : 'person' ?></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-[var(--text-main)] font-bold text-lg"><?= $log['action'] ?></h3>
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded <?= $isSystem ? 'bg-medical-blue/20 text-medical-blue' : 'bg-slate-700 text-slate-400' ?>">
                                    <?= $isSystem ? 'Acción de Sistema' : 'Acción Humana' ?>
                                </span>
                            </div>
                            <p class="text-[var(--text-muted)] text-sm mt-1">Activo: <button onclick="window.location.href='?page=asset&id=<?= $log['asset_id'] ?>'" class="text-medical-blue hover:underline font-mono"><?= $log['asset_id'] ?></button></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-500 uppercase"><?= date('d M Y, H:i:s', strtotime($log['timestamp'])) ?></p>
                        <p class="text-[10px] font-mono text-slate-600 mt-1">IP: <?= $log['ip_address'] ?></p>
                    </div>
                </div>

                <?php if ($isSystem && isset($details['agentic_reasoning'])): ?>
                    <div class="mt-4 p-4 bg-medical-blue/5 border border-medical-blue/10 rounded-xl relative overflow-hidden">
                        <div class="absolute right-[-10px] bottom-[-10px] opacity-10 rotate-12">
                            <span class="material-symbols-outlined text-6xl text-medical-blue">analytics</span>
                        </div>
                        <h4 class="text-[10px] font-black uppercase text-medical-blue tracking-widest mb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-xs">info</span>
                            Justificación Técnica Sistémica
                        </h4>
                        <p class="text-sm text-[var(--text-muted)] italic leading-relaxed">
                            "<?= $details['agentic_reasoning'] ?>"
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 text-sm text-[var(--text-muted)]">
                        <?= $log['reason'] ?? '' ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if (isset($details['system_version'])): ?>
                        <span class="text-[10px] font-bold text-slate-600 border border-slate-700/50 px-2 py-0.5 rounded">Kernel: <?= $details['system_version'] ?></span>
                    <?php endif; ?>
                    <span class="text-[10px] font-bold text-slate-600 border border-slate-700/50 px-2 py-0.5 rounded">Ref: <?= $log['id'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>