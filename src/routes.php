<?php
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PersonnelController;
use App\Controllers\VehicleController;
use App\Controllers\GateController;
use App\Controllers\IncidentController;
use App\Controllers\SidController;
use App\Controllers\ReportController;
use App\Controllers\ConfigController;
use App\Controllers\NotificationController;
use App\Controllers\MediaController;

return function (Router $r) {
    // Autenticación y Sesión
    $r->get('/login', [AuthController::class, 'showLogin']);
    $r->post('/login', [AuthController::class, 'login'], ['csrf']);
    $r->get('/logout', [AuthController::class, 'logout']);
    $r->post('/logout', [AuthController::class, 'logout']);
    $r->get('/api/session/check', [AuthController::class, 'checkSession']);
    $r->post('/api/session/ping', [AuthController::class, 'pingSession']);

    // Portal Principal
    $r->get('/', [HomeController::class, 'index'], ['auth']);

    // Registro de Personal (Solo Administrador)
    $r->get('/personnel', [PersonnelController::class, 'index'], ['auth', 'admin']);
    $r->get('/personnel/create', [PersonnelController::class, 'create'], ['auth', 'admin']);
    $r->post('/personnel/store', [PersonnelController::class, 'store'], ['auth', 'admin', 'csrf']);
    $r->get('/personnel/import', [PersonnelController::class, 'importView'], ['auth', 'admin']);
    $r->get('/personnel/template', [PersonnelController::class, 'downloadTemplate'], ['auth', 'admin']);
    $r->post('/personnel/import-preview', [PersonnelController::class, 'previewImport'], ['auth', 'admin', 'csrf']);
    $r->post('/personnel/import-confirm', [PersonnelController::class, 'confirmImport'], ['auth', 'admin', 'csrf']);
    $r->get('/personnel/photos-zip', [PersonnelController::class, 'photosZipView'], ['auth', 'admin']);
    $r->post('/personnel/photos-zip-preview', [PersonnelController::class, 'previewPhotosZip'], ['auth', 'admin', 'csrf']);
    $r->post('/personnel/photos-zip-confirm', [PersonnelController::class, 'confirmPhotosZip'], ['auth', 'admin', 'csrf']);
    $r->get('/personnel/photo-requests', [PersonnelController::class, 'photoRequests'], ['auth', 'admin']);
    $r->get('/personnel/export-excel', [PersonnelController::class, 'exportExcel'], ['auth', 'admin']);
    $r->get('/personnel/{id}', [PersonnelController::class, 'show'], ['auth', 'admin']);

    // Registro de Vehículos (Solo Administrador)
    $r->get('/vehicles', [VehicleController::class, 'index'], ['auth', 'admin']);
    $r->get('/vehicles/create', [VehicleController::class, 'create'], ['auth', 'admin']);
    $r->post('/vehicles/store', [VehicleController::class, 'store'], ['auth', 'admin', 'csrf']);
    $r->get('/vehicles/lookup-personnel', [VehicleController::class, 'lookupPersonnelByDni'], ['auth', 'admin']);
    $r->get('/vehicles/export-excel', [VehicleController::class, 'exportExcel'], ['auth', 'admin']);
    $r->get('/vehicles/{id}', [VehicleController::class, 'show'], ['auth', 'admin']);

    // Control de Puerta y Asistencia (Administrador y Servicio)
    $r->get('/gate', [GateController::class, 'index'], ['auth', 'service']);
    $r->get('/api/gate/lookup', [GateController::class, 'lookup'], ['auth', 'service']);
    $r->post('/api/gate/movement', [GateController::class, 'storeMovement'], ['auth', 'service', 'csrf']);
    $r->post('/api/gate/request-photo', [GateController::class, 'requestPhoto'], ['auth', 'service', 'csrf']);

    // Incidencias y Eventos (Administrador y Servicio)
    $r->get('/incidents', [IncidentController::class, 'index'], ['auth', 'service']);
    $r->get('/incidents/create', [IncidentController::class, 'create'], ['auth', 'service']);
    $r->post('/incidents/store', [IncidentController::class, 'store'], ['auth', 'service', 'csrf']);
    $r->get('/incidents/{id}', [IncidentController::class, 'show'], ['auth', 'service']);
    $r->post('/incidents/{id}/note', [IncidentController::class, 'addNote'], ['auth', 'service', 'csrf']);
    $r->post('/incidents/{id}/close', [IncidentController::class, 'close'], ['auth', 'admin', 'csrf']);

    // SID — Síntesis de Información Diaria / Noticias
    $r->get('/sid', [SidController::class, 'index'], ['auth', 'service']);
    $r->get('/sid/history', [SidController::class, 'history'], ['auth', 'service']);
    $r->post('/sid/{id}/editorial', [SidController::class, 'updateEditorial'], ['auth', 'admin', 'csrf']);
    $r->post('/sid/{id}/visibility', [SidController::class, 'toggleVisibility'], ['auth', 'admin', 'csrf']);
    $r->get('/sid/export-excel', [SidController::class, 'exportExcel'], ['auth', 'service']);
    $r->get('/sid/export-pdf', [SidController::class, 'exportPdf'], ['auth', 'service']);

    // Reportes Generales (Solo Administrador)
    $r->get('/reports', [ReportController::class, 'index'], ['auth', 'admin']);
    $r->get('/reports/gate', [ReportController::class, 'gateReport'], ['auth', 'admin']);
    $r->get('/reports/gate/export-excel', [ReportController::class, 'exportGateExcel'], ['auth', 'admin']);
    $r->get('/reports/incidents', [ReportController::class, 'incidentsReport'], ['auth', 'admin']);

    // Configuración Institucional y Auditoría (Solo Administrador)
    $r->get('/config', [ConfigController::class, 'index'], ['auth', 'admin']);
    $r->get('/config/users', [ConfigController::class, 'users'], ['auth', 'admin']);
    $r->post('/config/users/create', [ConfigController::class, 'createUser'], ['auth', 'admin', 'csrf']);
    $r->post('/config/users/{id}/toggle-status', [ConfigController::class, 'toggleUserStatus'], ['auth', 'admin', 'csrf']);
    $r->post('/config/users/{id}/reset-password', [ConfigController::class, 'resetPassword'], ['auth', 'admin', 'csrf']);
    $r->get('/config/audit', [ConfigController::class, 'auditLog'], ['auth', 'admin']);
    $r->get('/config/backups', [ConfigController::class, 'backups'], ['auth', 'admin']);
    $r->post('/config/backups/create', [ConfigController::class, 'createBackup'], ['auth', 'admin', 'csrf']);
    $r->get('/config/backups/download/{filename}', [ConfigController::class, 'downloadBackup'], ['auth', 'admin']);

    // Notificaciones Polling
    $r->get('/api/notifications/poll', [NotificationController::class, 'poll'], ['auth']);
    $r->post('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead'], ['auth', 'csrf']);

    // Medios Privados
    $r->get('/media/photo/{dni}', [MediaController::class, 'servePhoto'], ['auth']);
    $r->get('/media/evidence/{id}', [MediaController::class, 'serveEvidence'], ['auth']);
};

