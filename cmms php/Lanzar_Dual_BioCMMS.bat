@echo off
TITLE BioCMMS v4.5 - Lanzador DUAL (Python + PHP)
echo ============================================================
echo   BIOCMMS DUAL-STACK - MODO AUDITORIA
echo ============================================================
echo.

REM 1. Start Python
echo [1/2] Iniciando BioCMMS Python (Puerto 3000)...
cd /d "%~dp0\CMMS Python"

if exist "venv\Scripts\activate.bat" (
    echo [INFO] Activando venv y lanzando backend...
    start "BioCMMS Python Backend" cmd /k "venv\Scripts\activate.bat && python -m uvicorn app.main:app --host 0.0.0.0 --port 3000 --reload"
) else (
    echo [WARN] No se detecto venv. Intentando con Python global...
    start "BioCMMS Python Backend" cmd /k "python -m uvicorn app.main:app --host 0.0.0.0 --port 3000 --reload"
)

REM 2. Start PHP
echo [2/2] Iniciando BioCMMS Legacy (Puerto 8080)...
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
