<?php
// config.php - Configuration file

// Database Credentials (Placeholder)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'biocmms_db');

// App Constants
define('APP_NAME', 'BioCMMS v4.2 Pro');
define('APP_VERSION', '4.2.0');

// Labels (UI)
define('SIDEBAR_DASHBOARD', 'Dashboard');
define('SIDEBAR_CALENDAR', 'Agenda Técnica');
define('SIDEBAR_ORDERS', 'Órdenes');
define('SIDEBAR_INVENTORY', 'Inventario');
define('SIDEBAR_FAMILY_ANALYSIS', 'Análisis por Familia');
define('SIDEBAR_MESSENGER', 'SMS OT');

define('BTN_NEW_ASSET', 'Nuevo Activo');
define('BTN_UPLOAD_EXCEL', 'Cargar Excel');
define('BTN_DOWNLOAD_EXCEL', 'Descargar Excel');

// Status Constants
define('STATUS_OPERATIVE', 'OPERATIVE');
define('STATUS_MAINTENANCE', 'MAINTENANCE');
define('STATUS_OUT_OF_SERVICE', 'OUT_OF_SERVICE');
define('STATUS_OPERATIVE_WITH_OBS', 'OPERATIVE_WITH_OBS');
define('STATUS_NO_OPERATIVE', 'NO_OPERATIVE'); // No Operativo
define('STATUS_DECOMMISSIONED', 'DECOMMISSIONED'); // Dado de Baja

// Helper functions para verificación de permisos
function canModify()
{
    return !in_array($_SESSION['user_role'] ?? '', ['Técnico', 'Auditor']);
}

function canExecuteWorkOrder()
{
    return in_array($_SESSION['user_role'] ?? '', ['Técnico', 'Ingeniero', 'Admin']);
}

function canCompleteWorkOrder()
{
    return !in_array($_SESSION['user_role'] ?? '', ['Técnico', 'Auditor']);
}

function isReadOnly()
{
    return ($_SESSION['user_role'] ?? '') === 'Auditor';
}

function canViewDashboard()
{
    // Técnico y Usuario NO pueden ver el dashboard
    return !in_array($_SESSION['user_role'] ?? '', ['Técnico', 'Usuario']);
}

function canRequestService()
{
    // Todos los roles pueden solicitar servicio
    return !empty($_SESSION['user_role']);
}

// Error Reporting (Enable for Dev)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
