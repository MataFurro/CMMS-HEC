<?php
// BioCMMS v4.2 Pro - Configuración Global (Portado a cmms php)
require_once __DIR__ . '/includes/constants.php';
session_start();

// Datos Simulados (Mock Database) - Paridad con versión GitHub
$technicians = [
    ['name' => 'Mario Gómez', 'role' => 'Especialista UCI', 'initial' => 'MG', 'stats' => ['done' => 50, 'progress' => 25, 'pending' => 25], 'total' => 28, 'capacity' => 85],
    ['name' => 'Pablo Rojas', 'role' => 'Electromedicina', 'initial' => 'PR', 'stats' => ['done' => 30, 'progress' => 50, 'pending' => 20], 'total' => 20, 'capacity' => 95],
    ['name' => 'Ana Muñoz', 'role' => 'Imagenología', 'initial' => 'AM', 'stats' => ['done' => 60, 'progress' => 20, 'pending' => 20], 'total' => 15, 'capacity' => 62],
];

// Usuarios Simulados (RBAC)
$mock_users = [
    'u1' => ['id' => 4, 'name' => 'Mario Gómez', 'role' => 'TECHNICIAN', 'avatar' => 'https://i.pravatar.cc/150?u=u1'],
    'u2' => ['id' => 3, 'name' => 'Laura Ingeniera', 'role' => 'ENGINEER', 'avatar' => 'https://i.pravatar.cc/150?u=u2'],
    'u3' => ['id' => 2, 'name' => 'Roberto Jefe', 'role' => 'CHIEF_ENGINEER', 'avatar' => 'https://i.pravatar.cc/150?u=u3'],
    'u4' => ['id' => 1, 'name' => 'Ana Auditora', 'role' => 'AUDITOR', 'avatar' => 'https://i.pravatar.cc/150?u=u4'],
];

// Labels (UI)
define('SIDEBAR_DASHBOARD', 'Dashboard');
define('SIDEBAR_CALENDAR', 'Agenda Técnica');
define('SIDEBAR_ORDERS', 'Órdenes');
define('SIDEBAR_INVENTORY', 'Inventario');
define('SIDEBAR_FAMILY_ANALYSIS', 'Análisis por Familia');
define('SIDEBAR_MESSENGER', 'SMS OT');

// Login simulado para compatibilidad con router legacy
if (!isset($_SESSION['user_id'])) {
    $default_user = $mock_users['u3'];
    $_SESSION['user_id'] = $default_user['id'];
    $_SESSION['user_name'] = $default_user['name'];
    $_SESSION['user_role'] = $default_user['role']; // Ahora usando roles UPPERCASE de la v4.2
    $_SESSION['user'] = $default_user;
}

// ── Helpers de Permisos Mejorados ──
function canModify()
{
    return in_array($_SESSION['user']['role'] ?? '', ['ENGINEER', 'CHIEF_ENGINEER']);
}

function canExecuteWorkOrder()
{
    return in_array($_SESSION['user']['role'] ?? '', ['TECHNICIAN', 'CHIEF_ENGINEER']);
}

function canViewDashboard()
{
    return true;
}


function getStatusClass($status)
{
    return match ($status) {
        'COMPLETED', STATUS_OPERATIVE => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
        'IN_PROGRESS', STATUS_MAINTENANCE => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
        'PENDING', 'Pendiente' => 'bg-slate-700/10 text-slate-500 border-slate-700/30',
        default => 'bg-red-500/10 text-red-500 border-red-500/30',
    };
}

// Error Reporting (Mantener para Dev)
error_reporting(E_ALL);
ini_set('display_errors', 1);
