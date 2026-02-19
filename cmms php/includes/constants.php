<?php

/**
 * includes/constants.php
 * Parámetros de configuración financiera y operativa para BioCMMS.
 */

// --- Identidad Visual ---
define('APP_NAME_HTML', '<b class="text-medical-blue">Bio</b>CMMS <small class="text-slate-500 font-light">Pro</small>');
define('COLOR_MEDICAL_BLUE', '#3b82f6');
define('COLOR_MEDICAL_DARK', '#0f172a');
define('COLOR_BG_DARK',      '#1e293b');
define('COLOR_PANEL_DARK',   '#334155');
define('COLOR_SLATE_700',    '#334155');

// --- Botones y Acciones de Interfaz ---
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
define('STATUS_OUT_OF_SERVICE',      'NO_OPERATIVE'); // Alias para compatibilidad

// --- Parámetros de Simulación y Metas ---
define('DEFAULT_BETA_WEIBULL', 1.5);

// --- Roles de Sistema (Mapeo a DB ENUM) ---
define('ROLE_CHIEF_ENGINEER', 'CHIEF_ENGINEER');
define('ROLE_ENGINEER',       'ENGINEER');
define('ROLE_TECHNICIAN',     'TECHNICIAN');
define('ROLE_AUDITOR',        'AUDITOR');
define('ROLE_USER',           'USER');

// --- Textos de Sidebar / Menú ---
define('SIDEBAR_DASHBOARD',       'Tablero de Gestión');
define('SIDEBAR_CALENDAR',        'Calendario de Mantenimiento');
define('SIDEBAR_WORK_ORDERS',      'Órdenes de Trabajo');
define('SIDEBAR_INVENTORY',       'Inventario Biomédico');
define('SIDEBAR_FAMILY_ANALYSIS', 'Análisis por Familia');
define('SIDEBAR_MESSENGER',       'SMS OT (Solicitudes)');

// --- Factores de Costo TCO / Depreciación ---
define('MAINTENANCE_COST_FACTOR', 0.08); // 8% del valor del activo anual
define('REPLACEMENT_COST_FACTOR', 1.25); // 125% del valor original (inflación técnica)
define('RESIDUAL_VALUE_FACTOR', 0.10);    // 10% de valor residual al final de vida útil

// --- Metas Operacionales ---
define('UPTIME_GOAL', 98.5); // Meta de disponibilidad (%)

// --- Costos de Downtime por Área (USD/Hora) ---
// Representa la pérdida de utilidad o impacto por inactividad.
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
define('PENALTY_MISSED_PM', 500);  // USD por cada preventivo no realizado
define('PENALTY_ADVERSE_EVENT', 2500); // USD por cada incidente registrado
