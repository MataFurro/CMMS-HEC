-- Migration v4.6: Add contract_id to assets
USE biocmms;

ALTER TABLE assets 
ADD COLUMN contract_id VARCHAR(100) NULL AFTER vendor;
