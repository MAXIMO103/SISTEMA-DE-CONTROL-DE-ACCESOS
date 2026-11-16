<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditService;
use App\Services\ReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class ReportController
{
    public function index(Request $request): void
    {
        $period    = $request->query('period', 'hoy');
        $startDate = $request->query('start_date', null);
        $endDate   = $request->query('end_date', null);

        // Dashboard estadístico integrado exclusivamente dentro de Reportes
        $dashboardData = ReportService::getGateDashboardStats($period, $startDate, $endDate);

        View::render('reports.index', [
            'title'     => 'Reportes Generales y Estadísticas - COEDE',
            'period'    => $period,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'stats'     => $dashboardData
        ], 'main');
    }

    public function gateReport(Request $request): void
    {
        $filters = [
            'movement_type' => $request->query('movement_type', 'TODOS'),
            'target_type'   => $request->query('target_type', 'TODOS'),
            'is_visitor'    => $request->query('is_visitor', 'TODOS'),
            'dni'           => $request->query('dni', ''),
            'plate'         => $request->query('plate', ''),
            'start_date'    => $request->query('start_date', date('Y-m-d')),
            'end_date'      => $request->query('end_date', date('Y-m-d'))
        ];

        $movements = ReportService::getGateMovements($filters);

        View::render('reports.gate_report', [
            'title'     => 'Reporte de Control de Puerta Principal',
            'filters'   => $filters,
            'movements' => $movements
        ], 'main');
    }

    public function incidentsReport(Request $request): void
    {
        $filters = [
            'status'     => $request->query('status', 'TODOS'),
            'event_type' => $request->query('event_type', ''),
            'start_date' => $request->query('start_date', date('Y-m-d', strtotime('-30 days'))),
            'end_date'   => $request->query('end_date', date('Y-m-d'))
        ];

        $incidents = ReportService::getIncidentsReport($filters);

        View::render('reports.incidents_report', [
            'title'     => 'Reporte General de Incidencias',
            'filters'   => $filters,
            'incidents' => $incidents
        ], 'main');
    }

    public function exportGateExcel(Request $request): void
    {
        $filters = [
            'movement_type' => $request->query('movement_type', 'TODOS'),
            'target_type'   => $request->query('target_type', 'TODOS'),
            'is_visitor'    => $request->query('is_visitor', 'TODOS'),
            'dni'           => $request->query('dni', ''),
            'plate'         => $request->query('plate', ''),
            'start_date'    => $request->query('start_date', date('Y-m-d')),
            'end_date'      => $request->query('end_date', date('Y-m-d'))
        ];

        $movements = ReportService::getGateMovements($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimientos Puerta');

        $headers = [
            'N.°', 'FECHA/HORA', 'TIPO MOVIMIENTO', 'MODALIDAD', 'CONDICIÓN',
            'DNI', 'APELLIDOS Y NOMBRES', 'GRADO / CLASE', 'DEPENDENCIA',
            'PLACA', 'DETALLE VEHÍCULO', 'DESTINO', 'AUTORIZÓ', 'DECISIÓN', 'OBSERVACIÓN', 'OPERADOR'
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3022');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $sheet->getStyle('F')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $rowNum = 2;
        $idx = 1;
        foreach ($movements as $m) {
            $sheet->setCellValue('A' . $rowNum, $idx);
            $sheet->setCellValue('B' . $rowNum, $m['recorded_at']);
            $sheet->setCellValue('C' . $rowNum, $m['movement_type']);
            $sheet->setCellValue('D' . $rowNum, $m['target_type']);
            $sheet->setCellValue('E' . $rowNum, $m['is_visitor'] ? 'VISITANTE' : 'TRABAJADOR');
            $sheet->setCellValueExplicit('F' . $rowNum, (string)$m['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowNum, $m['person_name']);
            $sheet->setCellValue('H' . $rowNum, $m['rank_or_type'] ?? '-');
            $sheet->setCellValue('I' . $rowNum, $m['unit_name'] ?? '-');
            $sheet->setCellValue('J' . $rowNum, $m['plate'] ?? '-');
            $sheet->setCellValue('K' . $rowNum, $m['vehicle_details'] ?? '-');
            $sheet->setCellValue('L' . $rowNum, $m['destination'] ?? '-');
            $sheet->setCellValue('M' . $rowNum, $m['authority_authorizer'] ?? '-');
            $sheet->setCellValue('N' . $rowNum, $m['operator_decision']);
            $sheet->setCellValue('O' . $rowNum, $m['observation'] ?? '-');
            $sheet->setCellValue('P' . $rowNum, $m['operator_name'] ?? $m['operator_username'] ?? 'Sistema');
            $rowNum++;
            $idx++;
        }

        $out = dirname(__DIR__, 2) . '/storage/templates/Reporte_Puerta_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($out);

        AuditService::log('EXPORT_GATE_EXCEL', 'movements_demo');
        Response::sendFile($out, 'Reporte_Control_Puerta_COEDE.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

