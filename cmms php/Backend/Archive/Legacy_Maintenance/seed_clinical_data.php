<?php

/**
 * seed_clinical_data.php
 * Inyecta datos de acreditación (Clase Riesgo, Riesgo Biomédico, Valor Reposición)
 * basados en nombres de equipos para realismo.
 */

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
require_once __DIR__ . '/Backend/Repositories/AssetRepository.php';

use Backend\Core\DatabaseService;
use Backend\Repositories\AssetRepository;

echo "🧪 INICIANDO INYECCIÓN DE DATOS CLÍNICOS ESTRATÉGICOS...\n";

try {
    $db = DatabaseService::getInstance();
    $repo = new AssetRepository();
    $assets = iterator_to_array($repo->findAll());

    $total = count($assets);
    echo "📊 Procesando $total activos...\n\n";

    $count = 0;
    foreach ($assets as $asset) {
        $name = strtolower($asset->name);

        // Lógica de clasificación inventada pero lógica clinical
        $clase = 'IIa';
        $riesgo = 'Medio';
        $reposicion = $asset->acquisitionCost * 1.2; // Inflación estimada
        $frecuencia = 6;

        if (str_contains($name, 'ventilador') || str_contains($name, 'anestesia') || str_contains($name, 'desfibrilador')) {
            $clase = 'III';
            $riesgo = 'Alto';
            $frecuencia = 4;
        } elseif (str_contains($name, 'bomba') || str_contains($name, 'monitor') || str_contains($name, 'ecógrafo')) {
            $clase = 'IIb';
            $riesgo = 'Alto';
            $frecuencia = 6;
        } elseif (str_contains($name, 'termómetro') || str_contains($name, 'esfigmomanómetro')) {
            $clase = 'I';
            $riesgo = 'Bajo';
            $frecuencia = 12;
        }

        // Valor de reposición base si es 0
        if ($reposicion <= 0) {
            $reposicion = rand(500, 50000);
        }

        $sql = "UPDATE assets SET 
                clase_riesgo = :clase, 
                riesgo_biomedico = :riesgo, 
                valor_reposicion = :reposicion,
                frecuencia_mp_meses = :frecuencia
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':clase'      => $clase,
            ':riesgo'     => $riesgo,
            ':reposicion' => $reposicion,
            ':frecuencia' => $frecuencia,
            ':id'         => $asset->id
        ]);

        $count++;
        if ($count % 50 === 0) echo "✔️ $count/$total procesados...\n";
    }

    echo "\n🏆 DATOS CLÍNICOS INYECTADOS CON ÉXITO.\n";
    echo "✨ Ahora el sistema puede generar reportes de Riesgo y Plan de Renovación Tecnológica.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "💡 Nota: Si el error es de columnas inexistentes, asegúrate de correr run_migration_clinical_fields.php primero.\n";
}
