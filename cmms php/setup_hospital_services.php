<?php

/**
 * setup_hospital_services.php
 * Seeding script for the hospital_services table.
 * Data extracted from Res Nº 0997 (HEC 2025) - Definitive Source.
 */

require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Core/LoggerService.php';

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;

$services = [
    ['name' => 'Alto Riesgo Obstétrico', 'area' => 'Clínica'],
    ['name' => 'Anatomía Patológica', 'area' => 'Apoyo'],
    ['name' => 'Banco de Órganos y Tejidos', 'area' => 'Apoyo'],
    ['name' => 'Centro de Atención de Especialidades (CAE)', 'area' => 'Clínica'],
    ['name' => 'Cirugía', 'area' => 'Clínica'],
    ['name' => 'Emergencia Adulto', 'area' => 'Crítica'],
    ['name' => 'Emergencia Gineco-Obstétrico', 'area' => 'Crítica'],
    ['name' => 'Emergencia Infantil', 'area' => 'Crítica'],
    ['name' => 'Especialidades Odontológicas', 'area' => 'Clínica'],
    ['name' => 'Esterilización', 'area' => 'Apoyo'],
    ['name' => 'Farmacia', 'area' => 'Apoyo'],
    ['name' => 'Geriatría de Agudos (UGA)', 'area' => 'Clínica'],
    ['name' => 'Gestión de Bodegas', 'area' => 'Administrativa'],
    ['name' => 'Gestión del Cuidado', 'area' => 'Administrativa'],
    ['name' => 'Hospitalización Domiciliaria', 'area' => 'Clínica'],
    ['name' => 'Hospitalización Quirúrgica', 'area' => 'Clínica'],
    ['name' => 'Imagenología', 'area' => 'Apoyo'],
    ['name' => 'Ingeniería Biomédica', 'area' => 'Apoyo'],
    ['name' => 'Laboratorio', 'area' => 'Apoyo'],
    ['name' => 'Laboratorio / Farmacia', 'area' => 'Apoyo'],
    ['name' => 'Manejo Integral de Heridas Avanzadas', 'area' => 'Clínica'],
    ['name' => 'Medicina', 'area' => 'Clínica'],
    ['name' => 'Medicina Física y Rehabilitación', 'area' => 'Apoyo'],
    ['name' => 'Medicina Transfusional', 'area' => 'Apoyo'],
    ['name' => 'Movilización', 'area' => 'Administrativa'],
    ['name' => 'Nefrología y Diálisis', 'area' => 'Clínica'],
    ['name' => 'Neonatología y UPC Neonatal', 'area' => 'Crítica'],
    ['name' => 'Nutrición', 'area' => 'Apoyo'],
    ['name' => 'Pabellón Central', 'area' => 'Crítica'],
    ['name' => 'Pediatría', 'area' => 'Clínica'],
    ['name' => 'Policlínico de Infectología', 'area' => 'Clínica'],
    ['name' => 'Proyectos', 'area' => 'Administrativa'],
    ['name' => 'Puerperio', 'area' => 'Clínica'],
    ['name' => 'Recursos Físicos', 'area' => 'Administrativa'],
    ['name' => 'Salas de Atención Integral del Parto (SAIP)', 'area' => 'Crítica'],
    ['name' => 'Salud del Trabajador (UST)', 'area' => 'Administrativa'],
    ['name' => 'Salud Mental', 'area' => 'Clínica'],
    ['name' => 'Toma de Muestras', 'area' => 'Apoyo'],
    ['name' => 'Traslados Internos', 'area' => 'Administrativa'],
    ['name' => 'Unidad de Cuidados Paliativos', 'area' => 'Clínica'],
    ['name' => 'Unidad de Observación', 'area' => 'Clínica'],
    ['name' => 'Unidad de Paciente Crítico Coronario (UCO)', 'area' => 'Crítica'],
    ['name' => 'Unidad de Tratamiento Intermedio (UTI)', 'area' => 'Crítica'],
    ['name' => 'UPC Adulto (UPC)', 'area' => 'Crítica'],
    ['name' => 'UPC Pediátrico (UPCP)', 'area' => 'Crítica']
];

try {
    $db = DatabaseService::getInstance();

    // 1. Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS hospital_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        area VARCHAR(100) DEFAULT 'Clínica',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Clear existing (to ensure sync with Res 0997)
    $db->exec("DELETE FROM hospital_services");

    $stmt = $db->prepare("INSERT INTO hospital_services (name, area) VALUES (:name, :area)");

    $count = 0;
    foreach ($services as $service) {
        if ($stmt->execute([':name' => $service['name'], ':area' => $service['area']])) {
            $count++;
        }
    }

    echo "Successfully synchronized $count hospital services from Res 0997.\n";
    LoggerService::info("Hospital services synchronized from Res 0997", ['count' => $count]);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    LoggerService::error("Synchronization failed", ['error' => $e->getMessage()]);
}
