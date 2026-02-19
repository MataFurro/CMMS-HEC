-- ═══════════════════════════════════════════════════════
-- BioCMMS - Schema MySQL
-- Sistema de Gestión de Mantenimiento Biomédico
-- ═══════════════════════════════════════════════════════
-- asset_id es la FK central que conecta todas las tablas

CREATE DATABASE IF NOT EXISTS biocmms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biocmms;

-- ───────────────────────────────────────────────────────
-- USUARIOS
-- ───────────────────────────────────────────────────────
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(180) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('AUDITOR','TECHNICIAN','ENGINEER','CHIEF_ENGINEER','USER') NOT NULL DEFAULT 'TECHNICIAN',
    avatar_url      VARCHAR(500) NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- ACTIVOS BIOMÉDICOS (tabla central)
-- ───────────────────────────────────────────────────────
CREATE TABLE assets (
    id                      VARCHAR(30) PRIMARY KEY COMMENT 'Ej: PB-840-00122',
    serial_number           VARCHAR(50) NULL,
    name                    VARCHAR(200) NOT NULL,
    brand                   VARCHAR(100) NULL,
    model                   VARCHAR(100) NULL,
    location                VARCHAR(200) NULL,
    sub_location            VARCHAR(200) NULL,
    vendor                  VARCHAR(200) NULL,
    ownership               ENUM('Propio','Comodato','Arriendo') NOT NULL DEFAULT 'Propio',
    criticality             ENUM('CRITICAL','RELEVANT','LOW') NOT NULL DEFAULT 'RELEVANT',
    status                  ENUM('OPERATIVE','MAINTENANCE','NO_OPERATIVE','OPERATIVE_WITH_OBS') NOT NULL DEFAULT 'OPERATIVE',
    riesgo_ge               VARCHAR(50) NULL COMMENT 'Life Support, High Risk, etc.',
    codigo_umdns            VARCHAR(20) NULL,
    fecha_instalacion       DATE NULL,
    purchased_year          YEAR NULL,
    acquisition_cost        DECIMAL(12,2) NULL DEFAULT 0.00,
    total_useful_life       INT NULL COMMENT 'Años de vida útil total',
    useful_life_pct         INT NULL COMMENT 'Porcentaje de vida útil restante',
    years_remaining         INT NULL,
    warranty_expiration     DATE NULL,
    under_maintenance_plan  TINYINT(1) NOT NULL DEFAULT 0,
    en_uso                  TINYINT(1) NOT NULL DEFAULT 1,
    image_url               VARCHAR(500) NULL,
    observations            TEXT NULL,
    -- Puntajes GE (Gestión de Equipos)
    funcion_ge              INT NULL DEFAULT 0 COMMENT 'Puntaje función (0-10)',
    riesgo_ge_score         INT NULL DEFAULT 0 COMMENT 'Puntaje riesgo (0-5)',
    mantenimiento_ge        INT NULL DEFAULT 0 COMMENT 'Puntaje mantenimiento (0-5)',
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_assets_status (status),
    INDEX idx_assets_criticality (criticality),
    INDEX idx_assets_location (location)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- TÉCNICOS (extiende users con datos de capacidad)
-- ───────────────────────────────────────────────────────
CREATE TABLE technicians (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    specialty       VARCHAR(150) NULL,
    active_ots      INT NOT NULL DEFAULT 0,
    completed_ots   INT NOT NULL DEFAULT 0,
    capacity_pct    INT NOT NULL DEFAULT 0 COMMENT '0-100%',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- ÓRDENES DE TRABAJO
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE work_orders (
    id                  VARCHAR(30) PRIMARY KEY COMMENT 'Ej: OT-2026-4584',
    asset_id            VARCHAR(30) NOT NULL COMMENT 'FK central al equipo',
    type                ENUM('Preventiva','Correctiva','Calibracion') NOT NULL,
    status              ENUM('Pendiente','En Proceso','Terminada','Cancelada') NOT NULL DEFAULT 'Pendiente',
    assigned_tech_id    INT NULL,
    created_date        DATE NOT NULL,
    completed_date      DATE NULL,
    priority            ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
    checklist_template  VARCHAR(80) NULL COMMENT 'Key del template en checklist_templates.php',
    observations        TEXT NULL,
    duration_hours      DECIMAL(6,2) NULL DEFAULT 0.00,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_tech_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_wo_asset (asset_id),
    INDEX idx_wo_status (status),
    INDEX idx_wo_type (type),
    INDEX idx_wo_tech (assigned_tech_id)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- RESULTADOS DE CHECKLIST
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE checklist_results (
    id                          INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id               VARCHAR(30) NOT NULL,
    asset_id                    VARCHAR(30) NOT NULL COMMENT 'FK central al equipo',
    template_key                VARCHAR(80) NOT NULL,
    qualitative_results         JSON NULL,
    quantitative_results        JSON NULL,
    electrical_safety_results   JSON NULL,
    completed_at                DATETIME NULL,
    completed_by                INT NULL,

    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cr_asset (asset_id),
    INDEX idx_cr_wo (work_order_id)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- ADJUNTOS / FOTOS DE OT (evidencia fotográfica)
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE ot_attachments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id   VARCHAR(30) NOT NULL,
    asset_id        VARCHAR(30) NOT NULL COMMENT 'FK central al equipo',
    uploaded_by     INT NULL,
    file_path       VARCHAR(500) NOT NULL COMMENT 'Ruta en uploads/ot/{work_order_id}/',
    file_type       VARCHAR(30) NOT NULL DEFAULT 'image/jpeg',
    caption         VARCHAR(300) NULL,
    category        ENUM('antes','durante','despues','evidencia') NOT NULL DEFAULT 'evidencia',
    uploaded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ota_asset (asset_id),
    INDEX idx_ota_wo (work_order_id)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- SOLICITUDES DE SERVICIO
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE service_requests (
    id              VARCHAR(30) PRIMARY KEY COMMENT 'Ej: SOL-2026-0045',
    asset_id        VARCHAR(30) NOT NULL COMMENT 'FK central al equipo',
    requested_by    INT NOT NULL,
    priority        ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
    description     TEXT NOT NULL,
    status          ENUM('Pendiente','Revisada','Convertida_OT','Rechazada') NOT NULL DEFAULT 'Pendiente',
    generated_ot_id VARCHAR(30) NULL COMMENT 'OT generada si fue aprobada',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (generated_ot_id) REFERENCES work_orders(id) ON DELETE SET NULL,
    INDEX idx_sr_asset (asset_id),
    INDEX idx_sr_status (status)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- AUDIT TRAIL (FDA 21 CFR Part 11)
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE audit_trail (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    action          VARCHAR(100) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, SIGN, LOGIN, etc.',
    asset_id        VARCHAR(30) NULL COMMENT 'FK central al equipo (si aplica)',
    target_type     VARCHAR(50) NOT NULL COMMENT 'asset, work_order, checklist, etc.',
    details         JSON NULL,
    ip_address      VARCHAR(45) NULL,
    timestamp       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    INDEX idx_at_user (user_id),
    INDEX idx_at_asset (asset_id),
    INDEX idx_at_timestamp (timestamp)
) ENGINE=InnoDB;

-- ───────────────────────────────────────────────────────
-- RECALLS / ALERTAS SANITARIAS
-- asset_id conecta con assets.id
-- ───────────────────────────────────────────────────────
CREATE TABLE asset_recalls (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    asset_id        VARCHAR(30) NOT NULL COMMENT 'FK central al equipo',
    recall_code     VARCHAR(50) NOT NULL,
    agency          VARCHAR(80) NOT NULL COMMENT 'ISP, FDA, etc.',
    priority        ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
    description     TEXT NOT NULL,
    resolved        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    INDEX idx_recall_asset (asset_id)
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════
-- DATOS INICIALES (mock → seed)
-- ═══════════════════════════════════════════════════════

-- Usuarios de prueba
INSERT INTO users (id, name, email, password_hash, role, avatar_url) VALUES
(1, 'Lic. Auditor',      'auditor@biocmms.com', '$2y$10$placeholder_hash', 'AUDITOR',    'https://i.pravatar.cc/150?u=auditor'),
(2, 'Ing. Roberto Jefe', 'jefe@biocmms.com',    '$2y$10$placeholder_hash', 'CHIEF_ENGINEER',  'https://i.pravatar.cc/150?u=chief'),
(3, 'Ing. Laura',        'ing@biocmms.com',     '$2y$10$placeholder_hash', 'ENGINEER',  'https://i.pravatar.cc/150?u=eng'),
(4, 'Téc. Mario',        'tec@biocmms.com',     '$2y$10$placeholder_hash', 'TECHNICIAN',    'https://i.pravatar.cc/150?u=tech'),
(5, 'Dr. Clínico Demo',  'demo@biocmms.com',    '$2y$10$placeholder_hash', 'USER',    'https://i.pravatar.cc/150?u=demo');

-- Técnicos
INSERT INTO technicians (user_id, specialty, active_ots, completed_ots, capacity_pct) VALUES
(2, 'Ing. Clínico Sr.',        8,  12, 85),
(3, 'Técnico Biomédico',        3,  15, 45),
(4, 'Técnico Especialista',     5,  10, 60);

-- Activos
INSERT INTO assets (id, serial_number, name, brand, model, location, sub_location, vendor, ownership, criticality, status, riesgo_ge, codigo_umdns, fecha_instalacion, purchased_year, acquisition_cost, total_useful_life, useful_life_pct, years_remaining, warranty_expiration, under_maintenance_plan, en_uso, funcion_ge, riesgo_ge_score, mantenimiento_ge) VALUES
('PB-840-00122', 'SN-992031-B',  'Ventilador Mecánico',        'Puritan Bennett', '840',             'UCI Adultos - Box 04', 'Cama 4',        'Draeger Medical',  'Propio',   'CRITICAL', 'OPERATIVE',          'Life Support', '17-429', '2020-05-10', 2020, 45000.00, 10, 75, 4, '2026-12-15', 1, 1, 10, 5, 4),
('AL-500-00441', 'SN-882211-X',  'Bomba de Infusión',          'Alaris',          'GH Plus',         'Urgencias - Sala 01',  'Box 1',         'Becton Dickinson', 'Comodato', 'RELEVANT', 'MAINTENANCE',        'High Risk',    '13-215', '2021-02-15', 2021,  8500.00, 10, 85, 7, '2026-02-15', 1, 0,  8, 4, 3),
('MM-X3-00922',  'SN-773344-Y',  'Monitor Multiparamétrico',   'Mindray',         'BeneVision X3',   'Pabellón 03',          'Mesa Anestesia','Mindray Chile',    'Propio',   'CRITICAL', 'OPERATIVE_WITH_OBS', 'High Risk',    '12-630', '2022-08-20', 2022, 12000.00, 10, 90, 8, '2025-08-20', 1, 1,  9, 4, 3),
('DF-CU-00210',  'SN-554433-Z',  'Desfibrilador',              'Zoll',            'R Series',        'Piso 3 - Torre A',     'Carro de Paro', 'Medtronic',        'Propio',   'CRITICAL', 'NO_OPERATIVE',       'Life Support', '11-129', '2020-03-30', 2020, 15000.00, 10, 40, 4, '2025-03-30', 1, 0, 10, 5, 3),
('ECG-2024-001', 'SN-ECG-9988',  'Electrocardiógrafo',         'Philips',         'PageWriter TC70', 'Cardiología',          'Consulta 2',    'Philips Medical',  'Propio',   'RELEVANT', 'OPERATIVE_WITH_OBS', NULL,           NULL,     NULL,         2023,  5500.00,  8, 60, 4, '2027-01-01', 1, 1,  5, 2, 3);

-- Recalls
INSERT INTO asset_recalls (asset_id, recall_code, agency, priority, description) VALUES
('AL-500-00441', 'AV-2024-01', 'ISP', 'Alta', 'Falla en software de goteo');

-- Órdenes de trabajo
INSERT INTO work_orders (id, asset_id, type, status, assigned_tech_id, created_date, priority, checklist_template) VALUES
('OT-2026-4584', 'MM-X3-00922',  'Calibracion', 'Pendiente',  3, '2026-02-11', 'Baja',  NULL),
('OT-2026-4583', 'AL-500-00441', 'Correctiva',  'En Proceso', 4, '2026-02-10', 'Media', NULL),
('OT-2025-3210', 'DF-CU-00210',  'Preventiva',  'Terminada',  3, '2025-11-20', 'Baja',  NULL),
('OT-2024-1105', 'PB-840-00122', 'Preventiva',  'Terminada',  4, '2024-08-15', 'Alta',  'ventilador_mecanico');
