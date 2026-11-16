<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditService;
use App\Services\SidNewsService;
use PDO;

class SidController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();

        $selectedDept   = $request->query('department', 'Lima');
        $selectedThreat = $request->query('threat', 'Conflictividad social');
        $isSearch       = $request->query('search', '0') === '1';

        $events = [];
        if ($isSearch || !empty($request->query('department'))) {
            $events = SidNewsService::searchNews($selectedDept, $selectedThreat);
        }

        View::render('sid.index', [
            'title'          => 'Síntesis de Información Diaria (SID) - Noticias',
            'departamentos'  => SidNewsService::DEPARTAMENTOS,
            'amenazas'       => SidNewsService::AMENAZAS,
            'selectedDept'   => $selectedDept,
            'selectedThreat' => $selectedThreat,
            'events'         => $events,
            'isSearch'       => $isSearch,
            'isAdmin'        => Auth::isAdmin()
        ], 'main');
    }

    public function history(Request $request): void
    {
        $db = Database::getConnection();

        $department = $request->query('department', 'TODOS');
        $threat     = $request->query('threat', 'TODAS');
        $startDate  = $request->query('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate    = $request->query('end_date', date('Y-m-d'));

        $query = "
            SELECT e.*, u.name AS creator_name,
                   COUNT(s.id) AS sources_count
            FROM news_events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN news_sources s ON e.id = s.news_event_id
            WHERE 1=1
        ";
        $params = [];

        if ($department !== 'TODOS') {
            $query .= " AND e.department = :dept";
            $params['dept'] = $department;
        }
        if ($threat !== 'TODAS') {
            $query .= " AND e.threat_type = :threat";
            $params['threat'] = $threat;
        }
        if (!empty($startDate)) {
            $query .= " AND e.event_date >= :sdate";
            $params['sdate'] = $startDate . ' 00:00:00';
        }
        if (!empty($endDate)) {
            $query .= " AND e.event_date <= :edate";
            $params['edate'] = $endDate . ' 23:59:59';
        }

        $query .= " GROUP BY e.id ORDER BY e.event_date DESC LIMIT 200";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        foreach ($events as &$evt) {
            $srcStmt = $db->prepare("SELECT * FROM news_sources WHERE news_event_id = :id ORDER BY published_at DESC");
            $srcStmt->execute(['id' => $evt['id']]);
            $evt['sources'] = $srcStmt->fetchAll();
        }

        View::render('sid.history', [
            'title'          => 'Histórico de Síntesis SID - COEDE',
            'departamentos'  => SidNewsService::DEPARTAMENTOS,
            'amenazas'       => SidNewsService::AMENAZAS,
            'department'     => $department,
            'threat'         => $threat,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'events'         => $events,
            'isAdmin'        => Auth::isAdmin()
        ], 'main');
    }

    public function updateEditorial(Request $request, string $id): void
    {
        if (!Auth::isAdmin()) {
            Response::json(['success' => false, 'message' => 'Solo el Administrador puede editar noticias del SID.'], 403);
        }

        $headline  = trim($request->input('headline', ''));
        $synthesis = trim($request->input('synthesis', ''));
        $notes     = trim($request->input('edit_notes', ''));

        if (empty($headline) || empty($synthesis)) {
            Response::json(['success' => false, 'message' => 'El encabezado y la síntesis son obligatorios.'], 400);
        }

        $result = SidNewsService::updateEditorial((int)$id, $headline, $synthesis, $notes);
        Response::json($result);
    }

    public function toggleVisibility(Request $request, string $id): void
    {
        if (!Auth::isAdmin()) {
            Response::json(['success' => false, 'message' => 'Solo el Administrador puede ocultar o recuperar noticias.'], 403);
        }

        $hide   = (int)$request->input('hide', 1) === 1;
        $reason = trim($request->input('reason', ''));

        if ($hide && empty($reason)) {
            Response::json(['success' => false, 'message' => 'Debe registrar un motivo para ocultar la noticia del boletín institucional.'], 400);
        }

        $result = SidNewsService::toggleVisibility((int)$id, $hide, $reason);
        Response::json($result);
    }

    public function exportExcel(Request $request): void
    {
        $dept      = $request->query('department', 'TODOS');
        $threat    = $request->query('threat', 'TODAS');
        $startDate = $request->query('start_date', null);
        $endDate   = $request->query('end_date', null);

        $filePath = SidNewsService::exportExcel($dept, $threat, $startDate, $endDate);
        AuditService::log('EXPORT_SID_EXCEL', 'news_events', null, null, ['department' => $dept, 'threat' => $threat]);
        Response::sendFile($filePath, 'SID_Reporte_' . date('Ymd_His') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function exportPdf(Request $request): void
    {
        $dept      = $request->query('department', 'TODOS');
        $threat    = $request->query('threat', 'TODAS');
        $startDate = $request->query('start_date', null);
        $endDate   = $request->query('end_date', null);

        $filePath = SidNewsService::exportPdf($dept, $threat, $startDate, $endDate);
        AuditService::log('EXPORT_SID_PDF', 'news_events', null, null, ['department' => $dept, 'threat' => $threat]);
        Response::sendFile($filePath, 'SID_Boletin_' . date('Ymd_His') . '.pdf', 'application/pdf', true);
    }
}

