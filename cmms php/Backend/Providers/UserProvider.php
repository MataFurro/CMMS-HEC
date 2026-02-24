<?php

/**
 * Backend/Providers/UserProvider.php
 * ─────────────────────────────────────────────────────
 * Interfaz de acceso a datos de Usuarios y Técnicos.
 * Acceso directo a MySQL (Repositorios).
 * ─────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

use Backend\Repositories\UserRepository;

/**
 * Autenticar Usuario por Email (Demo simplified)
 */
function authenticateUser(string $email): ?array
{
    $user = getUserByEmail($email);
    if (!$user) return null;

    return [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar' => $user['avatar_url'] ?? 'https://i.pravatar.cc/150?u=' . $user['id']
    ];
}

/**
 * Obtener items del sidebar filtrados por rol
 */
function getSidebarMenu(string $userRole): array
{
    $allMenuItems = [
        ['name' => SIDEBAR_DASHBOARD, 'path' => 'dashboard', 'icon' => 'dashboard', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER]],
        ['name' => SIDEBAR_CALENDAR, 'path' => 'calendar', 'icon' => 'calendar_month', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN]],
        ['name' => SIDEBAR_WORK_ORDERS, 'path' => 'work_orders', 'icon' => 'assignment', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN]],
        ['name' => SIDEBAR_INVENTORY, 'path' => 'inventory', 'icon' => 'precision_manufacturing', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN, ROLE_AUDITOR]],
        ['name' => SIDEBAR_FAMILY_ANALYSIS, 'path' => 'family_analysis', 'icon' => 'analytics', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_AUDITOR]],
        ['name' => SIDEBAR_MESSENGER, 'path' => 'messenger_requests', 'icon' => 'mail', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER]],
        ['name' => 'Análisis Financiero', 'path' => 'financial_analysis', 'icon' => 'payments', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER]],
        ['name' => 'Acreditación', 'path' => 'accreditation_dashboard', 'icon' => 'verified', 'roles' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_AUDITOR]],
    ];

    return array_filter($allMenuItems, function ($item) use ($userRole) {
        return in_array($userRole, $item['roles']);
    });
}

/**
 * Verificar permisos de acceso a una página
 */
function userHasPermission(string $userRole, string $page): bool
{
    $page_permissions = [
        'dashboard' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER],
        'calendar' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN],
        'work_orders' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN],
        'inventory' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN, ROLE_AUDITOR],
        'family_analysis' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_AUDITOR],
        'messenger_requests' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER],
        'financial_analysis' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER],
        'service_request' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_USER],
        'accreditation_dashboard' => [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_AUDITOR],
    ];

    if (!isset($page_permissions[$page])) return true;
    return in_array($userRole, $page_permissions[$page]);
}

/**
 * Helper: Can current user modify assets/orders?
 */
function canModify(): bool
{
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER]);
}

/**
 * Helper: Can current user execute work orders?
 */
function canExecuteWorkOrder(): bool
{
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN]);
}

/**
 * Helper: Is the current user restricted to read-only access for execution data?
 */
function isReadOnly(): bool
{
    $role = $_SESSION['user_role'] ?? '';
    // Solo técnicos e ingenieros pueden editar datos de ejecución
    return !in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN]);
}

/**
 * Helper: Can current user finalize/complete the work order?
 */
function canCompleteWorkOrder(): bool
{
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_TECHNICIAN]);
}

/**
 * Helper: Can current user view administrative dashboards?
 */
function canViewDashboard(): bool
{
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER, ROLE_AUDITOR]);
}

/**
 * Obtener todos los técnicos activos
 */
function getActiveTechnicians(): array
{
    try {
        $repo = new UserRepository();
        return $repo->findAllTechnicians();
    } catch (Exception $e) {
        error_log("UserProvider::getActiveTechnicians Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener técnicos por productividad para el dashboard (MySQL Real)
 */
function getTechnicianProductivity(): array
{
    try {
        $repo = new UserRepository();
        return $repo->getTechniciansByProductivity();
    } catch (Exception $e) {
        error_log("UserProvider::getTechnicianProductivity Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Buscar un usuario por email (para login/perfil)
 */
function getUserByEmail(string $email): ?array
{
    try {
        $repo = new UserRepository();
        return $repo->findByEmail($email);
    } catch (Exception $e) {
        error_log("UserProvider::getUserByEmail Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Registrar un nuevo usuario y vincular perfil técnico si aplica
 */
function registerNewUser(array $userData, string $specialty = 'General Biomédico'): ?int
{
    try {
        $repo = new UserRepository();
        $userId = $repo->create($userData);

        if ($userId && $userData['role'] === ROLE_TECHNICIAN) {
            $repo->createTechnician($userId, $specialty);
        }

        return $userId;
    } catch (Exception $e) {
        error_log("UserProvider::registerNewUser Error: " . $e->getMessage());
        return null;
    }
}
