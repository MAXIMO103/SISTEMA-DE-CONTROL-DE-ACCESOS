@echo off
title Sistema Web COEDE - Servidor Local
echo ========================================================
echo   COMANDO DE EDUCACION Y DOCTRINA DEL EJERCITO (COEDE)
echo   Iniciando Servidor Web Local en puerto 8080...
echo ========================================================
echo.
echo Abra su navegador en: http://localhost:8080
echo.
echo Presione Ctrl + C para detener el servidor.
echo.
php -S localhost:8080 -t public
pause

