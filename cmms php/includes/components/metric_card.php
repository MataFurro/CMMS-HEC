<?php

/**
 * metric_card.php
 * Componente modular para tarjetas de métricas KPI.
 */

$label = $label ?? 'Métrica';
$value = $value ?? '0';
$subValue = $subValue ?? '';
$icon = $icon ?? 'monitoring';
$colorClass = $colorClass ?? 'medical-blue'; // emerald-500, amber-500, etc.
$description = $description ?? '';
$trend = $trend ?? '';
$extraClasses = $extraClasses ?? '';

// Determinamos colores de fondo y bordes basados en el color base
$bgClass = "bg-$colorClass/10";
$borderClass = "border-$colorClass/20";
$textClass = "text-$colorClass";
?>

<div class="card-glass p-6 group hover:border-<?= $colorClass ?>/50 transition-all duration-500 relative overflow-hidden <?= $extraClasses ?>">
    <div class="absolute -right-4 -top-4 w-24 h-24 bg-<?= $colorClass ?>/5 rounded-full blur-2xl group-hover:bg-<?= $colorClass ?>/10 transition-colors"></div>
    <div class="flex items-center gap-5 relative z-10">
        <div class="shrink-0 w-14 h-14 <?= $bgClass ?> rounded-2xl flex items-center justify-center border <?= $borderClass ?> group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined <?= $textClass ?> text-3xl font-variation-fill"><?= $icon ?></span>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[9px] 2xl:text-[10px] font-black uppercase tracking-widest text-text-muted mb-1 truncate"><?= htmlspecialchars($label) ?></p>
            <div class="flex items-baseline gap-1 flex-wrap">
                <h3 class="text-xl lg:text-3xl font-black text-text-main leading-none"><?= htmlspecialchars($value) ?></h3>
                <?php if ($subValue): ?>
                    <span class="text-[9px] font-medium text-text-muted"><?= htmlspecialchars($subValue) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($description || $trend): ?>
                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                    <?php if ($trend): ?>
                        <span class="text-[9px] font-black <?= $textClass ?> uppercase tracking-tighter whitespace-nowrap"><?= htmlspecialchars($trend) ?></span>
                    <?php endif; ?>
                    <?php if ($description): ?>
                        <span class="text-[8px] text-text-muted italic truncate"><?= htmlspecialchars($description) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>