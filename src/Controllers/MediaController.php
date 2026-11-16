<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class MediaController
{
    /**
     * Entrega privada y autenticada de fotografía de trabajador
     * Las fotografías NO están en un directorio público accesible por URL directa.
     */
    public function servePhoto(Request $request, string $dni): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            echo "Acceso denegado a fotografías institucionales.";
            exit;
        }

        $dni = preg_replace('/[^0-9]/', '', $dni);
        $dni = str_pad($dni, 8, '0', STR_PAD_LEFT);

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT photo_path FROM personnel_demo WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $photoName = $stmt->fetchColumn();

        $photoDir = dirname(__DIR__, 2) . '/storage/photos/';
        $filePath = $photoDir . $photoName;

        if (!$photoName || !file_exists($filePath)) {
            // Entregar imagen placeholder SVG transparente institucional
            header('Content-Type: image/svg+xml');
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="250" viewBox="0 0 200 250">
                    <rect width="200" height="250" fill="#18221b"/>
                    <circle cx="100" cy="90" r="45" fill="#2c3830"/>
                    <ellipse cx="100" cy="210" rx="70" ry="50" fill="#2c3830"/>
                    <text x="100" y="240" fill="#c59b3f" font-family="sans-serif" font-size="11" text-anchor="middle" font-weight="bold">FOTO PENDIENTE</text>
                  </svg>';
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        Response::sendFile($filePath, "foto_{$dni}.jpg", $mime, true);
    }

    /**
     * Entrega privada de archivos de evidencia de incidencias
     */
    public function serveEvidence(Request $request, string $id): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            echo "Acceso denegado a evidencias institucionales.";
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT file_path, original_name, mime_type FROM incident_evidence_demo WHERE id = :id");
        $stmt->execute(['id' => (int)$id]);
        $evidence = $stmt->fetch();

        if (!$evidence) {
            http_response_code(404);
            echo "Evidencia no encontrada.";
            exit;
        }

        $filePath = dirname(__DIR__, 2) . '/storage/evidence/' . $evidence['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Archivo de evidencia físico no encontrado.";
            exit;
        }

        Response::sendFile($filePath, $evidence['original_name'], $evidence['mime_type'], true);
    }
}

