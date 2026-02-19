<?php

/**
 * Autoloader PSR-4 básico para el namespace 'Backend'
 */
spl_autoload_register(function ($class) {
    // Prefijo del namespace
    $prefix = 'Backend\\';

    // Directorio base para el prefijo de namespace
    $base_dir = __DIR__ . '/';

    // ¿La clase usa el prefijo del namespace?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre de la clase relativo al prefijo
    $relative_class = substr($class, $len);

    // Reemplazar el prefijo del namespace con el directorio base, 
    // reemplazar los separadores de namespace con separadores de directorio en el nombre 
    // de la clase relativo, y agregar .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});
