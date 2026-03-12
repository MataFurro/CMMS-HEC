<?php
// pages/login.php

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ?page=login');
    exit;
}

// ── Backend Provider ──
require_once __DIR__ . '/../Backend/Providers/UserProvider.php';

// Handle Login POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Token de seguridad inválido. Por favor, recargue la página e intente de nuevo.';
    } else {
        $email = $_POST['email'] ?? '';
        $user = authenticateUser($email);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];
            $_SESSION['user'] = $user; // Store full object for helpers

            $redirect = 'dashboard';
            if ($user['role'] === ROLE_TECHNICIAN) $redirect = 'work_orders';
            if ($user['role'] === ROLE_USER) $redirect = 'service_request';
            if ($user['role'] === ROLE_AUDITOR) $redirect = 'inventory';

            header('Location: ?page=' . $redirect);
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        "medical-blue": "var(--medical-blue)",
                        "medical-dark": "var(--medical-dark)",
                        "medical-surface": "var(--medical-surface)",
                        "text-main": "var(--text-main)",
                        "text-muted": "var(--text-muted)",
                    },
                    fontFamily: {
                        sans: ["Inter", "sans-serif"]
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
            --medical-blue: #059669;    /* Emerald 600 */
            --medical-blue-hover: #047857; /* Emerald 700 */
            --medical-dark: #f8fafc;
            --medical-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #334155;
            --border-color: #b1b9c5;
            --bg-pattern: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 30L30 25L35 25L35 30L40 30L40 35L35 35L35 40L30 40L30 35L25 35L25 30L30 30Z' fill='%2310b981' fill-opacity='0.05'/%3E%3C/svg%3E");
        }
        .dark {
            --medical-blue: #3b82f6;    /* Blue 500 */
            --medical-blue-hover: #2563eb; 
            --medical-dark: #0f172a;
            --medical-surface: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #475569;
            --bg-pattern: none;
        }
        body { 
            background-color: var(--medical-dark); 
            background-image: var(--bg-pattern);
            background-attachment: fixed;
            color: var(--text-main); 
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 transition-colors duration-500 overflow-hidden relative">

    <!-- Decorative Background Elements -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-medical-blue/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Theme Switcher -->
    <button onclick="toggleTheme()" class="fixed top-6 right-6 w-12 h-12 rounded-2xl bg-medical-surface border border-[var(--border-color)] flex items-center justify-center shadow-xl hover:scale-110 active:scale-95 transition-all z-50 group">
        <span id="themeIcon" class="material-symbols-outlined text-text-main group-hover:text-medical-blue transition-colors">dark_mode</span>
    </button>

    <div class="w-full max-w-md bg-medical-surface/80 backdrop-blur-xl border border-[var(--border-color)] rounded-[2.5rem] p-10 shadow-[0_20px_50px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] transition-all duration-500 relative overflow-hidden group">
        <!-- Shine Effect -->
        <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 pointer-events-none"></div>

        <div class="text-center mb-10">
            <div class="bg-medical-blue rounded-3xl w-20 h-20 flex items-center justify-center shadow-2xl shadow-medical-blue/30 mx-auto mb-6 transform hover:rotate-12 transition-transform duration-500">
                <svg class="w-12 h-12 text-white fill-current" viewBox="0 0 24 24">
                    <path d="M22,2v20h-8V2H22z M12,2v20H4V2H12z" />
                </svg>
            </div>
            <h1 class="text-3xl font-black text-text-main uppercase tracking-tighter">BioCMMS <span class="text-medical-blue font-light">Pro</span></h1>
            <div class="flex items-center justify-center gap-2 mt-2">
                <span class="h-[1px] w-4 bg-medical-blue/40"></span>
                <p class="text-text-muted text-[10px] font-black uppercase tracking-[0.3em]">Gestión Inteligente</p>
                <span class="h-[1px] w-4 bg-medical-blue/40"></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-4 rounded-2xl text-xs font-black uppercase tracking-widest mb-8 text-center animate-pulse">
                <span class="material-symbols-outlined text-sm inline-block align-middle mr-1">warning</span>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-7">
            <?= csrfField() ?>
            <div>
                <label class="block text-[10px] font-black text-text-main uppercase tracking-[0.2em] mb-3 ml-2">Acceso Institucional</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted group-focus-within:text-medical-blue transition-colors text-xl">alternate_email</span>
                    <input id="emailInput" type="email" name="email" value="admin@biocmms.com"
                        class="w-full bg-medical-surface border-2 border-[var(--border-color)] rounded-2xl pl-12 pr-6 py-4 text-text-main font-semibold focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue outline-none transition-all placeholder:text-text-muted/60"
                        placeholder="usuario@hospital.cl">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-text-main uppercase tracking-[0.2em] mb-3 ml-2">Llave de Seguridad</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted group-focus-within:text-medical-blue transition-colors text-xl">lock_open</span>
                    <input type="password" name="password" value="password"
                        class="w-full bg-medical-surface border-2 border-[var(--border-color)] rounded-2xl pl-12 pr-6 py-4 text-text-main font-semibold focus:ring-4 focus:ring-medical-blue/10 focus:border-medical-blue outline-none transition-all placeholder:text-text-muted/60"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full bg-medical-blue hover:bg-[var(--medical-blue-hover)] text-white font-black py-5 rounded-2xl shadow-xl shadow-medical-blue/20 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3 group border-b-4 border-black/10 hover:border-black/20">
                Entrar al Sistema
                <span class="material-symbols-outlined text-base group-hover:translate-x-1 transition-transform">bolt</span>
            </button>
        </form>

        <div class="mt-10 text-center">
            <p class="text-text-main text-[11px] font-bold uppercase tracking-widest opacity-60">Versión 4.4 Build 2026</p>
        </div>
    </div>

    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script>
        function fillLogin(email) {
            document.getElementById('emailInput').value = email;
        }

        function toggleTheme() {
            const current = localStorage.getItem('theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', next);
            applyTheme(next);
            updateThemeIcon(next);
        }

        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
            }
        }

        // Initialize icon
        updateThemeIcon(localStorage.getItem('theme') || 'dark');
    </script>
</body>

</html>