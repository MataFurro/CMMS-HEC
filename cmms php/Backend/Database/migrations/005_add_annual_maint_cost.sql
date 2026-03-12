-- Migration v4.5: Add annual_maint_cost to assets
USE biocmms;

ALTER TABLE assets 
ADD COLUMN annual_maint_cost DECIMAL(12,2) NULL DEFAULT 0.00 AFTER acquisition_cost;
