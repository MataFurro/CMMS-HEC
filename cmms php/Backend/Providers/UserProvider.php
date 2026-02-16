<?php

/**
 * Backend/providers/UserProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Usuarios y Técnicos.
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

use Backend\Repositories\UserRepository;

/**
 * Autenticar usuario por email y contraseña
 */
function authenticateUser(string $email, string $password = ''): ?array
{
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        global $mock_users;
        foreach ($mock_users as $user) {
            // Simplified mock auth: allow any mock user for demo
            return $user;
        }
    }

    $repo = new UserRepository();
    $user = $repo->findByEmail($email);

    if ($user) {
        // En modo demo v4.2 Pro, permitimos login simplificado si la pass es placeholder
        if ($user['password_hash'] === '$2y$10$placeholder_hash' || password_verify($password, $user['password_hash'])) {
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
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
        global $technicians;
        $mock_techs = [];
        foreach ($technicians as $t) {
            $mock_techs[] = [
                'name' => $t['name'] ?? '',
                'role' => $t['role'] ?? '',
                'initial' => $t['initial'] ?? '?',
                'ot_terminadas' => $t['stats']['done'] ?? 0,
                'ot_progreso' => $t['stats']['progress'] ?? 0,
                'ot_pendientes' => $t['stats']['pending'] ?? 0,
                'total_ot' => $t['total'] ?? 0,
                'capacity_pct' => $t['capacity'] ?? 0,
                'active' => $t['stats']['progress'] ?? 0,
                'completed' => $t['stats']['done'] ?? 0,
                'capacity' => $t['capacity'] ?? 0
            ];
        }
        return $mock_techs;
    }

    $repo = new UserRepository();
    $techs = $repo->findAllTechnicians();

    return array_map(function ($t) {
        $t['initial'] = !empty($t['name']) ? strtoupper(substr($t['name'], 0, 1)) : '?';
        return $t;
    }, $techs);
}

/**
 * Obtener técnicos ordenados por carga de trabajo/productividad
 */
function getTechnicianProductivity(): array
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
