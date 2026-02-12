<?php
// pages/inventory.php

// --- MOCK DATA ---
$assets = [
    [
        'id' => 'PB-840-00122',
        'serialNumber' => 'SN-992031-B',
        'name' => 'Ventilador Mecánico',
        'brand' => 'Puritan Bennett',
        'model' => '840',
        'location' => 'UCI Adultos - Box 04',
        'subLocation' => 'Cama 4',
        'vendor' => 'Draeger Medical',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE',
        'riesgoGE' => 'Life Support',
        'codigoUMDNS' => '17-429',
        'fechaInstalacion' => '2020-05-10',
        'purchasedYear' => 2020,
        'acquisitionCost' => 45000,
        'vencimientoVidaUtil' => '2030-05-10',
        'totalUsefulLife' => 10,
        'usefulLife' => 75,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2026-12-15',
        'warrantyExpiration' => '2026-12-15',
        'lastMaintenance' => '2026-01-20',
        'nextMaintenance' => '2026-07-20',
        'underMaintenancePlan' => true,
        'enUso' => true,
        'recalls' => [],
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE'
    ],
    [
        'id' => 'AL-500-00441',
        'serialNumber' => 'SN-882211-X',
        'name' => 'Bomba de Infusión',
        'brand' => 'Alaris',
        'model' => 'GH Plus',
        'location' => 'Urgencias - Sala 01',
        'subLocation' => 'Box 1',
        'vendor' => 'Becton Dickinson',
        'ownership' => 'Comodato',
        'criticality' => 'RELEVANT',
        'status' => 'MAINTENANCE',
        'riesgoGE' => 'High Risk',
        'codigoUMDNS' => '13-215',
        'fechaInstalacion' => '2021-02-15',
        'purchasedYear' => 2021,
        'acquisitionCost' => 8500,
        'vencimientoVidaUtil' => '2031-02-15',
        'totalUsefulLife' => 10,
        'usefulLife' => 85,
        'yearsRemaining' => 7,
        'warrantyUntil' => '2026-02-15',
        'warrantyExpiration' => '2026-02-15',
        'lastMaintenance' => '2026-02-05',
        'nextMaintenance' => '2026-08-05',
        'underMaintenancePlan' => true,
        'enUso' => false,
        'recalls' => [
            ['id' => 'AV-2024-01', 'agency' => 'ISP', 'priority' => 'Alta', 'description' => 'Falla en software de goteo']
        ],
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCqTY2B_WeZ_djLO8S6euAFUJ1WzmzbMSUGYduj9g59ioholcDXR6um3kCeWy8poItGYaqCnDdViYv97BGdYNx8ucDtglCYRM8Bn0aWVnXsngWQharhj4mTBqGiH58Vdm_g9T9eTm72oulsEa28E5vrELOutWmLu_-yqJAEsNzS1XHxFT7w4-ZP_s112NWvyWajE_qYtSotNUDX_RmK9BRoKh5E1SLr0eiG4u1f8btf5ya4Q9ay-aLE9NzkgsKJHPSZonQn9m3BQ4o'
    ],
    [
        'id' => 'MM-X3-00922',
        'serialNumber' => 'SN-773344-Y',
        'name' => 'Monitor Multiparamétrico',
        'brand' => 'Mindray',
        'model' => 'BeneVision X3',
        'location' => 'Pabellón 03',
        'subLocation' => 'Mesa Anestesia',
        'vendor' => 'Mindray Chile',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE_WITH_OBS',
        'riesgoGE' => 'High Risk',
        'codigoUMDNS' => '12-630',
        'fechaInstalacion' => '2022-08-20',
        'purchasedYear' => 2022,
        'acquisitionCost' => 12000,
        'vencimientoVidaUtil' => '2032-08-20',
        'totalUsefulLife' => 10,
        'usefulLife' => 90,
        'yearsRemaining' => 8,
        'warrantyUntil' => '2025-08-20',
        'warrantyExpiration' => '2025-08-20',
        'lastMaintenance' => '2026-01-15',
        'nextMaintenance' => '2026-07-15',
        'underMaintenancePlan' => true,
        'enUso' => true,
        'recalls' => [],
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'DF-CU-00210',
        'serialNumber' => 'SN-554433-Z',
        'name' => 'Desfibrilador',
        'brand' => 'Zoll',
        'model' => 'R Series',
        'location' => 'Piso 3 - Torre A',
        'subLocation' => 'Carro de Paro',
        'vendor' => 'Medtronic',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'NO_OPERATIVE',
        'riesgoGE' => 'Life Support',
        'codigoUMDNS' => '11-129',
        'fechaInstalacion' => '2020-03-30',
        'purchasedYear' => 2020,
        'acquisitionCost' => 15000,
        'vencimientoVidaUtil' => '2030-03-30',
        'totalUsefulLife' => 10,
        'usefulLife' => 40,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2025-03-30',
        'warrantyExpiration' => '2025-03-30',
        'lastMaintenance' => '2023-12-01',
        'nextMaintenance' => '2024-06-01',
        'underMaintenancePlan' => true,
        'enUso' => false,
        'recalls' => [],
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'ECG-2024-001',
        'serialNumber' => 'SN-ECG-9988',
        'name' => 'Electrocardiógrafo',
        'brand' => 'Philips',
        'model' => 'PageWriter TC70',
        'location' => 'Cardiología',
        'subLocation' => 'Consulta 2',
        'vendor' => 'Philips Medical',
        'ownership' => 'Propio',
        'criticality' => 'RELEVANT',
        'status' => 'OPERATIVE_WITH_OBS',
        'observations' => 'Pantalla con leve parpadeo ocasional.',
        'purchasedYear' => 2023,
        'acquisitionCost' => 5500,
        'totalUsefulLife' => 8,
        'usefulLife' => 60,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2027-01-01',
        'warrantyExpiration' => '2027-01-01',
        'underMaintenancePlan' => true,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE'
    ]

];

