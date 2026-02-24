<?php
// includes/sidebar.php
require_once __DIR__ . '/../Backend/Providers/UserProvider.php'; // Standardized Path
$menuItems = getSidebarMenu($_SESSION['user_role'] ?? '');
?>
<aside class="fixed lg:sticky top-0 left-0 flex flex-col w-20 lg:w-72 bg-medical-surface border-r border-border-dark h-screen shrink-0 z-50 transition-all duration-300">
    <div class="p-4 lg:p-8 flex flex-col items-center lg:items-start overflow-y-auto custom-scrollbar">
        <div class="flex items-center gap-3 mb-10 lg:mb-12">
            <div class="bg-medical-blue rounded-lg w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center shadow-lg shadow-medical-blue/20">
                <span class="material-symbols-outlined text-white text-2xl lg:text-3xl font-variation-fill">engineering</span>
            </div>
            <div class="hidden lg:block">
                <h2 class="text-sm font-black tracking-tight text-text-main leading-none uppercase">BioCMMS</h2>
                <p class="text-[10px] text-medical-blue font-black tracking-widest uppercase mt-1">v4.4 PRO EDITION</p>
            </div>
        </div>

        <nav class="flex flex-col gap-1.5 w-full">
            <?php foreach ($menuItems as $item):
                $isActive = (($page ?? '') === $item['path']);
            ?>
                <a href="?page=<?= $item['path'] ?>" title="<?= $item['name'] ?>"
                    class="flex items-center justify-center lg:justify-start gap-4 p-3 lg:px-5 lg:py-3.5 rounded-xl transition-all duration-200 group <?= $isActive ? 'sidebar-link-active' : 'sidebar-link-inactive' ?>">
                    <span class="material-symbols-outlined text-2xl <?= $isActive ? 'fill-1' : '' ?>">
                        <?= $item['icon'] ?>
                    </span>
                    <span class="hidden lg:block text-xs font-semibold uppercase tracking-wider"><?= $item['name'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="mt-auto p-4 lg:p-8 border-t border-border-dark w-full bg-medical-surface/50 backdrop-blur-md">
        <div class="flex items-center gap-4">
            <div class="size-10 rounded-full bg-slate-800/50 flex items-center justify-center shrink-0 border border-slate-700 shadow-inner">
                <span class="material-symbols-outlined text-text-muted">account_circle</span>
            </div>
            <div class="hidden lg:block overflow-hidden flex-1">
                <p class="text-xs font-bold text-text-main leading-none truncate"><?= $_SESSION['user']['name'] ?? 'Usuario' ?></p>
                <p class="text-[10px] text-medical-blue uppercase font-black tracking-widest mt-1.5 truncate">
                    <?= $_SESSION['user']['role'] ?? 'Demo' ?></p>
            </div>
            <a href="?page=login&action=logout" class="text-text-muted hover:text-red-500 transition-colors" title="Cerrar Sesión">
                <span class="material-symbols-outlined text-xl">logout</span>
            </a>
        </div>
    </div>
</aside>