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

// Mock Work Orders for this asset
$workOrders = [
    ['id' => 'OT-2026-001', 'type' => 'Preventivo', 'status' => 'Terminada', 'date' => '2026-01-10', 'tech' => 'Mario Gómez', 'desc' => 'Cambio de filtros y batería'],
    ['id' => 'OT-2026-015', 'type' => 'Correctivo', 'status' => 'En Proceso', 'date' => '2026-02-11', 'tech' => 'Pablo Rojas', 'desc' => 'Falla en sensor de flujo'],
    ['id' => 'OT-2025-089', 'type' => 'Calibración', 'status' => 'Terminada', 'date' => '2025-12-05', 'tech' => 'Ana Muñoz', 'desc' => 'Calibración anual de sensores'],
    ['id' => 'OT-2024-045', 'type' => 'Preventivo', 'status' => 'Terminada', 'date' => '2024-08-15', 'tech' => 'Mario Gómez', 'desc' => 'Mantenimiento preventivo anual 2024'],
];

// Mock Observations
$observations = [
    ['date' => '2026-02-11 14:30', 'author' => 'Ing. Laura', 'text' => 'Falla reportada hoy en el sensor. Se inicia diagnóstico inmediato.', 'type' => 'warning'],
    ['date' => '2026-01-10 09:15', 'author' => 'Téc. Mario Gómez', 'text' => 'Mantenimiento preventivo completado sin novedades.', 'type' => 'normal'],
    ['date' => '2025-12-05 16:45', 'author' => 'Ing. Roberto Jefe', 'text' => 'Calibración 2025 exitosa.', 'type' => 'normal'],
    ['date' => '2024-08-20 11:00', 'author' => 'Téc. Pablo Rojas', 'text' => 'HISTÓRICO: Batería reemplazada en agosto 2024.', 'type' => 'normal'],
];

require_once 'config.php';
require_once 'includes/audit_trail.php';

// Mock Documents
$documents = [
    ['name' => 'Manual_PB840_ES.pdf', 'type' => 'Manual', 'size' => '2.4 MB', 'date' => '2025-01-15'],
    ['name' => 'Certificado_Calibracion_2025.pdf', 'type' => 'Certificado', 'size' => '156 KB', 'date' => '2025-12-05'],
    ['name' => 'Protocolo_Mantenimiento_Preventivo.pdf', 'type' => 'Protocolo', 'size' => '890 KB', 'date' => '2026-01-10'],
    ['name' => 'Foto_Placa_Identificacion.jpg', 'type' => 'Foto', 'size' => '1.2 MB', 'date' => '2025-06-20'],
    ['name' => 'Ficha_Tecnica_Fabricante.pdf', 'type' => 'Ficha Técnica', 'size' => '3.1 MB', 'date' => '2025-01-15'],
];

// Mock Accounting Data
$asset['contabilidad'] = [
    'fechaCompra' => '2020-03-15',
    'fechaInstalacion' => '2020-04-01',
    'costoAdquisicion' => 45000,
    'proveedor' => 'Medtronic Chile',
    'garantiaVence' => '2025-04-01',
    'vidaUtil' => 10, // años
    'anosTranscurridos' => 4,
    'anosRestantes' => 6,
    'depreciacionAnual' => 4500,
    'depreciacionAcumulada' => 18000,
    'valorActual' => 27000,
    'porcentajeVidaUtil' => 40, // % consumido
    'valorResidual' => 4500, // 10% del costo original
    'vencimientoVidaUtil' => '2030-04-01',
    'mantenimiento' => [
        'costoTotal' => 8500,
        'cantidadOT' => 4,
        'promedioPorOT' => 2125
    ]
];

