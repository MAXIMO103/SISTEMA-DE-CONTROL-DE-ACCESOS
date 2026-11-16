<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\IncidentService;
use PDO;

class IncidentController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();

        $status = $request->query('status', 'TODOS'); // TODOS, PENDIENTE, EN_REVISION, CERRADA
        $search = trim($request->query('q', ''));

        $query = "
            SELECT i.*, u1.name AS creator_name, u2.name AS closer_name,
                   COUNT(e.id) AS evidence_count
            FROM incidents_demo i
            LEFT JOIN users u1 ON i.created_by = u1.id
            LEFT JOIN users u2 ON i.closed_by = u2.id
            LEFT JOIN incident_evidence_demo e ON i.id = e.incident_id
            WHERE 1=1
        ";
        $params = [];

        if ($status !== 'TODOS') {
            $query .= " AND i.status = :status";
            $params['status'] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (i.incident_code LIKE :s1 OR i.event_type LIKE :s2 OR i.description LIKE :s3 OR i.ref_dni LIKE :s4 OR i.ref_plate LIKE :s5)";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
            $params['s5'] = "%{$search}%";
        }

        // Requisito: Las incidencias pendientes se ordenan de más antigua a más reciente
        $query .= " GROUP BY i.id ORDER BY (CASE WHEN i.status = 'PENDIENTE' THEN 0 WHEN i.status = 'EN_REVISION' THEN 1 ELSE 2 END) ASC, i.created_at ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $incidents = $stmt->fetchAll();

        View::render('incidents.index', [
            'title'     => 'Registro de Incidencias y Eventos - COEDE',
            'incidents' => $incidents,
            'status'    => $status,
            'search'    => $search,
            'isAdmin'   => Auth::isAdmin()
        ], 'main');
    }

    public function create(Request $request): void
    {
        // Puede recibir DNI o placa prellenados desde control de puerta
        $refDni   = $request->query('dni', '');
        $refPlate = $request->query('plate', '');

        View::render('incidents.create', [
            'title'    => 'Registrar Nueva Incidencia - Guardia de Puerta',
            'refDni'   => $refDni,
            'refPlate' => $refPlate
        ], 'main');
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $file = $request->file('evidence_file');
        $webcam = $request->input('webcam_photo', null);

        if (empty(trim($data['event_type'] ?? '')) || empty(trim($data['description'] ?? ''))) {
            if ($request->isAjax()) {
                Response::json([
                    'success' => false,
                    'message' => 'El tipo de hecho y la descripción de la incidencia son obligatorios.'
                ], 400);
            }
            Session::flash('error', 'El tipo de hecho y la descripción de la incidencia son obligatorios.');
            Response::redirect('/incidents/create');
        }

        $result = IncidentService::createIncident($data, $file, $webcam);

        if ($request->isAjax()) {
            Response::json($result, $result['success'] ? 200 : 400);
        }

        if ($result['success']) {
            Session::flash('success', "Incidencia {$result['code']} registrada con éxito.");
            Response::redirect('/incidents/' . $result['incident_id']);
        } else {
            Session::flash('error', $result['message']);
            Response::redirect('/incidents/create');
        }
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT i.*, u1.name AS creator_name, u2.name AS closer_name
            FROM incidents_demo i
            LEFT JOIN users u1 ON i.created_by = u1.id
            LEFT JOIN users u2 ON i.closed_by = u2.id
            WHERE i.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $incident = $stmt->fetch();

        if (!$incident) {
            Session::flash('error', 'Incidencia no encontrada.');
            Response::redirect('/incidents');
        }

        // Cargar evidencias asociadas
        $evStmt = $db->prepare("SELECT * FROM incident_evidence_demo WHERE incident_id = :id ORDER BY uploaded_at ASC");
        $evStmt->execute(['id' => (int)$id]);
        $evidences = $evStmt->fetchAll();

        // Cargar bitácora de eventos y notas
        $eventStmt = $db->prepare("
            SELECT e.*, u.name AS user_name, u.username
            FROM incident_events_demo e
            JOIN users u ON e.user_id = u.id
            WHERE e.incident_id = :id
            ORDER BY e.created_at ASC
        ");
        $eventStmt->execute(['id' => (int)$id]);
        $events = $eventStmt->fetchAll();

        View::render('incidents.show', [
            'title'     => "Incidencia {$incident['incident_code']} - COEDE",
            'incident'  => $incident,
            'evidences' => $evidences,
            'events'    => $events,
            'isAdmin'   => Auth::isAdmin()
        ], 'main');
    }

    public function addNote(Request $request, string $id): void
    {
        $notes = trim($request->input('notes', ''));
        if (empty($notes)) {
            Session::flash('error', 'El texto de la observación complementaria no puede estar vacío.');
            Response::redirect("/incidents/{$id}");
        }

        $result = IncidentService::addNote((int)$id, $notes);
        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }
        Response::redirect("/incidents/{$id}");
    }

    public function close(Request $request, string $id): void
    {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Solo el Administrador tiene autorización para resolver y cerrar incidencias.');
            Response::redirect("/incidents/{$id}");
        }

        $measure     = trim($request->input('resolution_measure', ''));
        $explanation = trim($request->input('resolution_explanation', ''));

        $result = IncidentService::closeIncident((int)$id, $measure, $explanation);

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }
        Response::redirect("/incidents/{$id}");
    }
}

