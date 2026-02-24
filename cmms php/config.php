<?php

// BioCMMS v4.3 Pro - Configuración Global
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

// El sistema ahora utiliza repositorios MySQL. 
// Las consultas de técnicos y usuarios se realizan vía UserProvider.php

// Helpers de UI
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
