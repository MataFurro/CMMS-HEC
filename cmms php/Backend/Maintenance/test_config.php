<?php
require_once 'config.php';

echo "<h1>Debug BioCMMS Config</h1>";
echo "<h3>Variables de Entorno (\$_ENV):</h3>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h3>Constantes Definidas:</h3>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'UNDEFINED') . "<br>";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'UNDEFINED') . "<br>";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'UNDEFINED') . "<br>";
echo "DB_PASS: " . (defined('DB_PASS') ? (empty(DB_PASS) ? '(VACÍO)' : '********') : 'UNDEFINED') . "<br>";
echo "USE_MOCK_DATA: " . (defined('USE_MOCK_DATA') ? (USE_MOCK_DATA ? 'TRUE' : 'FALSE') : 'UNDEFINED') . "<br>";

echo "<h3>Intentando Conexión via DatabaseService:</h3>";
try {
    require_once 'Backend/Core/DatabaseService.php';
    $db = Backend\Core\DatabaseService::getInstance();
    echo "✅ Conexión Exitosa!";
} catch (Exception $e) {
    echo "❌ Error de Conexión: " . $e->getMessage();

    // Intento de conexión directa para ver el error real de PDO
    echo "<h4>Intento de Conexión Directa (Debug):</h4>";
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $test_pdo = new PDO($dsn, DB_USER, DB_PASS);
        echo "✅ Conexión Directa Funciona!";
    } catch (PDOException $pe) {
        echo "❌ Error Directo PDO: " . $pe->getMessage();
    }
}
