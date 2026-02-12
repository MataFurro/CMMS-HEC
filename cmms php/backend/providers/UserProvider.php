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
 * Autenticar usuario por email (mock)
 * En producción: validar contra BD con hash de contraseña
 */
function authenticateUser(string $email): ?array
{
    global $MOCK_USERS;

    $userKey = 'auditor'; // Default
    if (strpos($email, 'jefe') !== false) $userKey = 'chief';
    if (strpos($email, 'ing') !== false) $userKey = 'engineer';
    if (strpos($email, 'tec') !== false) $userKey = 'tech';

    if (!empty($email) && isset($MOCK_USERS[$userKey])) {
        return $MOCK_USERS[$userKey];
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
    usort($techs, fn($a, $b) => $b['otTerminadas'] - $a['otTerminadas']);
    return $techs;
}

/**
 * Obtener datos de carga de trabajo de técnicos
 */
function getTechnicianWorkload(): array
{
    return getAllTechnicians();
}
