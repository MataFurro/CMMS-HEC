<?php

// BioCMMS v4.2 Pro - Configuración Global
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/Backend/autoloader.php';
session_start();

// --- Migrador de Roles de Sesión (Previene bucles por datos antiguos) ---
if (isset($_SESSION['user_role'])) {
    $current_role = $_SESSION['user_role'];
    $role_map = [
        'Ingeniero' => ROLE_CHIEF_ENGINEER,
        'Técnico'   => ROLE_TECHNICIAN,
        'Auditor'   => ROLE_AUDITOR,
        'Usuario'   => ROLE_USER,
        'chief_engineer' => ROLE_CHIEF_ENGINEER,
        'engineer'       => ROLE_ENGINEER,
        'technician'    => ROLE_TECHNICIAN,
        'auditor'       => ROLE_AUDITOR,
        'user'          => ROLE_USER,
        'TECHNICIAN'    => ROLE_TECHNICIAN,
        'ENGINEER'      => ROLE_ENGINEER,
        'CHIEF_ENGINEER' => ROLE_CHIEF_ENGINEER
    ];
    if (isset($role_map[$current_role])) {
        $_SESSION['user_role'] = $role_map[$current_role];
    }
}

// --- Soporte para Variables de Entorno (.env) ---
function loadEnv($path)
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') === false)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/.env');

// Parámetros de Base de Datos
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'biocmms');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Modo Demo - Activar para auditoría (Desconecta la DB)
define('USE_MOCK_DATA', filter_var($_ENV['USE_MOCK_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Datos Simulados (Mock Database)
$technicians = [
    ['name' => 'Mario Gómez', 'role' => 'Especialista UCI', 'initial' => 'MG', 'stats' => ['done' => 50, 'progress' => 25, 'pending' => 25], 'total' => 28, 'capacity_pct' => 85],
    ['name' => 'Pablo Rojas', 'role' => 'Electromedicina', 'initial' => 'PR', 'stats' => ['done' => 30, 'progress' => 50, 'pending' => 20], 'total' => 20, 'capacity_pct' => 95],
    ['name' => 'Ana Muñoz', 'role' => 'Imagenología', 'initial' => 'AM', 'stats' => ['done' => 60, 'progress' => 20, 'pending' => 20], 'total' => 15, 'capacity_pct' => 62],
];

// Usuarios Simulados (RBAC)
$mock_users = [
    'u1' => ['id' => 4, 'name' => 'Mario Gómez', 'role' => ROLE_TECHNICIAN, 'avatar' => 'https://i.pravatar.cc/150?u=u1'],
    'u2' => ['id' => 3, 'name' => 'Laura Ingeniera', 'role' => ROLE_ENGINEER, 'avatar' => 'https://i.pravatar.cc/150?u=u2'],
    'u3' => ['id' => 2, 'name' => 'Roberto Jefe', 'role' => ROLE_CHIEF_ENGINEER, 'avatar' => 'https://i.pravatar.cc/150?u=u3'],
    'u4' => ['id' => 1, 'name' => 'Ana Auditora', 'role' => ROLE_AUDITOR, 'avatar' => 'https://i.pravatar.cc/150?u=u4'],
];

// Login simulado para compatibilidad con router legacy
if (!isset($_SESSION['user_id'])) {
    $default_user = $mock_users['u3'];
    $_SESSION['user_id'] = $default_user['id'];
    $_SESSION['user_name'] = $default_user['name'];
    $_SESSION['user_role'] = $default_user['role'];
    $_SESSION['user'] = $default_user;
}

// Helpers de Permisos
function canModify()
{
    return in_array($_SESSION['user_role'] ?? '', [ROLE_ENGINEER, ROLE_CHIEF_ENGINEER]);
}
function canExecuteWorkOrder()
{
    return in_array($_SESSION['user_role'] ?? '', [ROLE_TECHNICIAN, ROLE_CHIEF_ENGINEER]);
}
function canViewDashboard()
{
    return true;
}

function getStatusClass($status)
{
    return match ($status) {
        'COMPLETED', STATUS_OPERATIVE, 'Terminada' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
        'IN_PROGRESS', STATUS_MAINTENANCE, 'En Proceso' => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
        'PENDING', 'Pendiente' => 'bg-slate-700/10 text-slate-500 border-slate-700/30',
        default => 'bg-red-500/10 text-red-500 border-red-500/30',
    };
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
