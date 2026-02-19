<?php
// index.php - Main Router

require_once 'config.php';
if (!defined('APP_NAME')) define('APP_NAME', 'BioCMMS v4.2 Pro');

// 1. Determine target page
$allowed_pages = ['dashboard', 'inventory', 'calendar', 'work_orders', 'new_asset', 'login', 'asset', 'work_order_execution', 'work_order_opening', 'family_analysis', 'financial_analysis', 'messenger_requests', 'service_request', 'service_request_review', 'audit_trail'];

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$page = $_GET['page'] ?? null;

// Ensure landing on login if no session
if (!$page && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$page = $page ?? 'dashboard';
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// 2. Auth Check
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: ?page=login');
    exit;
}

// 3. Prevent logged-in users from seeing login page
if (isset($_SESSION['user_id']) && $page === 'login' && ($_GET['action'] ?? '') !== 'logout') {
    $role = $_SESSION['user_role'] ?? '';
    $redirect = 'dashboard';
    if ($role === ROLE_TECHNICIAN) $redirect = 'work_orders';
    if ($role === ROLE_USER) $redirect = 'service_request';
    if ($role === ROLE_AUDITOR) $redirect = 'inventory';

    header("Location: ?page=$redirect");
    exit;
}

// 4. User Bypass
if (isset($_SESSION['user_id']) && $page === 'dashboard' && ($_SESSION['user_role'] ?? '') === ROLE_USER) {
    header('Location: ?page=service_request');
    exit;
}

// 5. RBAC Check
if (isset($_SESSION['user_id'])) {
    require_once 'Backend/Providers/UserProvider.php'; // Standardized Path
    if (!userHasPermission($_SESSION['user_role'] ?? '', $page)) {
        if ($page !== 'dashboard') {
            header("Location: ?page=dashboard");
            exit;
        }
        echo "<div class='p-10 text-center bg-medical-dark min-h-screen'><h1 class='text-2xl font-black text-red-500'>Acceso Restringido</h1><p class='text-slate-500'>No tienes permisos para esta vista.</p><a href='?page=login&action=logout' class='mt-4 inline-block text-medical-blue font-bold'>Cerrar Sesión</a></div>";
        exit;
    }
}

$hide_sidebar = ($page === 'login' || ($_SESSION['user_role'] ?? '') === ROLE_USER);

if ($page === 'login' || $page === 'service_request') {
    include "pages/{$page}.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME_HTML ?> - Gestión Biomédica</title>

    <!-- External Dependencies -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        "medical-blue": "<?= COLOR_MEDICAL_BLUE ?>",
                        "medical-dark": "<?= COLOR_MEDICAL_DARK ?>",
                        "medical-surface": "<?= COLOR_BG_DARK ?>",
                        "panel-dark": "<?= COLOR_PANEL_DARK ?>",
                        "border-dark": "<?= COLOR_SLATE_700 ?>",
                        "primary": "<?= COLOR_MEDICAL_BLUE ?>",
                        "success": "<?= COLOR_EMERALD ?>",
                        "danger": "<?= COLOR_RED ?>",
                        "excel-green": "#16a34a",
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .card-glass { @apply bg-medical-surface border border-slate-700/50 rounded-xl shadow-lg transition-all duration-300; }
            .stat-value { @apply text-2xl font-bold text-white; }
            .stat-label { @apply text-xs font-medium text-slate-400 uppercase tracking-wider; }
        }
        body { font-family: 'Inter', sans-serif; background-color: <?= COLOR_MEDICAL_DARK ?>; color: #e2e8f0; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: <?= COLOR_MEDICAL_DARK ?>; }
        ::-webkit-scrollbar-thumb { @apply bg-slate-700 rounded-full hover:bg-slate-600; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>

<body class="antialiased min-h-screen">
    <div class="flex">
        <?php if (!$hide_sidebar) include 'includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
            <?php if (!$hide_sidebar) include 'includes/header.php'; ?>

            <main class="p-6 lg:p-10 max-w-[1600px] w-full mx-auto overflow-y-auto">
                <?php
                $file = "pages/{$page}.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<div class='h-[60vh] flex flex-col items-center justify-center text-center space-y-4 text-slate-500'>
                            <span class='material-symbols-outlined text-6xl'>construction</span>
                            <p>Módulo <strong>{$page}</strong> en fase de optimización.</p>
                          </div>";
                }
                ?>
            </main>
        </div>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>