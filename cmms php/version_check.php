<?php
echo "<h1>PHP Version & Compatibility Check</h1>";
echo "Current PHP Version: " . PHP_VERSION . "<br>";

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "❌ <b style='color:red'>Incompatible:</b> Se requiere PHP 8.2 o superior para las características 'readonly class'.<br>";
} else {
    echo "✅ PHP Version is compatible with 8.2 features.<br>";
}

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "❌ <b style='color:red'>Incompatible:</b> Se requiere PHP 8.1 o superior para 'Enums'.<br>";
}

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌ <b style='color:red'>Incompatible:</b> Se requiere PHP 8.0 o superior para la expresión 'match'.<br>";
}

echo "<h3>Extensones Cargadas:</h3>";
echo implode(", ", get_loaded_extensions());
