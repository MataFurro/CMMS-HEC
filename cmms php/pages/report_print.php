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

$templateKey = $ot['checklist_template'] ?? null;
if (!$templateKey) {
    $assetName = mb_strtolower($asset['name'] ?? '', 'UTF-8');
    if (strpos($assetName, 'ventilador') !== false) {
        $templateKey = 'ventilador_mecanico';
    } elseif (strpos($assetName, 'bomba de infus') !== false) {
        $templateKey = 'bomba_infusion';
    } elseif (strpos($assetName, 'desfibrilador') !== false) {
        $templateKey = 'monitor_desfibrilador';
    } elseif (strpos($assetName, 'electrocardiógrafo') !== false || strpos($assetName, 'electrocardiografo') !== false) {
        $templateKey = 'electrocardiografo';
    } elseif (strpos($assetName, 'monitor') !== false) {
        $templateKey = 'monitor_signos_vitales';
    } else {
        $templateKey = 'formato_general';
    }
}
$template = getChecklistTemplate($templateKey);
if (!$template) {
    $template = getChecklistTemplate('formato_general');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Técnico #<?= $id ?></title>
    <script src="assets/vendor/tailwind.min.js"></script>
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
        <div class="flex gap-3">
            <a href="?page=report_print_pdf&id=<?= $id ?>" target="_blank" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-emerald-700 transition-all flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Exportar PDF
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all">Imprimir Reporte</button>
        </div>
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
        <?php $savedChecklist = $ot['checklist_data'] ?? []; ?>
        <div class="grid grid-cols-2 gap-8 text-[11px]">
            <div>
                <h3 class="font-bold text-slate-800 mb-2 border-b border-slate-100 pb-1">Checklist Cualitativo</h3>
                <ul class="space-y-1">
                    <?php foreach ($template['qualitative'] ?? [] as $idx => $check):
                        $val = $savedChecklist['qualitative']["q_$idx"] ?? 'na';
                        $color = $val === 'pass' ? 'text-green-600' : ($val === 'fail' ? 'text-red-600' : 'text-slate-400');
                        $label = $val === 'pass' ? '✓ PASA' : ($val === 'fail' ? '✗ FALLA' : '- N/A');
                    ?>
                        <li class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0 text-slate-600 font-medium">
                            <span class="pr-4"><?= $check ?></span>
                            <span class="font-black <?= $color ?> text-right whitespace-nowrap"><?= $label ?></span>
                        </li>
                    <?php endforeach; ?>

                    <?php
                    // Custom Qualitative Checks
                    foreach (($savedChecklist['qualitative'] ?? []) as $k => $v) {
                        if (str_starts_with($k, 'q_custom_label_')) {
                            $id = str_replace('q_custom_label_', '', $k);
                            $val = $savedChecklist['qualitative']["q_custom_val_$id"] ?? 'na';
                            $color = $val === 'pass' ? 'text-green-600' : ($val === 'fail' ? 'text-red-600' : 'text-slate-400');
                            $label = $val === 'pass' ? '✓ PASA' : ($val === 'fail' ? '✗ FALLA' : '- N/A');
                    ?>
                            <li class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0 text-slate-600 font-medium">
                                <span class="pr-4"><?= htmlspecialchars($v) ?> <span class="text-[7px] bg-slate-100 text-slate-400 px-1 rounded ml-1 uppercase tracking-widest">Adicional</span></span>
                                <span class="font-black <?= $color ?> text-right whitespace-nowrap"><?= $label ?></span>
                            </li>
                    <?php
                        }
                    }
                    ?>
                </ul>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 mb-2 border-b border-slate-100 pb-1">Metrología Básica</h3>
                <ul class="space-y-1 text-slate-600">
                    <?php foreach ($template['electrical_safety'] ?? [] as $sIdx => $safety):
                        $val = $savedChecklist['electrical_safety']["es_$sIdx"] ?? '—';
                    ?>
                        <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                            <span class="pr-4"><?= $safety['param'] ?> (<?= htmlspecialchars($safety['expected']) ?>)</span>
                            <span class="font-black text-slate-800 text-right whitespace-nowrap"><?= htmlspecialchars($val) ?></span>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($template['quantitative'] ?? [] as $gIdx => $group):
                        $groupSavedNA = isset($savedChecklist['quantitative']["group_na_$gIdx"]) && $savedChecklist['quantitative']["group_na_$gIdx"] == 'on';
                        if ($groupSavedNA) {
                    ?>
                            <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                                <span class="pr-4"><?= $group['group'] ?></span>
                                <span class="font-black text-slate-400 text-right whitespace-nowrap">- N/A</span>
                            </li>
                        <?php
                            continue;
                        }
                        foreach ($group['points'] as $pIdx => $point):
                            $val = $savedChecklist['quantitative']["m_{$gIdx}_{$pIdx}"] ?? '—';
                        ?>
                            <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                                <span class="pr-4"><?= $group['group'] ?>: <?= $point['simulated'] ?><?= $group['unit'] ?></span>
                                <span class="font-black text-slate-800 text-right whitespace-nowrap"><?= htmlspecialchars($val) ?> <?= $group['unit'] ?></span>
                            </li>
                    <?php
                        endforeach;
                    endforeach;
                    ?>

                    <?php
                    // Custom Quantitative Checks
                    foreach (($savedChecklist['quantitative'] ?? []) as $k => $v) {
                        if (str_starts_with($k, 'm_custom_label_')) {
                            $id = str_replace('m_custom_label_', '', $k);
                            $val = $savedChecklist['quantitative']["m_custom_val_$id"] ?? '—';
                    ?>
                            <li class="flex items-center justify-between py-1 border-b border-slate-50 font-medium">
                                <span class="pr-4"><?= htmlspecialchars($v) ?> <span class="text-[7px] bg-slate-100 text-slate-400 px-1 rounded ml-1 uppercase tracking-widest">Adicional</span></span>
                                <span class="font-black text-slate-800 text-right whitespace-nowrap"><?= htmlspecialchars($val) ?></span>
                            </li>
                    <?php
                        }
                    }
                    ?>
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