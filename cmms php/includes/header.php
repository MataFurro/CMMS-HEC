<?php
// includes/header.php
?>
<header class="h-16 lg:h-20 border-b border-border-dark bg-medical-surface/80 backdrop-blur-md sticky top-0 z-40 px-6 lg:px-10 flex items-center justify-between ml-20 lg:ml-0" x-data="{ showNotifications: false, showSettings: false }">
    <div class="flex lg:hidden items-center gap-3">
        <h2 class="text-sm font-black text-text-main tracking-tighter uppercase">BioCMMS</h2>
    </div>
    <div class="hidden md:flex items-center bg-medical-surface border border-border-dark rounded-lg px-4 py-1.5 group focus-within:border-medical-blue/50 transition-all">
        <span class="material-symbols-outlined text-text-muted text-lg group-focus-within:text-medical-blue transition-colors">search</span>
        <input
            class="bg-transparent border-none focus:ring-0 text-xs w-48 lg:w-72 placeholder:text-slate-600 text-text-main outline-none"
            placeholder="Buscar activos, OTs, personal..."
            type="text" />
    </div>
    <div class="flex items-center gap-3 lg:gap-5">
        <!-- Theme Switcher -->
        <button
            onclick="const newTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark'; applyTheme(newTheme);"
            class="p-2 rounded-lg bg-medical-surface border border-border-dark text-text-muted hover:text-medical-blue transition-all active:scale-95 flex items-center justify-center"
            title="Cambiar Tema (Día/Noche)">
            <span class="material-symbols-outlined text-xl dark:hidden">dark_mode</span>
            <span class="material-symbols-outlined text-xl hidden dark:block">light_mode</span>
        </button>

        <div class="hidden sm:flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">En Línea</span>
        </div>

        <!-- Notifications Dropdown (Alpine.js) -->
        <div class="relative">
            <button
                @click="showNotifications = !showNotifications; showSettings = false"
                class="p-2 rounded-lg bg-medical-surface border border-border-dark text-text-muted relative hover:text-medical-blue transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span class="absolute top-2 right-2 size-1.5 bg-red-500 rounded-full border border-medical-surface"></span>
            </button>

            <div x-show="showNotifications"
                @click.away="showNotifications = false"
                class="absolute right-0 top-full mt-2 w-80 bg-medical-surface border border-border-dark rounded-xl shadow-2xl z-50 overflow-hidden"
                style="display: none;">
                <div class="p-3 border-b border-border-dark flex justify-between items-center">
                    <span class="text-xs font-bold text-text-main uppercase tracking-wider">Notificaciones</span>
                    <span class="text-[10px] text-medical-blue font-bold cursor-pointer hover:underline">Marcar leídas</span>
                </div>
                <!-- Mock Notifications -->
                <div class="max-h-64 overflow-y-auto custom-scrollbar p-3 text-center text-slate-500 text-xs">
                    No hay notificaciones nuevas
                </div>
            </div>
        </div>

        <div class="h-6 w-px bg-slate-700 hidden lg:block"></div>

        <!-- Settings Dropdown (Alpine.js) -->
        <div class="relative hidden lg:block">
            <button
                @click="showSettings = !showSettings; showNotifications = false"
                class="p-2 rounded-lg bg-medical-surface border border-border-dark text-text-muted hover:text-medical-blue transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">settings</span>
            </button>

            <div x-show="showSettings"
                @click.away="showSettings = false"
                class="absolute right-0 top-full mt-2 w-48 bg-medical-surface border border-border-dark rounded-xl shadow-2xl z-50 overflow-hidden"
                style="display: none;">
                <div class="p-3 border-b border-border-dark">
                    <p class="text-xs font-bold text-text-main"><?= $_SESSION['user_name'] ?></p>
                    <p class="text-[10px] text-text-muted uppercase font-bold"><?= $_SESSION['user_role'] ?></p>
                </div>
                <div class="p-1">
                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-text-muted hover:text-text-main hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-base">settings</span>
                        Configuración
                    </button>
                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-text-muted hover:text-text-main hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-base">person</span>
                        Perfil
                    </button>
                    <a href="?page=login&action=logout" class="w-full text-left px-3 py-2 text-xs font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-base">logout</span>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>