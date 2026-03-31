<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();

    // Disable foreign key checks temporarily to allow truncation
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Truncate the table (Deletes all rows and resets AUTO_INCREMENT)
    $db->exec('TRUNCATE TABLE assets');

    // Re-enable foreign key checks
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "¡Éxito! La tabla 'assets' ha sido vaciada completamente.\n";
    echo "El contador de IDs internos (AUTO_INCREMENT) ha vuelto a 1.\n";
    echo "Puedes proceder a subir tu archivo Excel limpio para la prueba final.\n";
} catch (PDOException $e) {
    echo "Error al vaciar la tabla: " . $e->getMessage() . "\n";
    if (isset($db)) $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}
