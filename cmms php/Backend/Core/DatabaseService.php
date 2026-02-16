<?php

namespace Backend\Core;

use PDO;
use Exception;

/**
 * core/DatabaseService.php
 * ─────────────────────────────────────────────────────
 * Servicio Singleton para la conexión a Base de Datos.
 * Gestiona una instancia única de PDO.
 * ─────────────────────────────────────────────────────
 */
class DatabaseService
{
    private static ?PDO $instance = null;

    /**
     * Obtener la instancia de conexión (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
            throw new Exception("DATABASE_MOCK_MODE_ACTIVE: La base de datos está desconectada por auditoría.");
        }

        if (self::$instance === null) {
            try {
                // Configuración de conexión (usando dinámicos o constantes)
                $host = defined('DB_HOST') ? DB_HOST : 'localhost';
                $db = defined('DB_NAME') ? DB_NAME : 'biocmms';
                $user = defined('DB_USER') ? DB_USER : 'root';
                $pass = defined('DB_PASS') ? DB_PASS : '';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                // Loguear error y fallar con mensaje controlado
                error_log("DATABASE CONNECTION ERROR: " . $e->getMessage());
                throw new Exception("Error de conexión con la base de datos.");
            }
        }

        return self::$instance;
    }

    /**
     * Evitar clonación del Singleton
     */
    private function __clone()
    {
    }
    public function __wakeup()
    {
    }
    private function __construct()
    {
    }
}
