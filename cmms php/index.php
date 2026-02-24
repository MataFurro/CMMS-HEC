<?php
// index.php - Main Router

// ── Manejador Global de Excepciones (previene Fatal Error por DB) ──
set_exception_handler(function (Throwable $e) {
    $isDbError = str_contains($e->getMessage(), 'MySQL') || str_contains($e->getMessage(), 'conexión') || str_contains($e->getMessage(), 'SQLSTATE') || $e instanceof PDOException;
    $title   = $isDbError ? 'Base de Datos No Disponible' : 'Error del Sistema';
    $icon    = $isDbError ? 'storage' : 'bug_report';
    $detail  = htmlspecialchars($e->getMessage());
    http_response_code($isDbError ? 503 : 500);
    echo "<!DOCTYPE html><html lang='es' class='dark'><head>
        <meta charset='UTF-8'><title>BioCMMS - Error</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link href='https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined' rel='stylesheet'>
    </head><body class='bg-slate-900 min-h-screen flex items-center justify-center font-sans'>
        <div class='text-center space-y-6 max-w-lg px-8'>
            <span class='material-symbols-outlined text-7xl text-red-500'>$icon</span>
            <h1 class='text-2xl font-black text-white'>$title</h1>
            <p class='text-slate-400 text-sm'>Ocurrió un error que impidió cargar la aplicación. Por favor verifica el estado del servidor MySQL en el Panel de Control de XAMPP.</p>
            <details class='text-left bg-slate-800 rounded-xl p-4 text-xs text-red-400 font-mono cursor-pointer'>
                <summary class='text-slate-500 mb-2'>Ver detalle técnico</summary>
                $detail
            </details>
            <a href='?page=dashboard' class='inline-block mt-4 px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all'>
                Reintentar
            </a>
        </div>
    </body></html>";
    exit;
});

require_once 'config.php';
if (!defined('APP_NAME')) define('APP_NAME', 'BioCMMS v4.3 Pro');

// 1. Determine target page
$allowed_pages = ['dashboard', 'inventory', 'calendar', 'work_orders', 'new_asset', 'login', 'asset', 'work_order_execution', 'work_order_opening', 'family_analysis', 'financial_analysis', 'messenger_requests', 'service_request', 'service_request_review', 'accreditation_dashboard'];

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
                        "medical-blue": "var(--medical-blue)",
                        "medical-dark": "var(--medical-dark)",
                        "medical-surface": "var(--medical-surface)",
                        "panel-dark": "var(--panel-dark)",
                        "border-dark": "var(--border-dark)",
                        "text-main": "var(--text-main)",
                        "text-muted": "var(--text-muted)",
                        "success": "#166534",
                        "danger": "#ef4444",
                        "excel-green": "#16a34a",
                    }
                }
            }
        }

        const applyTheme = (theme) => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            localStorage.setItem('theme', theme);
        };
        applyTheme(localStorage.getItem('theme') || 'dark');
    </script>
    <style type="text/tailwindcss">
        :root {
            /* Light Theme: Clinical (Solid & Clear) */
            --medical-blue: #025082;
            --medical-dark: #f1f5f9;   /* Slate 100 - Clean Background */
            --medical-surface: #ffffff;
            --panel-dark: #e2e8f0;     /* Slate 200 - Sidebar/Header */
            --border-dark: #cbd5e1;    /* Slate 300 - Borders */
            --text-main: #0f172a;      /* Slate 900 - Dark Text */
            --text-muted: #475569;     /* Slate 600 - Visible Labels */
            --input-bg: #ffffff;
            --header-bg: #ffffff;
        }

        .dark {
            /* Dark Theme: Deep Medical */
            --medical-blue: #3b82f6;
            --medical-dark: #0f172a;
            --medical-surface: #1e293b;
            --panel-dark: #111827;
            --border-dark: #334155;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --header-bg: rgba(15, 23, 42, 0.9);
        }

        .card-glass { 
            background-color: var(--medical-surface);
            border: 1px solid var(--border-dark);
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
            transition-property: all;
            transition-duration: 300ms;
        }
        .sidebar-link-active {
            color: var(--medical-blue);
            background-color: rgba(59, 130, 246, 0.1); /* bg-medical-blue/10 */
            font-weight: 700;
            border-right-width: 4px;
            border-color: var(--medical-blue);
        }
        .sidebar-link-inactive {
            color: var(--text-muted);
            transition-property: all;
            transition-duration: 300ms;
        }
        .sidebar-link-inactive:hover {
            color: var(--text-main);
        }
        .dark .sidebar-link-inactive:hover {
            background-color: #1e293b; /* bg-slate-800 */
        }
        .sidebar-link-inactive:hover:not(.dark *) {
            background-color: #e2e8f0; /* bg-slate-200 */
        }
        .stat-value { font-size: 1.5rem; line-height: 2rem; font-weight: 700; color: var(--text-main); }
        .stat-label { font-size: 0.75rem; line-height: 1rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--medical-dark); 
            color: var(--text-main); 
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--medical-dark); }
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