@echo off
title Sistema Web COEDE - Migraciones y Carga Inicial
echo ========================================================
echo   COMANDO DE EDUCACION Y DOCTRINA DEL EJERCITO (COEDE)
echo   Ejecutando Migraciones SQL y Datos Sinteticos Demo...
echo ========================================================
echo.
php scripts/migrate.php
echo.
php scripts/seed.php
echo.
echo Proceso finalizado.
pause

