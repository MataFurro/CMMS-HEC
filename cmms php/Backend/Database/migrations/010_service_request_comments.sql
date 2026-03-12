-- Migración: 010_service_request_comments.sql

CREATE TABLE IF NOT EXISTS service_request_comments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    request_id      VARCHAR(30) NOT NULL,
    user_id         INT NOT NULL,
    message         TEXT NOT NULL,
    is_internal     TINYINT(1) DEFAULT 0 COMMENT '1 si es solo para ingenieros, 0 si lo ve el cliente',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Agregar columna para nombre de activo en caso de que no exista el asset_id (fallback)
-- (Ya existe en la DB pero por si acaso en el schema falta)
ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS asset_name_fallback VARCHAR(200) NULL AFTER asset_id;
