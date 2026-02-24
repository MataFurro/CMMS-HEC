-- Migración: Añadir soporte para Categorías de Equipos (Familias)

-- 1. Crear tabla de categorías
CREATE TABLE IF NOT EXISTS asset_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Añadir columna category_id a assets
ALTER TABLE assets ADD COLUMN category_id INT NULL AFTER name;

-- 3. Establecer la relación (FK)
ALTER TABLE assets 
ADD CONSTRAINT fk_asset_category 
FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL;

-- 4. Insertar categorías base aprobadas
INSERT IGNORE INTO asset_categories (name) VALUES 
('Balanzas y Básculas'),
('Monitores de Signos Vitales'),
('Monitoreo Especializado'),
('Equipos de Anestesia'),
('Ventilación Mecánica'),
('Bombas de Infusión y Jeringa'),
('Bombas de Aspiración'),
('Desfibriladores y DEA'),
('Equipos Quirúrgicos'),
('Equipos de Esterilización'),
('Ecografía e Imagen'),
('Equipos de Laboratorio'),
('Refrigeración Clínica'),
('Mobiliario Clínico'),
('Equipos de Terapia'),
('Equipos Odontológicos'),
('Rehabilitación y Fisio'),
('Otros Equipos');
