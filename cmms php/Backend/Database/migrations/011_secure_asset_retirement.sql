-- Backend/Database/migrations/011_secure_asset_retirement.sql
-- Agregando columnas para trazabilidad de baja de equipos y periodo de gracia.

ALTER TABLE assets 
ADD COLUMN retirement_reason TEXT NULL AFTER observations,
ADD COLUMN retirement_requested_at DATETIME NULL AFTER updated_at;

-- Crear un índice para búsquedas rápidas de equipos por retirar
CREATE INDEX idx_assets_retirement ON assets(retirement_requested_at);
