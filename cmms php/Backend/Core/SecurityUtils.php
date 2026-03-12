<?php

/**
 * Backend/Core/SecurityUtils.php
 * Utilidades para proteger la aplicación web contra ataques comunes.
 */

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField()
    {
        $token = generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($token === null && isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } else if ($token === null && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