// --- FILTERING LOGIC ---
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'ALL';

$filteredAssets = array_filter($assets, function ($asset) use ($searchTerm, $statusFilter) {
    // Search
    $matchSearch = empty($searchTerm) ||
        stripos($asset['name'], $searchTerm) !== false ||
        stripos($asset['brand'], $searchTerm) !== false ||
        stripos($asset['id'], $searchTerm) !== false;

    // Status
    $matchStatus = $statusFilter === 'ALL' || $asset['status'] === $statusFilter;

    return $matchSearch && $matchStatus;
});

?>

<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-white flex items-center gap-4">
                <?= SIDEBAR_INVENTORY ?>
                <span class="text-medical-blue font-light text-2xl tracking-normal opacity-60">(Activos Biomédicos)</span>
            </h1>
            <p class="text-slate-400 mt-2 text-lg">Gestión centralizada de equipamiento clínico y soporte de vida.</p>
        </div>
        <div class="flex items-center gap-4">
            <?php if (canModify()): ?>
                <!-- Export - Hidden for Técnico and Auditor -->
                <button class="h-11 flex items-center gap-3 px-6 bg-slate-700 text-white rounded-2xl hover:bg-slate-600 transition-all font-bold shadow-xl shadow-slate-700/20 active:scale-95">
                    <span class="material-symbols-outlined text-xl">download</span>
                    <span><?= BTN_DOWNLOAD_EXCEL ?></span>
                </button>
            <?php endif; ?>

            <?php if (canModify()): ?>
                <!-- Import/Create - Hidden for Técnico and Auditor -->
                <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                    <input type="file" name="excel_file" id="excel_input" class="hidden" accept=".xlsx, .xls, .csv" onchange="this.form.submit()">
                    <button type="button" onclick="document.getElementById('excel_input').click()" class="h-11 flex items-center gap-3 px-6 bg-excel-green text-white rounded-2xl hover:bg-excel-green/90 transition-all font-bold shadow-xl shadow-excel-green/20 active:scale-95">
                        <span class="material-symbols-outlined text-xl">upload_file</span>
                        <span><?= BTN_UPLOAD_EXCEL ?></span>
                    </button>
                </form>
                <a href="?page=new_asset" class="h-11 flex items-center gap-3 px-8 bg-primary text-white rounded-2xl hover:bg-primary/90 transition-all font-bold shadow-xl shadow-primary/20 active:scale-95 hover:shadow-primary/40">
                    <span class="material-symbols-outlined text-xl">add_box</span>
                    <span><?= BTN_NEW_ASSET ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card-glass p-6 shadow-2xl">
        <form method="GET" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <input type="hidden" name="page" value="inventory">

            <div class="lg:col-span-5 relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-medical-blue transition-colors">search</span>
                <input
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    class="w-full bg-white/5 border border-slate-700/50 rounded-2xl pl-12 pr-6 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all placeholder:text-slate-600 text-white"
                    placeholder="Nombre de equipo, marca o ID inventario..." />
            </div>
            <div class="lg:col-span-3">
                <select name="status" class="w-full bg-white/5 border border-slate-700/50 rounded-2xl px-6 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-white">
                    <option value="ALL">Todos los Estados</option>
                    <option value="<?= STATUS_OPERATIVE ?>" <?= $statusFilter === STATUS_OPERATIVE ? 'selected' : '' ?>>Operativo</option>
                    <option value="<?= STATUS_MAINTENANCE ?>" <?= $statusFilter === STATUS_MAINTENANCE ? 'selected' : '' ?>>En Mantención</option>
                    <option value="<?= STATUS_OPERATIVE_WITH_OBS ?>" <?= $statusFilter === STATUS_OPERATIVE_WITH_OBS ? 'selected' : '' ?>>Operativo con Obs.</option>
                    <option value="<?= STATUS_OUT_OF_SERVICE ?>" <?= $statusFilter === STATUS_OUT_OF_SERVICE ? 'selected' : '' ?>>Fuera de Servicio</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <button type="submit" class="w-full bg-medical-blue/10 text-medical-blue border border-medical-blue/20 rounded-2xl py-3 text-sm font-bold uppercase tracking-wider hover:bg-medical-blue hover:text-white transition-all">
                    Filtrar
                </button>
            </div>
            <div class="lg:col-span-2">
                <a href="?page=inventory" class="w-full flex items-center justify-center gap-2 text-slate-500 hover:text-white transition-colors font-bold text-sm py-3">
                    <span class="material-symbols-outlined text-xl">filter_list_off</span>
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-glass overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-slate-700/50">
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Activo / Modelo</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Criticidad</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Ubicación / Sub</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Proveedor / Garantía</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Plan Mant.</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Pertenencia</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Año / Costo</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Estado</th>
                        <th class="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Vida Útil (Total)</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($filteredAssets)): ?>
                        <tr>
                            <td colspan="9" class="px-8 py-10 text-center text-slate-500">No se encontraron activos.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($filteredAssets as $asset): ?>
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 rounded-xl border border-slate-700/50 overflow-hidden bg-medical-dark p-1 shrink-0 group-hover:scale-110 transition-transform">
                                        <img src="<?= $asset['imageUrl'] ?>" class="w-full h-full object-cover rounded-lg" alt="<?= $asset['name'] ?>">
                                    </div>
                                    <div>
                                        <a href="?page=asset&id=<?= $asset['id'] ?>" class="font-bold text-white hover:text-medical-blue transition-colors block text-base">
                                            <?= $asset['name'] ?>
                                        </a>
                                        <div class="text-xs text-slate-500 mt-1 uppercase font-semibold">
                                            <?= $asset['brand'] ?> <?= $asset['model'] ?> · <span class="font-mono text-medical-blue"><?= $asset['id'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase border <?= $asset['criticality'] === 'CRITICAL' ? 'bg-danger/10 text-danger border-danger/30' : 'bg-medical-blue/10 text-medical-blue border-medical-blue/30' ?>">
                                    <span class="material-symbols-outlined text-[14px] fill-1"><?= $asset['criticality'] === 'CRITICAL' ? 'bolt' : 'clinical_notes' ?></span>
                                    <?= $asset['criticality'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-6">
                                <div class="font-bold text-slate-200 text-sm"><?= $asset['location'] ?></div>
                                <div class="text-[10px] text-slate-500 uppercase mt-0.5 font-bold"><?= $asset['subLocation'] ?? '-' ?></div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="font-bold text-slate-200 text-xs"><?= $asset['vendor'] ?? '-' ?></div>
                                <?php if (!empty($asset['warrantyExpiration'])): ?>
                                    <div class="text-[9px] uppercase mt-0.5 font-bold <?= strtotime($asset['warrantyExpiration']) < time() ? 'text-danger' : 'text-emerald-500' ?>">
                                        Vence: <?= $asset['warrantyExpiration'] ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase border <?= $asset['underMaintenancePlan'] ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30' : 'bg-slate-700/10 text-slate-500 border-slate-700/30' ?>">
                                    <?= $asset['underMaintenancePlan'] ? 'Sí' : 'No' ?>
                                </span>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <div class="font-bold text-slate-200 text-xs"><?= $asset['ownership'] ?? '-' ?></div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <div class="font-bold text-white text-xs"><?= $asset['purchasedYear'] ?? '-' ?></div>
                                <div class="text-[9px] text-medical-blue font-bold mt-0.5">$<?= isset($asset['acquisitionCost']) ? number_format($asset['acquisitionCost']) : '-' ?></div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <?php
                                $statusClass = match ($asset['status']) {
                                    STATUS_OPERATIVE => 'bg-success/10 text-success border-success/30',
                                    STATUS_MAINTENANCE => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
                                    STATUS_OPERATIVE_WITH_OBS => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/30',
                                    STATUS_NO_OPERATIVE => 'bg-red-500/10 text-red-500 border-red-500/30',
                                    default => 'bg-slate-700/10 text-slate-500 border-slate-700/30'
                                };
                                ?>
                                <span class="px-4 py-1.5 rounded-xl text-xs font-black inline-flex items-center gap-2 uppercase tracking-wide border <?= $statusClass ?>">
                                    <?= $asset['status'] === STATUS_OPERATIVE ? 'Operativo' : $asset['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-6">
                                <div class="w-28 mx-auto">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-black text-slate-500"><?= $asset['usefulLife'] ?>%</span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase">(Total: <?= $asset['totalUsefulLife'] ?? '-' ?> a)</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden border border-white/5">
                                        <div class="h-full <?= $asset['usefulLife'] > 50 ? 'bg-success' : 'bg-amber-500' ?>" style="width: <?= $asset['usefulLife'] ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?page=asset&id=<?= $asset['id'] ?>" class="px-4 py-2 bg-medical-blue/10 text-medical-blue rounded-xl hover:bg-medical-blue hover:text-white transition-all border border-medical-blue/20 flex items-center gap-2 text-[10px] font-black uppercase tracking-wider shadow-lg shadow-medical-blue/5" title="Ver Historial y OTs">
                                        <span class="material-symbols-outlined text-sm">history</span>
                                        Historial
                                    </a>
                                    <button class="p-2 text-slate-500 hover:text-white hover:bg-white/5 rounded-xl border border-transparent hover:border-slate-700/50 transition-all">
                                        <span class="material-symbols-outlined text-xl">more_vert</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 bg-white/5 border-t border-slate-700/50 flex items-center justify-between">
            <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">
                Mostrando <?= count($filteredAssets) ?> de <?= count($assets) ?> Activos Biomédicos Registrados
            </p>
            <div class="flex gap-3">
                <button class="px-4 py-2 text-xs font-bold border border-slate-700/50 rounded-xl text-slate-500 hover:bg-white/5 transition-all disabled:opacity-30" disabled>Anterior</button>
                <button class="size-9 text-xs bg-medical-blue text-white rounded-xl font-bold">1</button>
                <button class="px-4 py-2 text-xs font-bold border border-slate-700/50 rounded-xl text-slate-500 hover:bg-white/5 transition-all">Siguiente</button>
            </div>
        </div>
    </div>
</div>