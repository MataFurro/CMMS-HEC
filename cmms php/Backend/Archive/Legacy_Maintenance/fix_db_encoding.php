<?php
/**
 * scripts/fix_db_encoding.php
 * Limpieza heurística de nombres de activos con caracteres corruptos (?).
 * Versión 2.0 - Cobertura extendida.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

$db = Backend\Core\DatabaseService::getInstance();

// Diccionario de correcciones comunes (Context-aware)
$corrections = [
    'Aspiraci?n'      => 'Aspiración',
    'Cl?nico'         => 'Clínico',
    'Cl?nica'         => 'Clínica',
    'Ec?grafo'        => 'Ecógrafo',
    'Odontolog?a'      => 'Odontología',
    'Diagn?stico'     => 'Diagnóstico',
    'Quir?rgico'      => 'Quirúrgico',
    'Terap?utico'     => 'Terapéutico',
    'Imagenolog?a'    => 'Imagenología',
    'Endosc?pico'     => 'Endoscópico',
    'Iluminaci?n'     => 'Iluminación',
    'Rehabilitaci?n'  => 'Rehabilitación',
    'Esterilizaci?n'  => 'Esterilización',
    'Presi?n'         => 'Presión',
    'Desfibrilaci?n' => 'Desfibrilación',
    'Inhalaci?n'      => 'Inhalación',
    'Succi?n'         => 'Succión',
    'Monitorizaci?n'  => 'Monitorización',
    'Electr?nico'     => 'Electrónico',
    'Electr?nica'     => 'Electrónica',
    'Ba?o'            => 'Baño',
    'Mec?nica'        => 'Mecánica',
    'Mec?nico'        => 'Mecánico',
    'Microbiol?gia'   => 'Microbiología',
    'Microbiolog?a'   => 'Microbiología',
    'Autom?tico'      => 'Automático',
    'Anest?sico'      => 'Anestésico',
    'Uretr?tomos'     => 'Uretrótomos',
    'Impedanci?metro' => 'Impedanciómetro',
    'Esteril'         => 'Estéril',
    'L?mpara'         => 'Lámpara',
    'Bater?a'         => 'Batería',
    'Cicloerg?metro'  => 'Cicloergómetro',
    'Inyecci?n'       => 'Inyección',
    'Fisiol?gico'     => 'Fisiológico',
    'Port?til'        => 'Portátil',
    'Médico-Quir?rgico' => 'Médico-Quirúrgico',
];

echo "Iniciando Limpieza Heurística de Base de Datos v2.0...\n";

$count = 0;
foreach ($corrections as $search => $replace) {
    $stmt = $db->prepare("UPDATE assets SET name = REPLACE(name, :search, :replace) WHERE name LIKE :like");
    $stmt->execute([
        'search'  => $search,
        'replace' => $replace,
        'like'    => '%' . $search . '%'
    ]);
    $affected = $stmt->rowCount();
    if ($affected > 0) {
        echo " - Corregido '$search' -> '$replace' ($affected filas)\n";
        $count += $affected;
    }
}

// Limpieza de Work Orders también
foreach ($corrections as $search => $replace) {
    $stmt = $db->prepare("UPDATE work_orders SET observations = REPLACE(observations, :search, :replace) WHERE observations LIKE :like");
    $stmt->execute([
        'search'  => $search,
        'replace' => $replace,
        'like'    => '%' . $search . '%'
    ]);
    $affectedWork = $stmt->rowCount();
    if ($affectedWork > 0) {
        echo " - (OT) Corregido '$search' -> '$replace' ($affectedWork filas)\n";
        $count += $affectedWork;
    }
}

echo "Limpieza finalizada. Total de campos actualizados: $count\n";
