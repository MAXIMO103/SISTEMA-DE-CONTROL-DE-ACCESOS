<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use ZipArchive;

class ZipPhotoImportService
{
    private const MAX_ZIP_FILES = 500;
    private const MAX_PHOTO_SIZE = 4 * 1024 * 1024; // 4MB por foto
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/pjpeg'];

    /**
     * Inspecciona y valida el archivo ZIP de fotografías masivas
     */
    public static function previewZip(string $zipFilePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            return [
                'success' => false,
                'message' => 'No se pudo abrir el archivo ZIP. Verifique que no esté dañado o protegido con contraseña.'
            ];
        }

        $numFiles = $zip->numFiles;
        if ($numFiles > self::MAX_ZIP_FILES) {
            $zip->close();
            return [
                'success' => false,
                'message' => 'El archivo ZIP excede el límite de seguridad de 500 archivos (protección contra ZIP bomb).'
            ];
        }

        $db = Database::getConnection();
        $personnelList = $db->query("SELECT id, dni, rank_grade, surnames, names, photo_path FROM personnel_demo")->fetchAll();
        $personnelMap = [];
        foreach ($personnelList as $p) {
            $personnelMap[$p['dni']] = $p;
        }

        $items = [];
        $dnisInZip = [];

        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];

            // Omitir directorios o archivos ocultos de sistema (.DS_Store, Thumbs.db)
            if (str_ends_with($filename, '/') || str_starts_with(basename($filename), '.')) {
                continue;
            }

            // Prevención de ZIP Traversal (evitar ../ o nombres absolutos)
            if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                $items[] = [
                    'filename' => $filename,
                    'dni' => null,
                    'status' => 'RECHAZADO',
                    'reason' => 'Ruta insegura detectada (intento de Directory Traversal).'
                ];
                continue;
            }

            $baseName = basename($filename);
            $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));

            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                $items[] = [
                    'filename' => $baseName,
                    'dni' => null,
                    'status' => 'INVALIDO',
                    'reason' => "Extensión '.{$ext}' no permitida. Solo se admiten archivos .jpg/.jpeg."
                ];
                continue;
            }

            if ($stat['size'] > self::MAX_PHOTO_SIZE) {
                $items[] = [
                    'filename' => $baseName,
                    'dni' => null,
                    'status' => 'INVALIDO',
                    'reason' => 'Tamaño excede los 4 MB permitidos.'
                ];
                continue;
            }

            $dniCandidate = pathinfo($baseName, PATHINFO_FILENAME);
            if (!preg_match('/^\d{8}$/', $dniCandidate)) {
                $items[] = [
                    'filename' => $baseName,
                    'dni' => $dniCandidate,
                    'status' => 'INVALIDO',
                    'reason' => "El nombre de archivo debe ser exactamente el DNI de 8 dígitos (ejemplo: '45892134.jpg')."
                ];
                continue;
            }

            if (isset($dnisInZip[$dniCandidate])) {
                $items[] = [
                    'filename' => $baseName,
                    'dni' => $dniCandidate,
                    'status' => 'DUPLICADO',
                    'reason' => 'DNI repetido dentro del mismo paquete ZIP.'
                ];
                continue;
            }
            $dnisInZip[$dniCandidate] = true;

            // Comprobar si existe en el padrón
            if (!isset($personnelMap[$dniCandidate])) {
                $items[] = [
                    'filename' => $baseName,
                    'dni' => $dniCandidate,
                    'status' => 'NO_ENCONTRADO',
                    'reason' => 'DNI no registrado en el padrón de personal COEDE.'
                ];
                continue;
            }

            $person = $personnelMap[$dniCandidate];
            $isReplacement = !empty($person['photo_path']);

            $items[] = [
                'filename' => $baseName,
                'zip_index' => $i,
                'dni' => $dniCandidate,
                'personnel_id' => $person['id'],
                'full_name' => ($person['rank_grade'] ? $person['rank_grade'] . ' ' : '') . $person['surnames'] . ', ' . $person['names'],
                'status' => $isReplacement ? 'REEMPLAZO' : 'COINCIDENCIA',
                'reason' => $isReplacement ? 'Fotografía existente; se actualizará con confirmación.' : 'Listo para asociar a la ficha institucional.'
            ];
        }

        $zip->close();

        return [
            'success' => true,
            'total_files' => count($items),
            'ready_count' => count(array_filter($items, fn($it) => in_array($it['status'], ['COINCIDENCIA', 'REEMPLAZO']))),
            'items' => $items
        ];
    }

    /**
     * Aplica la importación masiva de fotos validadas y actualiza BD y solicitudes pendientes
     */
    public static function processZip(string $zipFilePath, array $selectedIndices): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            return ['success' => false, 'message' => 'No se pudo abrir el archivo ZIP.'];
        }

        $db = Database::getConnection();
        $targetDir = dirname(__DIR__, 2) . '/storage/photos/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $saved = 0;
        $adminId = Auth::id();

        foreach ($selectedIndices as $index) {
            $stat = $zip->statIndex((int)$index);
            if (!$stat) continue;

            $filename = basename($stat['name']);
            $dni = pathinfo($filename, PATHINFO_FILENAME);
            if (!preg_match('/^\d{8}$/', $dni)) continue;

            // Extraer a memoria para verificar cabecera MIME real
            $stream = $zip->getStream($stat['name']);
            if (!$stream) continue;
            $contents = stream_get_contents($stream);
            fclose($stream);

            // Validar cabecera real de imagen JPEG
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($contents);
            if (!in_array($mime, self::ALLOWED_MIMES, true)) {
                continue;
            }

            // Guardar en almacenamiento privado (fuera de public)
            $destination = $targetDir . $dni . '.jpg';
            file_put_contents($destination, $contents);

            // Actualizar registro de personal
            $stmt = $db->prepare("UPDATE personnel_demo SET photo_path = :photo, updated_at = NOW() WHERE dni = :dni");
            $stmt->execute(['photo' => $dni . '.jpg', 'dni' => $dni]);

            // Marcar solicitudes de foto pendientes como completadas
            $updReq = $db->prepare("
                UPDATE photo_requests_demo 
                SET status = 'COMPLETADA', completed_at = NOW() 
                WHERE personnel_id = (SELECT id FROM personnel_demo WHERE dni = :dni LIMIT 1) AND status = 'PENDIENTE'
            ");
            $updReq->execute(['dni' => $dni]);

            $saved++;
        }

        $zip->close();
        AuditService::log('IMPORT_PHOTOS_ZIP', 'personnel_demo', null, null, ['imported_count' => $saved]);

        return [
            'success' => true,
            'imported' => $saved
        ];
    }
}

