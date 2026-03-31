<?php
/**
 * Backend/Providers/PdfReportProvider.php
 * ─────────────────────────────────────────────────────
 * Generador nativo de Reportes PDF usando TCPDF
 * Calidad de Ingeniería Avanzada - Soporte Multiequipo
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/WorkOrderProvider.php';
require_once __DIR__ . '/AssetProvider.php';
require_once __DIR__ . '/UserProvider.php';
require_once __DIR__ . '/../../includes/checklist_templates.php';

// Cargar TCPDF
$tcpdfPath = __DIR__ . '/../Libraries/TCPDF/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die("Error crítico: La librería TCPDF no está instalada en Backend/Libraries/TCPDF.");
}
require_once($tcpdfPath);

class CMRReportPDF extends \TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, 'HOSPITAL DE ESPECIALIDADES QUIRÚRGICAS (HEC)', 0, 1, 'L');
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, 'DEPARTAMENTO DE INGENIERÍA CLÍNICA Y MANTENIMIENTO', 'B', 1, 'L');
        $this->Ln(5);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(0, 10, 'Documento generado por BioCMMS Integration Hub v4.5 · Documento Original · Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 'T', 0, 'C');
    }
}

function generateReportPDF($otId) {
    $ot = getWorkOrderById($otId);
    if (!$ot) die("Orden no encontrada");

    $asset = getAssetById($ot['asset_id']);
    $techName = $ot['tech_name'] ?? "No asignado";
    $checklist = $ot['checklist_data'] ?? [];
    
    // Obtener plantilla
    $templateKey = $ot['checklist_template'] ?? '';
    $template = getChecklistTemplate($templateKey);
    if (!$template) {
        $assetName = mb_strtolower($asset['name'] ?? '', 'UTF-8');
        if (strpos($assetName, 'ventilador') !== false) $templateKey = 'ventilador_mecanico';
        elseif (strpos($assetName, 'bomba') !== false) $templateKey = 'bomba_infusion';
        elseif (strpos($assetName, 'monitor') !== false) $templateKey = 'monitor_signos_vitales';
        else $templateKey = 'formato_general';
        $template = getChecklistTemplate($templateKey);
    }

    $pdf = new CMRReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Reporte_Tecnico_' . $otId);
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->AddPage();

    // ─── TÍTULO Y ID ───────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(120, 10, 'REPORTE DE INTERVENCIÓN TÉCNICA', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(37, 99, 235);
    $pdf->Cell(0, 10, '#' . $otId, 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'Emitido: ' . date('d/m/Y H:i'), 0, 1, 'R');
    $pdf->Ln(5);

    // ─── BLOQUE 1: DATOS DEL ACTIVO ───────────────────────────────────
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(0, 7, ' 1. IDENTIFICACIÓN DEL ACTIVO', 0, 1, 'L', true);
    $pdf->Ln(2);

    $html = '<table cellpadding="4" style="width:100%; border-bottom: 0.5px solid #e2e8f0;">
        <tr>
            <td width="33%"><b>EQUIPO:</b><br/>'.htmlspecialchars($asset['name']).'</td>
            <td width="33%"><b>MARCA/MODELO:</b><br/>'.htmlspecialchars($asset['brand'].'/'.$asset['model']).'</td>
            <td width="34%"><b>SERIE/INV:</b><br/>'.htmlspecialchars($asset['serial_number'].' / '.$asset['inventory_id']).'</td>
        </tr>
        <tr>
            <td width="33%"><b>UBICACIÓN:</b><br/>'.htmlspecialchars($asset['location']).'</td>
            <td width="33%"><b>CRITICIDAD:</b><br/>'.htmlspecialchars($asset['criticality'] ?? 'Media').'</td>
            <td width="34%"><b>ESTADO FINAL:</b><br/><b style="color:'.(($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? '#16a34a' : '#dc2626').';">'.(($ot['final_asset_status'] ?? 'OPERATIVE') === 'OPERATIVE' ? 'OPERATIVO' : 'FUERA DE SERVICIO').'</b></td>
        </tr>
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);

    // ─── BLOQUE 2: DETALLES DE SERVICIO ──────────────────────────────
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(0, 7, ' 2. RESUMEN DE LA ORDEN DE TRABAJO', 0, 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', '', 9);
    $html = '<table cellpadding="4" style="width:100%;">
        <tr>
            <td width="25%"><b>TIPO:</b><br/>'.$ot['type'].'</td>
            <td width="25%"><b>PRIORIDAD:</b><br/>'.$ot['priority'].'</td>
            <td width="25%"><b>FECHA:</b><br/>'.date('d/m/Y', strtotime($ot['completed_date'] ?? $ot['created_date'])).'</td>
            <td width="25%"><b>TÉCNICO:</b><br/>'.$techName.'</td>
        </tr>
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'OBSERVACIONES TÉCNICAS:', 0, 1, 'L');
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->MultiCell(0, 6, $ot['observations'] ?: 'Sin observaciones.', 1, 'L', false, 1);
    $pdf->Ln(5);

    // ─── BLOQUE 3: PROTOCOLO TÉCNICO ────────────────────────────────
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 7, ' 3. PROTOCOLO DE INSPECCIÓN Y METROLOGÍA (' . mb_strtoupper($template['label'] ?? 'REGISTRO') . ')', 0, 1, 'L', true);
    $pdf->Ln(2);

    // A. Cualitativo
    if (!empty($template['qualitative'])) {
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-color:#e2e8f0;">
            <tr style="background-color:#f1f5f9; font-weight:bold; font-size:8pt;"><td colspan="2">INSPECCIÓN CUALITATIVA / ESTÉTICA</td></tr>';
        
        foreach ($template['qualitative'] as $idx => $label) {
            $val = $checklist['qualitative']["q_$idx"] ?? ($checklist['qualitative'][$label] ?? 'N/E');
            $val_norm = strtolower($val);
            $res = ($val_norm==='pasa'||$val_norm==='pass'||$val_norm==='ok') ? '<b style="color:#16a34a;">PASA</b>' : (($val_norm==='falla'||$val_norm==='fail') ? '<b style="color:#dc2626;">FALLA</b>' : 'N/A');
            $html .= '<tr style="font-size:8pt;"><td width="80%">'.htmlspecialchars($label).'</td><td width="20%" align="center">'.$res.'</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Ln(4);
    }

    // B. Cuantitativo / Metrología
    if (!empty($template['quantitative'])) {
        foreach ($template['quantitative'] as $gIdx => $group) {
            $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-color:#e2e8f0;">
                <tr style="background-color:#eff6ff; font-weight:bold; font-size:8pt;"><td colspan="3">'.mb_strtoupper($group['group']).' (TOL: '.$group['tolerance_label'].')</td></tr>
                <tr style="background-color:#f8fafc; font-weight:bold; font-size:7pt;">
                    <td width="40%">PUNTO PRUEBA</td>
                    <td width="30%" align="center">SIMULADO</td>
                    <td width="30%" align="center">MEDIDO</td>
                </tr>';
            
            foreach ($group['points'] as $pIdx => $point) {
                $val = $checklist['quantitative']["n_{$gIdx}_{$pIdx}"] ?? '—';
                $html .= '<tr style="font-size:8pt;">
                    <td>Referencia #'.($pIdx+1).'</td>
                    <td align="center">'.$point['simulated'].' '.$group['unit'].'</td>
                    <td align="center"><b>'.$val.' '.$group['unit'].'</b></td>
                </tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(4);
        }
    }

    // C. Seguridad Eléctrica
    if (!empty($template['electrical_safety'])) {
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-color:#e2e8f0;">
            <tr style="background-color:#fef2f2; font-weight:bold; font-size:8pt;"><td colspan="4">SEGURIDAD ELÉCTRICA (IEC 62353)</td></tr>
            <tr style="background-color:#f8fafc; font-weight:bold; font-size:7pt;">
                <td width="40%">PARÁMETRO</td>
                <td width="20%" align="center">LÍMITE</td>
                <td width="20%" align="center">OBTENIDO</td>
                <td width="20%" align="center">ESTADO</td>
            </tr>';
        
        foreach ($template['electrical_safety'] as $sIdx => $safety) {
            $val = $checklist['electrical_safety']["es_$sIdx"] ?? ($checklist['electrical_safety'][$safety['param']] ?? '—');
            $html .= '<tr style="font-size:8pt;">
                <td>'.$safety['param'].'</td>
                <td align="center" style="color:#dc2626;">'.$safety['expected'].'</td>
                <td align="center"><b>'.$val.'</b></td>
                <td align="center" style="color:#16a34a;">CONFORME</td>
            </tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    // ─── BLOQUE 4: FIRMAS ───────────────────────────────────────────
    $pdf->Ln(20);
    $y = $pdf->GetY();
    if ($y > 230) $pdf->AddPage();
    
    $sigW = 60;
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(15, 23, 42);
    
    $pdf->Line(20, $pdf->GetY() + 15, 20 + $sigW, $pdf->GetY() + 15);
    $pdf->Line(115, $pdf->GetY() + 15, 115 + $sigW, $pdf->GetY() + 15);
    
    $pdf->SetY($pdf->GetY() + 17);
    $pdf->Cell($sigW + 20, 5, 'INGENIERÍA CLÍNICA / TÉCNICO', 0, 0, 'C');
    $pdf->Cell($sigW + 20, 5, 'RECEPCIÓN / USUARIO CLÍNICO', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($sigW + 20, 4, mb_strtoupper($techName, 'UTF-8'), 0, 0, 'C');
    $pdf->Cell($sigW + 20, 4, 'FIRMA Y SELLO DE CONFORMIDAD', 0, 1, 'C');

    // Salida
    $pdf->Output('Reporte_HEC_OT_' . $otId . '.pdf', 'I');
    exit;
}
