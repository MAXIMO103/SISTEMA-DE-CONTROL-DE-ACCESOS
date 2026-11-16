@echo off
title Sistema Web COEDE - Respaldo Manual
echo ========================================================
echo   COMANDO DE EDUCACION Y DOCTRINA DEL EJERCITO (COEDE)
echo   Generando Respaldo Completo (MySQL + Fotos + Evidencias)...
echo ========================================================
echo.
php scripts/backup_cron.php
echo.
pause

