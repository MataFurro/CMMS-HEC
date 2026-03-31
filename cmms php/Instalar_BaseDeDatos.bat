@echo off
setlocal
title BioCMMS v4.5 - Instalador de Base de Datos
color 0b

echo ======================================================
echo    BIOCMMS v4.5 - INSTALADOR (MySQL INDEPENDIENTE)
echo ======================================================
echo.

:: 1. Verificar si PHP está instalado y en el PATH
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0c
    echo [ERROR] No se encontro 'php' en el sistema.
    echo Asegurate de que XAMPP (o PHP standalone) este instalado
    echo y que el ejecutable php.exe este en tu PATH.
    echo.
    pause
    exit /b 1
)

:: 2. Recordatorio crítico para instalaciones independientes
echo [AVISO IMPORTANTE]
echo Si este equipo tiene un servidor MySQL instalado por separado
echo (no via XAMPP), es probable que el usuario 'root' tenga clave.
echo.
echo Asegurate de haber editado la linea DB_PASS en tu archivo '.env'
echo con la contraseña correcta antes de continuar.
echo.
echo Presiona cualquier tecla para intentar conectar e instalar...
pause >nul

:: 3. Verificar que el script de migración existe
if not exist "run_migrations.php" (
    color 0c
    echo [ERROR] No se encontro el archivo 'run_migrations.php'.
    echo Asegurate de ejecutar este .bat desde la carpeta raiz del proyecto.
    echo.
    pause
    exit /b 1
)

:: 4. Ejecutar la migración
echo.
echo [*] Iniciando proceso de instalacion de base de datos...
php run_migrations.php
echo.

:: 5. Resultado final
if %errorlevel% neq 0 (
    color 0c
    echo [!] Hubo un problema durante la instalacion. 
    echo Causas probables:
    echo 1. La clave en el archivo '.env' es incorrecta (DB_PASS).
    echo 2. El servidor MySQL (Standalone o XAMPP) no esta iniciado.
    echo 3. MySQL esta corriendo en un puerto diferente al 3306.
) else (
    color 0a
    echo [OK] Base de Datos configurada correctamente.
)

echo.
echo Presiona cualquier tecla para salir...
pause >nul
exit /b 0
