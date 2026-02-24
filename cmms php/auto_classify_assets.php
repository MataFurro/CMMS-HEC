<?php

/**
 * Script de clasificación automática de equipos por categorías (familias).
 * Usa reglas de palabras clave para asignar la categoría correcta a los equipos existentes.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/Core/DatabaseService.php';
require_once __DIR__ . '/backend/Repositories/CategoryRepository.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\CategoryRepository;

$db = DatabaseService::getInstance();
$categoryRepo = new CategoryRepository($db);

// Mapa de clasificación: Categoría => Palabras Clave
$classificationMap = [
    'Balanzas y Básculas' => ['BALANZA', 'BASCULA', 'BIOIMPEDANCIOMETRO', 'PESAJE'],
    'Monitores de Signos Vitales' => ['MONITOR SIGNOS VITALES', 'UMEC', 'EPM', 'BENEVIEW', 'BENEVISION', 'PHILIPS MX', 'MONITOR MULTIPARAMETRICO'],
    'Monitoreo Especializado' => ['HOLTER', 'MAPA', 'CARDIOFETAL', 'GASTO CARDIACO', 'APNEA', 'PROFUNDIDAD ANESTESICA', 'DETECTOR LATIDOS'],
    'Equipos de Anestesia' => ['ANESTESIA', 'VAPORIZADOR'],
    'Ventilación Mecánica' => ['VENTILADOR', 'AIRVO', 'HUMIDIFICADOR'],
    'Bombas de Infusión y Jeringa' => ['BOMBA DE INFUSION', 'BOMBA DE JERINGA', 'VOLUMETRICA'],
    'Bombas de Aspiración' => ['ASPIRACION'],
    'Desfibriladores y DEA' => ['DESFIBRILADOR', 'DEA'],
    'Equipos Quirúrgicos' => ['ELECTROBISTURI', 'TORRE DE ENDOSCOPIA', 'FUENTE DE LUZ', 'INSUFLADOR', 'MESA QUIRURGICA', 'MOTOR QUIRURGICO', 'SHAVER', 'QUIRURGIC'],
    'Equipos de Esterilización' => ['AUTOCLAVE', 'LAVADORA DESINFECTADORA', 'LAVADORA ULTRASONICA', 'ESTERILIZADOR'],
    'Ecografía e Imagen' => ['ECOGRAFO', 'RAYOS X', 'MAMOGRAFO', 'TOMOGRAFO', 'ANGIOGRAFO', 'ARCO EN C'],
    'Equipos de Laboratorio' => ['ANALIZADOR', 'MICROSCOPIO', 'CENTRIFUGA', 'ESTUFA', 'INCUBADORA', 'BAÑO MARIA', 'CAMPANA'],
    'Refrigeración Clínica' => ['REFRIGERADOR', 'CONGELADOR', 'FREEZER', 'VITRINA'],
    'Mobiliario Clínico' => ['CAMA', 'CATRE', 'CAMILLA', 'VELADOR', 'SILLON', 'CARRO DE PARO', 'CARRO'],
    'Equipos de Terapia' => ['CUNA RADIANTE', 'FOTOTERAPIA', 'DIALISIS', 'HIPOTERMIA'],
    'Equipos Odontológicos' => ['DENTAL', 'SILLON DENTAL', 'ODONTOLOG'],
    'Rehabilitación y Fisio' => ['ELECTROESTIMULADOR', 'TROTADORA', 'BICICLETA ESTATICA', 'MOTOMED']
];

echo "Iniciando clasificación automática...\n";

// Obtener todos los assets que no tienen categoría asignada
$stmt = $db->query("SELECT id, name FROM assets WHERE category_id IS NULL");
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($assets as $asset) {
    $nameUpper = mb_strtoupper($asset['name'], 'UTF-8');
    $assignedCategory = 'Otros Equipos';

    foreach ($classificationMap as $categoryName => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($nameUpper, $kw) !== false) {
                $assignedCategory = $categoryName;
                break 2;
            }
        }
    }

    // Obtener ID de la categoría
    $catData = $categoryRepo->findByName($assignedCategory);
    if ($catData) {
        $updateStmt = $db->prepare("UPDATE assets SET category_id = ? WHERE id = ?");
        $updateStmt->execute([$catData['id'], $asset['id']]);
        $count++;
    }
}

echo "Clasificación finalizada. Se actualizaron $count equipos.\n";
