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
                        "medical-dark": "var(--medical-dark)",
                        "medical-blue": "#025082",
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
        };
        applyTheme(localStorage.getItem('theme') || 'dark');
    </script>
    <style type="text/tailwindcss">
        :root {
            --medical-blue: #025082;
            --medical-dark: #f1f5f9;
            --medical-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border-color: #cbd5e1;
        }
        .dark {
            --medical-blue: #3b82f6;
            --medical-dark: #0f172a;
            --medical-surface: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
        }
        body { background-color: var(--medical-dark); color: var(--text-main); }
    </style>
</head>

<div class="w-full max-w-md bg-medical-surface border border-[var(--border-color)] rounded-2xl p-8 shadow-2xl transition-colors duration-300">
    <div class="text-center mb-8">
        <div class="bg-medical-blue rounded-xl w-16 h-16 flex items-center justify-center shadow-lg shadow-medical-blue/20 mx-auto mb-4">
            <svg class="w-10 h-10 text-white fill-current" viewBox="0 0 24 24">
                <path d="M22,2v20h-8V2H22z M12,2v20H4V2H12z" />
            </svg>
        </div>
        <h1 class="text-2xl font-black text-text-main uppercase tracking-tight">BioCMMS Pro</h1>
        <p class="text-text-muted text-sm font-bold uppercase tracking-widest mt-1">Gestión Biomédica Inteligente</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl text-sm font-bold mb-6 text-center">
            <?= $error ?>
        </div>
    <?php endif; ?>


    <form method="POST" class="space-y-6">
        <div>
            <label class="block text-xs font-bold text-text-muted uppercase tracking-wider mb-2">Correo Electrónico</label>
            <input id="emailInput" type="email" name="email" value="admin@biocmms.com" class="w-full bg-medical-surface border border-[var(--border-color)] rounded-xl px-4 py-3 text-text-main focus:ring-2 focus:ring-medical-blue/50 outline-none transition-all placeholder:text-text-muted" placeholder="usuario@hospital.cl">
        </div>
        <div>
            <label class="block text-xs font-bold text-text-muted uppercase tracking-wider mb-2">Contraseña</label>
            <input type="password" name="password" value="password" class="w-full bg-medical-surface border border-[var(--border-color)] rounded-xl px-4 py-3 text-text-main focus:ring-2 focus:ring-medical-blue/50 outline-none transition-all placeholder:text-text-muted" placeholder="••••••••">
        </div>

        <button type="submit" class="w-full bg-medical-blue hover:bg-medical-blue/90 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-medical-blue/20 transition-all active:scale-95 uppercase tracking-wide text-sm">
            Iniciar Sesión
        </button>
    </form>
</div>

<script>
    function fillLogin(email) {
        document.getElementById('emailInput').value = email;
    }
</script>
</body>

</html>