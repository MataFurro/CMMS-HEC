<?php

/**
 * migrate_missing_tables.php
 * Crea tablas que pueden estar faltando en la base de datos biocmms.
 * Ejecutar una sola vez en el navegador: http://localhost/cmms%20php/migrate_missing_tables.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

echo "<style>body{font-family:monospace;background:#0f1726;color:#e2e8f0;padding:2rem;}
h1{color:#38bdf8;} .ok{color:#10b981;} .skip{color:#f59e0b;} .err{color:#ef4444;}</style>";
echo "<h1>🗄️ BioCMMS — Migración de Tablas Faltantes</h1>";

try {
    $db = DatabaseService::getInstance();
    echo "<span class='ok'>✅ Conexión a MySQL exitosa</span><br><br>";
} catch (Exception $e) {
    echo "<span class='err'>❌ Sin conexión a MySQL: " . $e->getMessage() . "</span>";
    exit;
}

$migrations = [
    // ──────────────────────────────────────────────────────────────
    // Tabla: asset_recalls (alertas de retiro o recall de equipos)
    // ──────────────────────────────────────────────────────────────
    'asset_recalls' => "CREATE TABLE IF NOT EXISTS asset_recalls (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        asset_id    VARCHAR(50) NOT NULL,
        recall_date DATE NOT NULL,
        reason      TEXT,
        resolved    TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ──────────────────────────────────────────────────────────────
    // Tabla: ot_attachments (adjuntos de órdenes de trabajo)
    // ──────────────────────────────────────────────────────────────
    'ot_attachments' => "CREATE TABLE IF NOT EXISTS ot_attachments (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        ot_id       VARCHAR(50) NOT NULL,
        filename    VARCHAR(255) NOT NULL,
        filepath    VARCHAR(512) NOT NULL,
        filetype    VARCHAR(100) NULL,
        uploaded_by INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ot_id) REFERENCES work_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ──────────────────────────────────────────────────────────────
    // Tabla: audit_log (auditoría de acciones del sistema)
    // ──────────────────────────────────────────────────────────────
    'audit_log' => "CREATE TABLE IF NOT EXISTS audit_log (
        id          BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NULL,
        action      VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NULL,
        entity_id   VARCHAR(100) NULL,
        details     JSON NULL,
        ip_address  VARCHAR(45) NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ──────────────────────────────────────────────────────────────
    // Tabla: categories (categorías de activos)
    // ──────────────────────────────────────────────────────────────
    'asset_categories' => "CREATE TABLE IF NOT EXISTS asset_categories (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        description TEXT NULL,
        color       VARCHAR(20) DEFAULT '#0ea5e9',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ──────────────────────────────────────────────────────────────
    // Tabla: hospital_services (servicios del hospital)
    // ──────────────────────────────────────────────────────────────
    'hospital_services' => "CREATE TABLE IF NOT EXISTS hospital_services (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(150) NOT NULL,
        building    VARCHAR(100) NULL,
        floor       VARCHAR(50) NULL,
        contact     VARCHAR(100) NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

echo "<hr style='border-color:#1e293b;margin-bottom:1rem;'>";
foreach ($migrations as $table => $sql) {
    try {
        $db->exec($sql);
        echo "<span class='ok'>✅ Tabla <b>$table</b> — creada o ya existente</span><br>";
    } catch (Exception $e) {
        echo "<span class='err'>❌ Error en <b>$table</b>: " . $e->getMessage() . "</span><br>";
    }
}

// ──────────────────────────────────────────────────────────────
// Modificadores estructurales: Módulo Messenger a Service Requests
// ──────────────────────────────────────────────────────────────
try {
    $db->exec("ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS requester_email VARCHAR(180) NULL AFTER requested_by");
    $db->exec("ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS asset_name_fallback VARCHAR(255) NULL AFTER asset_id");

    // Convert to NULL allowed
    $db->exec("ALTER TABLE service_requests MODIFY COLUMN requested_by INT NULL");
    $db->exec("ALTER TABLE service_requests MODIFY COLUMN asset_id VARCHAR(30) NULL");
    $db->exec("ALTER TABLE service_requests MODIFY COLUMN status ENUM('Pendiente','Revisada','Convertida_OT','Finalizada','Rechazada') NOT NULL DEFAULT 'Pendiente'");
    echo "<span class='ok'>✅ Migración de esquema 'service_requests' completada (Módulo Correos)</span><br>";
} catch (Exception $e) {
    // Si la tabla no existe o error en alter, ignoramos (mostrar warning si queremos)
    echo "<span class='skip'>⚠️ Skiped 'service_requests' updates: " . $e->getMessage() . "</span><br>";
}

echo "<hr style='border-color:#1e293b;margin-top:1rem;'>";
echo "<br><span class='ok'><b>Migración completada.</b></span><br><br>";
echo "<a href='index.php' style='color:#38bdf8;text-decoration:none;'>→ Volver a la aplicación</a>";
