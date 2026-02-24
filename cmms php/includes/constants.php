<?php

/**
 * includes/constants.php
 * Parámetros de configuración financiera y operativa para BioCMMS.
 */

// --- Identidad Visual & Paleta Premium ---
define('APP_NAME_HTML', 'BioCMMS v4.3 <span class="text-medical-blue font-light">Pro</span>');
define('COLOR_MEDICAL_BLUE', '#0ea5e9'); // Más vibrante v4.2
define('COLOR_EMERALD', '#10b981');
define('COLOR_AMBER', '#f59e0b');
define('COLOR_RED', '#ef4444');
define('COLOR_SLATE_400', '#94a3b8');
define('COLOR_SLATE_700', '#334155');
define('COLOR_BG_DARK', '#1e293b');
define('COLOR_MEDICAL_DARK', '#0f172a');
define('COLOR_PANEL_DARK', '#111820');

// --- Botones y Acciones ---
define('BTN_DOWNLOAD_EXCEL', 'Descargar Reporte Excel');
define('BTN_UPLOAD_EXCEL',   'Subir Masivo Excel');
define('BTN_NEW_ASSET',      'Nuevo Activo Biomédico');
define('BTN_OPEN_OT',        'Abrir Orden de Trabajo');
define('BTN_PROCESS_REQ',    'Procesar Solicitud');

// --- Estados de Activos (Mapeo a DB ENUM) ---
define('STATUS_OPERATIVE',           'OPERATIVE');
define('STATUS_MAINTENANCE',         'MAINTENANCE');
define('STATUS_NO_OPERATIVE',        'NO_OPERATIVE');
define('STATUS_OPERATIVE_WITH_OBS',  'OPERATIVE_WITH_OBS');
define('STATUS_OUT_OF_SERVICE',      'NO_OPERATIVE');

// --- Criticidad (Labels) ---
define('CRITICALITY_CRITICAL', 'Crítico');
define('CRITICALITY_RELEVANT', 'Relevante');
define('CRITICALITY_LOW',      'Bajo');
define('CRITICALITY_NA',       'No Aplica');

// --- Roles de Sistema (Mapeo a DB ENUM) ---
define('ROLE_CHIEF_ENGINEER', 'CHIEF_ENGINEER');
define('ROLE_ENGINEER',       'ENGINEER');
define('ROLE_TECHNICIAN',     'TECHNICIAN');
define('ROLE_AUDITOR',        'AUDITOR');
define('ROLE_USER',           'USER');

// --- Sidebar Labels ---
define('SIDEBAR_DASHBOARD',       'Tablero de Gestión');
define('SIDEBAR_CALENDAR',        'Agenda Técnica');
define('SIDEBAR_WORK_ORDERS',      'Órdenes de Trabajo');
define('SIDEBAR_INVENTORY',       'Inventario Biomédico');
define('SIDEBAR_FAMILY_ANALYSIS', 'Clasificación por Clase');
define('SIDEBAR_MESSENGER',       'SMS OT (Solicitudes)');

// --- Factores de Costo TCO / Depreciación ---
define('MAINTENANCE_COST_FACTOR', 0.08);
define('REPLACEMENT_COST_FACTOR', 1.25);
define('RESIDUAL_VALUE_FACTOR', 0.10);
define('DEFAULT_BETA_WEIBULL', 1.45);

// --- Metas Operacionales ---
define('UPTIME_GOAL', 98.5);

// --- Costos de Downtime por Área (USD/Hora) ---
define('AREA_COST_HOURS', [
    'Pabellón Principal'        => 850,
    'UCI Adultos'               => 1200,
    'Urgencias'                 => 600,
    'Pabellón de Maternidad'    => 750,
    'Scanner / Imagenología'    => 1500,
    'Cardiología'               => 500,
    'Dental'                    => 200,
    'Default'                   => 100
]);

// --- Penalizaciones TINC ---
define('PENALTY_MISSED_PM', 500);
define('PENALTY_ADVERSE_EVENT', 2500);

define('DEFAULT_HOSPITAL_NAME', 'Hospital General HEC');
