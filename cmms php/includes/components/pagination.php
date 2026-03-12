<?php

/**
 * pagination.php
 * Componente modular para la navegación de tablas.
 */

$totalPages = $totalPages ?? 1;
$currentPage = $currentPage ?? 1;
$totalItems = $totalItems ?? 0;
$itemsPerPage = $itemsPerPage ?? 10;
$offset = ($currentPage - 1) * $itemsPerPage;
$buildUrl = $buildUrl ?? function ($p) {
    return "?p=$p";
};
$label = $label ?? 'items';

if ($totalPages <= 1) return;
?>

<div class="flex items-center justify-between card-glass px-8 py-5">
    <p class="text-[10px] font-black text-text-muted uppercase tracking-[0.2em]">
        Mostrando <span class="text-medical-blue"><?= ($offset + 1) ?>-<?= min($totalItems, $offset + $itemsPerPage) ?></span> de <span class="text-text-main"><?= $totalItems ?></span> <?= htmlspecialchars($label) ?>
    </p>
    <div class="flex items-center gap-2">
        <?php if ($currentPage > 1): ?>
            <a href="<?= $buildUrl($currentPage - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-medical-dark border border-border-color hover:border-medical-blue text-text-muted transition-all">
                <span class="material-symbols-outlined">chevron_left</span>
            </a>
        <?php endif; ?>

        <?php
        $range = 2;
        for ($i = 1; $i <= $totalPages; $i++):
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)):
        ?>
                <a href="<?= $buildUrl($i) ?>"
                    class="w-10 h-10 flex items-center justify-center rounded-xl text-[11px] font-black transition-all border 
                      <?= $i == $currentPage ? 'bg-medical-blue text-white border-medical-blue shadow-lg shadow-medical-blue/20' : 'bg-medical-dark border-border-color text-text-muted hover:border-medical-blue' ?>">
                    <?= $i ?>
                </a>
        <?php
            elseif ($i == 2 || $i == $totalPages - 1):
                echo '<span class="px-2 text-text-muted/30">...</span>';
            endif;
        endfor;
        ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= $buildUrl($currentPage + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-medical-dark border border-border-color hover:border-medical-blue text-text-muted transition-all">
                <span class="material-symbols-outlined">chevron_right</span>
            </a>
        <?php endif; ?>
    </div>
</div>