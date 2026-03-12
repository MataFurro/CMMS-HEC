<?php

/**
 * Backend/Core/TemplateUtils.php
 * ─────────────────────────────────────────────────────
 * Funciones de utilidad GLOBAL para visualización.
 * No usar namespace para compatibilidad directa con templates.
 * ─────────────────────────────────────────────────────
 */

if (!function_exists('highlight')) {
    /**
     * Resalta términos de búsqueda en un texto (UX)
     */
    function highlight($text, $term)
    {
        $text = (string)($text ?? '');
        if (empty($term)) return htmlspecialchars($text);

        // Limpiar el término para evitar inyecciones en la regex
        $quotedTerm = preg_quote((string)$term, '/');

        return preg_replace(
            '/(' . $quotedTerm . ')/i',
            '<mark class="bg-yellow-500/30 text-inherit p-0 rounded">$1</mark>',
            htmlspecialchars($text)
        );
    }
}

if (!function_exists('canModify')) {
    /**
     * Helper para verificar permisos de edición rápida
     */
    function canModify()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $role = $_SESSION['user_role'] ?? '';
        return in_array($role, [ROLE_CHIEF_ENGINEER, ROLE_ENGINEER]);
    }
}
