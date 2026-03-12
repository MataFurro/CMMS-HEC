<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $officialClasses = [
        'APOYO DIAGNÓSTICO',
        'APOYO ENDOSCÓPICO',
        'APOYO INDUSTRIAL',
        'APOYO QUIRÚRGICO',
        'APOYO TERAPÉUTICO',
        'ESTERILIZACIÓN',
        'IMAGENOLOGÍA',
        'LABORATORIO / FARMACIA',
        'MED. FIS. REHABILITACIÓN',
        'MOBILIARIO',
        'MONITOREO',
        'ODONTOLOGÍA',
        'BAJO COSTO',
        'GENERAL',
        'INSTRUMENTAL'
    ];

    $db->exec("DELETE FROM asset_classes");
    $stmt = $db->prepare("INSERT INTO asset_classes (name) VALUES (:name)");
    foreach ($officialClasses as $name) {
        $stmt->execute(['name' => $name]);
    }
    echo "Asset Classes populated successfully (" . count($officialClasses) . " classes).\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
