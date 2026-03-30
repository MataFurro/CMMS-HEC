@echo off
TITLE BioCMMS v4.5 - Lanzador Independiente (PHP Legacy)
echo ============================================================
echo   BIOCMMS LEGACY - MODO ZERO-SERVER (PHP)
echo ============================================================
echo.

set PHP_PATH=C:\xampp\php\php.exe
cd /d "%~dp0\cmms php"

REM Check for PHP
if exist "%PHP_PATH%" (
    echo [INFO] Iniciando Servidor Legacy en http://localhost:8080...
    start "BioCMMS Legacy Server" cmd /c "%PHP_PATH% -S localhost:8080 -t ."
) else (
    echo [ERROR] No se encontro PHP en %PHP_PATH%
    echo Por favor instala XAMPP o PHP para ejecutar la version legacy.
    pause
    exit
)

echo [INFO] Abriendo navegador...
timeout /t 2 /nobreak > nul
start chrome http://localhost:8080

echo.
echo Servidor en ejecucion. Para detenerlo, cierra la ventana de comandos "BioCMMS Legacy Server".
pause
