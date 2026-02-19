-- Backend/Database/migrations/002_create_hospital_services.sql
-- Create formal table for Hospital Services (Locations)

CREATE TABLE IF NOT EXISTS hospital_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    area VARCHAR(100) DEFAULT 'Clínica', -- e.g., Clínica, Apoyo, Administrativa
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: We maintain 'location' in 'assets' table as a string for now 
-- but we will validate/populate it from this table.
