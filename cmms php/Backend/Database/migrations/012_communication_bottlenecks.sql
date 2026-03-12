-- 012_communication_bottlenecks.sql (v2)
-- Añadir campos para seguimiento de coordinación y entrega clínica

-- 1. Soporte para Handover en OTs
-- Usamos IF NOT EXISTS en PHP para evitar errores secundarios si falla parcial mente
ALTER TABLE work_orders 
ADD COLUMN IF NOT EXISTS handover_confirmed_by VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS handover_location VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS handover_timestamp DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS coordination_stalled_reason TEXT DEFAULT NULL;

-- 2. Soporte para verificación de retiro físico en Activos
ALTER TABLE assets
ADD COLUMN IF NOT EXISTS physical_withdrawal_confirmed TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS physical_withdrawal_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS physical_withdrawal_by VARCHAR(255) DEFAULT NULL;

-- 3. Crear tabla para LOGS de coordinación
-- El ID de work_orders es VARCHAR(30) en el schema.sql original (L119)
CREATE TABLE IF NOT EXISTS coordination_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id VARCHAR(30) NOT NULL,
    stalled_at DATETIME NOT NULL,
    resumed_at DATETIME DEFAULT NULL,
    reason TEXT,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;
