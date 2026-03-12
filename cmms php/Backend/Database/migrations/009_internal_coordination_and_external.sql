-- Migración: 009_internal_coordination_and_external.sql

-- 1. Campos para coordinación interna y tiempos precisos
ALTER TABLE work_orders 
    ADD COLUMN scheduled_at DATETIME NULL AFTER checklist_template,
    ADD COLUMN release_at DATETIME NULL AFTER scheduled_at,
    ADD COLUMN return_at DATETIME NULL AFTER release_at;

-- 2. Tabla de proveedores externos
CREATE TABLE IF NOT EXISTS external_providers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    tax_id          VARCHAR(20) NULL COMMENT 'RUT/NIT',
    contact_email   VARCHAR(180) NULL,
    specialty       VARCHAR(150) NULL,
    active          TINYINT(1) DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Campos para vinculación externa en OTs
ALTER TABLE work_orders
    ADD COLUMN external_provider_id INT NULL AFTER assigned_tech_id,
    ADD COLUMN external_service_number VARCHAR(50) NULL AFTER external_provider_id,
    ADD CONSTRAINT fk_wo_external FOREIGN KEY (external_provider_id) REFERENCES external_providers(id) ON DELETE SET NULL;

-- 4. Datos de prueba para proveedores
INSERT INTO external_providers (name, contact_email, specialty) VALUES
('Draeger Medical Chile', 'soporte@draeger.com', 'Soporte Vital / Ventiladores'),
('Philips Healthcare', 'service.cl@philips.com', 'Imagenología / Monitoreo'),
('Zoll Medical Tech', 'tecnico@zoll.cl', 'Desfibriladores');
