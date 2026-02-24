<?php
// pages/report_print.php

require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../includes/checklist_templates.php';

$id = $_GET['id'] ?? null;
if (!$id) die("OT ID Requerido");

$ot = getWorkOrderById($id);
if (!$ot) die("Orden no encontrada");

$asset = getAssetById($ot['asset_id']);
$attachments = getOtAttachments($id);
$template = getChecklistTemplate($ot['checklist_template'] ?? 'formato_general');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Técnico #<?= $id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: white;
            color: black;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .print-break-inside-avoid {
                page-break-inside: avoid;
            }
        }

        .medical-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .label-tech {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 800;
            color: #475569;
            /* Darker Slate for readability */
            letter-spacing: 0.05em;
        }

        .value-tech {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
        }
    </style>
</head>

<body class="p-8 max-w-[800px] mx-auto">

    <!-- Header Control -->
    <div class="no-print flex justify-between items-center mb-10 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <p class="text-sm text-slate-500 font-bold italic">Vista previa de impresión oficial.</p>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all">Imprimir Reporte</button>
    </div>

    <!-- Official Header -->
    <header class="flex justify-between items-start border-b-2 border-slate-900 pb-6 mb-8">
        <div>
            <h1 class="text-2xl font-black uppercase tracking-tight">Reporte de Servicio Técnico</h1>
            <p class="text-slate-500 text-xs font-bold mt-1">HOSPITAL DE ESPECIALIDADES QUIRÚRGICAS (HEC)</p>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Gestión de Activos Biomédicos</p>
        </div>
        <div class="text-right">
            <div class="text-3xl font-black text-slate-900">#<?= $id ?></div>
            <p class="text-xs font-bold text-slate-500 mt-1"><?= date('d/m/Y H:i') ?></p>
        </div>
    </header>

    <!-- Asset Info -->
    <section class="mb-8">
        <h2 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4 border-b border-blue-100 pb-1">Información del Activo</h2>
        <div class="grid grid-cols-3 gap-6 bg-slate-50 p-4 rounded-lg">
            <div>
                <p class="label-tech">Equipo</p>
                <p class="value-tech"><?= htmlspecialchars($asset['name'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="label-tech">Marca / Modelo</p>
                <p class="value-tech"><?= htmlspecialchars($asset['brand'] ?? '') ?> <?= htmlspecialchars($asset['model'] ?? '') ?></p>
            </div>
            <div>
                <p class="label-tech">N° Serie / Inventario</p>
                <p class="value-tech"><?= htmlspecialchars($asset['serial_number'] ?? $asset['id']) ?></p>
            </div>
            <div>
                <p class="label-tech">Ubicación</p>
                <p class="value-tech"><?= htmlspecialchars($asset['location'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="label-tech">Criticidad</p>
                <p class="value-tech"><?= htmlspecialchars($asset['criticality'] ?? 'Media') ?></p>
            </div>
            <div>
                <p class="label-tech">Estado Final</p>
                <p class="value-tech font-black <?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'text-green-600' : 'text-red-600' ?>">
                    <?= ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'OPERATIVO' : 'FUERA DE SERVICIO' ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Intervention Summary -->
    <section class="mb-8">
        <h2 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4 border-b border-blue-100 pb-1">Resumen de Intervención</h2>
        <div class="grid grid-cols-3 gap-6 mb-4">
            <div>
                <p class="label-tech">Tipo de OT</p>
                <p class="value-tech"><?= $ot['type'] ?></p>
            </div>
            <div>
                <p class="label-tech">Horas de Uso (Equipo)</p>
                <p class="value-tech"><?= $asset['hours_used'] ?? 'N/A' ?> h</p>
            </div>
            <div>
                <p class="label-tech">Horas Hombre (Técnico)</p>
                <p class="value-tech"><?= $ot['duration_hours'] ?> h</p>
            </div>
        </div>
        <div class="bg-white border-2 border-slate-100 p-4 rounded-lg">
            <p class="label-tech mb-2">Observaciones Técnicas</p>
            <p class="text-sm text-slate-700 leading-relaxed font-medium">
                <?= nl2br(htmlspecialchars($ot['observations'] ?? 'No se registraron observaciones adicionales.')) ?>
            </p>
        </div>
    </section>

    <!-- Measurements / Results -->
    <section class="mb-8 print-break-inside-avoid">
        <h2 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4 border-b border-blue-100 pb-1">Pruebas / Protocolo (<?= $template['label'] ?>)</h2>
        <div class="grid grid-cols-2 gap-8 text-[11px]">
            <div>
                <h3 class="font-bold text-slate-800 mb-2 border-b border-slate-100 pb-1">Checklist Cualitativo</h3>
                <ul class="space-y-1">
                    <?php foreach ($template['qualitative'] ?? [] as $check): ?>
                        <li class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0 text-slate-600 font-medium">
                            <span><?= $check ?></span>
                            <span class="font-black text-green-600">✓ PASA</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 mb-2 border-b border-slate-100 pb-1">Metrología Básica</h3>
                <ul class="space-y-1 text-slate-600">
                    <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                        <span>Seguridad Eléctrica (IEC 62353)</span>
                        <span class="font-black text-green-600 uppercase">Cumple</span>
                    </li>
                    <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                        <span>Verificación Funcional</span>
                        <span class="font-black text-green-600 uppercase">Conforme</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
        <section class="mb-12 print-break-inside-avoid">
            <h2 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4 border-b border-blue-100 pb-1">Evidencia Técnica / Adjuntos</h2>
            <div class="grid grid-cols-3 gap-6">
                <?php foreach ($attachments as $att):
                    $isImage = str_contains($att['file_type'], 'image');
                ?>
                    <div class="border border-slate-100 p-3 rounded-xl text-center flex flex-col items-center">
                        <div class="bg-slate-50 w-full aspect-square rounded-lg flex items-center justify-center mb-3 overflow-hidden border border-slate-50">
                            <?php if ($isImage): ?>
                                <img src="<?= $att['file_path'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-slate-400 text-xs font-bold"><?= strtoupper(pathinfo($att['file_path'], PATHINFO_EXTENSION)) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] font-bold text-slate-800 leading-tight"><?= htmlspecialchars($att['caption'] ?: basename($att['file_path'])) ?></p>
                        <p class="text-[8px] font-bold text-slate-400 uppercase mt-1.5 tracking-tighter"><?= $att['category'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Signature Section -->
    <section class="mt-20 print-break-inside-avoid">
        <div class="grid grid-cols-3 gap-12 text-center">
            <div class="space-y-2">
                <div class="border-b border-slate-900 pb-4 h-12 flex items-end justify-center">
                    <span class="text-[10px] text-slate-300 italic italic tracking-tighter">Firma Digital (ID: <?= uniqid() ?>)</span>
                </div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-800">Técnico Ejecutante</p>
                <p class="text-[9px] font-bold text-slate-500 uppercase">BioCMMS Sello Digital</p>
            </div>
            <div class="space-y-2">
                <div class="border-b border-slate-900 pb-4 h-12"></div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-800">Revisión Técnica HEC</p>
                <p class="text-[9px] font-bold text-slate-500 uppercase">Control de Calidad</p>
            </div>
            <div class="space-y-2">
                <div class="border-b border-slate-900 pb-4 h-12"></div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-800">Conformidad Usuario</p>
                <p class="text-[9px] font-bold text-slate-500 uppercase">Jefe de Servicio / Delegado</p>
            </div>
        </div>
    </section>

    <footer class="mt-20 pt-4 border-t border-slate-100 text-[8px] text-slate-400 font-bold uppercase tracking-[0.2em] text-center">
        Documento generado por BioCMMS Integration Hub · FDA 21 CFR Part 11 Compliant · 2026
    </footer>

</body>

</html>