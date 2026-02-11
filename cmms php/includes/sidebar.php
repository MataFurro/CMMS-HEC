<?php
// includes/sidebar.php

$menuItems = [
    ['name' => SIDEBAR_DASHBOARD, 'path' => 'dashboard', 'icon' => 'dashboard'],
    ['name' => SIDEBAR_CALENDAR, 'path' => 'calendar', 'icon' => 'calendar_month'],
    ['name' => SIDEBAR_ORDERS, 'path' => 'work_orders', 'icon' => 'assignment'],
    ['name' => SIDEBAR_INVENTORY, 'path' => 'inventory', 'icon' => 'precision_manufacturing'],
    ['name' => SIDEBAR_FAMILY_ANALYSIS, 'path' => 'family_analysis', 'icon' => 'analytics'],
    ['name' => 'Análisis Financiero', 'path' => 'financial_analysis', 'icon' => 'payments'],
];
?>
<aside class="fixed lg:sticky top-0 left-0 flex flex-col w-20 lg:w-72 bg-medical-surface border-r border-slate-700/50 h-screen shrink-0 z-50 transition-all duration-300">
    <div class="p-4 lg:p-8 flex flex-col items-center lg:items-start">
        <div class="flex items-center gap-3 mb-10 lg:mb-12">
            <div class="bg-medical-blue rounded-lg w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center shadow-lg shadow-medical-blue/20">
                <span class="material-symbols-outlined text-white text-2xl lg:text-3xl font-variation-fill">engineering</span>
            </div>
            <div class="hidden lg:block">
                <h2 class="text-sm font-black tracking-tight text-white leading-none uppercase">BioCMMS</h2>
                <p class="text-[10px] text-medical-blue font-black tracking-widest uppercase mt-1">Professional Edition</p>
            </div>
        </div>

        <nav class="flex flex-col gap-1.5 w-full">
            <?php foreach ($menuItems as $item):
                $isActive = $page === $item['path'];
                $activeClass = 'text-medical-blue bg-medical-blue/10 font-bold border-r-4 border-medical-blue rounded-r-none';
                $inactiveClass = 'text-slate-400 hover:text-white hover:bg-slate-800';
            ?>
                <a href="?page=<?= $item['path'] ?>"
                    title="<?= $item['name'] ?>"
                    class="flex items-center justify-center lg:justify-start gap-4 p-3 lg:px-5 lg:py-3.5 rounded-xl transition-all duration-200 group <?= $isActive ? $activeClass : $inactiveClass ?>">
                    <span class="material-symbols-outlined text-2xl <?= $isActive ? 'fill-1' : '' ?>">
                        <?= $item['icon'] ?>
                    </span>
                    <span class="hidden lg:block text-xs font-semibold uppercase tracking-wider"><?= $item['name'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="mt-auto p-4 lg:p-8 border-t border-slate-700/50 w-full">
        <div class="flex items-center gap-4 mb-4">
            <div class="size-10 lg:size-10 rounded-full bg-slate-700 flex items-center justify-center shrink-0 overflow-hidden border border-slate-600">
                <img src="<?= $_SESSION['user_avatar'] ?>" class="w-full h-full object-cover" alt="User">
            </div>
            <div class="hidden lg:block overflow-hidden">
                <p class="text-xs font-bold text-white leading-none truncate"><?= $_SESSION['user_name'] ?></p>
                <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mt-1.5 truncate"><?= $_SESSION['user_role'] ?></p>
            </div>
        </div>
    </div>
</aside>