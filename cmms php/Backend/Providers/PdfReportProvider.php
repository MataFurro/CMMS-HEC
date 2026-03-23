<?php
/**
 * Backend/Providers/PdfReportProvider.php
 * ─────────────────────────────────────────────────────
 * Generador nativo de Reportes PDF usando TCPDF
 * Calidad de Ingeniería Avanzada
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/WorkOrderProvider.php';
require_once __DIR__ . '/AssetProvider.php';
require_once __DIR__ . '/UserProvider.php';
require_once __DIR__ . '/../../includes/checklist_templates.php';

// Cargar TCPDF (asegúrate de que la carpeta TCPDF exista en Backend/Libraries)
$tcpdfPath = __DIR__ . '/../Libraries/TCPDF/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die("Error crítico: La librería TCPDF no está instalada en Backend/Libraries/TCPDF. Ejecute el script de instalación.");
}
require_once($tcpdfPath);

class CMRReportPDF extends \TCPDF {
    public function Header() {
        // Logo o Título Institucional
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(15, 23, 42); // slate-900
        $this->Cell(0, 8, 'HOSPITAL DE ESPECIALIDADES QUIRÚRGICAS (HEC)', 0, 1, 'L');
        
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(100, 116, 139); // slate-500
        $this->Cell(0, 5, 'DEPARTAMENTO DE GESTIÓN BIOMÉDICA', 'B', 1, 'L'); // Borde inferior
        
        // Espaciado dinámico
        $this->Ln(4);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(148, 163, 184); // slate-400
        $this->Cell(0, 10, 'Documento generado nativamente por BioCMMS Integration Hub · FDA 21 CFR Part 11 Compliant · Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 'T', 0, 'C');
    }
}

function generateReportPDF($otId) {
    if (!$otId) die("OT ID Requerido");

    $ot = getWorkOrderById($otId);
    if (!$ot) die("Orden no encontrada");

    $asset = getAssetById($ot['asset_id']);
    
    // Determinar técnico
    $techName = "No asignado";
    if (!empty($ot['assigned_tech_id'])) {
        $tech = getUserById((int)$ot['assigned_tech_id']);
        if ($tech) $techName = $tech['name'];
    }

    // Determinar plantilla
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

    // Inicializar PDF
    $pdf = new CMRReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('BioCMMS System');
    $pdf->SetTitle('Reporte_Tecnico_OT_' . $otId);
    
    // Configuración de márgenes
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    $pdf->AddPage();

    // ─── CABECERA DEL DOCUMENTO ─────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(120, 10, 'REPORTE DE SERVICIO TÉCNICO', 0, 0, 'L');
    
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(37, 99, 235); // blue-600
    $pdf->Cell(0, 10, '#' . $otId, 0, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'Fecha de Emisión: ' . date('d/m/Y H:i'), 0, 1, 'R');
    $pdf->Ln(5);

    // ─── BLOQUE 1: INFORMACIÓN DEL ACTIVO ───────────────────────────────
    $pdf->SetFillColor(241, 245, 249); // slate-100
    $pdf->SetTextColor(15, 23, 42); // slate-900
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, '  1. INFORMACIÓN DEL ACTIVO BIOMÉDICO', 0, 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(100, 116, 139);
    $w = [60, 60, 60];

    $pdf->Cell($w[0], 5, 'EQUIPO', 0, 0, 'L');
    $pdf->Cell($w[1], 5, 'MARCA / MODELO', 0, 0, 'L');
    $pdf->Cell($w[2], 5, 'N° SERIE / INVENTARIO', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(15, 23, 42);
    $brandModel = ($asset['brand'] ?? '') . ' / ' . ($asset['model'] ?? '');
    $serial = $asset['serial_number'] ?? $asset['id'];
    $pdf->Cell($w[0], 6, $asset['name'] ?? 'N/A', 0, 0, 'L');
    $pdf->Cell($w[1], 6, $brandModel, 0, 0, 'L');
    $pdf->Cell($w[2], 6, $serial, 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($w[0], 5, 'UBICACIÓN', 0, 0, 'L');
    $pdf->Cell($w[1], 5, 'CRITICIDAD', 0, 0, 'L');
    $pdf->Cell($w[2], 5, 'ESTADO FINAL', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell($w[0], 6, $asset['location'] ?? 'N/A', 0, 0, 'L');
    $pdf->Cell($w[1], 6, $asset['criticality'] ?? 'Media', 0, 0, 'L');
    
    $statusText = ($ot['final_asset_status'] ?? '') === 'OPERATIVE' ? 'OPERATIVO' : 'FUERA DE SERVICIO';
    if ($statusText === 'OPERATIVO') {
        $pdf->SetTextColor(22, 163, 74); // green-600
    } else {
        $pdf->SetTextColor(220, 38, 38); // red-600
    }
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($w[2], 6, $statusText, 0, 1, 'L');
    $pdf->Ln(5);

    // ─── BLOQUE 2: RESUMEN DE INTERVENCIÓN ──────────────────────────────
    $pdf->SetFillColor(241, 245, 249); 
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, '  2. RESUMEN DE INTERVENCIÓN', 0, 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(60, 5, 'TIPO DE OT', 0, 0, 'L');
    $pdf->Cell(60, 5, 'TÉCNICO EJECUTANTE', 0, 0, 'L');
    $pdf->Cell(60, 5, 'DURACIÓN (HH)', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(60, 6, $ot['type'], 0, 0, 'L');
    $pdf->Cell(60, 6, $techName, 0, 0, 'L');
    $pdf->Cell(60, 6, ($ot['duration_hours'] ?? '0') . ' h', 0, 1, 'L');
    $pdf->Ln(2);

    // Problema Reportado (si viene de Solicitud)
    if (!empty($ot['ms_request_id'])) {
        $db = \Backend\Core\DatabaseService::getInstance();
        $stmt = $db->prepare("SELECT description FROM service_requests WHERE id = ?");
        $stmt->execute([$ot['ms_request_id']]);
        $origDesc = $stmt->fetchColumn();
        
        if ($origDesc) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(0, 5, 'PROBLEMA REPORTADO POR USUARIO', 0, 1, 'L');
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->MultiCell(0, 5, '"' . trim($origDesc) . '"', 0, 'L', false, 1, '', '', true);
            $pdf->Ln(2);
        }
    }

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'DIAGNÓSTICO Y ACCIONES TÉCNICAS', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(15, 23, 42);
    $obs = trim($ot['observations'] ?? '');
    if (empty($obs)) $obs = "No se registraron observaciones adicionales.";
    $pdf->MultiCell(0, 6, $obs, 1, 'L', false, 1, '', '', true);
    $pdf->Ln(5);

    // ─── BLOQUE 3: METROLOGÍA Y PROTOCOLO ───────────────────────────────
    $pdf->SetFillColor(241, 245, 249); 
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, '  3. PRUEBAS / PROTOCOLO (' . mb_strtoupper($template['label'] ?? 'REGISTRO', 'UTF-8') . ')', 0, 1, 'L', true);
    $pdf->Ln(2);

    $savedChecklist = $ot['checklist_data'] ?? [];
    
    // Tabla renderizada con HTML (TCPDF la procesa excelente)
    $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-color:#e2e8f0; font-family:helvetica; font-size:8pt;">';
    
    // Cualitativo
    if (!empty($template['qualitative'])) {
        $html .= '<tr style="background-color:#f8fafc; font-weight:bold;"><td colspan="2">CHECKLIST CUALITATIVO</td></tr>';
        foreach ($template['qualitative'] as $idx => $check) {
            $val = $savedChecklist['qualitative']["q_$idx"] ?? 'na';
            $label = $val === 'pass' ? '<span style="color:#16a34a; font-weight:bold;">PASA</span>' : ($val === 'fail' ? '<span style="color:#dc2626; font-weight:bold;">FALLA</span>' : 'N/A');
            $html .= '<tr><td width="80%">' . htmlspecialchars($check) . '</td><td width="20%" align="center">' . $label . '</td></tr>';
        }
    }

    // Eléctrica / Metrología
    $hasElectric = !empty($template['electrical_safety']);
    $hasQuant = !empty($template['quantitative']);
    if ($hasElectric || $hasQuant) {
        $html .= '<tr style="background-color:#f8fafc; font-weight:bold;"><td colspan="2">PRUEBAS CUANTITATIVAS / SEGURIDAD</td></tr>';
        
        foreach ($template['electrical_safety'] ?? [] as $sIdx => $safety) {
            $val = $savedChecklist['electrical_safety']["es_$sIdx"] ?? '—';
            $html .= '<tr><td width="80%">' . htmlspecialchars($safety['param']) . ' (Esp: ' . htmlspecialchars($safety['expected'] ?? '') . ')</td><td width="20%" align="center"><b>' . htmlspecialchars($val) . '</b></td></tr>';
        }

        foreach ($template['quantitative'] ?? [] as $gIdx => $group) {
            $groupSavedNA = isset($savedChecklist['quantitative']["group_na_$gIdx"]) && $savedChecklist['quantitative']["group_na_$gIdx"] == 'on';
            if ($groupSavedNA) {
                $html .= '<tr><td width="80%">' . htmlspecialchars($group['group']) . '</td><td width="20%" align="center">N/A</td></tr>';
                continue;
            }
            foreach ($group['points'] as $pIdx => $point) {
                $val = $savedChecklist['quantitative']["m_{$gIdx}_{$pIdx}"] ?? '—';
                $html .= '<tr><td width="80%">' . htmlspecialchars($group['group'] . ': ' . $point['simulated']) . htmlspecialchars($group['unit']) . '</td><td width="20%" align="center"><b>' . htmlspecialchars($val) . ' ' . htmlspecialchars($group['unit']) . '</b></td></tr>';
            }
        }
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, false, false, '');

    $pdf->Ln(10);

    // ─── BLOQUE 4: FIRMAS AUTOMÁTICAS ───────────────────────────────
    // Prevenir corte de página si hay firmas
    $y = $pdf->GetY();
    if ($y > 220) {
        $pdf->AddPage();
    }

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(15, 23, 42);
    
    // Celdas invisibles para estructurar 3 columnas
    $sigW = 55;
    
    $pdf->Line(20, $pdf->GetY() + 15, 20 + $sigW - 10, $pdf->GetY() + 15);
    $pdf->Line(80, $pdf->GetY() + 15, 80 + $sigW - 10, $pdf->GetY() + 15);
    $pdf->Line(140, $pdf->GetY() + 15, 140 + $sigW - 10, $pdf->GetY() + 15);
    
    $pdf->SetY($pdf->GetY() + 17);
    $pdf->Cell($sigW, 5, 'TÉCNICO EJECUTANTE', 0, 0, 'C');
    $pdf->Cell($sigW, 5, 'REVISIÓN TÉCNICA (HEC)', 0, 0, 'C');
    $pdf->Cell($sigW, 5, 'CONFORMIDAD USUARIO', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($sigW, 4, mb_strtoupper($techName, 'UTF-8'), 0, 0, 'C');
    $pdf->Cell($sigW, 4, 'Control de Calidad (BioCMMS)', 0, 0, 'C');
    $pdf->Cell($sigW, 4, 'Validación de Servicio', 0, 1, 'C');

    // ─── SALIDA DEL ARCHIVO PDF ──────────────────────────────────────────
    $pdf->Output('Reporte_HEC_OT_' . $otId . '.pdf', 'I');
    exit;
}
