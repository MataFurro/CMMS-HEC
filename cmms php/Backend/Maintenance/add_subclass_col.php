<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';
try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $db->exec("ALTER TABLE assets ADD COLUMN subclase VARCHAR(100) AFTER riesgo_ge");
    echo "Columna subclase añadida con éxito\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
