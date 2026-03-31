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

    public static function getInstance(): PDO|\Backend\Core\SafePDO
    {
        if (self::$instance === null) {
            try {
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
                self::$connectionError = $e->getMessage();
                error_log("DATABASE CONNECTION ERROR: " . self::$connectionError);

                // En caso de fallo, devolvemos un SafePDO que envuelve un SQLite en memoria
                $fallbackPDO = new PDO('sqlite::memory:', null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
                ]);
                return new SafePDO($fallbackPDO);
            }
        }

        return self::$instance;
    }

    private function __clone() {}
    public function __wakeup() {}
    private function __construct() {}
}

/**
 * Clase defensiva para evitar el error "Call to a member function execute() on bool"
 * cuando las tablas no existen en el fallback de SQLite.
 */
class SafePDO
{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function prepare($sql, $options = []) {
        $stmt = $this->pdo->prepare($sql, $options);
        return $stmt === false ? new NullStatement() : $stmt;
    }

    public function query($sql, $mode = null, $arg3 = null, $arg4 = null) {
        $stmt = $this->pdo->query($sql, $mode, $arg3, $arg4);
        return $stmt === false ? new NullStatement() : $stmt;
    }

    public function __call($name, $arguments) {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }
}

/**
 * Representa una sentencia que no hace nada pero evita crasheos.
 */
class NullStatement
{
    public function execute($params = null) { return false; }
    public function fetch($mode = null, $cursor = null, $offset = null) { return false; }
    public function fetchAll($mode = null, $arg2 = null, $arg3 = null) { return []; }
    public function fetchColumn($column = 0) { return null; }
    public function rowCount() { return 0; }
    public function __call($name, $arguments) { return null; }
}
