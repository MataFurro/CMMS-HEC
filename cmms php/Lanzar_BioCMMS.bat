@echo off
TITLE BioCMMS v4.5 - Lanzador PHP
echo ============================================================
echo   BIOCMMS PHP - MODO AUDITORIA
echo ============================================================
echo.

REM 1. Start PHP
echo [1/1] Iniciando BioCMMS Legacy (Puerto 8080)...
cd /d "%~dp0\cmms php"

if exist "C:\xampp\php\php.exe" (
    start "BioCMMS Legacy Server (XAMPP)" cmd /k "C:\xampp\php\php.exe -S localhost:8080 -t ."
) else (
    echo [WARN] XAMPP no detectado en C:\xampp\php\php.exe. Intentando con PHP global...
    start "BioCMMS Legacy Server (Global)" cmd /k "php -S localhost:8080 -t ."
)

echo.
echo [OK] Ambos sistemas iniciandose...
timeout /t 3 /nobreak > nul

echo [INFO] Abriendo BioCMMS en el navegador...
start chrome http://localhost:8080

echo.
echo Lanzador activo.
pause
