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
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        "medical-dark": "#0f172a",
                        "medical-blue": "#0ea5e9",
                        "medical-surface": "#1e293b",
                    },
                    fontFamily: {
                        sans: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-medical-dark text-slate-200 antialiased h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-medical-surface border border-slate-700/50 rounded-2xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <div class="bg-medical-blue rounded-xl w-16 h-16 flex items-center justify-center shadow-lg shadow-medical-blue/20 mx-auto mb-4">
                <svg class="w-10 h-10 text-white fill-current" viewBox="0 0 24 24">
                    <path d="M22,2v20h-8V2H22z M12,2v20H4V2H12z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-white uppercase tracking-tight">BioCMMS Pro</h1>
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mt-1">Gestión Biomédica Inteligente</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl text-sm font-bold mb-6 text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Demo Users Hint -->
        <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4 mb-6">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Selecciona un Rol (Demo):</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="fillLogin('auditor@biocmms.com')" class="text-left p-2 hover:bg-white/5 rounded-lg transition-colors group">
                    <div class="text-xs font-bold text-white group-hover:text-medical-blue">Auditor</div>
                    <div class="text-[9px] text-slate-500">Solo Ver</div>
                </button>
                <button type="button" onclick="fillLogin('jefe@biocmms.com')" class="text-left p-2 hover:bg-white/5 rounded-lg transition-colors group">
                    <div class="text-xs font-bold text-white group-hover:text-medical-blue">Ing. Jefe</div>
                    <div class="text-[9px] text-slate-500">Admin Total</div>
                </button>
                <button type="button" onclick="fillLogin('ing@biocmms.com')" class="text-left p-2 hover:bg-white/5 rounded-lg transition-colors group">
                    <div class="text-xs font-bold text-white group-hover:text-medical-blue">Ing. Biomédico</div>
                    <div class="text-[9px] text-slate-500">Gestión</div>
                </button>
                <button type="button" onclick="fillLogin('tec@biocmms.com')" class="text-left p-2 hover:bg-white/5 rounded-lg transition-colors group">
                    <div class="text-xs font-bold text-white group-hover:text-medical-blue">Técnico</div>
                    <div class="text-[9px] text-slate-500">Operativo</div>
                </button>
                <button type="button" onclick="fillLogin('demo@biocmms.com')" class="text-left p-2 hover:bg-white/5 rounded-lg transition-colors group col-span-2 border border-dashed border-slate-700 hover:border-medical-blue/50">
                    <div class="text-xs font-bold text-white group-hover:text-medical-blue flex items-center gap-2">
                        <span class="material-symbols-outlined text-xs">clinical_notes</span> Usuario Clínico (Demo)
                    </div>
                    <div class="text-[9px] text-slate-500">Petición de Soporte</div>
                </button>
            </div>
        </div>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
                <input id="emailInput" type="email" name="email" value="admin@biocmms.com" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-medical-blue/50 outline-none transition-all placeholder:text-slate-600" placeholder="usuario@hospital.cl">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Contraseña</label>
                <input type="password" name="password" value="password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-medical-blue/50 outline-none transition-all placeholder:text-slate-600" placeholder="••••••••">
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