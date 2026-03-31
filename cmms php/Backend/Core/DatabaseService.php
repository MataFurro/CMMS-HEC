<?php

namespace Backend\Core;

use PDO;
use Exception;

/**
 * Backend/Core/DatabaseService.php
 * ─────────────────────────────────────────────────────
 * Servicio Singleton para la conexión a Base de Datos.
 * Gestiona una instancia única de PDO.
 * ─────────────────────────────────────────────────────
 */
class DatabaseService
{
    private static ?PDO $instance = null;
    public static ?string $connectionError = null;

    /**
     * Obtener la instancia de conexión (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (defined('USE_MOCK_DATA') && USE_MOCK_DATA === true) {
            // En modo Mock, devolvemos una conexión SQLite en memoria para evitar excepciones
            // y permitir que el sistema renderice el front sin servidor SQL.
            if (self::$instance === null) {
                self::$instance = new \PDO('sqlite::memory:');
            }
            return self::$instance;
        }

        if (self::$instance === null) {
            try {
                // Configuración de conexión flexible
                $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
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
                // FALLBACK: Si falla MySQL, devolvemos una base de datos en memoria vacía
                self::$connectionError = $e->getMessage();
                error_log("DATABASE CONNECTION ERROR (Switching to Zero-State): " . self::$connectionError);
                
                // Retornamos un PDO de SQLite en memoria (estará vacío, por lo que las tablas no existirán)
                return new PDO('sqlite::memory:', null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT // Silencioso para evitar warnings en UI
                ]);
            }
        }

        return self::$instance;
    }

    /**
     * Evitar clonación del Singleton
     */
    private function __clone() {}
    public function __wakeup() {}
    private function __construct() {}
}
