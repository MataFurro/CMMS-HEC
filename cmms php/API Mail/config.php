<?php

/**
 * API Mail/config.php
 * Configuración central para el Mensajero (MS) - Standalone Version
 */

define('MS_UPLOAD_DIR', __DIR__ . '/uploads/');
define('MS_LOG_FILE', __DIR__ . '/messenger.log');

// Configuración SMTP (Ejemplo para PHPMailer o mail() nativo)
define('SMTP_HOST', 'smtp.ejemplo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'soporte@hec.cl');
define('SMTP_PASS', 'clave_segura');

// Nota: Integración con CMMS PHP desactivada temporalmente
// define('CMMS_PROVIDERS_PATH', __DIR__ . '/../backend/providers/');
