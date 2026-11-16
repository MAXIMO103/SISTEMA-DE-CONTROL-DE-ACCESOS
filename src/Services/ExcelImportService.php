<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExcelImportService
{
    /**
     * Genera plantillas oficiales de Excel para descarga
     */
    public static function generateTemplate(string $type): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headerColor = '1E3022'; // Olive dark
        $headerFontColor = 'FFFFFF';

        if ($type === 'militar') {
            $sheet->setTitle('Personal Militar');
            $headers = ['DNI', 'CIP', 'GRADO', 'APELLIDOS', 'NOMBRES', 'CODIGO_UNIDAD', 'INICIO_VIGENCIA', 'FIN_VIGENCIA', 'NOTAS'];
            $sample = [
                ['45892134', 'CIP-104921', 'Coronel', 'CRICRI_2026', '', 'EM-COEDE', '2026-01-01', '2027-01-01', 'Autorización anual'],
                ['71029384', 'CIP-219482', 'Capitán', 'Rojas Mendoza', 'Edgar Daniel', 'ESGE', '2026-06-01', '2026-11-20', 'Por vencer'],
                ['08192834', 'CIP-339102', 'Mayor', 'Alvarez Castro', 'Manuel Antonio', 'EMCH', '2026-02-01', '2027-02-01', 'Nuevo registro']
            ];
        } elseif ($type === 'civil') {
            $sheet->setTitle('Personal Civil');
            $headers = ['DNI', 'IDENTIFICADOR_CIV', 'APELLIDOS', 'NOMBRES', 'CODIGO_UNIDAD', 'INICIO_VIGENCIA', 'FIN_VIGENCIA', 'NOTAS'];
            $sample = [
                ['08928374', 'CIV-0482', 'Paredes Benavides', 'María Elena', 'EM-COEDE', '2026-01-01', '2027-01-01', 'Personal nombrado'],
                ['44819203', 'CIV-0912', 'Campos Alarcón', 'Roberto Carlos', 'ICTE', '2026-08-01', '2026-11-18', 'Contrato CAS'],
                ['01928371', 'CIV-1102', 'Vargas Salazar', 'Patricia Yolanda', 'ESGE', '2026-03-01', '2027-03-01', 'Docente civil']
            ];
        } elseif ($type === 'vehiculos_particulares') {
            $sheet->setTitle('Vehículos Particulares');
            $headers = ['PLACA', 'DNI_PROPIETARIO', 'CIP_CIV', 'PROPIETARIO_NOMBRE', 'MARCA', 'MODELO', 'CODIGO_UNIDAD', 'INICIO_VIGENCIA', 'FIN_VIGENCIA'];
            $sample = [
                ['B6A-912', '45892134', 'CIP-104921', 'CRICRI_2026', 'Hyundai', 'Tucson', 'EM-COEDE', '2026-01-01', '2027-01-01'],
                ['F8T-443', '43110294', 'CIP-184920', 'Zavala Cornejo, Juan Fernando', 'Kia', 'Sportage', 'EMCH', '2026-06-01', '2026-11-19']
            ];
        } else { // vehiculos_oficiales
            $sheet->setTitle('Vehículos Oficiales');
            $headers = ['PLACA', 'MARCA', 'MODELO', 'CODIGO_UNIDAD', 'INICIO_VIGENCIA', 'FIN_VIGENCIA', 'DETALLES_ASIGNACION'];
            $sample = [
                ['EP-12401', 'Toyota', 'Hilux 4x4', 'EM-COEDE', '2026-01-01', '2027-01-01', 'Comando Central COEDE'],
                ['EP-08912', 'Nissan', 'Frontier', 'ESGE', '2026-01-01', '2027-01-01', 'Unidad de Apoyo ESGE']
            ];
        }

        // Escribir cabeceras
        $colIdx = 'A';
        foreach ($headers as $header) {
            $cell = $colIdx . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB($headerFontColor);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerColor);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($colIdx)->setAutoSize(true);
            $colIdx++;
        }

        // Formatear columnas de DNI explícitamente como TEXTO para preservar ceros a la izquierda
        $sheet->getStyle('A:B')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        // Escribir filas de ejemplo
        $rowIdx = 2;
        foreach ($sample as $row) {
            $cIdx = 'A';
            foreach ($row as $val) {
                // Forzar string para DNI y PLACA
                $sheet->setCellValueExplicit($cIdx . $rowIdx, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cIdx++;
            }
            $rowIdx++;
        }

        $templatePath = dirname(__DIR__, 2) . '/storage/templates/plantilla_' . $type . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($templatePath);
        return $templatePath;
    }

    /**
     * Previsualiza y valida la importación de personal militar o civil
     */
    public static function previewPersonnel(string $filePath, string $category, int $targetUnitId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $db = Database::getConnection();
        
        // Obtener dependencias válidas
        $unitMap = [];
        foreach ($db->query("SELECT id, code, name FROM units WHERE is_active = 1")->fetchAll() as $u) {
            $unitMap[strtoupper(trim($u['code']))] = $u['id'];
        }

        // Obtener personal actual de la unidad para comparar ausentes
        $existingInUnitStmt = $db->prepare("
            SELECT dni, rank_grade, surnames, names 
            FROM personnel_demo 
            WHERE unit_id = :unit_id AND category = :cat AND is_active = 1
        ");
        $existingInUnitStmt->execute(['unit_id' => $targetUnitId, 'cat' => $category]);
        $existingInUnit = [];
        foreach ($existingInUnitStmt->fetchAll() as $p) {
            $existingInUnit[$p['dni']] = $p;
        }

        $processed = [];
        $dnisInFile = [];
        $header = array_shift($rows); // Quitar fila de cabecera

        $rowNum = 1;
        foreach ($rows as $r) {
            $rowNum++;
            // Saltar filas totalmente vacías
            if (empty(array_filter($r))) continue;

            $dni = isset($r['A']) ? str_pad(trim((string)$r['A']), 8, '0', STR_PAD_LEFT) : '';
            
            $observations = [];
            $actionType = 'NUEVO'; // NUEVO, MODIFICACION, RENOVACION, TRASLADO, OBSERVADO
            $requiresIndividualApproval = false;

            // Validar DNI
            if (!preg_match('/^\d{8}$/', $dni)) {
                $observations[] = "DNI inválido ('{$dni}'). Debe constar de 8 dígitos numéricos.";
                $actionType = 'OBSERVADO';
            } elseif (isset($dnisInFile[$dni])) {
                $observations[] = "DNI duplicado en el mismo archivo (aparece en fila {$dnisInFile[$dni]}).";
                $actionType = 'OBSERVADO';
            } else {
                $dnisInFile[$dni] = $rowNum;
            }

            if ($category === 'MILITAR') {
                $cip = trim($r['B'] ?? '');
                $rank = trim($r['C'] ?? '');
                $surnames = trim($r['D'] ?? '');
                $names = trim($r['E'] ?? '');
                $unitCode = strtoupper(trim($r['F'] ?? ''));
                $authStart = trim($r['G'] ?? '');
                $authEnd = trim($r['H'] ?? '');
                $notes = trim($r['I'] ?? '');
            } else {
                $cip = trim($r['B'] ?? ''); // CIV
                $rank = null;
                $surnames = trim($r['C'] ?? '');
                $names = trim($r['D'] ?? '');
                $unitCode = strtoupper(trim($r['E'] ?? ''));
                $authStart = trim($r['F'] ?? '');
                $authEnd = trim($r['G'] ?? '');
                $notes = trim($r['H'] ?? '');
            }

            if (empty($surnames) || empty($names)) {
                $observations[] = "Apellidos y nombres son obligatorios.";
                $actionType = 'OBSERVADO';
            }

            // Validar unidad
            $unitId = $unitMap[$unitCode] ?? $targetUnitId;
            if (!empty($unitCode) && !isset($unitMap[$unitCode])) {
                $observations[] = "Código de unidad '{$unitCode}' no existe. Se asignará la unidad seleccionada.";
            }

            // Validar fechas
            $validDates = true;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $authStart) || !strtotime($authStart)) {
                $observations[] = "Fecha de inicio inválida ('{$authStart}'). Use formato AAAA-MM-DD.";
                $validDates = false;
                $actionType = 'OBSERVADO';
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $authEnd) || !strtotime($authEnd)) {
                $observations[] = "Fecha de fin inválida ('{$authEnd}'). Use formato AAAA-MM-DD.";
                $validDates = false;
                $actionType = 'OBSERVADO';
            }
            if ($validDates && strtotime($authEnd) <= strtotime($authStart)) {
                $observations[] = "La fecha de fin debe ser posterior a la fecha de inicio.";
                $actionType = 'OBSERVADO';
            }

            // Comparar con base de datos existente
            if ($actionType !== 'OBSERVADO') {
                $existStmt = $db->prepare("
                    SELECT p.*, a.auth_start, a.auth_end 
                    FROM personnel_demo p
                    LEFT JOIN personnel_authorizations_demo a ON p.id = a.personnel_id
                    WHERE p.dni = :dni
                    ORDER BY a.id DESC LIMIT 1
                ");
                $existStmt->execute(['dni' => $dni]);
                $existing = $existStmt->fetch();

                if ($existing) {
                    // Detectar si hay cambios de grado o nombres
                    $nameDiff = (strcasecmp($existing['surnames'], $surnames) !== 0 || strcasecmp($existing['names'], $names) !== 0);
                    $rankDiff = ($category === 'MILITAR' && strcasecmp($existing['rank_grade'] ?? '', $rank) !== 0);

                    if ($nameDiff || $rankDiff) {
                        $actionType = 'MODIFICACION_CRITICA';
                        $requiresIndividualApproval = true;
                        if ($nameDiff) $observations[] = "Discrepancia en nombres: BD('{$existing['surnames']}, {$existing['names']}') vs Archivo('{$surnames}, {$names}'). Requiere confirmación individual.";
                        if ($rankDiff) $observations[] = "Cambio de grado detectado: BD('{$existing['rank_grade']}') -> Archivo('{$rank}'). Requiere confirmación individual.";
                    } elseif ($existing['unit_id'] != $unitId) {
                        $actionType = 'TRASLADO';
                        $observations[] = "Traslado de dependencia detectado.";
                    } elseif ($existing['auth_end'] !== $authEnd) {
                        $actionType = 'RENOVACION';
                        $observations[] = "Actualización de vigencia (Renovación).";
                    } else {
                        $actionType = 'SIN_CAMBIOS';
                        $observations[] = "Registro idéntico sin cambios.";
                    }
                }
            }

            $processed[] = [
                'row_number' => $rowNum,
                'dni' => $dni,
                'cip_civ' => $cip,
                'rank_grade' => $rank,
                'surnames' => $surnames,
                'names' => $names,
                'unit_id' => $unitId,
                'auth_start' => $authStart,
                'auth_end' => $authEnd,
                'notes' => $notes,
                'action_type' => $actionType,
                'requires_approval' => $requiresIndividualApproval,
                'observations' => $observations
            ];
        }

        // Detectar ausentes de la relación anterior de la unidad (sin darlos de baja automáticamente)
        $absent = [];
        foreach ($existingInUnit as $dni => $pers) {
            if (!isset($dnisInFile[$dni])) {
                $absent[] = [
                    'dni' => $dni,
                    'rank_grade' => $pers['rank_grade'] ?? 'Civil',
                    'full_name' => $pers['surnames'] . ', ' . $pers['names'],
                    'status' => 'AUSENTE_EN_NUEVA_RELACION',
                    'note' => 'No figura en la nueva plantilla. No se da de baja automáticamente; permanece en su estado actual.'
                ];
            }
        }

        return [
            'total_rows' => count($processed),
            'valid_rows' => count(array_filter($processed, fn($p) => $p['action_type'] !== 'OBSERVADO')),
            'observed_rows' => count(array_filter($processed, fn($p) => $p['action_type'] === 'OBSERVADO')),
            'critical_rows' => count(array_filter($processed, fn($p) => $p['requires_approval'])),
            'absent_count' => count($absent),
            'items' => $processed,
            'absent_items' => $absent
        ];
    }

    /**
     * Confirma e inserta filas validadas en base de datos
     */
    public static function confirmPersonnelImport(array $items, string $category, array $approvedCriticalDnis = []): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $adminId = Auth::id();

        try {
            foreach ($items as $item) {
                // Si la fila fue observada por error, saltar
                if ($item['action_type'] === 'OBSERVADO') {
                    $skipped++;
                    continue;
                }

                // Si requiere aprobación individual y no fue aprobada explícitamente, saltar
                if ($item['requires_approval'] && !in_array($item['dni'], $approvedCriticalDnis, true)) {
                    $skipped++;
                    continue;
                }

                // Buscar si ya existe
                $stmt = $db->prepare("SELECT id FROM personnel_demo WHERE dni = :dni");
                $stmt->execute(['dni' => $item['dni']]);
                $existingId = $stmt->fetchColumn();

                if ($existingId) {
                    // Actualizar ficha
                    $upd = $db->prepare("
                        UPDATE personnel_demo 
                        SET cip_civ = :cip, rank_grade = :rank, surnames = :surnames, names = :names, 
                            unit_id = :unit_id, is_active = 1, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $upd->execute([
                        'cip'      => $item['cip_civ'],
                        'rank'     => $item['rank_grade'],
                        'surnames' => $item['surnames'],
                        'names'    => $item['names'],
                        'unit_id'  => $item['unit_id'],
                        'id'       => $existingId
                    ]);

                    // Actualizar o crear autorización
                    $authUpd = $db->prepare("
                        INSERT INTO personnel_authorizations_demo (personnel_id, auth_start, auth_end, status, notes, created_by, created_at)
                        VALUES (:pid, :start, :end, 'ACTIVO', :notes, :created_by, NOW())
                    ");
                    $authUpd->execute([
                        'pid'        => $existingId,
                        'start'      => $item['auth_start'],
                        'end'        => $item['auth_end'],
                        'notes'      => $item['notes'] ?? 'Importación Excel',
                        'created_by' => $adminId
                    ]);

                    $updated++;
                } else {
                    // Insertar nuevo
                    $ins = $db->prepare("
                        INSERT INTO personnel_demo (category, dni, cip_civ, rank_grade, surnames, names, unit_id, is_active, created_at)
                        VALUES (:cat, :dni, :cip, :rank, :surnames, :names, :unit_id, 1, NOW())
                    ");
                    $ins->execute([
                        'cat'      => $category,
                        'dni'      => $item['dni'],
                        'cip'      => $item['cip_civ'],
                        'rank'     => $item['rank_grade'],
                        'surnames' => $item['surnames'],
                        'names'    => $item['names'],
                        'unit_id'  => $item['unit_id']
                    ]);
                    $newId = (int)$db->lastInsertId();

                    $authIns = $db->prepare("
                        INSERT INTO personnel_authorizations_demo (personnel_id, auth_start, auth_end, status, notes, created_by, created_at)
                        VALUES (:pid, :start, :end, 'ACTIVO', :notes, :created_by, NOW())
                    ");
                    $authIns->execute([
                        'pid'        => $newId,
                        'start'      => $item['auth_start'],
                        'end'        => $item['auth_end'],
                        'notes'      => $item['notes'] ?? 'Alta inicial Excel',
                        'created_by' => $adminId
                    ]);

                    $inserted++;
                }
            }

            $db->commit();
            AuditService::log('IMPORT_PERSONNEL', 'personnel_demo', null, null, [
                'category' => $category,
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped
            ]);

            return [
                'success'  => true,
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Error durante la confirmación: ' . $e->getMessage()
            ];
        }
    }
}

