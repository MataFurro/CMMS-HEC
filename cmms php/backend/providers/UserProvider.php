<?php

/**
 * backend/providers/UserProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Usuarios y Técnicos.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../data/mock_data.php';

/**
 * Obtener todos los roles de usuario disponibles
 */
function getAllUserRoles(): array
{
    global $MOCK_USERS;
    return $MOCK_USERS;
}

/**
 * Autenticar usuario por email y contraseña (mock)
 */
function authenticateUser(string $email, string $password = ''): ?array
{
    global $MOCK_USERS;

    $userKey = null;
    if (strpos($email, 'jefe') !== false)
        $userKey = 'chief';
    elseif (strpos($email, 'ing') !== false)
        $userKey = 'engineer';
    elseif (strpos($email, 'tec') !== false)
        $userKey = 'tech';
    elseif (strpos($email, 'auditor') !== false)
        $userKey = 'auditor';

    if ($userKey && isset($MOCK_USERS[$userKey])) {
        $user = $MOCK_USERS[$userKey];
        // En modo demo, si no se provee pass, dejamos pasar (solo para login simplificado)
        // En producción ESTO DEBE SER OBLIGATORIO
        if (empty($password) || password_verify($password, $user['password_hash'])) {
            return $user;
        }
    }

    return null;
}

/**
 * Obtener todos los técnicos con métricas de trabajo
 */
function getAllTechnicians(): array
{
    global $MOCK_TECHNICIANS;
    return $MOCK_TECHNICIANS;
}

/**
 * Obtener técnicos ordenados por OT terminadas (ranking)
 */
function getTechnicianRanking(): array
{
    $techs = getAllTechnicians();
    usort($techs, fn($a, $b) => $b['ot_terminadas'] - $a['ot_terminadas']);
    return $techs;
}

/**
 * Obtener datos de carga de trabajo de técnicos
 */
function getTechnicianWorkload(): array
{
    return getAllTechnicians();
}
