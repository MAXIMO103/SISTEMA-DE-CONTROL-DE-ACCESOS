<?php
/**
 * SISTEMA WEB COEDE - Script para Programador de Tareas de Windows (Task Scheduler)
 * Ejecuta copia automática diaria de BD, fotos y documentos con retención de 90 días.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Cargar variables de entorno
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Lima');

echo "[" . date('Y-m-d H:i:s') . "] Iniciando tarea programada de respaldo COEDE...\n";

$result = \App\Services\BackupService::createBackup('AUTOMATICO', null);

if ($result['success']) {
    echo "✓ Respaldo completado con éxito: " . $result['filename'] . " (" . round($result['size'] / 1024, 2) . " KB)\n";
    exit(0);
} else {
    echo "✗ Error en respaldo: " . $result['message'] . "\n";
    exit(1);
}

