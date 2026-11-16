<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class AuditService
{
    public static function log(
        string $action, 
        string $entity, 
        ?int $entityId = null, 
        ?array $oldValues = null, 
        ?array $newValues = null,
        ?int $userId = null
    ): void
    {
        try {
            $db = Database::getConnection();
            
            // Si no se pasó userId, obtenerlo directamente de la sesión sin recursionar en Auth
            if ($userId === null) {
                $userId = Session::get('user_id');
            }
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido', 0, 255);

            // Sanitizar valores: NUNCA guardar passwords ni tokens
            if ($oldValues) {
                unset($oldValues['password'], $oldValues['password_hash'], $oldValues['csrf_token'], $oldValues['session_token']);
            }
            if ($newValues) {
                unset($newValues['password'], $newValues['password_hash'], $newValues['csrf_token'], $newValues['session_token']);
            }

            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, entity, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (:user_id, :action, :entity, :entity_id, :old_values, :new_values, :ip, :ua, NOW())
            ");

            $stmt->execute([
                'user_id'    => $userId ? (int)$userId : null,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => $ip,
                'ua'         => $ua
            ]);
        } catch (\Exception $e) {
            // Silencioso para no detener flujo crítico si falla logging
            error_log("Fallo al registrar auditoría: " . $e->getMessage());
        }
    }
}

