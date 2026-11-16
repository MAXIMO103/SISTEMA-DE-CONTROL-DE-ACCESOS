<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Dompdf\Dompdf;
use Dompdf\Options;

class SidNewsService
{
    public const DEPARTAMENTOS = [
        'Amazonas', 'Áncash', 'Apurímac', 'Arequipa', 'Ayacucho', 'Cajamarca', 
        'Callao', 'Cusco', 'Huancavelica', 'Huánuco', 'Ica', 'Junín', 
        'La Libertad', 'Lambayeque', 'Lima', 'Loreto', 'Madre de Dios', 
        'Moquegua', 'Pasco', 'Piura', 'Puno', 'San Martín', 'Tacna', 'Tumbes', 'Ucayali'
    ];

    public const AMENAZAS = [
        'Conflictividad social' => 'Paros, huelgas, bloqueos de vías, movilizaciones, marchas, protestas y conflictos laborales',
        'Delincuencia común y ciberdelincuencia' => 'Robos, hurtos, asaltos a mano armada, estafas y fraudes informáticos',
        'Criminalidad organizada y extorsiones' => 'Cobro de cupos, extorsión a comercios/obras, sicariato y bandas criminales',
        'Organizaciones criminales transnacionales' => 'Trata de personas, tráfico ilícito de armas y narcotráfico transfronterizo'
    ];

