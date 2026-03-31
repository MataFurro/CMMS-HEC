<?php

/**
 * Script para ejecutar la migración de estandarización y auditoría.
 */
require_once __DIR__ . '/config.php';

use Backend\Core\DatabaseService;

try {
    $db = DatabaseService::getInstance();
    $sql = file_get_contents(__DIR__ . '/Backend/Database/migrations/008_standardize_status_and_audit.sql');

    // Ejecutar multi-query (separando por punto y coma para PDO simple)
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q)) {
            $db->exec($q);
        }
    }

    echo "[SUCCESS] Migración 008 completada: Estados estandarizados y tabla audit_trail creada.\n";
} catch (Exception $e) {
    echo "[ERROR] Falló la migración: " . $e->getMessage() . "\n";
}
