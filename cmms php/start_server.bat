@echo off
REM start_server.bat - Script para iniciar el servidor PHP de BioCMMS
REM Usa XAMPP instalado en C:\xampp

echo ========================================
echo   BioCMMS v4.2 Pro - Servidor PHP
echo ========================================
echo.

REM Verificar que XAMPP existe
if not exist "C:\xampp\php\php.exe" (
    echo [ERROR] XAMPP no encontrado en C:\xampp
    echo Por favor instala XAMPP o ajusta la ruta en este script.
    pause
    exit /b 1
)

REM Configurar PATH temporal para esta sesión
set PATH=C:\xampp\php;%PATH%

REM Mostrar versión de PHP
echo [INFO] Verificando PHP...
php --version
echo.

REM Verificar que estamos en el directorio correcto
if not exist "index.php" (
    echo [ERROR] No se encuentra index.php
    echo Asegurate de ejecutar este script desde la carpeta 'cmms php'
    pause
    exit /b 1
)

echo [INFO] Iniciando servidor PHP en puerto 8000...
echo.
echo ========================================
echo   SERVIDOR INICIADO
echo ========================================
echo.
echo   URL: http://localhost:8000
echo   Presiona Ctrl+C para detener
echo.
echo ========================================
echo.

REM Iniciar servidor PHP built-in
php -S localhost:8000 -t .

pause
