<?php

/**
 * run_migration_clinical_fields.php
 * Migración para añadir campos de gestión biomédica profesional y acreditación.
 */

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

echo "⚙️ Iniciando migración de campos clínicos (Acreditación Hospitalaria)...\n\n";

try {
    $db = DatabaseService::getInstance();

    // SQL de alteración
    $sql = "ALTER TABLE assets 
            ADD COLUMN clase_riesgo ENUM('I', 'IIa', 'IIb', 'III') NULL DEFAULT 'I' AFTER riesgo_ge,
            ADD COLUMN riesgo_biomedico ENUM('Bajo', 'Medio', 'Alto') NULL DEFAULT 'Medio' AFTER clase_riesgo,
            ADD COLUMN valor_reposicion DECIMAL(12,2) NULL DEFAULT 0.00 AFTER acquisition_cost,
            ADD COLUMN frecuencia_mp_meses INT NULL DEFAULT 6 AFTER under_maintenance_plan;";

    $db->exec($sql);
    echo "✅ Tabla 'assets' actualizada exitosamente.\n";
    echo "   [+] Campo 'clase_riesgo' añadido (Clase I, II, III).\n";
    echo "   [+] Campo 'riesgo_biomedico' añadido.\n";
    echo "   [+] Campo 'valor_reposicion' añadido.\n";
    echo "   [+] Campo 'frecuencia_mp_meses' añadido.\n";

    echo "\n🚀 Siguiente paso: Actualizando AssetEntity.php y Repository...\n";
} catch (Exception $e) {
    echo "\n❌ ERROR EN MIGRACIÓN: " . $e->getMessage() . "\n";
}
