<?php
/**
 * Backend/Exports/generate_ot_pdf.php
 * ─────────────────────────────────────────────────────
 * Endpoint para generación de reporte de OT en PDF.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Providers/PdfReportProvider.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de Orden de Trabajo requerido.");
}

// Generar y servir el PDF
// La función generateReportPDF() en el proveedor ya hace el Output('I') y exit
generateReportPDF($id);
