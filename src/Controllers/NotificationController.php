<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class NotificationController
{
    /**
     * Endpoint AJAX para polling periódico (cada 30 segundos) de la campana superior
     */
    public function poll(Request $request): void
    {
        if (!Auth::check()) {
            Response::json(['count' => 0, 'notifications' => []]);
        }

        $db = Database::getConnection();
        $role = Auth::role();

        // 1. Obtener notificaciones no leídas para el rol actual
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE (target_role = :role OR target_role = 'TODOS') AND is_read = 0
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute(['role' => $role]);
        $notifications = $stmt->fetchAll();

        // 2. Contadores en tiempo real
        $openIncidentsStmt = $db->query("SELECT COUNT(*) FROM incidents_demo WHERE status != 'CERRADA'");
        $openIncidentsCount = (int)$openIncidentsStmt->fetchColumn();

        $expiringStmt = $db->query("
            SELECT COUNT(*) FROM personnel_authorizations_demo 
            WHERE DATEDIFF(auth_end, CURDATE()) BETWEEN 0 AND 7 AND status != 'VENCIDO'
        ");
        $expiringPersonnelCount = (int)$expiringStmt->fetchColumn();

        $pendingPhotosStmt = $db->query("SELECT COUNT(*) FROM photo_requests_demo WHERE status = 'PENDIENTE'");
        $pendingPhotosCount = (int)$pendingPhotosStmt->fetchColumn();

        $totalBadge = count($notifications) + ($role === 'ADMINISTRADOR' ? ($openIncidentsCount > 0 ? 1 : 0) : 0);

        Response::json([
            'unread_count'     => $totalBadge,
            'open_incidents'   => $openIncidentsCount,
            'expiring_count'   => $expiringPersonnelCount,
            'pending_photos'   => $pendingPhotosCount,
            'notifications'    => $notifications
        ]);
    }

    public function markAsRead(Request $request, string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
        $stmt->execute(['id' => (int)$id]);
        Response::json(['success' => true]);
    }
}

