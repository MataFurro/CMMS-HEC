@echo off
echo ========================================
echo  BioCMMS - Sync Workspace to XAMPP
echo ========================================
echo.
echo Sincronizando archivos...
robocopy "c:\Users\star_\OneDrive\Escritorio\Diseño\cmms php" "C:\xampp\htdocs\cmms-hec\cmms php" /MIR /XD ".git" "node_modules" /NFL /NDL /NJH /NJS /nc /ns /np
echo.
echo ========================================
echo  SYNC COMPLETO
echo ========================================
pause
