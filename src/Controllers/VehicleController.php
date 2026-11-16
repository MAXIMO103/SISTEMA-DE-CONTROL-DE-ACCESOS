<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class VehicleController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();

        $category = $request->query('category', 'TODOS'); // TODOS, PARTICULAR, OFICIAL
        $status   = $request->query('status', 'TODOS');   // TODOS, ACTIVO, INACTIVO
        $validity = $request->query('validity', 'TODOS'); // TODOS, VIGENTE, POR_VENCER, VENCIDO
        $unitId   = $request->query('unit_id', 'TODOS');
        $search   = trim($request->query('q', ''));

        $query = "
            SELECT v.*, u.name AS unit_name, u.code AS unit_code,
                   a.auth_start, a.auth_end, a.status AS auth_status_db,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM vehicles_demo v
            JOIN units u ON v.unit_id = u.id
            LEFT JOIN vehicle_authorizations_demo a ON v.id = a.vehicle_id
            WHERE 1=1
        ";
        $params = [];

        if ($category !== 'TODOS') {
            $query .= " AND v.category = :category";
            $params['category'] = $category;
        }

        if ($status !== 'TODOS') {
            $query .= " AND v.is_active = :is_active";
            $params['is_active'] = ($status === 'ACTIVO') ? 1 : 0;
        }

        if ($unitId !== 'TODOS' && !empty($unitId)) {
            $query .= " AND v.unit_id = :unit_id";
            $params['unit_id'] = (int)$unitId;
        }

        if (!empty($search)) {
            $query .= " AND (v.plate LIKE :s1 OR v.owner_dni LIKE :s2 OR v.owner_name LIKE :s3 OR v.make LIKE :s4 OR v.model LIKE :s5)";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
            $params['s5'] = "%{$search}%";
        }

        $query .= " ORDER BY v.category, v.plate ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rawVehicles = $stmt->fetchAll();

        $vehicles = [];
        foreach ($rawVehicles as $v) {
            $days = $v['days_left'] !== null ? (int)$v['days_left'] : null;
            if ($days === null) {
                $computedValidity = 'SIN_AUTORIZACION';
            } elseif ($days < 0) {
                $computedValidity = 'VENCIDO';
            } elseif ($days <= 30) {
                $computedValidity = 'POR_VENCER';
            } else {
                $computedValidity = 'VIGENTE';
            }
            $v['computed_validity'] = $computedValidity;

            if ($validity !== 'TODOS' && $computedValidity !== $validity) {
                continue;
            }
            $vehicles[] = $v;
        }

        $units = $db->query("SELECT id, code, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('vehicles.index', [
            'title'    => 'Padrón de Vehículos - COEDE',
            'vehicles' => $vehicles,
            'units'    => $units,
            'filters'  => [
                'category' => $category,
                'status'   => $status,
                'validity' => $validity,
                'unit_id'  => $unitId,
                'q'        => $search
            ]
        ], 'main');
    }

    public function create(Request $request): void
    {
        $db = Database::getConnection();
        $units = $db->query("SELECT id, code, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('vehicles.create', [
            'title' => 'Nuevo Registro de Vehículo - COEDE',
            'units' => $units
        ], 'main');
    }

    /**
     * Endpoint AJAX para autocompletar datos del personal militar/civil desde MySQL por DNI
     */
    public function lookupPersonnelByDni(Request $request): void
    {
        $dni = trim($request->query('dni', ''));
        if (!preg_match('/^\d{8}$/', $dni)) {
            Response::json(['found' => false, 'message' => 'DNI inválido']);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.id, p.dni, p.cip_civ, p.rank_grade, p.surnames, p.names, p.unit_id,
                   u.name AS unit_name
            FROM personnel_demo p
            JOIN units u ON p.unit_id = u.id
            WHERE p.dni = :dni AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['dni' => $dni]);
        $person = $stmt->fetch();

        if ($person) {
            $fullName = ($person['rank_grade'] ? $person['rank_grade'] . ' ' : '') . $person['surnames'] . ', ' . $person['names'];
            Response::json([
                'found' => true,
                'dni' => $person['dni'],
                'cip_civ' => $person['cip_civ'],
                'full_name' => $fullName,
                'unit_id' => $person['unit_id'],
                'unit_name' => $person['unit_name']
            ]);
        } else {
            Response::json(['found' => false, 'message' => 'DNI no encontrado en el padrón de trabajadores de COEDE']);
        }
    }

    public function store(Request $request): void
    {
        $db = Database::getConnection();
        $category = strtoupper(trim($request->input('category', 'PARTICULAR')));
        $plate    = strtoupper(trim(str_replace([' ', '-'], '', $request->input('plate', ''))));
        $ownerDni = ($category === 'PARTICULAR') ? trim($request->input('owner_dni', '')) : null;
        $ownerCip = trim($request->input('owner_cip_civ', ''));
        $ownerName = trim($request->input('owner_name', ''));
        $make     = trim($request->input('make', ''));
        $model    = trim($request->input('model', ''));
        $unitId   = (int)$request->input('unit_id', 0);
        $start    = trim($request->input('auth_start', ''));
        $end      = trim($request->input('auth_end', ''));
        $notes    = trim($request->input('notes', ''));

        if (empty($plate)) {
            Session::flash('error', 'La placa es obligatoria.');
            Response::redirect('/vehicles/create');
        }

        // Comprobar placa duplicada
        $stmtP = $db->prepare("SELECT id FROM vehicles_demo WHERE REPLACE(REPLACE(plate, '-', ''), ' ', '') = :plate");
        $stmtP->execute(['plate' => $plate]);
        if ($stmtP->fetch()) {
            Session::flash('error', "La placa {$plate} ya está registrada en el sistema.");
            Response::redirect('/vehicles/create');
        }

        $db->beginTransaction();
        try {
            $ins = $db->prepare("
                INSERT INTO vehicles_demo (category, plate, owner_dni, owner_cip_civ, owner_name, make, model, unit_id, is_active, created_at)
                VALUES (:cat, :plate, :dni, :cip, :name, :make, :model, :unit, 1, NOW())
            ");
            $ins->execute([
                'cat'   => $category,
                'plate' => $plate,
                'dni'   => $ownerDni,
                'cip'   => $ownerCip,
                'name'  => $ownerName,
                'make'  => $make,
                'model' => $model,
                'unit'  => $unitId
            ]);
            $vehicleId = (int)$db->lastInsertId();

            if (!empty($start) && !empty($end)) {
                $authIns = $db->prepare("
                    INSERT INTO vehicle_authorizations_demo (vehicle_id, auth_start, auth_end, status, notes, created_by, created_at)
                    VALUES (:vid, :start, :end, 'ACTIVO', :notes, :created_by, NOW())
                ");
                $authIns->execute([
                    'vid'        => $vehicleId,
                    'start'      => $start,
                    'end'        => $end,
                    'notes'      => $notes,
                    'created_by' => Auth::id()
                ]);
            }

            $db->commit();
            AuditService::log('CREATE_VEHICLE', 'vehicles_demo', $vehicleId, null, [
                'plate' => $plate,
                'category' => $category,
                'unit_id' => $unitId
            ]);

            Session::flash('success', "Vehículo de placa {$plate} registrado exitosamente.");
            Response::redirect('/vehicles');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Error al registrar vehículo: ' . $e->getMessage());
            Response::redirect('/vehicles/create');
        }
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT v.*, u.name AS unit_name,
                   a.auth_start, a.auth_end, a.status AS auth_status_db, a.notes AS auth_notes,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM vehicles_demo v
            JOIN units u ON v.unit_id = u.id
            LEFT JOIN vehicle_authorizations_demo a ON v.id = a.vehicle_id
            WHERE v.id = :id
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            Session::flash('error', 'Ficha de vehículo no encontrada.');
            Response::redirect('/vehicles');
        }

        // Historial de movimientos vehiculares
        $movStmt = $db->prepare("
            SELECT * FROM movements_demo 
            WHERE plate = :plate 
            ORDER BY recorded_at DESC LIMIT 50
        ");
        $movStmt->execute(['plate' => $vehicle['plate']]);
        $movements = $movStmt->fetchAll();

        View::render('vehicles.show', [
            'title' => "Ficha Vehicular: Placa {$vehicle['plate']}",
            'vehicle' => $vehicle,
            'movements' => $movements
        ], 'main');
    }

    public function exportExcel(Request $request): void
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT v.*, u.name AS unit_name, a.auth_start, a.auth_end,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM vehicles_demo v
            JOIN units u ON v.unit_id = u.id
            LEFT JOIN vehicle_authorizations_demo a ON v.id = a.vehicle_id
            ORDER BY v.category, v.plate
        ");
        $vehicles = $stmt->fetchAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Padrón Vehículos');

        $headers = ['CATEGORÍA', 'PLACA', 'DNI RESPONSABLE', 'CIP/CIV', 'RESPONSABLE / ASIGNACIÓN', 'MARCA', 'MODELO', 'DEPENDENCIA', 'INICIO', 'VENCIMIENTO', 'ESTADO'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3022');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $rowNum = 2;
        foreach ($vehicles as $v) {
            $days = $v['days_left'] !== null ? (int)$v['days_left'] : null;
            $vigencia = 'SIN VIGENCIA';
            if ($days !== null) {
                if ($days < 0) $vigencia = 'VENCIDO';
                elseif ($days <= 7) $vigencia = "POR VENCER ({$days} DÍAS)";
                elseif ($days <= 30) $vigencia = 'POR VENCER (30 DÍAS)';
                else $vigencia = 'VIGENTE';
            }

            $sheet->setCellValue('A' . $rowNum, $v['category']);
            $sheet->setCellValueExplicit('B' . $rowNum, (string)$v['plate'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowNum, (string)($v['owner_dni'] ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowNum, (string)($v['owner_cip_civ'] ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $rowNum, $v['owner_name'] ?? '-');
            $sheet->setCellValue('F' . $rowNum, $v['make']);
            $sheet->setCellValue('G' . $rowNum, $v['model']);
            $sheet->setCellValue('H' . $rowNum, $v['unit_name']);
            $sheet->setCellValue('I' . $rowNum, $v['auth_start'] ?? '-');
            $sheet->setCellValue('J' . $rowNum, $v['auth_end'] ?? '-');
            $sheet->setCellValue('K' . $rowNum, $vigencia);
            $rowNum++;
        }

        $out = dirname(__DIR__, 2) . '/storage/templates/Padron_Vehiculos_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($out);

        Response::sendFile($out, 'Padron_Vehiculos_COEDE.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

