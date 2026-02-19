<?php
// config.php
require_once __DIR__ . '/Backend/autoloader.php';
require_once 'includes/constants.php';
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
        'user'          => ROLE_USER
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
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/.env');

// Parámetros de Base de Datos (Prioridad: .env > Constantes locales)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'biocmms');
define('DB_USER', $_ENV['DB_USER'] ?? 'biocmms_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'BioPass2026');

// Modo Demo - Activar para auditoría (Desconecta la DB)
define('USE_MOCK_DATA', isset($_ENV['USE_MOCK_DATA']) ? ($_ENV['USE_MOCK_DATA'] === 'true') : false);
