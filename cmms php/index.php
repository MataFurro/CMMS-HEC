<?php
// index.php - Main Router

require_once 'config.php';

// Simple Router
$page = $_GET['page'] ?? 'dashboard';

// Safe allow-list of pages
$allowed_pages = ['dashboard', 'inventory', 'calendar', 'work_orders', 'new_asset', 'login', 'asset', 'work_order_execution', 'work_order_opening', 'family_analysis', 'financial_analysis'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Handle Login Layout vs Main Layout
if ($page === 'login') {
    include "pages/{$page}.php";
    exit;
}

// Check Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Gestión Biomédica</title>

    <!-- External Dependencies (CDN for Simplicity) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: "#137fec",
                        success: "#10b981",
                        warning: "#f59e0b",
                        danger: "#ef4444",
                        "background-light": "#f6f7f8",
                        "background-dark": "#0a0f14",
                        "panel-dark": "#111820",
                        "border-dark": "#283039",
                        "excel-green": "#217346",
                        "medical-blue": "#0ea5e9",
                        "medical-dark": "#0f172a",
                        "medical-surface": "#1e293b",
                        "minsal-blue": "#005bb7",
                    },
                    fontFamily: {
                        display: ["Space Grotesk", "sans-serif"],
                        sans: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
        .card-glass {
          @apply bg-medical-surface border border-slate-700/50 rounded-xl shadow-lg transition-all duration-300;
        }
        .stat-value {
          @apply text-2xl font-bold text-white;
        }
        .stat-label {
          @apply text-xs font-medium text-slate-400 uppercase tracking-wider;
        }
      }
      body { font-family: 'Inter', sans-serif; }
      .custom-scrollbar::-webkit-scrollbar { width: 6px; }
      .custom-scrollbar::-webkit-scrollbar-track { background: #0a0f14; }
      .custom-scrollbar::-webkit-scrollbar-thumb { background: #283039; border-radius: 10px; }
      .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>

<body class="bg-medical-dark text-slate-200 antialiased min-h-screen">
    <div class="flex min-h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
            <?php include 'includes/header.php'; ?>

            <main class="p-6 lg:p-8 max-w-[1600px] w-full mx-auto custom-scrollbar ml-20 lg:ml-0 overflow-x-hidden">
                <?php
                $file = "pages/{$page}.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<div class='p-10 text-center text-slate-500'>Página <strong>{$page}</strong> en construcción.</div>";
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Alpine.js for Interactivity replacement (Simple Dropdowns/Modals) -->
    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>