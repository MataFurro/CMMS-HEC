<?php
// pages/asset.php (Asset History)

$id = $_GET['id'] ?? 'UNKNOWN';

// Mock Data for a single asset (In real app, fetch by ID)
$asset = [
    'id' => $id,
    'serialNumber' => 'SN-992031-B',
    'name' => 'Ventilador Mecánico',
    'brand' => 'Puritan Bennett',
    'model' => '840',
    'location' => 'UCI Adultos - Box 04',
    'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE',
    'status' => 'OPERATIVE',
    'criticality' => 'CRITICAL'
];

$history = [
    ['date' => '2023-10-10', 'type' => 'Preventivo', 'desc' => 'Cambio de filtros y batería', 'tech' => 'Mario Gómez'],
    ['date' => '2023-08-15', 'type' => 'Correctivo', 'desc' => 'Falla en sensor de flujo', 'tech' => 'Pablo Rojas'],
    ['date' => '2023-05-20', 'type' => 'Preventivo', 'desc' => 'Mantenimiento semestral', 'tech' => 'Mario Gómez'],
];
?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="?page=inventory" class="p-3 bg-slate-800 rounded-xl text-slate-400 hover:text-white hover:bg-slate-700 transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight flex items-center gap-3">
                    <?= $asset['name'] ?>
                    <span class="text-lg text-slate-500 font-mono bg-slate-800 px-2 py-1 rounded-lg border border-slate-700">
                        <?= $asset['id'] ?>
                    </span>
                </h1>
                <p class="text-slate-400 text-sm font-bold uppercase tracking-wider mt-1">
                    <?= $asset['brand'] ?> <?= $asset['model'] ?>
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <button class="px-6 py-2 bg-medical-blue text-white rounded-xl font-bold hover:bg-medical-blue/90 shadow-lg shadow-medical-blue/20">
                Editar Activo
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="card-glass p-2">
                <img src="<?= $asset['imageUrl'] ?>" class="w-full h-64 object-cover rounded-lg" alt="Asset Image">
            </div>

            <div class="card-glass p-6 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-700 pb-2">Estado Actual</h3>

                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-400">Estado</span>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-black uppercase">
                        <?= $asset['status'] ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-400">Criticidad</span>
                    <span class="px-3 py-1 rounded-full bg-red-500/10 text-red-500 border border-red-500/20 text-xs font-black uppercase">
                        <?= $asset['criticality'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="lg:col-span-2 card-glass p-8">
            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-medical-blue">history</span>
                Historial de Intervenciones
            </h3>

            <div class="relative pl-4 border-l-2 border-slate-700 space-y-8">
                <?php foreach ($history as $event): ?>
                    <div class="relative pl-6">
                        <div class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-slate-900 border-2 border-medical-blue ring-4 ring-slate-900"></div>
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-sm font-black text-white uppercase tracking-wider"><?= $event['type'] ?></span>
                            <span class="text-xs font-bold text-slate-500"><?= $event['date'] ?></span>
                        </div>
                        <p class="text-slate-300 text-sm mb-2"><?= $event['desc'] ?></p>
                        <div class="flex items-center gap-2 text-xs text-slate-500 font-bold bg-slate-800/50 w-fit px-2 py-1 rounded-lg">
                            <span class="material-symbols-outlined text-sm">person</span>
                            <?= $event['tech'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>