<?php

/**
 * BioCMMS v4.2 Pro - PHP Constants
 * Nomenclatura estandarizada y tokens de diseño.
 */

// Roles de Usuario (RBAC)
define('ROLE_CHIEF_ENGINEER', 'CHIEF_ENGINEER');
define('ROLE_ENGINEER', 'ENGINEER');
define('ROLE_TECHNICIAN', 'TECHNICIAN');
define('ROLE_AUDITOR', 'AUDITOR');
define('ROLE_USER', 'USER');

// Configuración Estadística/Confiabilidad
define('DEFAULT_BETA_WEIBULL', 1.5);

// Paleta de Colores Premium
define('COLOR_MEDICAL_BLUE', '#0ea5e9');
define('COLOR_EMERALD', '#10b981');
define('COLOR_AMBER', '#f59e0b');
define('COLOR_RED', '#ef4444');
define('COLOR_SLATE_400', '#94a3b8');
define('COLOR_SLATE_700', '#334155');
define('COLOR_BG_DARK', '#1e293b');
define('COLOR_MEDICAL_DARK', '#0f172a');
define('COLOR_PANEL_DARK', '#111820');

// Estados de Activos
define('STATUS_OPERATIVE', 'OPERATIVE');
define('STATUS_MAINTENANCE', 'MAINTENANCE');
define('STATUS_OUT_OF_SERVICE', 'OUT_OF_SERVICE');
define('STATUS_OPERATIVE_WITH_OBS', 'OPERATIVE_WITH_OBS');
define('STATUS_NO_OPERATIVE', 'NO_OPERATIVE');

// Criticidad
define('CRITICALITY_CRITICAL', 'Crítico');
define('CRITICALITY_RELEVANT', 'Relevante');
define('CRITICALITY_LOW', 'Bajo');
define('CRITICALITY_NA', 'No Aplica');

// Etiquetas de UI
define('APP_NAME_HTML', 'BioCMMS v4.2 <span class="text-medical-blue font-light">Pro</span>');
define('SIDEBAR_DASHBOARD', 'Dashboard');
define('SIDEBAR_INVENTORY', 'Inventario');
define('SIDEBAR_WORK_ORDERS', 'Órdenes de Trabajo');
define('SIDEBAR_CALENDAR', 'Agenda Técnica');
define('SIDEBAR_ORDERS', 'Órdenes');
define('SIDEBAR_FAMILY_ANALYSIS', 'Análisis por Familia');

define('BTN_NEW_ASSET', 'Nuevo Activo');
define('BTN_UPLOAD_EXCEL', 'Cargar Excel');
define('BTN_DOWNLOAD_EXCEL', 'Descargar Excel');
