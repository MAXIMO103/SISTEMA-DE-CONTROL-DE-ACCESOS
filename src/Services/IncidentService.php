<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class IncidentService
{
    /**
     * Genera código consecutivo anual atómico (INC-2026-0001) garantizando unicidad en concurrencia
     */
    public static function generateIncidentCode(PDO $db): string
    {
        $year = date('Y');
        $prefix = "INC-{$year}-";

        // Consultar el último número para el año actual bloqueando la lectura con FOR UPDATE
        $stmt = $db->prepare("
            SELECT incident_code FROM incidents_demo 
            WHERE incident_code LIKE :prefix 
            ORDER BY id DESC LIMIT 1 FOR UPDATE
        ");
        $stmt->execute(['prefix' => "{$prefix}%"]);
        $lastCode = $stmt->fetchColumn();

        if ($lastCode) {
            $numPart = (int)substr($lastCode, -4);
            $nextNum = $numPart + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registra una nueva incidencia con soporte para evidencia opcional (subida o cámara)
     */
    public static function createIncident(array $data, ?array $uploadedFile = null, ?string $webcamBase64 = null): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $userId = Auth::id();
            $code = self::generateIncidentCode($db);

            $stmt = $db->prepare("
                INSERT INTO incidents_demo (
                    incident_code, event_type, description, ref_dni, ref_plate, action_taken,
                    status, created_by, created_at
                ) VALUES (
                    :code, :event_type, :description, :ref_dni, :ref_plate, :action_taken,
                    'PENDIENTE', :created_by, NOW()
                )
            ");

            $stmt->execute([
                'code'         => $code,
                'event_type'   => trim($data['event_type'] ?? 'Incidente no tipificado'),
                'description'  => trim($data['description'] ?? ''),
                'ref_dni'      => !empty($data['ref_dni']) ? trim($data['ref_dni']) : null,
                'ref_plate'    => !empty($data['ref_plate']) ? strtoupper(trim(str_replace([' ', '-'], '', $data['ref_plate']))) : null,
                'action_taken' => trim($data['action_taken'] ?? 'Se procedió conforme a directiva institucional.'),
                'created_by'   => $userId
            ]);

            $incidentId = (int)$db->lastInsertId();

            // Registrar evento de creación
            $eventStmt = $db->prepare("
                INSERT INTO incident_events_demo (incident_id, user_id, event_type, notes, created_at)
                VALUES (:inc_id, :uid, 'CREACION', :notes, NOW())
            ");
            $eventStmt->execute([
                'inc_id' => $incidentId,
                'uid'    => $userId,
                'notes'  => 'Registro inicial del incidente en la guardia de puerta.'
            ]);

            // Procesar evidencia si existe (archivo tradicional o webcam en base64)
            $evidenceDir = dirname(__DIR__, 2) . '/storage/evidence/';
            if (!is_dir($evidenceDir)) {
                mkdir($evidenceDir, 0755, true);
            }

            if ($uploadedFile && !empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
                $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                if (in_array($ext, $allowed, true) && $uploadedFile['size'] <= 8 * 1024 * 1024) {
                    $fileName = 'INC_' . $incidentId . '_' . time() . '.' . $ext;
                    move_uploaded_file($uploadedFile['tmp_name'], $evidenceDir . $fileName);

                    $evStmt = $db->prepare("
                        INSERT INTO incident_evidence_demo (incident_id, file_path, original_name, mime_type, file_size, captured_via, uploaded_by, uploaded_at)
                        VALUES (:inc_id, :path, :orig, :mime, :size, 'UPLOAD', :uid, NOW())
                    ");
                    $evStmt->execute([
                        'inc_id' => $incidentId,
                        'path'   => $fileName,
                        'orig'   => $uploadedFile['name'],
                        'mime'   => $uploadedFile['type'] ?? 'application/octet-stream',
                        'size'   => $uploadedFile['size'],
                        'uid'    => $userId
                    ]);
                }
            } elseif (!empty($webcamBase64)) {
                // Manejo de captura por cámara (base64 data URL)
                if (preg_match('/^data:image\/(jpeg|png);base64,(.+)$/', $webcamBase64, $m)) {
                    $raw = base64_decode($m[2]);
                    if ($raw && strlen($raw) <= 5 * 1024 * 1024) {
                        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                        $fileName = 'WEBCAM_' . $incidentId . '_' . time() . '.' . $ext;
                        file_put_contents($evidenceDir . $fileName, $raw);

                        $evStmt = $db->prepare("
                            INSERT INTO incident_evidence_demo (incident_id, file_path, original_name, mime_type, file_size, captured_via, uploaded_by, uploaded_at)
                            VALUES (:inc_id, :path, :orig, :mime, :size, 'WEBCAM', :uid, NOW())
                        ");
                        $evStmt->execute([
                            'inc_id' => $incidentId,
                            'path'   => $fileName,
                            'orig'   => 'Captura_Camara_Guardia_' . date('Ymd_His') . '.' . $ext,
                            'mime'   => 'image/' . $m[1],
                            'size'   => strlen($raw),
                            'uid'    => $userId
                        ]);
                    }
                }
            }

            // Notificación para la campana administrativa
            $notif = $db->prepare("
                INSERT INTO notifications (type, title, message, link, target_role, is_read, created_at)
                VALUES ('INCIDENCIA_ABIERTA', :title, :msg, :link, 'ADMINISTRADOR', 0, NOW())
            ");
            $notif->execute([
                'title' => "Nueva incidencia registrada: {$code}",
                'msg'   => "Se reportó '{$data['event_type']}' en la puerta principal.",
                'link'  => "/incidents/{$incidentId}"
            ]);

            $db->commit();
            AuditService::log('CREATE_INCIDENT', 'incidents_demo', $incidentId, null, [
                'code' => $code,
                'event_type' => $data['event_type']
            ]);

            return [
                'success' => true,
                'incident_id' => $incidentId,
                'code' => $code,
                'message' => "Incidencia {$code} registrada correctamente."
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Error al registrar incidencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Añade nota complementaria (permitido al operador y administrador)
     */
    public static function addNote(int $incidentId, string $notes): array
    {
        $db = Database::getConnection();
        $userId = Auth::id() ?? 1;

        try {
            $stmt = $db->prepare("
                INSERT INTO incident_events_demo (incident_id, user_id, event_type, notes, created_at)
                VALUES (:inc_id, :uid, 'OBSERVACION', :notes, NOW())
            ");
            $stmt->execute(['inc_id' => $incidentId, 'uid' => $userId, 'notes' => trim($notes)]);
            return ['success' => true, 'message' => 'Nota complementaria agregada al expediente.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cierra la incidencia (solo Administrador, con medida y justificación obligatorias)
     */
    public static function closeIncident(int $incidentId, string $measure, string $explanation): array
    {
        $db = Database::getConnection();
        $userId = Auth::id() ?? 1;

        if (empty(trim($measure)) || empty(trim($explanation))) {
            return [
                'success' => false,
                'message' => 'Para cerrar una incidencia es obligatorio registrar la medida adoptada y la explicación de resolución.'
            ];
        }

        try {
            $stmt = $db->prepare("
                UPDATE incidents_demo 
                SET status = 'CERRADA',
                    resolution_measure = :measure,
                    resolution_explanation = :explanation,
                    closed_by = :uid,
                    closed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'measure'     => trim($measure),
                'explanation' => trim($explanation),
                'uid'         => $userId,
                'id'          => $incidentId
            ]);

            $eventStmt = $db->prepare("
                INSERT INTO incident_events_demo (incident_id, user_id, event_type, notes, created_at)
                VALUES (:inc_id, :uid, 'CIERRE', :notes, NOW())
            ");
            $eventStmt->execute([
                'inc_id' => $incidentId,
                'uid'    => $userId,
                'notes'  => "Cierre formal de incidencia. Medida: {$measure}. Detalle: {$explanation}"
            ]);

            AuditService::log('CLOSE_INCIDENT', 'incidents_demo', $incidentId, null, [
                'measure' => $measure,
                'closed_by' => $userId
            ]);

            return ['success' => true, 'message' => 'Incidencia cerrada exitosamente con justificación y medida archivada.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
