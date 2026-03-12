<?php
// index.php - Main Router
ob_start();

// ── Manejador Global de Excepciones (previene Fatal Error por DB) ──
set_exception_handler(function (Throwable $e) {
    $isDbError = str_contains($e->getMessage(), 'MySQL') || str_contains($e->getMessage(), 'conexión') || str_contains($e->getMessage(), 'SQLSTATE') || $e instanceof PDOException;
    $title   = $isDbError ? 'Base de Datos No Disponible' : 'Error del Sistema';
    $icon    = $isDbError ? 'storage' : 'bug_report';
    $detail  = htmlspecialchars($e->getMessage()) . "\nFile: " . htmlspecialchars($e->getFile()) . "\nLine: " . $e->getLine();
    $instruction = $isDbError
        ? 'Por favor verifica el estado del servidor MySQL en el Panel de Control de XAMPP.'
        : 'Se ha detectado una anomalía técnica en el código de la aplicación.';

    http_response_code($isDbError ? 503 : 500);
    echo "<!DOCTYPE html><html lang='es' class='dark'><head>
        <meta charset='UTF-8'><title>BioCMMS - Error</title>
        <script src='assets/vendor/tailwind.min.js'></script>
        <link href='https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined' rel='stylesheet'>
        <style>
            :root {
                --medical-blue: #059669;
                --medical-dark: #f8fafc;
                --medical-surface: #ffffff;
                --text-main: #0f172a;
                --text-muted: #334155;
            }
            .dark {
                --medical-blue: #3b82f6;
                --medical-dark: #0f172a;
                --medical-surface: #1e293b;
                --text-main: #f1f5f9;
                --text-muted: #94a3b8;
            }
            body { background-color: var(--medical-dark); color: var(--text-main); font-family: sans-serif; }
            .card { background-color: var(--medical-surface); border: 1px solid rgba(0,0,0,0.1); }
            .dark .card { border-color: rgba(255,255,255,0.1); }
        </style>
        <script>
            if (localStorage.getItem('theme') === 'light') document.documentElement.classList.remove('dark');
        </script>
    </head><body class='min-h-screen flex items-center justify-center p-6 transition-colors duration-500'>
        <div class='card text-center space-y-6 max-w-lg w-full px-8 py-10 rounded-[2rem] shadow-2xl'>
            <div class='w-20 h-20 bg-red-500/10 rounded-3xl flex items-center justify-center mx-auto mb-4'>
                <span class='material-symbols-outlined text-5xl text-red-500'>$icon</span>
            </div>
            <h1 class='text-2xl font-black tracking-tight'>$title</h1>
            <p class='text-sm opacity-70 leading-relaxed'>$instruction</p>
            <details class='text-left bg-black/5 dark:bg-white/5 rounded-2xl p-4 text-[10px] font-mono cursor-pointer border border-transparent hover:border-red-500/30 transition-all'>
                <summary class='opacity-50 mb-2 font-bold uppercase tracking-widest'>Detalle técnico</summary>
                <div class='whitespace-pre-wrap opacity-80'>$detail</div>
            </details>
            <a href='?page=dashboard' class='inline-flex items-center gap-2 px-8 py-4 bg-red-500 hover:bg-red-600 text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-red-500/20 transition-all active:scale-95'>
                <span class='material-symbols-outlined text-base'>refresh</span>
                Reintentar Conexión
            </a>
        </div>
    </body></html>";
    exit;
});

require_once 'Backend/Core/TemplateUtils.php';
require_once 'Backend/Core/SecurityUtils.php';
require_once 'config.php';
if (!defined('APP_NAME')) define('APP_NAME', 'BioCMMS v4.5');

// 1. Determine target page
$allowed_pages = ['dashboard', 'inventory', 'calendar', 'work_orders', 'new_asset', 'login', 'asset', 'work_order_execution', 'work_order_opening', 'family_analysis', 'financial_analysis', 'messenger_requests', 'service_request', 'service_request_review', 'accreditation_dashboard', 'bulk_management', 'my_work_orders', 'retired_assets'];

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$page = $_GET['page'] ?? null;

// Ensure landing on login if no session
if (!$page && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

// --- Validación CSRF Global (Enterprise Security) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $providedToken)) {
        http_response_code(403);
        die("Error de Seguridad [CSRF]: Solicitud no autorizada en BioCMMS v4.5");
    }
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

if ($page === 'login' || $page === 'service_request' || (in_array($page, ['bulk_management', 'retired_assets']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
    include "pages/{$page}.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BioCMMS v4.5 - Gestión Biomédica</title>

    <!-- External Dependencies -->
    <script src="assets/vendor/tailwind.min.js"></script>
    <!-- Alpine.js Plugins (MUST come before core) -->
    <!-- Dependencies (Localized v4.5 Enterprise) -->
    <script src="assets/vendor/alpine-collapse.min.js" defer></script>
    <script src="assets/vendor/alpine.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet">

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
        // Reset global para Ultra Wide
        window.addEventListener('load', () => {
            document.documentElement.style.maxWidth = 'none';
            document.body.style.maxWidth = 'none';
        });
    </script>
    <link rel="stylesheet" href="assets/css/core-v45.css">
    <style>
        html,
        body {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Contenedor principal: usa todo el ancho disponible del flex-1 */
        main {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            padding: 2rem !important;
        }

        /* Contenido interior: ancho máximo para pantallas ultra wide */
        main>* {
            max-width: 1800px;
            /* Permitimos mucho más espacio en vez de 1400 */
            margin: 0 auto;
            /* Centrar orgánicamente si es más grande que 1800 */
            width: 100%;
        }

        /* Ejecución de OT: SIN max-width, usa todo el espacio del flex-1 */
        .page-work_order_execution main>*,
        .page-work_order_execution #executionState,
        .page-work_order_execution #executionForm {
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Ejecución de OT necesita padding propio para que sticky funcione bien */
        .page-work_order_execution main {
            padding: 1.5rem 2rem !important;
        }

        /* 3. Componentes de Mediciones Técnicas (Grid Horizontal) */
        .grid-tech-horizontal {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
        }

        .tech-measurement-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 0.75rem 1rem;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            transition: all 0.2s;
        }

        .tech-label {
            font-size: 10px;
            font-weight: 900;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-grow: 1;
        }

        .tech-input-wrapper {
            width: 120px;
            flex-shrink: 0;
        }
    </style>
    <script src="assets/vendor/chart.min.js"></script>
    <script src="assets/vendor/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="assets/css/theme-pro.css">
</head>

<body class="antialiased min-h-screen page-<?= $page ?>">
    <div class="flex w-full min-h-screen">
        <?php if (!$hide_sidebar) include 'includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 w-full">
            <?php if (!$hide_sidebar) include 'includes/header.php'; ?>

            <main class="w-full flex-1 overflow-y-auto">
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
</body>

</html>