    /**
     * Búsqueda pública de noticias (últimas 24 horas móviles en hora de Lima)
     */
    public static function searchNews(string $department, string $threat): array
    {
        $db = Database::getConnection();
        $userId = Auth::id() ?? 1;

        // 1. Consultar eventos guardados en la BD en las últimas 24 horas para este departamento y amenaza
        $stmt = $db->prepare("
            SELECT e.*, u.name AS creator_name,
                   COUNT(s.id) AS sources_count
            FROM news_events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN news_sources s ON e.id = s.news_event_id
            WHERE e.department = :dept 
              AND e.threat_type = :threat
              AND e.event_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY e.id
            ORDER BY e.event_date DESC
        ");
        $stmt->execute(['dept' => $department, 'threat' => $threat]);
        $existingEvents = $stmt->fetchAll();

        // 2. Si no hay eventos registrados en las últimas 24h, consultar adaptador simulado demo
        if (empty($existingEvents)) {
            $generated = self::fetchFromDemoAdapter($department, $threat);
            foreach ($generated as $item) {
                self::persistEvent($item, $userId);
            }
            // Re-consultar
            $stmt->execute(['dept' => $department, 'threat' => $threat]);
            $existingEvents = $stmt->fetchAll();
        }

        // Cargar fuentes para cada evento
        foreach ($existingEvents as &$evt) {
            $srcStmt = $db->prepare("
                SELECT media_name, source_url, published_at, photo_url, photo_credit
                FROM news_sources 
                WHERE news_event_id = :id 
                ORDER BY published_at DESC
            ");
            $srcStmt->execute(['id' => $evt['id']]);
            $evt['sources'] = $srcStmt->fetchAll();
        }

        // Registrar búsqueda en historial
        $logSearch = $db->prepare("
            INSERT INTO news_searches (department, threat_type, results_count, performed_by, created_at)
            VALUES (:dept, :threat, :cnt, :uid, NOW())
        ");
        $logSearch->execute([
            'dept' => $department,
            'threat' => $threat,
            'cnt' => count($existingEvents),
            'uid' => $userId
        ]);

        return $existingEvents;
    }

    /**
     * Persiste un evento noticioso y sus fuentes deduplicadas
     */
    public static function persistEvent(array $item, int $userId): int
    {
        $db = Database::getConnection();

        // Comprobar si ya existe evento similar en fecha
        $chk = $db->prepare("
            SELECT id FROM news_events 
            WHERE department = :dept AND threat_type = :threat AND headline = :hl
            LIMIT 1
        ");
        $chk->execute([
            'dept' => $item['department'],
            'threat' => $item['threat_type'],
            'hl' => $item['headline']
        ]);
        $existingId = $chk->fetchColumn();

        if ($existingId) {
            $eventId = (int)$existingId;
        } else {
            $ins = $db->prepare("
                INSERT INTO news_events (
                    department, threat_type, headline, synthesis, original_headline,
                    original_synthesis, event_date, photo_url, photo_credit, is_hidden, created_by, created_at
                ) VALUES (
                    :dept, :threat, :hl, :synth, :orig_hl, :orig_synth, :edate, :purl, :pcred, 0, :uid, NOW()
                )
            ");
            $ins->execute([
                'dept' => $item['department'],
                'threat' => $item['threat_type'],
                'hl' => $item['headline'],
                'synth' => $item['synthesis'],
                'orig_hl' => $item['headline'],
                'orig_synth' => $item['synthesis'],
                'edate' => $item['event_date'] ?? date('Y-m-d H:i:s'),
                'purl' => $item['photo_url'] ?? null,
                'pcred' => $item['photo_credit'] ?? null,
                'uid' => $userId
            ]);
            $eventId = (int)$db->lastInsertId();
        }

        // Insertar fuentes deduplicadas por hash de URL canónica
        if (!empty($item['sources'])) {
            $insSrc = $db->prepare("
                INSERT IGNORE INTO news_sources (
                    news_event_id, media_name, source_url, canonical_hash, published_at, photo_url, photo_credit, created_at
                ) VALUES (
                    :eid, :media, :url, :hash, :pub_at, :purl, :pcred, NOW()
                )
            ");
            foreach ($item['sources'] as $src) {
                $hash = hash('sha256', rtrim($src['source_url'], '/'));
                $insSrc->execute([
                    'eid' => $eventId,
                    'media' => $src['media_name'],
                    'url' => $src['source_url'],
                    'hash' => $hash,
                    'pub_at' => $src['published_at'] ?? date('Y-m-d H:i:s'),
                    'purl' => $src['photo_url'] ?? null,
                    'pcred' => $src['photo_credit'] ?? null
                ]);
            }
        }

        return $eventId;
    }

    /**
     * Adaptador simulado que genera síntesis noticiosas institucionales de prueba
     */
    private static function fetchFromDemoAdapter(string $department, string $threat): array
    {
        $now = date('Y-m-d H:i:s', time() - 3600 * 2); // Hace 2 horas
        $events = [];

        if ($threat === 'Conflictividad social') {
            $events[] = [
                'department' => $department,
                'threat_type' => $threat,
                'headline' => "QUÉ: Bloqueo intermitente de vía principal | QUIÉN: Colectivo de transportistas de carga pesada | DÓNDE: {$department}",
                'synthesis' => "Un colectivo de transportistas de carga pesada inició un bloqueo intermitente en el kilómetro 48 de la red vial principal de {$department} el día de hoy aproximadamente a las 06:00 horas, colocando vehículos y neumáticos en ambos sentidos de la calzada para exigir la reducción de peajes y mayor seguridad ante asaltos nocturnos, con el objetivo de presionar al Ministerio de Transportes para la instalación de una mesa técnica de diálogo de emergencia.",
                'event_date' => $now,
                'photo_url' => null,
                'photo_credit' => 'Agencia Informativa Regional (Demostración)',
                'sources' => [
                    ['media_name' => 'Diario La República Regional', 'source_url' => "https://larepublica.pe/sociedad/demo-bloqueo-{$department}", 'published_at' => $now],
                    ['media_name' => 'Radio Programas del Perú (RPP)', 'source_url' => "https://rpp.pe/peru/demo-transporte-{$department}", 'published_at' => $now]
                ]
            ];
        } elseif ($threat === 'Criminalidad organizada y extorsiones') {
            $events[] = [
                'department' => $department,
                'threat_type' => $threat,
                'headline' => "QUÉ: Intervención policial a banda dedicada al cobro de cupos | QUIÉN: Presunta red delictiva 'Los Injertos del Norte' | DÓNDE: {$department}",
                'synthesis' => "Efectivos de la División de Investigación Criminal (DIVINCRI) intervinieron a cuatro presuntos integrantes de la banda 'Los Injertos del Norte' en un inmueble del cercado de {$department} en horas de la madrugada, mediante un allanamiento judicial tras semanas de seguimiento encubierto, debido a constantes denuncias de extorsión y colocación de artefactos pirotécnicos a microempresarios locales, con el fin de desarticular la estructura que exigía pagos semanales a comerciantes de la zona.",
                'event_date' => $now,
                'photo_url' => null,
                'photo_credit' => 'Nota de Prensa Policial (Demostración)',
                'sources' => [
                    ['media_name' => 'El Comercio - Seguridad', 'source_url' => "https://elcomercio.pe/seguridad/demo-operativo-{$department}", 'published_at' => $now],
                    ['media_name' => 'Andina Agencia de Noticias', 'source_url' => "https://andina.pe/noticia-demo-extorsion-{$department}", 'published_at' => $now]
                ]
            ];
        } else {
            $events[] = [
                'department' => $department,
                'threat_type' => $threat,
                'headline' => "QUÉ: Operativo contra el transporte ilícito y comercio informal | QUIÉN: Policía Nacional y Ministerio Público | DÓNDE: {$department}",
                'synthesis' => "La Policía Nacional en coordinación con la Fiscalía de Prevención del Delito ejecutó un operativo conjunto en las inmediaciones del terminal terrestre de {$department} a las 09:30 horas, inspeccionando encomiendas y vehículos de transporte interprovincial ante alertas de mercadería de contrabando y tráfico ilícito menor, con la finalidad de restaurar el principio de autoridad y prevenir hechos delictivos que afecten la seguridad de la ciudadanía.",
                'event_date' => $now,
                'photo_url' => null,
                'photo_credit' => 'Dirección de Comunicaciones (Demostración)',
                'sources' => [
                    ['media_name' => 'Diario Oficial El Peruano', 'source_url' => "https://elperuano.pe/noticia-demo-operativo-{$department}", 'published_at' => $now]
                ]
            ];
        }

        return $events;
    }

    /**
     * Edición editorial (Solo Administrador)
     */
    public static function updateEditorial(int $eventId, string $newHeadline, string $newSynthesis, ?string $notes): array
    {
        $db = Database::getConnection();
        $userId = Auth::id();

        try {
            $db->beginTransaction();

            // Obtener versión actual
            $curStmt = $db->prepare("SELECT headline, synthesis FROM news_events WHERE id = :id");
            $curStmt->execute(['id' => $eventId]);
            $current = $curStmt->fetch();

            if (!$current) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Evento noticioso no encontrado.'];
            }

            // Guardar versión anterior en historial
            $vStmt = $db->prepare("SELECT MAX(version_num) FROM news_versions WHERE news_event_id = :id");
            $vStmt->execute(['id' => $eventId]);
            $nextV = ((int)$vStmt->fetchColumn()) + 1;

            $insV = $db->prepare("
                INSERT INTO news_versions (news_event_id, version_num, headline, synthesis, edited_by, edit_notes, created_at)
                VALUES (:eid, :vnum, :hl, :synth, :uid, :notes, NOW())
            ");
            $insV->execute([
                'eid' => $eventId,
                'vnum' => $nextV,
                'hl' => $current['headline'],
                'synth' => $current['synthesis'],
                'uid' => $userId,
                'notes' => $notes ?? 'Actualización editorial administrativa'
            ]);

            // Actualizar evento
            $upd = $db->prepare("
                UPDATE news_events 
                SET headline = :hl, synthesis = :synth, updated_at = NOW() 
                WHERE id = :id
            ");
            $upd->execute([
                'hl' => trim($newHeadline),
                'synth' => trim($newSynthesis),
                'id' => $eventId
            ]);

            $db->commit();
            AuditService::log('EDIT_SID_NEWS', 'news_events', $eventId, $current, ['headline' => $newHeadline]);
            return ['success' => true, 'message' => 'Noticia actualizada y versionada exitosamente.'];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ocultar o recuperar noticia (Solo Administrador)
     */
    public static function toggleVisibility(int $eventId, bool $hide, ?string $reason): array
    {
        $db = Database::getConnection();
        $userId = Auth::id();

        try {
            $stmt = $db->prepare("
                UPDATE news_events 
                SET is_hidden = :hidden, hide_reason = :reason, hidden_by = :uid, hidden_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                'hidden' => $hide ? 1 : 0,
                'reason' => $reason,
                'uid'    => $userId,
                'id'     => $eventId
            ]);

            AuditService::log($hide ? 'HIDE_SID_NEWS' : 'RESTORE_SID_NEWS', 'news_events', $eventId, null, ['reason' => $reason]);
            return ['success' => true, 'message' => $hide ? 'Noticia ocultada del boletín.' : 'Noticia restaurada en el boletín.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Exportación Excel para SID con protección contra inyección de fórmulas (=, +, -, @)
     */
    public static function exportExcel(string $department, string $threat, ?string $startDate, ?string $endDate): string
    {
        $db = Database::getConnection();
        $query = "
            SELECT e.*, u.name AS user_name
            FROM news_events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.is_hidden = 0
        ";
        $params = [];

        if (!empty($department) && $department !== 'TODOS') {
            $query .= " AND e.department = :dept";
            $params['dept'] = $department;
        }
        if (!empty($threat) && $threat !== 'TODAS') {
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

        $query .= " ORDER BY e.event_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SID Noticias');

        // Función de sanitización anti inyección de fórmulas
        $sanitize = function(mixed $val): string {
            $str = (string)($val ?? '');
            if (in_array(substr($str, 0, 1), ['=', '+', '-', '@'], true)) {
                return "'" . $str;
            }
            return $str;
        };

        $headers = [
            'N.°', 'FECHA/HORA PUBLICACIÓN', 'DEPARTAMENTO', 'AMENAZA', 
            'TITULAR (QUÉ / QUIÉN / DÓNDE)', 'SÍNTESIS CONTINUA DE INTELIGENCIA', 
            'MEDIOS Y FUENTES', 'ENLACES ORIGINALES', 'FECHA DE REGISTRO', 'USUARIO RESPONSABLE'
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3022');
            $sheet->getColumnDimension($col)->setWidth(20);
            $col++;
        }
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(60);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(35);

        $rowNum = 2;
        $counter = 1;
        foreach ($events as $evt) {
            // Cargar fuentes
            $srcStmt = $db->prepare("SELECT media_name, source_url FROM news_sources WHERE news_event_id = :id");
            $srcStmt->execute(['id' => $evt['id']]);
            $sources = $srcStmt->fetchAll();

            $mediaNames = array_column($sources, 'media_name');
            $sourceUrls = array_column($sources, 'source_url');

            $sheet->setCellValue('A' . $rowNum, $counter);
            $sheet->setCellValue('B' . $rowNum, $evt['event_date']);
            $sheet->setCellValue('C' . $rowNum, $sanitize($evt['department']));
            $sheet->setCellValue('D' . $rowNum, $sanitize($evt['threat_type']));
            $sheet->setCellValue('E' . $rowNum, $sanitize($evt['headline']));
            $sheet->setCellValue('F' . $rowNum, $sanitize($evt['synthesis']));
            $sheet->setCellValue('G' . $rowNum, $sanitize(implode(" | ", $mediaNames)));
            $sheet->setCellValue('H' . $rowNum, $sanitize(implode(" \n ", $sourceUrls)));
            $sheet->setCellValue('I' . $rowNum, $evt['created_at']);
            $sheet->setCellValue('J' . $rowNum, $sanitize($evt['user_name'] ?? 'Sistema'));

            $sheet->getStyle('E' . $rowNum . ':H' . $rowNum)->getAlignment()->setWrapText(true);
            $rowNum++;
            $counter++;
        }

        // Congelar primera fila
        $sheet->freezePane('A2');

        $outPath = dirname(__DIR__, 2) . '/storage/templates/Reporte_SID_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($outPath);
        return $outPath;
    }

    /**
     * Generación de Reporte PDF Institucional para SID con Dompdf
     */
    public static function exportPdf(string $department, string $threat, ?string $startDate, ?string $endDate): string
    {
        $db = Database::getConnection();
        $query = "
            SELECT e.*, u.name AS user_name
            FROM news_events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.is_hidden = 0
        ";
        $params = [];

        if (!empty($department) && $department !== 'TODOS') {
            $query .= " AND e.department = :dept";
            $params['dept'] = $department;
        }
        if (!empty($threat) && $threat !== 'TODAS') {
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

        $query .= " ORDER BY e.event_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        foreach ($events as &$evt) {
            $srcStmt = $db->prepare("SELECT media_name, source_url, published_at FROM news_sources WHERE news_event_id = :id");
            $srcStmt->execute(['id' => $evt['id']]);
            $evt['sources'] = $srcStmt->fetchAll();
        }

        // Renderizar plantilla HTML para PDF
        $shieldPath = dirname(__DIR__, 2) . '/public/img/ESCUDO COEDE.jpeg';
        if (!file_exists($shieldPath)) {
            $shieldPath = dirname(__DIR__, 2) . '/public/assets/img/ESCUDO COEDE.jpeg';
        }
        $shieldData = file_exists($shieldPath) ? file_get_contents($shieldPath) : '';
        $base64Shield = 'data:image/jpeg;base64,' . base64_encode($shieldData);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
            @page { margin: 20mm 15mm 20mm 15mm; }
            body { font-family: "Helvetica", "Arial", sans-serif; color: #1a1a1a; font-size: 10pt; line-height: 1.4; }
            .cover { text-align: center; page-break-after: always; padding-top: 80px; }
            .cover-title { font-size: 20pt; font-weight: bold; color: #1e3022; margin-top: 25px; letter-spacing: 2px; }
            .cover-sub { font-size: 13pt; color: #a27a29; font-weight: bold; margin-top: 8px; text-transform: uppercase; }
            .cover-meta { margin-top: 60px; font-size: 10pt; color: #444; line-height: 1.8; }
            .header-bar { border-bottom: 2px solid #a27a29; padding-bottom: 5px; margin-bottom: 15px; font-size: 8pt; color: #1e3022; font-weight: bold; text-transform: uppercase; }
            .news-card { border: 1px solid #d0d7d2; border-left: 4px solid #1e3022; padding: 12px; margin-bottom: 18px; background: #fafbfa; }
            .news-num { font-size: 11pt; font-weight: bold; color: #a27a29; margin-bottom: 4px; }
            .news-hl { font-size: 10pt; font-weight: bold; color: #1e3022; margin-bottom: 8px; }
            .news-synth { font-size: 9.5pt; text-align: justify; margin-bottom: 10px; }
            .news-sources { font-size: 8pt; color: #555; border-top: 1px dashed #ccc; padding-top: 6px; }
            .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8pt; color: #777; text-align: right; border-top: 1px solid #ddd; padding-top: 4px; }
        </style></head><body>';

        // PORTADA INSTITUCIONAL
        $html .= '<div class="cover">';
        $html .= '<div style="display: inline-block; padding: 6px; background: #ffffff; border: 1px solid #d4a017; border-radius: 12px; margin-bottom: 15px;">';
        $html .= '<img src="' . $base64Shield . '" width="130" style="display: block; object-fit: contain;">';
        $html .= '</div>';
        $html .= '<div class="cover-sub">EJÉRCITO DEL PERÚ</div>';
        $html .= '<div class="cover-title">COMANDO DE EDUCACIÓN Y DOCTRINA DEL EJÉRCITO</div>';
        $html .= '<div style="font-size: 16pt; font-weight: bold; color: #222; margin-top: 30px; letter-spacing: 1px;">SÍNTESIS DE INFORMACIÓN DIARIA (SID)</div>';
        $html .= '<div class="cover-meta">';
        $html .= '<strong>Departamento:</strong> ' . htmlspecialchars($department ?: 'TODOS') . '<br>';
        $html .= '<strong>Amenaza Monitoreada:</strong> ' . htmlspecialchars($threat ?: 'TODAS') . '<br>';
        $html .= '<strong>Rango de Consulta:</strong> ' . ($startDate ?: 'Últimas 24h') . ' al ' . ($endDate ?: date('Y-m-d')) . '<br>';
        $html .= '<strong>Fecha de Emisión:</strong> ' . date('d/m/Y H:i:s') . ' (Hora de Lima)<br>';
        $html .= '<strong>Clasificación:</strong> DOCUMENTO DE GESTIÓN INTERNA (BETA DE DEMOSTRACIÓN)';
        $html .= '</div></div>';

        // NOTICIAS CONSECUTIVAS DESDE LA PÁGINA 2
        $html .= '<div class="header-bar">COEDE | COMANDO DE EDUCACIÓN Y DOCTRINA DEL EJÉRCITO — SÍNTESIS DE INFORMACIÓN DIARIA</div>';
        
        if (empty($events)) {
            $html .= '<div style="text-align:center; padding: 40px; color: #666;">No se registraron acontecimientos públicos en el rango seleccionado.</div>';
        } else {
            $counter = 1;
            foreach ($events as $evt) {
                $html .= '<div class="news-card">';
                $html .= '<div class="news-num">NOTICIA N.º ' . str_pad((string)$counter, 2, '0', STR_PAD_LEFT) . ' — ' . htmlspecialchars($evt['department']) . ' [' . htmlspecialchars($evt['threat_type']) . ']</div>';
                $html .= '<div class="news-hl">' . htmlspecialchars($evt['headline']) . '</div>';
                $html .= '<div class="news-synth">' . nl2br(htmlspecialchars($evt['synthesis'])) . '</div>';
                $html .= '<div class="news-sources"><strong>Fuentes verificadas:</strong> ';
                $srcList = [];
                foreach ($evt['sources'] as $s) {
                    $srcList[] = htmlspecialchars($s['media_name']) . ' (' . htmlspecialchars($s['source_url']) . ')';
                }
                $html .= implode(' | ', $srcList);
                $html .= '<br><strong>Fecha de Hecho:</strong> ' . htmlspecialchars($evt['event_date']) . ' | <strong>Registro Sistema:</strong> ' . htmlspecialchars($evt['created_at']);
                $html .= '</div></div>';
                $counter++;
            }
        }

        $html .= '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Numeración de páginas "Página X de Y"
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(520, 810, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 8, [0.4, 0.4, 0.4]);

        $pdfPath = dirname(__DIR__, 2) . '/storage/templates/Reporte_SID_' . date('Ymd_His') . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        return $pdfPath;
    }
}

