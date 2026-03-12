<?php
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();

    // Crear tabla service_requests si no existiera
    $db->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id              VARCHAR(30) PRIMARY KEY,
        asset_id        VARCHAR(30) NULL,
        requested_by    INT NULL,
        requester_email VARCHAR(180) NULL,
        priority        ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
        description     TEXT NOT NULL,
        status          ENUM('Pendiente','Revisada','Convertida_OT','Finalizada','Rechazada') NOT NULL DEFAULT 'Pendiente',
        generated_ot_id VARCHAR(30) NULL,
        asset_name_fallback VARCHAR(255) NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
        FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (generated_ot_id) REFERENCES work_orders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Borramos los datos viejos conflictivos si los hubiese para limpiar, 
    // y aplicamos los cambios a la tabla en vivo por si ya existía sin email.
    try {
        $db->exec("ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS requester_email VARCHAR(180) NULL AFTER requested_by");
        $db->exec("ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS asset_name_fallback VARCHAR(255) NULL AFTER asset_id");
        $db->exec("ALTER TABLE service_requests MODIFY COLUMN requested_by INT NULL");
        $db->exec("ALTER TABLE service_requests MODIFY COLUMN asset_id VARCHAR(30) NULL");
        $db->exec("ALTER TABLE service_requests MODIFY COLUMN status ENUM('Pendiente','Revisada','Convertida_OT','Finalizada','Rechazada') NOT NULL DEFAULT 'Pendiente'");
    } catch (Exception $e) { /* Ignored if already exist */
    }

    echo "Tabla service_requests actualizada correctamente en base de datos Viva.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
