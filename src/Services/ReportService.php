<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class ReportService
{
    /**
     * Obtiene estadísticas agregadas para el dashboard integrado dentro de Reportes
     */
    public static function getGateDashboardStats(string $period = 'hoy', ?string $startDate = null, ?string $endDate = null): array
    {
        $db = Database::getConnection();

        // Determinar rango de fechas
        [$sDate, $eDate] = self::resolveDateRange($period, $startDate, $endDate);

        // 1. Totales de entradas y salidas (Separando Peatonal y Vehicular)
        $stmtTot = $db->prepare("
            SELECT 
                target_type,
                movement_type,
                COUNT(*) as total
            FROM movements_demo
            WHERE recorded_at >= :sdate AND recorded_at <= :edate
            GROUP BY target_type, movement_type
        ");
        $stmtTot->execute(['sdate' => $sDate, 'edate' => $eDate]);
        $totalsRaw = $stmtTot->fetchAll();

        $metrics = [
            'peatonal_entradas' => 0,
            'peatonal_salidas'  => 0,
            'vehicular_entradas' => 0,
            'vehicular_salidas'  => 0,
            'movimientos_abiertos' => 0,
            'declaraciones_pendientes' => 0,
            'estimacion_permanencia_peatonal' => 0,
            'estimacion_permanencia_vehicular' => 0
        ];

        foreach ($totalsRaw as $row) {
            $key = strtolower($row['target_type']) . '_' . strtolower($row['movement_type'] === 'ENTRADA' ? 'entradas' : 'salidas');
            if (isset($metrics[$key])) {
                $metrics[$key] = (int)$row['total'];
            }
        }

        // Estimación rotulada de ocupación
        $metrics['estimacion_permanencia_peatonal'] = max(0, $metrics['peatonal_entradas'] - $metrics['peatonal_salidas']);
        $metrics['estimacion_permanencia_vehicular'] = max(0, $metrics['vehicular_entradas'] - $metrics['vehicular_salidas']);

        // Movimientos abiertos en BD
        $stmtOpen = $db->query("SELECT COUNT(*) FROM movements_demo WHERE movement_type = 'ENTRADA' AND is_closed = 0");
        $metrics['movimientos_abiertos'] = (int)$stmtOpen->fetchColumn();

        // Declaraciones de salida pendientes de revisión
        $stmtDecl = $db->query("SELECT COUNT(*) FROM exit_declarations_demo WHERE status = 'PENDIENTE_REVISION'");
        $metrics['declaraciones_pendientes'] = (int)$stmtDecl->fetchColumn();

        // 2. Datos para gráfico de evolución temporal (por horas o por días)
        $timelineStmt = $db->prepare("
            SELECT 
                DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00') as time_slot,
                SUM(CASE WHEN movement_type = 'ENTRADA' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN movement_type = 'SALIDA' THEN 1 ELSE 0 END) as salidas
            FROM movements_demo
            WHERE recorded_at >= :sdate AND recorded_at <= :edate
            GROUP BY time_slot
            ORDER BY time_slot ASC
        ");
        $timelineStmt->execute(['sdate' => $sDate, 'edate' => $eDate]);
        $timeline = $timelineStmt->fetchAll();

        // 3. Distribución por dependencias
        $unitStmt = $db->prepare("
            SELECT unit_name, COUNT(*) as count 
            FROM movements_demo 
            WHERE recorded_at >= :sdate AND recorded_at <= :edate AND unit_name IS NOT NULL AND unit_name != ''
            GROUP BY unit_name 
            ORDER BY count DESC 
            LIMIT 6
        ");
        $unitStmt->execute(['sdate' => $sDate, 'edate' => $eDate]);
        $units = $unitStmt->fetchAll();

        return [
            'metrics' => $metrics,
            'timeline' => $timeline,
            'units' => $units,
            'start_date' => $sDate,
            'end_date' => $eDate
        ];
    }

    /**
     * Consulta detallada de movimientos para reporte tabular y exportación
     */
    public static function getGateMovements(array $filters): array
    {
        $db = Database::getConnection();
        $query = "
            SELECT m.*, u.username as operator_username, u.name as operator_name,
                   d.declared_gate, d.approx_time as decl_time, d.observation as decl_obs, d.status as decl_status
            FROM movements_demo m
            LEFT JOIN users u ON m.recorded_by = u.id
            LEFT JOIN exit_declarations_demo d ON m.id = d.open_movement_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['movement_type']) && $filters['movement_type'] !== 'TODOS') {
            $query .= " AND m.movement_type = :mtype";
            $params['mtype'] = $filters['movement_type'];
        }
        if (!empty($filters['target_type']) && $filters['target_type'] !== 'TODOS') {
            $query .= " AND m.target_type = :ttype";
            $params['ttype'] = $filters['target_type'];
        }
        if (!empty($filters['is_visitor']) && $filters['is_visitor'] !== 'TODOS') {
            $query .= " AND m.is_visitor = :vis";
            $params['vis'] = (int)$filters['is_visitor'];
        }
        if (!empty($filters['dni'])) {
            $query .= " AND m.dni = :dni";
            $params['dni'] = trim($filters['dni']);
        }
        if (!empty($filters['plate'])) {
            $query .= " AND m.plate LIKE :plate";
            $params['plate'] = '%' . trim($filters['plate']) . '%';
        }
        if (!empty($filters['start_date'])) {
            $query .= " AND m.recorded_at >= :sdate";
            $params['sdate'] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND m.recorded_at <= :edate";
            $params['edate'] = $filters['end_date'] . ' 23:59:59';
        }

        $query .= " ORDER BY m.recorded_at DESC LIMIT 500";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Consulta de incidencias para reportes
     */
    public static function getIncidentsReport(array $filters): array
    {
        $db = Database::getConnection();
        $query = "
            SELECT i.*, u1.name as creator_name, u2.name as closer_name,
                   COUNT(e.id) as evidence_count
            FROM incidents_demo i
            LEFT JOIN users u1 ON i.created_by = u1.id
            LEFT JOIN users u2 ON i.closed_by = u2.id
            LEFT JOIN incident_evidence_demo e ON i.id = e.incident_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'TODOS') {
            $query .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['event_type'])) {
            $query .= " AND i.event_type LIKE :etype";
            $params['etype'] = '%' . trim($filters['event_type']) . '%';
        }
        if (!empty($filters['start_date'])) {
            $query .= " AND i.created_at >= :sdate";
            $params['sdate'] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND i.created_at <= :edate";
            $params['edate'] = $filters['end_date'] . ' 23:59:59';
        }

        $query .= " GROUP BY i.id ORDER BY i.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function resolveDateRange(string $period, ?string $start, ?string $end): array
    {
        $today = date('Y-m-d');
        if ($period === 'hoy') {
            return [$today . ' 00:00:00', $today . ' 23:59:59'];
        }
        if ($period === 'semana') {
            $startWeek = date('Y-m-d', strtotime('-7 days'));
            return [$startWeek . ' 00:00:00', $today . ' 23:59:59'];
        }
        if ($period === 'mes') {
            $startMonth = date('Y-m-d', strtotime('-30 days'));
            return [$startMonth . ' 00:00:00', $today . ' 23:59:59'];
        }
        // Rango personalizado
        $s = !empty($start) ? $start . ' 00:00:00' : date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $e = !empty($end) ? $end . ' 23:59:59' : $today . ' 23:59:59';
        return [$s, $e];
    }
}