$asset['normativa'] = [
    'riesgoGE' => 'Life Support',
    'codigoUMDNS' => '17-429',
    'estandar' => 'ISO 13485 / FDA 21 CFR Part 11',
    'recalls' => [
        ['id' => 'AV-2026-05', 'fecha' => '2026-01-10', 'agencia' => 'ISP', 'priority' => 'Alta', 'description' => 'Revisión obligatoria de válvula de exhalación']
    ]
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
        <?php if (canModify()): ?>
            <div class="flex gap-3">
                <button class="px-6 py-2 bg-medical-blue text-white rounded-xl font-bold hover:bg-medical-blue/90 shadow-lg shadow-medical-blue/20">
                    Editar Activo
                </button>
            </div>
        <?php endif; ?>
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
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-400">Ubicación</span>
                    <span class="text-xs text-slate-300 font-bold"><?= $asset['location'] ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-400">Serie</span>
                    <span class="text-xs text-slate-300 font-mono"><?= $asset['serialNumber'] ?></span>
                </div>
                <!-- Campos Normativos Sidebar -->
                <div class="pt-2 border-t border-slate-700/50 mt-2 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-slate-400">Riesgo (GE)</span>
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] font-black uppercase">
                            <?= $asset['normativa']['riesgoGE'] ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-slate-400">Cód. UMDNS</span>
                        <span class="text-xs text-slate-300 font-mono"><?= $asset['normativa']['codigoUMDNS'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Alertas de Tecnovigilancia -->
            <?php if (!empty($asset['normativa']['recalls'])): ?>
                <div class="card-glass border-red-500/30 bg-red-500/5 p-6 animate-pulse">
                    <h3 class="text-xs font-black uppercase tracking-widest text-red-500 flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        Tecnovigilancia Activa
                    </h3>
                    <?php foreach ($asset['normativa']['recalls'] as $recall): ?>
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-white"><?= $recall['id'] ?> - <?= $recall['agencia'] ?></p>
                            <p class="text-[10px] text-slate-400 leading-tight"><?= $recall['description'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabs Content -->
        <div class="lg:col-span-2 card-glass p-8">
            <!-- Tab Headers -->
            <div class="flex gap-2 mb-6 border-b border-slate-700 overflow-x-auto">
                <button onclick="switchTab('ot')" id="tab-ot" class="tab-button active px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">assignment</span>
                        Órdenes de Trabajo
                    </span>
                </button>
                <button onclick="switchTab('obs')" id="tab-obs" class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">comment</span>
                        Observaciones
                    </span>
                </button>
                <button onclick="switchTab('docs')" id="tab-docs" class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">folder</span>
                        Documentos
                    </span>
                </button>
                <button onclick="switchTab('cont')" id="tab-cont" class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">account_balance</span>
                        Contabilidad
                    </span>
                </button>
                <button onclick="switchTab('audit')" id="tab-audit" class="tab-button px-6 py-3 font-bold text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">receipt_long</span>
                        Auditoría
                    </span>
                </button>
            </div>

            <!-- Tab Content: Work Orders -->
            <div id="content-ot" class="tab-content">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">history</span>
                    Historial de Órdenes de Trabajo
                </h3>

                <!-- Filtros -->
                <div class="card-glass p-4 mb-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Tipo</label>
                            <select id="filter-tipo" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                                <option value="">Todos</option>
                                <option value="Preventivo">Preventivo</option>
                                <option value="Correctivo">Correctivo</option>
                                <option value="Calibración">Calibración</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Estado</label>
                            <select id="filter-estado" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                                <option value="">Todos</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Terminada">Terminada</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Desde</label>
                            <input type="date" id="filter-desde" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Hasta</label>
                            <input type="date" id="filter-hasta" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-medical-blue">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="applyFilters()" class="px-4 py-2 bg-medical-blue text-white rounded-lg font-bold hover:bg-medical-blue/90 transition-all text-sm uppercase tracking-wider">
                            Filtrar
                        </button>
                        <button onclick="clearFilters()" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg font-bold hover:bg-slate-600 transition-all text-sm uppercase tracking-wider">
                            Limpiar
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" id="ot-table">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">ID</th>
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">Tipo</th>
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">Estado</th>
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">Fecha</th>
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">Técnico</th>
                                <th class="text-left py-3 px-4 text-xs font-black uppercase tracking-wider text-slate-500">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workOrders as $ot): ?>
                                <tr class="ot-row border-b border-slate-800 hover:bg-slate-800/30 transition-colors"
                                    data-tipo="<?= $ot['type'] ?>"
                                    data-estado="<?= $ot['status'] ?>"
                                    data-fecha="<?= $ot['date'] ?>">
                                    <td class="py-4 px-4">
                                        <a href="?page=work_order_execution&id=<?= $ot['id'] ?>" class="text-medical-blue hover:text-medical-blue/80 font-bold text-sm">
                                            <?= $ot['id'] ?>
                                        </a>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="text-sm text-slate-300"><?= $ot['type'] ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($ot['status'] === 'Terminada'): ?>
                                            <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                                                <?= $ot['status'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-bold">
                                                <?= $ot['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-400"><?= $ot['date'] ?></td>
                                    <td class="py-4 px-4 text-sm text-slate-300"><?= $ot['tech'] ?></td>
                                    <td class="py-4 px-4">
                                        <a href="?page=work_order_execution&id=<?= $ot['id'] ?>" class="text-xs font-bold text-medical-blue hover:text-medical-blue/80 uppercase tracking-wider">
                                            Ver Detalles →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Observations -->
            <div id="content-obs" class="tab-content hidden">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">comment</span>
                    Observaciones Técnicas
                </h3>

                <div class="relative pl-4 border-l-2 border-slate-700 space-y-6">
                    <?php foreach ($observations as $obs): ?>
                        <div class="relative pl-6">
                            <?php if ($obs['type'] === 'critical'): ?>
                                <div class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-red-500 border-2 border-red-500 ring-4 ring-slate-900"></div>
                            <?php elseif ($obs['type'] === 'warning'): ?>
                                <div class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-amber-500 border-2 border-amber-500 ring-4 ring-slate-900"></div>
                            <?php else: ?>
                                <div class="absolute -left-[25px] top-0 w-4 h-4 rounded-full bg-slate-900 border-2 border-medical-blue ring-4 ring-slate-900"></div>
                            <?php endif; ?>

                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-slate-500">person</span>
                                    <span class="text-sm font-bold text-white"><?= $obs['author'] ?></span>
                                </div>
                                <span class="text-xs font-bold text-slate-500"><?= $obs['date'] ?></span>
                            </div>

                            <?php if ($obs['type'] === 'critical'): ?>
                                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
                                    <p class="text-sm text-red-300"><?= $obs['text'] ?></p>
                                </div>
                            <?php elseif ($obs['type'] === 'warning'): ?>
                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                                    <p class="text-sm text-amber-300"><?= $obs['text'] ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-slate-300 text-sm"><?= $obs['text'] ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab Content: Documents -->
            <div id="content-docs" class="tab-content hidden">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">folder</span>
                    Documentos del Equipo
                </h3>

                <div class="space-y-3">
                    <?php foreach ($documents as $doc): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-800/30 rounded-xl hover:bg-slate-800/50 transition-all border border-slate-700/50">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-medical-blue/10 border border-medical-blue/20 flex items-center justify-center">
                                    <?php if ($doc['type'] === 'Foto'): ?>
                                        <span class="material-symbols-outlined text-medical-blue">image</span>
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-medical-blue">description</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white"><?= $doc['name'] ?></p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-slate-500 font-bold"><?= $doc['type'] ?></span>
                                        <span class="text-xs text-slate-600">•</span>
                                        <span class="text-xs text-slate-500"><?= $doc['size'] ?></span>
                                        <span class="text-xs text-slate-600">•</span>
                                        <span class="text-xs text-slate-500"><?= $doc['date'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-medical-blue/10 text-medical-blue rounded-lg hover:bg-medical-blue/20 transition-all text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">download</span>
                                Descargar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab Content: Contabilidad -->
            <div id="content-cont" class="tab-content hidden">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-medical-blue">account_balance</span>
                    Información Contable
                </h3>

                <div class="space-y-6">
                    <!-- Adquisición -->
                    <div class="card-glass p-6">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-700 pb-2 mb-4">Adquisición</h4>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Año Compra</span>
                                <p class="text-xl font-bold text-white mt-1"><?= date('Y', strtotime($asset['contabilidad']['fechaCompra'])) ?></p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Fecha Instalación</span>
                                <p class="text-xl font-bold text-emerald-400 mt-1"><?= $asset['contabilidad']['fechaInstalacion'] ?></p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Costo</span>
                                <p class="text-xl font-bold text-medical-blue mt-1">$<?= number_format($asset['contabilidad']['costoAdquisicion']) ?> USD</p>
                            </div>
                            <div class="lg:col-span-2">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Proveedor</span>
                                <p class="text-sm text-slate-300 mt-1"><?= $asset['contabilidad']['proveedor'] ?></p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Trazabilidad de Garantía</span>
                                <p class="text-xs font-bold text-amber-500 mt-1 uppercase">Hasta <?= $asset['contabilidad']['garantiaVence'] ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Análisis de Vida Útil -->
                    <div class="card-glass p-6">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-700 pb-2 mb-4">Análisis de Vida Útil</h4>

                        <!-- Barra de Progreso -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs text-slate-400 font-bold">Vida Útil Consumida</span>
                                <span class="text-xs text-medical-blue font-bold"><?= $asset['contabilidad']['porcentajeVidaUtil'] ?>%</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-medical-blue to-cyan-400 h-3 rounded-full transition-all" style="width: <?= $asset['contabilidad']['porcentajeVidaUtil'] ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-xs text-slate-500"><?= $asset['contabilidad']['anosTranscurridos'] ?> años transcurridos</span>
                                <span class="text-xs text-slate-500"><?= $asset['contabilidad']['anosRestantes'] ?> años restantes</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Vida Útil Total</span>
                                <p class="text-xl font-bold text-white mt-1"><?= $asset['contabilidad']['vidaUtil'] ?> años</p>
                            </div>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Depreciación Anual</span>
                                <p class="text-xl font-bold text-white mt-1">$<?= number_format($asset['contabilidad']['depreciacionAnual']) ?></p>
                            </div>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Depreciación Acumulada</span>
                                <p class="text-xl font-bold text-amber-500 mt-1">$<?= number_format($asset['contabilidad']['depreciacionAcumulada']) ?></p>
                            </div>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Valor Actual</span>
                                <p class="text-xl font-bold text-emerald-500 mt-1">$<?= number_format($asset['contabilidad']['valorActual']) ?></p>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-medical-blue/10 border border-medical-blue/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-medical-blue text-sm">info</span>
                                <span class="text-xs font-bold text-medical-blue uppercase tracking-wider">Valor Residual Estimado</span>
                            </div>
                            <p class="text-lg font-bold text-white">$<?= number_format($asset['contabilidad']['valorResidual']) ?> USD</p>
                        </div>
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-red-500 text-sm">event_busy</span>
                                <span class="text-xs font-bold text-red-500 uppercase tracking-wider">Fin Vida Útil Técnica</span>
                            </div>
                            <p class="text-lg font-bold text-white"><?= $asset['contabilidad']['vencimientoVidaUtil'] ?></p>
                        </div>
                    </div>

                    <!-- Costos de Mantenimiento -->
                    <div class="card-glass p-6">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-700 pb-2 mb-4">Costos de Mantenimiento</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Invertido</span>
                                <p class="text-xl font-bold text-white mt-1">$<?= number_format($asset['contabilidad']['mantenimiento']['costoTotal']) ?></p>
                            </div>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Cantidad OT</span>
                                <p class="text-xl font-bold text-white mt-1"><?= $contabilidad['mantenimiento']['cantidadOT'] ?></p>
                            </div>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Promedio por OT</span>
                                <p class="text-xl font-bold text-white mt-1">$<?= number_format($contabilidad['mantenimiento']['promedioPorOT']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'text-medical-blue', 'border-b-2', 'border-medical-blue');
            btn.classList.add('text-slate-500');
        });

        // Show selected tab content
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Add active class to selected button
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('active', 'text-medical-blue', 'border-b-2', 'border-medical-blue');
        activeBtn.classList.remove('text-slate-500');
    }

    function applyFilters() {
        const tipo = document.getElementById('filter-tipo').value;
        const estado = document.getElementById('filter-estado').value;
        const desde = document.getElementById('filter-desde').value;
        const hasta = document.getElementById('filter-hasta').value;

        const rows = document.querySelectorAll('.ot-row');

        rows.forEach(row => {
            let show = true;

            // Filtro por tipo
            if (tipo && row.dataset.tipo !== tipo) {
                show = false;
            }

            // Filtro por estado
            if (estado && row.dataset.estado !== estado) {
                show = false;
            }

            // Filtro por fecha desde
            if (desde && row.dataset.fecha < desde) {
                show = false;
            }

            // Filtro por fecha hasta
            if (hasta && row.dataset.fecha > hasta) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    function clearFilters() {
        document.getElementById('filter-tipo').value = '';
        document.getElementById('filter-estado').value = '';
        document.getElementById('filter-desde').value = '';
        document.getElementById('filter-hasta').value = '';

        document.querySelectorAll('.ot-row').forEach(row => {
            row.style.display = '';
        });
    }
</script>

<style>
    .tab-button {
        position: relative;
        color: rgb(100 116 139);
    }

    .tab-button.active {
        color: rgb(14 165 233);
    }

    .tab-button:hover {
        color: rgb(14 165 233);
    }
</style>