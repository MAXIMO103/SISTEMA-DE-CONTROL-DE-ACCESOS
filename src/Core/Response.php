<?php
namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void
    {
        // Si la URL es relativa y no empieza con http, ajustarla con la base URL
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $base = rtrim($_ENV['APP_URL'] ?? '', '/');
            // Detectar base dinámica si está corriendo en subdirectorio
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            if ($scriptDir !== '/' && $scriptDir !== '\\' && !empty($scriptDir)) {
                $scriptDir = str_replace('\\', '/', $scriptDir);
                $url = rtrim($scriptDir, '/') . '/' . ltrim($url, '/');
            } else {
                $url = '/' . ltrim($url, '/');
            }
        }
        header("Location: {$url}");
        exit;
    }

    public static function sendFile(string $filePath, string $filename, string $mimeType = 'application/octet-stream', bool $inline = false): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Archivo no encontrado";
            exit;
        }

        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, private');
        header('Pragma: private');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

