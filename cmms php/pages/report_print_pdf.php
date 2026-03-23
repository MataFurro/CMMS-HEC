<?php
/**
 * pages/report_print_pdf.php
 * ─────────────────────────────────────────────────────
 * Endpoint para disparar la generación del reporte PDF
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Backend/Providers/PdfReportProvider.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Error: Se requiere el ID de la Orden de Trabajo para generar el PDF.");
}

// Generar y entregar el PDF
// Esta función termina con un exit; y envía el PDF al navegador
generateReportPDF($id);
