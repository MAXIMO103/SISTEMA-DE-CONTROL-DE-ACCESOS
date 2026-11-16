<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\GateControlService;
use PDO;

class GateController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();

        // Obtener los últimos 20 movimientos del día para la tabla inferior de guardia
        $stmt = $db->query("
            SELECT m.*, u.username AS operator_username, u.name AS operator_name
            FROM movements_demo m
            LEFT JOIN users u ON m.recorded_by = u.id
            ORDER BY m.recorded_at DESC LIMIT 20
        ");
        $recentMovements = $stmt->fetchAll();

        // Lista de dependencias para destino sugerido
        $units = $db->query("SELECT id, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('gate.index', [
            'title'           => 'Control de Puerta Principal - COEDE',
            'recentMovements' => $recentMovements,
            'units'           => $units,
            'user'            => Auth::user()
        ], 'main');
    }

    /**
     * Endpoint AJAX para consulta rápida (teclado / lector de código de barras DNI / digitación de placa)
     */
    public function lookup(Request $request): void
    {
        $dni   = trim($request->query('dni', ''));
        $plate = trim($request->query('plate', ''));

        if (empty($dni) && empty($plate)) {
            Response::json(['error' => 'Ingrese DNI o Placa para consultar.'], 400);
        }

        $result = GateControlService::lookup($dni, $plate);
        Response::json($result);
    }

    /**
     * Registro de movimiento peatonal o vehicular
     */
    public function storeMovement(Request $request): void
    {
        $data = $request->all();

        $dni = trim($data['dni'] ?? '');
        $movementType = strtoupper($data['movement_type'] ?? 'ENTRADA');

        if (empty($dni)) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'message' => 'El DNI es obligatorio.'], 400);
            }
            Session::flash('error', 'El DNI es obligatorio para registrar un movimiento.');
            Response::redirect('/gate');
        }

        $result = GateControlService::recordMovement($data);

        if ($request->isAjax()) {
            Response::json($result);
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        Response::redirect('/gate');
    }

    /**
     * Endpoint AJAX para solicitar fotografía de personal sin foto
     */
    public function requestPhoto(Request $request): void
    {
        $personnelId = (int)$request->input('personnel_id', 0);
        $notes       = trim($request->input('notes', ''));

        if ($personnelId <= 0) {
            Response::json(['success' => false, 'message' => 'Personal inválido.'], 400);
        }

        $result = GateControlService::requestPhoto($personnelId, $notes);
        Response::json($result);
    }
}

