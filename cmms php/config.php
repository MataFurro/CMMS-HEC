<?php

// BioCMMS v4.5 - Configuración Global
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/Backend/autoloader.php';
session_start();

// --- Protección CSRF v4.5 ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Migrador de Roles de Sesión  ---
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

// --- Soporte para Variables de Entorno  ---
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

// MODO REAL FORZADO: Desactivar Mock Data para conectar a XAMPP directamente
// Cambiar a true solo para testear estados "sin datos" en las tablas (UI testing)
define('FORCE_EMPTY_STATE', false); 

// El sistema ahora utiliza repositorios MySQL. 
// Las consultas de técnicos y usuarios se realizan vía UserProvider.php

// Helpers de UI
function getStatusClass($status)
{
    return match ($status) {
        'Terminada' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
        'En Curso' => 'bg-blue-500/10 text-blue-500 border-blue-500/30',
        'En Espera' => 'bg-amber-500/10 text-amber-500 border-amber-500/30',
        'Cancelada' => 'bg-red-500/10 text-red-500 border-red-500/30',
        default => 'bg-slate-700/10 text-slate-500 border-slate-700/30',
    };
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
