<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditService;
use App\Services\ExcelImportService;
use App\Services\ZipPhotoImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PDO;

class PersonnelController
{
    public function index(Request $request): void
    {
        $db = Database::getConnection();

        $category   = $request->query('category', 'TODOS'); // TODOS, MILITAR, CIVIL
        $status     = $request->query('status', 'TODOS');   // TODOS, ACTIVO, INACTIVO
        $validity   = $request->query('validity', 'TODOS'); // TODOS, VIGENTE, POR_VENCER, VENCIDO
        $photoState = $request->query('photo', 'TODOS');    // TODOS, CON_FOTO, SIN_FOTO
        $unitId     = $request->query('unit_id', 'TODOS');
        $search     = trim($request->query('q', ''));

        $query = "
            SELECT p.*, u.name AS unit_name, u.code AS unit_code,
                   a.auth_start, a.auth_end, a.status AS auth_status_db,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM personnel_demo p
            JOIN units u ON p.unit_id = u.id
            LEFT JOIN personnel_authorizations_demo a ON p.id = a.personnel_id
            WHERE 1=1
        ";
        $params = [];

        if ($category !== 'TODOS') {
            $query .= " AND p.category = :category";
            $params['category'] = $category;
        }

        if ($status !== 'TODOS') {
            $query .= " AND p.is_active = :is_active";
            $params['is_active'] = ($status === 'ACTIVO') ? 1 : 0;
        }

        if ($unitId !== 'TODOS' && !empty($unitId)) {
            $query .= " AND p.unit_id = :unit_id";
            $params['unit_id'] = (int)$unitId;
        }

        if ($photoState === 'CON_FOTO') {
            $query .= " AND p.photo_path IS NOT NULL AND p.photo_path != ''";
        } elseif ($photoState === 'SIN_FOTO') {
            $query .= " AND (p.photo_path IS NULL OR p.photo_path = '')";
        }

        if (!empty($search)) {
            $query .= " AND (p.dni LIKE :s1 OR p.surnames LIKE :s2 OR p.names LIKE :s3 OR p.cip_civ LIKE :s4 OR p.rank_grade LIKE :s5)";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
            $params['s5'] = "%{$search}%";
        }

        $query .= " ORDER BY p.surnames ASC, p.names ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rawPersonnel = $stmt->fetchAll();

        // Filtrar vigencia calculada en servidor
        $personnel = [];
        foreach ($rawPersonnel as $p) {
            $days = $p['days_left'] !== null ? (int)$p['days_left'] : null;
            if ($days === null) {
                $computedValidity = 'SIN_AUTORIZACION';
            } elseif ($days < 0) {
                $computedValidity = 'VENCIDO';
            } elseif ($days <= 30) {
                $computedValidity = 'POR_VENCER';
            } else {
                $computedValidity = 'VIGENTE';
            }
            $p['computed_validity'] = $computedValidity;

            if ($validity !== 'TODOS' && $computedValidity !== $validity) {
                continue;
            }
            $personnel[] = $p;
        }

        $units = $db->query("SELECT id, code, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('personnel.index', [
            'title'     => 'Registro de Personal - COEDE',
            'personnel' => $personnel,
            'units'     => $units,
            'filters'   => [
                'category' => $category,
                'status'   => $status,
                'validity' => $validity,
                'photo'    => $photoState,
                'unit_id'  => $unitId,
                'q'        => $search
            ]
        ], 'main');
    }

    public function create(Request $request): void
    {
        $db = Database::getConnection();
        $units = $db->query("SELECT id, code, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('personnel.create', [
            'title' => 'Nuevo Registro de Personal - COEDE',
            'units' => $units
        ], 'main');
    }

    public function store(Request $request): void
    {
        $db = Database::getConnection();
        $category = strtoupper(trim($request->input('category', 'MILITAR')));
        $dni      = str_pad(trim($request->input('dni', '')), 8, '0', STR_PAD_LEFT);
        $cipCiv   = trim($request->input('cip_civ', ''));
        $rank     = ($category === 'MILITAR') ? trim($request->input('rank_grade', '')) : null;
        $surnames = trim($request->input('surnames', ''));
        $names    = trim($request->input('names', ''));
        $unitId   = (int)$request->input('unit_id', 0);
        $start    = trim($request->input('auth_start', ''));
        $end      = trim($request->input('auth_end', ''));
        $notes    = trim($request->input('notes', ''));

        // Validaciones
        if (!preg_match('/^\d{8}$/', $dni)) {
            Session::flash('error', 'El DNI debe tener exactamente 8 dígitos.');
            Response::redirect('/personnel/create');
        }

        // Comprobar DNI duplicado
        $stmtDni = $db->prepare("SELECT id FROM personnel_demo WHERE dni = :dni");
        $stmtDni->execute(['dni' => $dni]);
        if ($stmtDni->fetch()) {
            Session::flash('error', "El DNI {$dni} ya se encuentra registrado en el padrón.");
            Response::redirect('/personnel/create');
        }

        // Manejo de foto individual opcional
        $photoPath = null;
        $photoFile = $request->file('photo');
        if ($photoFile && !empty($photoFile['tmp_name']) && is_uploaded_file($photoFile['tmp_name'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($photoFile['tmp_name']);
            if (in_array($mime, ['image/jpeg', 'image/pjpeg'], true) && $photoFile['size'] <= 4 * 1024 * 1024) {
                $dir = dirname(__DIR__, 2) . '/storage/photos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fileName = $dni . '.jpg';
                move_uploaded_file($photoFile['tmp_name'], $dir . $fileName);
                $photoPath = $fileName;
            }
        }

        $db->beginTransaction();
        try {
            $ins = $db->prepare("
                INSERT INTO personnel_demo (category, dni, cip_civ, rank_grade, surnames, names, unit_id, is_active, photo_path, created_at)
                VALUES (:cat, :dni, :cip, :rank, :surnames, :names, :unit, 1, :photo, NOW())
            ");
            $ins->execute([
                'cat'      => $category,
                'dni'      => $dni,
                'cip'      => $cipCiv,
                'rank'     => $rank,
                'surnames' => $surnames,
                'names'    => $names,
                'unit'     => $unitId,
                'photo'    => $photoPath
            ]);
            $personnelId = (int)$db->lastInsertId();

            if (!empty($start) && !empty($end)) {
                $authIns = $db->prepare("
                    INSERT INTO personnel_authorizations_demo (personnel_id, auth_start, auth_end, status, notes, created_by, created_at)
                    VALUES (:pid, :start, :end, 'ACTIVO', :notes, :created_by, NOW())
                ");
                $authIns->execute([
                    'pid'        => $personnelId,
                    'start'      => $start,
                    'end'        => $end,
                    'notes'      => $notes,
                    'created_by' => Auth::id()
                ]);
            }

            $db->commit();
            AuditService::log('CREATE_PERSONNEL', 'personnel_demo', $personnelId, null, [
                'dni' => $dni,
                'name' => "{$surnames}, {$names}",
                'category' => $category
            ]);

            Session::flash('success', "Personal {$surnames}, {$names} registrado exitosamente.");
            Response::redirect('/personnel');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Error al guardar personal: ' . $e->getMessage());
            Response::redirect('/personnel/create');
        }
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, u.name AS unit_name, u.code AS unit_code,
                   a.auth_start, a.auth_end, a.status AS auth_status_db, a.notes AS auth_notes,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM personnel_demo p
            JOIN units u ON p.unit_id = u.id
            LEFT JOIN personnel_authorizations_demo a ON p.id = a.personnel_id
            WHERE p.id = :id
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $person = $stmt->fetch();

        if (!$person) {
            Session::flash('error', 'Ficha de personal no encontrada.');
            Response::redirect('/personnel');
        }

        // Historial de movimientos
        $movStmt = $db->prepare("
            SELECT * FROM movements_demo 
            WHERE dni = :dni 
            ORDER BY recorded_at DESC LIMIT 50
        ");
        $movStmt->execute(['dni' => $person['dni']]);
        $movements = $movStmt->fetchAll();

        // Solicitudes de fotos
        $reqStmt = $db->prepare("
            SELECT r.*, u.name AS requester_name 
            FROM photo_requests_demo r
            JOIN users u ON r.requested_by = u.id
            WHERE r.personnel_id = :pid
            ORDER BY r.requested_at DESC
        ");
        $reqStmt->execute(['pid' => $person['id']]);
        $photoRequests = $reqStmt->fetchAll();

        View::render('personnel.show', [
            'title' => "Ficha Institucional: {$person['surnames']}, {$person['names']}",
            'person' => $person,
            'movements' => $movements,
            'photoRequests' => $photoRequests
        ], 'main');
    }

    public function importView(Request $request): void
    {
        $db = Database::getConnection();
        $units = $db->query("SELECT id, code, name FROM units WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        View::render('personnel.import', [
            'title' => 'Importación de Padrón Excel - COEDE',
            'units' => $units
        ], 'main');
    }

    public function downloadTemplate(Request $request): void
    {
        $type = $request->query('type', 'militar');
        $filePath = ExcelImportService::generateTemplate($type);
        Response::sendFile($filePath, "Plantilla_COEDE_{$type}.xlsx", 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function previewImport(Request $request): void
    {
        $file = $request->file('excel_file');
        $category = strtoupper(trim($request->input('category', 'MILITAR')));
        $unitId = (int)$request->input('unit_id', 1);

        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            Session::flash('error', 'Por favor seleccione un archivo Excel (.xlsx) válido.');
            Response::redirect('/personnel/import');
        }

        try {
            $preview = ExcelImportService::previewPersonnel($file['tmp_name'], $category, $unitId);
            
            // Guardar datos temporales en sesión para la posterior confirmación
            Session::set('pending_import_items', $preview['items']);
            Session::set('pending_import_category', $category);

            View::render('personnel.import_preview', [
                'title'    => 'Vista Previa de Importación de Personal',
                'preview'  => $preview,
                'category' => $category,
                'unit_id'  => $unitId
            ], 'main');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al procesar el archivo Excel: ' . $e->getMessage());
            Response::redirect('/personnel/import');
        }
    }

    public function confirmImport(Request $request): void
    {
        $items = Session::get('pending_import_items');
        $category = Session::get('pending_import_category', 'MILITAR');
        $approvedDnis = $request->input('approved_dnis', []);

        if (empty($items)) {
            Session::flash('error', 'No hay datos pendientes de confirmación.');
            Response::redirect('/personnel/import');
        }

        $result = ExcelImportService::confirmPersonnelImport($items, $category, $approvedDnis);
        Session::remove('pending_import_items');
        Session::remove('pending_import_category');

        if ($result['success']) {
            Session::flash('success', "Importación completada: {$result['inserted']} nuevos registros, {$result['updated']} actualizados, {$result['skipped']} omitidos.");
        } else {
            Session::flash('error', $result['message']);
        }

        Response::redirect('/personnel');
    }

    public function photosZipView(Request $request): void
    {
        View::render('personnel.photos_zip', [
            'title' => 'Carga Masiva de Fotografías (ZIP) - COEDE'
        ], 'main');
    }

    public function previewPhotosZip(Request $request): void
    {
        $file = $request->file('zip_file');
        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            Session::flash('error', 'Por favor seleccione un archivo ZIP con fotografías.');
            Response::redirect('/personnel/photos-zip');
        }

        // Guardar ZIP temporalmente en storage
        $tempZip = dirname(__DIR__, 2) . '/storage/templates/temp_photos_' . time() . '.zip';
        move_uploaded_file($file['tmp_name'], $tempZip);

        $preview = ZipPhotoImportService::previewZip($tempZip);
        if (!$preview['success']) {
            @unlink($tempZip);
            Session::flash('error', $preview['message']);
            Response::redirect('/personnel/photos-zip');
        }

        Session::set('pending_photos_zip_path', $tempZip);
        Session::set('pending_photos_preview', $preview['items']);

        View::render('personnel.photos_zip_preview', [
            'title'   => 'Vista Previa de Fotografías ZIP',
            'preview' => $preview
        ], 'main');
    }

    public function confirmPhotosZip(Request $request): void
    {
        $zipPath = Session::get('pending_photos_zip_path');
        $selectedIndices = $request->input('selected_indices', []);

        if (!$zipPath || !file_exists($zipPath)) {
            Session::flash('error', 'Sesión de importación expirada. Intente de nuevo.');
            Response::redirect('/personnel/photos-zip');
        }

        $result = ZipPhotoImportService::processZip($zipPath, $selectedIndices);
        @unlink($zipPath);
        Session::remove('pending_photos_zip_path');
        Session::remove('pending_photos_preview');

        if ($result['success']) {
            Session::flash('success', "Se importaron exitosamente {$result['imported']} fotografías asociadas al padrón.");
        } else {
            Session::flash('error', $result['message']);
        }

        Response::redirect('/personnel');
    }

    public function photoRequests(Request $request): void
    {
        $db = Database::getConnection();
        $requests = $db->query("
            SELECT r.*, p.dni, p.rank_grade, p.surnames, p.names, p.category,
                   u.name AS unit_name, u2.name AS requester_name
            FROM photo_requests_demo r
            JOIN personnel_demo p ON r.personnel_id = p.id
            JOIN units u ON p.unit_id = u.id
            JOIN users u2 ON r.requested_by = u2.id
            WHERE r.status = 'PENDIENTE'
            ORDER BY u.name ASC, r.requested_at DESC
        ")->fetchAll();

        View::render('personnel.photo_requests', [
            'title' => 'Solicitudes de Fotografía Pendientes - COEDE',
            'requests' => $requests
        ], 'main');
    }

    public function exportExcel(Request $request): void
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT p.*, u.name AS unit_name, a.auth_start, a.auth_end,
                   DATEDIFF(a.auth_end, CURDATE()) AS days_left
            FROM personnel_demo p
            JOIN units u ON p.unit_id = u.id
            LEFT JOIN personnel_authorizations_demo a ON p.id = a.personnel_id
            ORDER BY p.category, p.surnames
        ");
        $personnel = $stmt->fetchAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Padrón Personal');

        $headers = ['CATEGORÍA', 'DNI', 'CIP/CIV', 'GRADO', 'APELLIDOS', 'NOMBRES', 'DEPENDENCIA', 'INICIO', 'VENCIMIENTO', 'ESTADO VIGENCIA', 'FOTOGRAFÍA'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3022');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Formato de texto para preservar ceros a la izquierda en DNI
        $sheet->getStyle('B:C')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $rowNum = 2;
        foreach ($personnel as $p) {
            $days = $p['days_left'] !== null ? (int)$p['days_left'] : null;
            $vigencia = 'SIN VIGENCIA';
            if ($days !== null) {
                if ($days < 0) $vigencia = 'VENCIDO';
                elseif ($days <= 7) $vigencia = "POR VENCER ({$days} DÍAS)";
                elseif ($days <= 30) $vigencia = 'POR VENCER (30 DÍAS)';
                else $vigencia = 'VIGENTE';
            }

            $sheet->setCellValue('A' . $rowNum, $p['category']);
            $sheet->setCellValueExplicit('B' . $rowNum, (string)$p['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowNum, (string)($p['cip_civ'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $rowNum, $p['rank_grade'] ?? '-');
            $sheet->setCellValue('E' . $rowNum, $p['surnames']);
            $sheet->setCellValue('F' . $rowNum, $p['names']);
            $sheet->setCellValue('G' . $rowNum, $p['unit_name']);
            $sheet->setCellValue('H' . $rowNum, $p['auth_start'] ?? '-');
            $sheet->setCellValue('I' . $rowNum, $p['auth_end'] ?? '-');
            $sheet->setCellValue('J' . $rowNum, $vigencia);
            $sheet->setCellValue('K' . $rowNum, !empty($p['photo_path']) ? 'REGISTRADA' : 'PENDIENTE');
            $rowNum++;
        }

        $out = dirname(__DIR__, 2) . '/storage/templates/Padron_Personal_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($out);

        Response::sendFile($out, 'Padron_Personal_COEDE.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

