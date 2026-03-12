-- Migración: Estandarización de Estados OT y Pista de Auditoría
-- BioCMMS v4.6 Alignment

-- 1. Crear tabla de auditoría robusta si no existe
CREATE TABLE IF NOT EXISTS audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    asset_id VARCHAR(50), -- Target principal (puede ser ID de activo o ID de OT)
    target_type VARCHAR(50), -- 'ASSET', 'WORK_ORDER', 'USER', etc.
    details JSON,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target_type, asset_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Asegurar que work_orders.status permita los nuevos valores (si es ENUM)
-- Nota: Si es VARCHAR, solo actualizamos los datos.
ALTER TABLE work_orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'OPEN';

-- 3. Mapear estados antiguos a nuevos
UPDATE work_orders SET status = 'OPEN' WHERE status = 'Pendiente';
UPDATE work_orders SET status = 'PROGRESS' WHERE status = 'En Proceso';
UPDATE work_orders SET status = 'CLOSED' WHERE status = 'Terminada';

-- 4. Asegurar que assets.status sea consistente
ALTER TABLE assets MODIFY COLUMN status VARCHAR(50) DEFAULT 'OPERATIVE';
