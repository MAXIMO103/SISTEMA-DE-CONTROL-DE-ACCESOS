<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditService;
use App\Services\BackupService;
use PDO;

class ConfigController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT * FROM settings ORDER BY id ASC")->fetchAll();

        View::render('config.index', [
            'title'    => 'Configuración Institucional - COEDE',
            'settings' => $settings
        ], 'main');
    }

    public function users(Request $request): void
    {
        $db = Database::getConnection();
        $users = $db->query("
            SELECT u.*, r.name AS role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
        ")->fetchAll();

        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        View::render('config.users', [
            'title' => 'Gestión de Usuarios y Permisos - COEDE',
            'users' => $users,
            'roles' => $roles
        ], 'main');
    }

    public function createUser(Request $request): void
    {
        $db = Database::getConnection();

        $username = trim($request->input('username', ''));
        $name     = trim($request->input('name', ''));
        $email    = trim($request->input('email', ''));
        $roleId   = (int)$request->input('role_id', 2);
        $password = $request->input('password', '');

        if (empty($username) || empty($name) || empty($password)) {
            Session::flash('error', 'Todos los campos obligatorios deben ser completados.');
            Response::redirect('/config/users');
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirect('/config/users');
        }

        // Verificar nombre de usuario existente
        $stmtChk = $db->prepare("SELECT id FROM users WHERE username = :u");
        $stmtChk->execute(['u' => $username]);
        if ($stmtChk->fetch()) {
            Session::flash('error', "El nombre de usuario '{$username}' ya está en uso.");
            Response::redirect('/config/users');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $db->prepare("
                INSERT INTO users (username, password_hash, name, email, role_id, is_active, created_at)
                VALUES (:u, :h, :n, :e, :r, 1, NOW())
            ");
            $stmt->execute([
                'u' => $username,
                'h' => $hash,
                'n' => $name,
                'e' => $email,
                'r' => $roleId
            ]);
            $newId = (int)$db->lastInsertId();

            AuditService::log('CREATE_USER', 'users', $newId, null, [
                'username' => $username,
                'name'     => $name,
                'role_id'  => $roleId
            ]);

            Session::flash('success', "Usuario '{$username}' creado exitosamente.");
        } catch (\Exception $e) {
            Session::flash('error', 'Error al crear usuario: ' . $e->getMessage());
        }

        Response::redirect('/config/users');
    }

    public function toggleUserStatus(Request $request, string $id): void
    {
        $userId = (int)$id;
        if ($userId === Auth::id()) {
            Session::flash('error', 'No puede desactivar su propia cuenta activa.');
            Response::redirect('/config/users');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT is_active, username FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            $upd = $db->prepare("UPDATE users SET is_active = :st WHERE id = :id");
            $upd->execute(['st' => $newStatus, 'id' => $userId]);

            // Si se desactiva, revocar sesiones activas
            if ($newStatus === 0) {
                $rev = $db->prepare("UPDATE sessions SET is_revoked = 1 WHERE user_id = :id");
                $rev->execute(['id' => $userId]);
            }

            AuditService::log('TOGGLE_USER_STATUS', 'users', $userId, ['is_active' => $user['is_active']], ['is_active' => $newStatus]);
            Session::flash('success', "Estado del usuario '{$user['username']}' actualizado correctamente.");
        }

        Response::redirect('/config/users');
    }

    public function resetPassword(Request $request, string $id): void
    {
        $userId = (int)$id;
        $password = $request->input('new_password', '');

        if (strlen($password) < 8) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            Response::redirect('/config/users');
        }

        $db = Database::getConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = :h, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['h' => $hash, 'id' => $userId]);

        // Revocar sesiones abiertas del usuario
        $rev = $db->prepare("UPDATE sessions SET is_revoked = 1 WHERE user_id = :id");
        $rev->execute(['id' => $userId]);

        AuditService::log('RESET_PASSWORD', 'users', $userId);
        Session::flash('success', 'Contraseña restablecida exitosamente.');
        Response::redirect('/config/users');
    }

    public function auditLog(Request $request): void
    {
        $db = Database::getConnection();

        $action = $request->query('action', 'TODAS');
        $entity = $request->query('entity', 'TODAS');

        $query = "
            SELECT a.*, u.username, u.name AS user_name 
            FROM audit_log a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($action !== 'TODAS') {
            $query .= " AND a.action = :act";
            $params['act'] = $action;
        }

        if ($entity !== 'TODAS') {
            $query .= " AND a.entity = :ent";
            $params['ent'] = $entity;
        }

        // Requisito de inmutabilidad en interfaz: solo lectura, orden cronológico descendente
        $query .= " ORDER BY a.created_at DESC LIMIT 300";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        View::render('config.audit', [
            'title'  => 'Registro de Auditoría de Solo Lectura - COEDE',
            'logs'   => $logs,
            'action' => $action,
            'entity' => $entity
        ], 'main');
    }

    public function backups(Request $request): void
    {
        $db = Database::getConnection();
        $runs = $db->query("
            SELECT b.*, u.name AS user_name 
            FROM backup_runs b
            LEFT JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC LIMIT 50
        ")->fetchAll();

        View::render('config.backups', [
            'title' => 'Gestión de Copias de Seguridad y Respaldos - COEDE',
            'runs'  => $runs
        ], 'main');
    }

    public function createBackup(Request $request): void
    {
        $result = BackupService::createBackup('MANUAL', Auth::id());
        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }
        Response::redirect('/config/backups');
    }

    public function downloadBackup(Request $request, string $filename): void
    {
        // Sanitizar nombre de archivo para prevenir Path Traversal
        $filename = basename($filename);
        if (!str_starts_with($filename, 'RESPALDO_COEDE_') || !str_ends_with($filename, '.zip')) {
            Session::flash('error', 'Nombre de archivo de respaldo inválido.');
            Response::redirect('/config/backups');
        }

        $fullPath = dirname(__DIR__, 2) . '/storage/backups/' . $filename;
        if (!file_exists($fullPath)) {
            Session::flash('error', 'El archivo de respaldo no existe o fue depurado por política de 90 días.');
            Response::redirect('/config/backups');
        }

        AuditService::log('DOWNLOAD_BACKUP', 'backup_runs', null, null, ['filename' => $filename]);
        Response::sendFile($fullPath, $filename, 'application/zip');
    }
}

