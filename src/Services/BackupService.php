<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use ZipArchive;
use PDO;

class BackupService
{
    private const RETENTION_DAYS = 90;

    /**
     * Ejecuta una copia de seguridad completa (BD + Fotos + Evidencias)
     */
    public static function createBackup(string $triggeredBy = 'MANUAL', ?int $userId = null): array
    {
        $backupDir = dirname(__DIR__, 2) . '/storage/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // Verificar espacio en disco (mínimo 50 MB requeridos)
        $freeSpace = disk_free_space($backupDir);
        if ($freeSpace !== false && $freeSpace < 50 * 1024 * 1024) {
            self::logRun('ERROR', 'Espacio insuficiente en disco para generar respaldo.', 0, $triggeredBy, $userId);
            return [
                'success' => false,
                'message' => 'Espacio en disco insuficiente para ejecutar la copia de seguridad.'
            ];
        }

        $timestamp = date('Ymd_His');
        $backupFileName = "RESPALDO_COEDE_{$timestamp}.zip";
        $zipPath = $backupDir . $backupFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            self::logRun('ERROR', 'No se pudo crear el contenedor ZIP de respaldo.', 0, $triggeredBy, $userId);
            return [
                'success' => false,
                'message' => 'Error al inicializar el archivo de respaldo.'
            ];
        }

        try {
            // 1. Exportar volcado SQL de todas las tablas de la base de datos
            $sqlDump = self::dumpDatabase();
            $zip->addFromString('database_coede_dump.sql', $sqlDump);

            // 2. Incluir fotografías de personal
            $photosDir = dirname(__DIR__, 2) . '/storage/photos/';
            if (is_dir($photosDir)) {
                $photoFiles = glob($photosDir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                foreach ($photoFiles as $pfile) {
                    $zip->addFile($pfile, 'photos/' . basename($pfile));
                }
            }

            // 3. Incluir evidencias de incidencias
            $evidenceDir = dirname(__DIR__, 2) . '/storage/evidence/';
            if (is_dir($evidenceDir)) {
                $evFiles = glob($evidenceDir . '*.*');
                foreach ($evFiles as $efile) {
                    $zip->addFile($efile, 'evidence/' . basename($efile));
                }
            }

            // 4. Incluir metadatos del sistema
            $manifest = [
                'system'       => 'Sistema Web COEDE Beta',
                'created_at'   => date('Y-m-d H:i:s'),
                'timezone'     => date_default_timezone_get(),
                'triggered_by' => $triggeredBy,
                'operator_id'  => $userId,
                'php_version'  => PHP_VERSION
            ];
            $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zip->close();
            $fileSize = filesize($zipPath);

            // Registrar en tabla de auditoría de respaldos
            self::logRun('EXITO', "Respaldo generado exitosamente ({$backupFileName})", $fileSize, $triggeredBy, $userId, $backupFileName);

            // 5. Aplicar política de retención de 90 días
            self::enforceRetentionPolicy($backupDir);

            AuditService::log('BACKUP_CREATED', 'backup_runs', null, null, [
                'filename' => $backupFileName,
                'size_bytes' => $fileSize,
                'type' => $triggeredBy
            ]);

            return [
                'success'  => true,
                'filename' => $backupFileName,
                'size'     => $fileSize,
                'message'  => "Copia de seguridad completada exitosamente. Archivo: {$backupFileName}"
            ];
        } catch (\Exception $e) {
            @unlink($zipPath);
            self::logRun('ERROR', 'Fallo durante el empaquetado: ' . $e->getMessage(), 0, $triggeredBy, $userId);
            return [
                'success' => false,
                'message' => 'Error al generar respaldo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Genera un volcado SQL estructurado de la base de datos
     */
    private static function dumpDatabase(): string
    {
        $db = Database::getConnection();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $dump = "-- RESPALDO OFICIAL DE BASE DE DATOS COEDE\n";
        $dump .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Zona Horaria: America/Lima\n";
        $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Estructura
            $createTable = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM)[1];
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $createTable . ";\n\n";

            // Datos
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colNames = implode('`, `', $columns);

                foreach ($rows as $row) {
                    $vals = array_map(function($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->quote($val);
                    }, array_values($row));
                    $dump .= "INSERT INTO `{$table}` (`{$colNames}`) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $dump .= "\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $dump;
    }

    /**
     * Elimina copias de seguridad con más de 90 días de antigüedad
     */
    private static function enforceRetentionPolicy(string $dir): void
    {
        $limitTimestamp = time() - (self::RETENTION_DAYS * 86400);
        $files = glob($dir . 'RESPALDO_COEDE_*.zip');

        foreach ($files as $file) {
            if (filemtime($file) < $limitTimestamp) {
                @unlink($file);
            }
        }
    }

    private static function logRun(string $status, string $details, int $fileSize, string $triggeredBy, ?int $userId, string $filename = ''): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO backup_runs (filename, file_size, status, triggered_by, user_id, details, created_at)
                VALUES (:fn, :fs, :st, :tb, :uid, :det, NOW())
            ");
            $stmt->execute([
                'fn'  => $filename,
                'fs'  => $fileSize,
                'st'  => $status,
                'tb'  => $triggeredBy,
                'uid' => $userId,
                'det' => $details
            ]);

            // Si falló, generar notificación de campana administrativa
            if ($status === 'ERROR') {
                $notif = $db->prepare("
                    INSERT INTO notifications (type, title, message, link, target_role, is_read, created_at)
                    VALUES ('RESPALDO_FALLO', 'Fallo en copia de seguridad', :msg, '/config/backups', 'ADMINISTRADOR', 0, NOW())
                ");
                $notif->execute(['msg' => $details]);
            }
        } catch (\Exception $e) {
            error_log("Error al registrar corrida de backup: " . $e->getMessage());
        }
    }
}

