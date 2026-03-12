<?php

/**
 * header_master.php
 * Componente reutilizable para las cabeceras de página.
 */

$preTitle = $preTitle ?? 'BioCMMS Pro';
$title = $title ?? 'Página';
$subTitle = $subTitle ?? '';
$icon = $icon ?? 'dashboard';
$description = $description ?? '';
$actions = $actions ?? ''; // Botones u otros elementos de acción
?>

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-700">
    <div class="space-y-2">
        <nav class="flex items-center gap-2 mb-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-medical-blue/60"><?= htmlspecialchars($preTitle) ?></span>
            <span class="w-1 h-1 rounded-full bg-medical-blue/30"></span>
            <span class="text-[10px] font-black uppercase tracking-widest text-text-muted"><?= htmlspecialchars($subTitle ?: $title) ?></span>
        </nav>
        <h1 class="text-4xl font-black tracking-tight text-text-main flex items-center gap-4">
            <span class="material-symbols-outlined text-4xl text-medical-blue font-variation-fill"><?= $icon ?></span>
            <?= htmlspecialchars($title) ?>
        </h1>
        <?php if ($description): ?>
            <p class="text-text-muted font-medium"><?= htmlspecialchars($description) ?></p>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <?= $actions ?>
    </div>
</div>