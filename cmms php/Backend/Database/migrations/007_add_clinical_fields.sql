-- Migración: Añadir campos clínicos y financieros faltantes a assets
ALTER TABLE assets 
ADD COLUMN clase_riesgo VARCHAR(10) DEFAULT 'I' AFTER observations,
ADD COLUMN riesgo_biomedico VARCHAR(50) DEFAULT 'Medio' AFTER clase_riesgo,
ADD COLUMN valor_reposicion DECIMAL(12,2) DEFAULT 0.00 AFTER riesgo_biomedico,
ADD COLUMN frecuencia_mp_meses INT DEFAULT 6 AFTER valor_reposicion;
