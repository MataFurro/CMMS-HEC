<?php
/**
 * pages/view_ot.php - Visualizador Premium de Orden de Trabajo
 * ─────────────────────────────────────────────────────────────────────
 * Vista de solo lectura optimizada para OTs Terminadas o Canceladas.
 * Diseño Glassmorphism, Responsive y Print-Friendly.
 * ─────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../includes/checklist_templates.php';

$ot_id = $_GET['id'] ?? null;
if (!$ot_id) {
    echo "<div class='p-12 text-center'><p class='text-text-muted'>ID de Orden de Trabajo no proporcionado.</p></div>";
    return;
}

$ot = getWorkOrderById($ot_id);
if (!$ot) {
    echo "<div class='p-12 text-center'><p class='text-text-muted'>Orden de Trabajo no encontrada.</p></div>";
    return;
}

$asset = getAssetById($ot['asset_id'] ?? 0);
$attachments = getOtAttachments($ot_id);

// El WorkOrderProvider (via Entity) ya entrega checklist_data como array
$checklist_data = $ot['checklist_data'] ?? [];

$template_key = $ot['checklist_template'] ?? '';
$template = getChecklistTemplate($template_key);

// Clases de estado
$status_classes = [
    'Terminada' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
    'Cancelada' => 'bg-red-500/10 text-red-500 border-red-500/20',
    'En Curso'  => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
    'En Espera' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
];
$st_class = $status_classes[$ot['status']] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20';

// Detección automática si no hay plantilla (Smart Mapping)
if (!$template) {
    $asset_name_lower = mb_strtolower($asset['name'] ?? '', 'UTF-8');
    if (strpos($asset_name_lower, 'ventilador') !== false) $template_key = 'ventilador_mecanico';
    elseif (strpos($asset_name_lower, 'bomba') !== false) $template_key = 'bomba_infusion';
    elseif (strpos($asset_name_lower, 'monitor') !== false) $template_key = 'monitor_signos_vitales';
    elseif (strpos($asset_name_lower, 'electrocardiografo') !== false) $template_key = 'electrocardiografo';
    else $template_key = 'formato_general';
    $template = getChecklistTemplate($template_key);
}

?>

<div class="max-w-[1400px] mx-auto animate-in fade-in slide-in-from-bottom-4 duration-700">
    
    <!-- ── HEADER DE ACCIÓN (No se imprime) ── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 print:hidden">
        <div class="flex items-center gap-4">
            <a href="?page=work_orders" class="w-10 h-10 rounded-full bg-medical-surface border border-[var(--border-color)] flex items-center justify-center text-text-muted hover:text-medical-blue hover:border-medical-blue transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-black text-text-main flex items-center gap-3">
                    <?= $ot_id ?>
                    <span class="px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest <?= $st_class ?>">
                        <?= $ot['status'] ?>
                    </span>
                </h1>
                <p class="text-xs text-text-muted font-bold uppercase tracking-tighter">Visualización de Registro Histórico • <?= $template['label'] ?? 'Genérico' ?></p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="Backend/Exports/generate_ot_pdf.php?id=<?= $ot_id ?>" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-medical-blue/20 hover:scale-[1.02] active:scale-95 transition-all">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                Descargar Reporte PDF
            </a>
            <button onclick="window.print()" class="flex items-center gap-2 px-6 py-3 bg-medical-surface border border-[var(--border-color)] text-text-main rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-medical-blue/10 transition-all">
                <span class="material-symbols-outlined text-sm">print</span>
                Imprimir
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- ── COLUMNA IZQUIERDA: RESUMEN Y ACTIVO ── -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- TARJETA DEL ACTIVO -->
            <div class="card-glass p-6 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-32 h-32 bg-medical-blue/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                
                <h3 class="text-xs font-black uppercase tracking-widest text-medical-blue mb-6 border-b border-medical-blue/10 pb-3">Información del Activo</h3>
                
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-medical-blue/10 text-medical-blue flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-3xl"><?= $template['icon'] ?? 'precision_manufacturing' ?></span>
                    </div>
                    <div>
                        <p class="text-lg font-black text-text-main leading-tight"><?= htmlspecialchars($asset['name'] ?? 'Equipo Desconocido') ?></p>
                        <p class="text-xs text-text-muted font-bold uppercase tracking-wider"><?= htmlspecialchars($asset['model'] ?? '-') ?> • <?= htmlspecialchars($asset['brand'] ?? '-') ?></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-[var(--border-color)]/50">
                        <span class="text-[10px] font-black uppercase text-text-muted tracking-widest">ID Inventario</span>
                        <span class="text-sm font-mono font-bold text-text-main"><?= $asset['inventory_id'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-[var(--border-color)]/50">
                        <span class="text-[10px] font-black uppercase text-text-muted tracking-widest">Serie N°</span>
                        <span class="text-sm font-bold text-text-main"><?= $asset['serial_number'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-[var(--border-color)]/50">
                        <span class="text-[10px] font-black uppercase text-text-muted tracking-widest">Ubicación</span>
                        <span class="text-sm font-bold text-medical-blue"><?= $asset['location'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-[10px] font-black uppercase text-text-muted tracking-widest">Criticidad</span>
                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-500 text-[10px] font-black"><?= $asset['criticality'] ?? 'Media' ?></span>
                    </div>
                </div>
            </div>

            <!-- DETALLES DE LA ORDEN -->
            <div class="card-glass p-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-medical-blue mb-6 border-b border-medical-blue/10 pb-3">Detalles de Ejecución</h3>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[9px] font-black uppercase text-text-muted tracking-widest mb-1">Tipo de Servicio</p>
                            <p class="text-sm font-bold text-text-main"><?= $ot['type'] ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black uppercase text-text-muted tracking-widest mb-1">Prioridad</p>
                            <p class="text-sm font-bold text-text-main"><?= $ot['priority'] ?></p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[var(--border-color)]/50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-medical-blue/10 text-medical-blue flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">engineering</span>
                            </div>
                            <div>
                                <p class="text-[9px] font-black uppercase text-text-muted tracking-widest">Técnico Asignado</p>
                                <p class="text-sm font-bold text-text-main"><?= htmlspecialchars($ot['tech_name'] ?? 'No asignado') ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500/10 text-blue-500 flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">event</span>
                            </div>
                            <div>
                                <p class="text-[9px] font-black uppercase text-text-muted tracking-widest">Fecha Cierre</p>
                                <p class="text-sm font-bold text-text-main"><?= date('d/m/Y', strtotime($ot['completed_date'] ?? $ot['created_date'])) ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($ot['duration_hours'])): ?>
                    <div class="p-4 rounded-xl bg-medical-blue/5 border border-medical-blue/10">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-medical-blue tracking-widest">Tiempo Invertido</span>
                            <span class="text-lg font-black text-medical-blue"><?= $ot['duration_hours'] ?> hrs</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: RESULTADOS Y EVIDENCIA ── -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- SECCIÓN 1: PROTOCOLO TÉCNICO COMPLETO -->
            <div class="card-glass p-8">
                <h2 class="text-lg font-black text-text-main mb-8 flex items-center gap-3">
                    <span class="material-symbols-outlined text-medical-blue font-variation-fill">fact_check</span>
                    Protocolo de Inspeccion y Metrología
                    <span class="text-[9px] font-black text-text-muted ml-auto bg-medical-surface px-2 py-1 rounded border border-[var(--border-color)] uppercase tracking-widest">Norma BioCMMS 4.5</span>
                </h2>

                <?php if (empty($checklist_data)): ?>
                    <div class="py-12 text-center opacity-50 bg-medical-surface rounded-3xl border border-dashed border-[var(--border-color)]">
                        <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                        <p class="text-sm font-bold italic">No se registraron datos técnicos en el checklist para esta orden.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- A. INSPECCIÓN CUALITATIVA (MAPEADA) -->
                    <?php 
                    $quali_items = $template['qualitative'] ?? [];
                    if (!empty($quali_items)): 
                    ?>
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-6 border-b border-[var(--border-color)] pb-3">
                            <span class="material-symbols-outlined text-medical-blue text-sm">visibility</span>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Inspección Cualitativa</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($quali_items as $idx => $label): 
                                // Buscar valor por ID de la plantilla o por label exacto
                                $stored_val = $checklist_data['qualitative']["q_$idx"] ?? ($checklist_data['qualitative'][$label] ?? 'N/E');
                                $val_norm = strtolower($stored_val);
                                $is_pass = ($val_norm === 'pasa' || $val_norm === 'pass' || $val_norm === 'ok');
                                $is_fail = ($val_norm === 'falla' || $val_norm === 'fail');
                                $is_na = ($val_norm === 'na' || $val_norm === 'n/a');
                                
                                $badge_class = $is_pass ? 'bg-emerald-500/10 text-emerald-500' : ($is_fail ? 'bg-red-500/10 text-red-500' : 'bg-slate-500/10 text-slate-500');
                                $icon = $is_pass ? 'check_circle' : ($is_fail ? 'cancel' : 'do_not_disturb_on');
                            ?>
                            <div class="flex items-center justify-between p-3.5 rounded-2xl border border-[var(--border-color)] bg-medical-surface/40 hover:bg-medical-surface transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-sm <?= $is_pass ? 'text-emerald-500' : ($is_fail ? 'text-red-500' : 'text-slate-400') ?>"><?= $icon ?></span>
                                    <span class="text-xs font-bold text-text-main leading-tight"><?= htmlspecialchars($label) ?></span>
                                </div>
                                <span class="shrink-0 px-2.5 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest <?= $badge_class ?>">
                                    <?= ($is_pass ? 'PASA' : ($is_fail ? 'FALLA' : ($is_na ? 'N/A' : 'N/E'))) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- B. MEDICIONES TÉCNICAS (METROLOGÍA POR GRUPOS) -->
                    <?php 
                    $quant_groups = $template['quantitative'] ?? [];
                    if (!empty($quant_groups)): 
                    ?>
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-6 border-b border-[var(--border-color)] pb-3">
                            <span class="material-symbols-outlined text-blue-500 text-sm">metrology</span>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Metrología y Parámetros Funcionales</h4>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($quant_groups as $gIdx => $group): ?>
                            <div class="rounded-3xl border border-[var(--border-color)] overflow-hidden bg-medical-surface/20">
                                <div class="bg-blue-500/5 px-5 py-3 border-b border-[var(--border-color)] flex justify-between items-center">
                                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest"><?= htmlspecialchars($group['group']) ?></span>
                                    <span class="text-[9px] font-bold text-text-muted px-2 py-0.5 bg-white/50 rounded-full border border-[var(--border-color)]">TOL: <?= $group['tolerance_label'] ?? '±5%' ?></span>
                                </div>
                                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php 
                                    $points = $group['points'] ?? [];
                                    foreach ($points as $pIdx => $point): 
                                        $measured = $checklist_data['quantitative']["n_{$gIdx}_{$pIdx}"] ?? '-';
                                    ?>
                                    <div class="flex flex-col p-3 rounded-2xl bg-white/40 border border-[var(--border-color)]/50">
                                        <p class="text-[9px] font-black text-text-muted uppercase tracking-tighter mb-2">Simulado: <?= $point['simulated'] ?? 'Ref' ?> <?= $group['unit'] ?? '' ?></p>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-sm font-black text-text-main"><?= $measured ?></span>
                                            <span class="text-[10px] font-bold text-blue-500 uppercase"><?= $group['unit'] ?? '' ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- C. SEGURIDAD ELÉCTRICA (IEC 62353) -->
                    <?php 
                    $elec_items = $template['electrical_safety'] ?? [];
                    if (!empty($elec_items)): 
                    ?>
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-6 border-b border-[var(--border-color)] pb-3">
                            <span class="material-symbols-outlined text-red-500 text-sm">bolt</span>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Pruebas Seguridad Eléctrica (IEC 62353)</h4>
                        </div>
                        <div class="overflow-hidden rounded-3xl border border-[var(--border-color)]">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-red-500/5 text-[9px] font-black uppercase tracking-widest text-text-muted">
                                    <tr>
                                        <th class="p-4">Parámetro</th>
                                        <th class="p-4">Límite</th>
                                        <th class="p-4">Medido</th>
                                        <th class="p-4 text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--border-color)] bg-medical-surface/20">
                                    <?php foreach ($elec_items as $sIdx => $safety): 
                                        $val = $checklist_data['electrical_safety']["es_$sIdx"] ?? ($checklist_data['electrical_safety'][$safety['param']] ?? '-');
                                    ?>
                                    <tr class="text-xs hover:bg-white/50 transition-colors">
                                        <td class="p-4 font-bold text-text-main"><?= $safety['param'] ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 bg-red-500/10 text-red-500 rounded font-black text-[9px]"><?= $safety['expected'] ?></span></td>
                                        <td class="p-4 font-mono font-black text-text-main"><?= $val ?></td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5 text-emerald-500 font-black text-[9px] uppercase">
                                                <span class="material-symbols-outlined text-sm">verified</span> CONFORME
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- SECCIÓN 2: OBSERVACIONES Y CONCLUSIÓN -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="card-glass p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="material-symbols-outlined text-text-muted text-sm italic">description</span>
                        <h3 class="text-xs font-black uppercase tracking-widest text-text-muted italic">Reporte de Servicio</h3>
                    </div>
                    <p class="text-sm text-text-main leading-relaxed italic bg-black/[0.02] dark:bg-white/[0.02] p-5 rounded-3xl border border-[var(--border-color)]/30 min-h-[120px]">
                        "<?= !empty($ot['observations']) ? nl2br(htmlspecialchars($ot['observations'])) : 'El técnico no registró observaciones adicionales en el informe final.' ?>"
                    </p>
                </div>

                <div class="card-glass p-8 border-l-4 border-medical-blue relative">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-medical-blue text-sm">verified_user</span>
                        <h3 class="text-xs font-black uppercase tracking-widest text-text-muted italic">Dictamen de Ingeniería</h3>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center <?= ($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-red-500 text-white shadow-red-500/30' ?> shadow-xl">
                            <span class="material-symbols-outlined text-3xl font-variation-fill"><?= ($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'check_circle' : 'warning' ?></span>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted font-black uppercase tracking-[0.1em]">Estado Final del Activo</p>
                            <p class="text-2xl font-black tracking-tight <?= ($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'text-emerald-500' : 'text-red-500' ?>">
                                <?= ($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'OPERATIVO' : 'FUERA DE SERVICIO' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: EVIDENCIA FOTOGRÁFICA -->
            <?php if (!empty($attachments)): ?>
            <div class="card-glass p-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-medical-blue mb-8 border-b border-medical-blue/10 pb-3">Registro Fotográfico / Evidencia técnica</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <?php foreach ($attachments as $att): ?>
                        <div class="group relative rounded-3xl overflow-hidden border border-[var(--border-color)] bg-medical-dark aspect-square shadow-sm hover:shadow-2xl transition-all cursor-zoom-in">
                            <img src="<?= $att['file_path'] ?>" alt="Evidencia OT" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-5">
                                <p class="text-[10px] font-black text-medical-blue uppercase tracking-widest"><?= htmlspecialchars($att['category'] ?? 'Evidencia') ?></p>
                                <p class="text-xs text-white font-bold mb-1"><?= htmlspecialchars($att['caption'] ?? 'Captura de inspección') ?></p>
                                <p class="text-[8px] text-white/50 italic"><?= date('d/m/Y H:i', strtotime($att['uploaded_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── SECCIÓN DE FIRMAS (Sólo Impresión) ── -->
            <div class="hidden print:grid grid-cols-2 gap-20 mt-24 text-center px-12">
                <div class="space-y-4">
                    <div class="border-b-2 border-slate-900 w-full h-24 flex items-end justify-center pb-2">
                        <span class="text-[10px] text-slate-400 italic">Espacio para timbre y firma</span>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest">Ingeniería Clínica / Técnico</p>
                        <p class="text-[10px] font-bold text-slate-600 mt-1"><?= htmlspecialchars($ot['tech_name'] ?? '-') ?></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="border-b-2 border-slate-900 w-full h-24 flex items-end justify-center pb-2">
                        <span class="text-[10px] text-slate-400 italic">Espacio para recepción clínica</span>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest">Conformidad Usuario Responsable</p>
                        <p class="text-[10px] font-bold text-slate-600 mt-1"><?= htmlspecialchars($ot['handover_confirmed_by'] ?? 'Pendiente de firma') ?></p>
                    </div>
                </div>
            </div>

            <p class="hidden print:block text-center text-[7px] text-slate-400 mt-16 uppercase tracking-[0.3em]">
                Certificación BioCMMS Intelligence v4.5 • Documento Electrónico Original • Generado: <?= date('d/m/Y H:i') ?>
            </p>

        </div>
    </div>
</div>

<style>
    @media print {
        body { background: white !important; color: black !important; padding: 0 !important; font-size: 12px; }
        .card-glass { background: white !important; border: 1.5px solid #e2e8f0 !important; box-shadow: none !important; margin-bottom: 2rem !important; border-radius: 12px !important; }
        .max-w-\[1400px\] { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 1cm !important; }
        .grid { display: block !important; }
        .lg\:col-span-12, .lg\:col-span-4, .lg\:col-span-8 { width: 100% !important; margin-bottom: 2rem !important; }
        .bg-medical-blue\/5, .bg-medical-blue\/10, .bg-blue-500\/5, .bg-black\/5, .bg-emerald-500\/10, .bg-red-500\/5 { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; color: black !important; }
        h1, h2, h3, h4 { color: black !important; }
        .text-medical-blue, .text-blue-500, .text-emerald-500, .text-red-500, .text-blue-600 { color: #0f172a !important; }
        .animate-in { animation: none !important; }
        img { max-height: 250px; width: auto; margin: 0 auto; border-radius: 8px !important; }
        table { border-radius: 0 !important; }
        th { background: #f1f5f9 !important; border-bottom: 2px solid #cbd5e1 !important; color: black !important; }
    }
</style>
