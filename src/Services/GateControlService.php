<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class GateControlService
{
    /**
     * Consulta integral por DNI o Placa
     */
    public static function lookup(string $dni = '', string $plate = ''): array
    {
        $db = Database::getConnection();
        $result = [
            'personnel' => null,
            'visitor' => null,
            'vehicle' => null,
            'restricted' => null,
            'open_movement' => null,
            'photo_pending' => false,
            'has_photo' => false,
            'photo_url' => null,
            'status_recommendation' => 'PENDIENTE', // AUTORIZADO, PENDIENTE, DENEGADO
            'status_label' => 'PENDIENTE DE AUTORIZACIÓN',
            'status_class' => 'gate-status-pending',
            'messages' => []
        ];

        $dni = trim($dni);
        $plate = strtoupper(trim(str_replace([' ', '-'], '', $plate)));

        // 1. Verificar lista de restricción simulada
        if (!empty($dni)) {
            $stmtRestr = $db->prepare("
                SELECT * FROM restricted_access_simulated 
                WHERE target_type = 'PERSONA' AND identifier = :dni AND is_active = 1
                LIMIT 1
            ");
            $stmtRestr->execute(['dni' => $dni]);
            $restr = $stmtRestr->fetch();
            if ($restr) {
                $result['restricted'] = $restr;
                $result['status_recommendation'] = 'DENEGADO';
                $result['status_label'] = 'ACCESO DENEGADO';
                $result['status_class'] = 'gate-status-denied';
                $result['messages'][] = "ALERTA DE SEGURIDAD SIMULADA: {$restr['reason_simulated']} (Autoridad: {$restr['authority']})";
            }
        }

        if (!empty($plate) && !$result['restricted']) {
            $stmtRestrV = $db->prepare("
                SELECT * FROM restricted_access_simulated 
                WHERE target_type = 'VEHICULO' AND identifier = :plate AND is_active = 1
                LIMIT 1
            ");
            $stmtRestrV->execute(['plate' => $plate]);
            $restrV = $stmtRestrV->fetch();
            if ($restrV) {
                $result['restricted'] = $restrV;
                $result['status_recommendation'] = 'DENEGADO';
                $result['status_label'] = 'VEHÍCULO NO AUTORIZADO';
                $result['status_class'] = 'gate-status-denied';
                $result['messages'][] = "ALERTA VEHICULAR SIMULADA: {$restrV['reason_simulated']} (Autoridad: {$restrV['authority']})";
            }
        }

        // 2. Buscar personal registrado en MySQL
        if (!empty($dni)) {
            $stmtP = $db->prepare("
                SELECT p.*, u.name AS unit_name, u.code AS unit_code,
                       a.auth_start, a.auth_end, a.status AS auth_status
                FROM personnel_demo p
                JOIN units u ON p.unit_id = u.id
                LEFT JOIN personnel_authorizations_demo a ON p.id = a.personnel_id
                WHERE p.dni = :dni
                ORDER BY a.id DESC LIMIT 1
            ");
            $stmtP->execute(['dni' => $dni]);
            $person = $stmtP->fetch();

            if ($person) {
                $result['personnel'] = $person;
                if (!empty($person['photo_path'])) {
                    $result['has_photo'] = true;
                    $result['photo_url'] = url('/media/photo/' . $person['dni']);
                } else {
                    $result['photo_pending'] = true;
                    $result['messages'][] = 'FOTOGRAFÍA PENDIENTE — verificar identidad físicamente y solicitar fotografía.';
                }

                // Evaluar vigencia
                $authEnd = $person['auth_end'] ?? null;
                if ($authEnd) {
                    $daysRemaining = (int)ceil((strtotime($authEnd) - time()) / 86400);
                    if ($daysRemaining < 0) {
                        $result['messages'][] = "Autorización vencida el {$authEnd} (Vencida no equivale a prohibición expresa, requiere autorización de jefatura).";
                        if ($result['status_recommendation'] !== 'DENEGADO') {
                            $result['status_recommendation'] = 'PENDIENTE';
                            $result['status_label'] = 'PENDIENTE DE AUTORIZACIÓN';
                            $result['status_class'] = 'gate-status-pending';
                        }
                    } elseif ($daysRemaining <= 7) {
                        $result['messages'][] = "ALERTA: Autorización por vencer en {$daysRemaining} días (Vence {$authEnd}).";
                        if ($result['status_recommendation'] !== 'DENEGADO') {
                            $result['status_recommendation'] = 'AUTORIZADO';
                            $result['status_label'] = 'ACCESO AUTORIZADO';
                            $result['status_class'] = 'gate-status-authorized';
                        }
                    } else {
                        if ($result['status_recommendation'] !== 'DENEGADO') {
                            $result['status_recommendation'] = 'AUTORIZADO';
                            $result['status_label'] = 'ACCESO AUTORIZADO';
                            $result['status_class'] = 'gate-status-authorized';
                        }
                    }
                }
            } else {
                // No es personal interno: consultar RENIEC para visitantes
                $visResponse = VisitorAdapterMock::lookupDni($dni);
                if ($visResponse['success']) {
                    $result['visitor'] = $visResponse['data'];
                    $result['messages'][] = "Visitante identificado vía Consulta RENIEC. Proceda con destino y motivo.";
                    if ($result['status_recommendation'] !== 'DENEGADO') {
                        $result['status_recommendation'] = 'PENDIENTE';
                        $result['status_label'] = 'PENDIENTE DE AUTORIZACIÓN';
                        $result['status_class'] = 'gate-status-pending';
                    }
                } else {
                    $result['visitor'] = null;
                    $result['visitor_not_found'] = true;
                    $result['messages'][] = "DNI no encontrado en RENIEC. Ingrese los datos manualmente.";
                    if ($result['status_recommendation'] !== 'DENEGADO') {
                        $result['status_recommendation'] = 'PENDIENTE';
                        $result['status_label'] = 'DNI NO ENCONTRADO EN RENIEC';
                        $result['status_class'] = 'gate-status-pending';
                    }
                }
            }

            // Comprobar si existe fotografía del efectivo en public/img/efectivo/{dni}.jpg
            $efectivoPublicPath = dirname(__DIR__, 2) . '/public/img/efectivo/' . $dni . '.jpg';
            if (file_exists($efectivoPublicPath)) {
                $result['has_photo'] = true;
                $result['photo_pending'] = false;
                $result['photo_url'] = url('/img/efectivo/' . $dni . '.jpg');
            }

            // Comprobar si existe movimiento de entrada abierto
            $stmtOpen = $db->prepare("
                SELECT id, recorded_at, target_type, plate 
                FROM movements_demo 
                WHERE dni = :dni AND movement_type = 'ENTRADA' AND is_closed = 0
                ORDER BY id DESC LIMIT 1
            ");
            $stmtOpen->execute(['dni' => $dni]);
            $openMov = $stmtOpen->fetch();
            if ($openMov) {
                $result['open_movement'] = $openMov;
                $result['messages'][] = "SALIDA PENDIENTE — El sistema registra un ingreso previo el {$openMov['recorded_at']} sin salida constatada. Verifique por qué puerta salió antes de registrar nuevo ingreso.";
            }
        }

        // 3. Buscar vehículo si se especificó placa
        if (!empty($plate)) {
            $stmtV = $db->prepare("
                SELECT v.*, u.name AS unit_name,
                       a.auth_start, a.auth_end, a.status AS auth_status
                FROM vehicles_demo v
                JOIN units u ON v.unit_id = u.id
                LEFT JOIN vehicle_authorizations_demo a ON v.id = a.vehicle_id
                WHERE REPLACE(REPLACE(v.plate, '-', ''), ' ', '') = :plate
                ORDER BY a.id DESC LIMIT 1
            ");
            $stmtV->execute(['plate' => $plate]);
            $vehicle = $stmtV->fetch();

            if ($vehicle) {
                $result['vehicle'] = $vehicle;
                $daysRemainingV = (int)ceil((strtotime($vehicle['auth_end'] ?? '1970-01-01') - time()) / 86400);
                if ($daysRemainingV < 0) {
                    $result['messages'][] = "Autorización vehicular de placa {$vehicle['plate']} vencida.";
                } elseif ($daysRemainingV <= 7) {
                    $result['messages'][] = "Autorización vehicular por vencer en {$daysRemainingV} días.";
                }
            }

            // Comprobar si existe fotografía del efectivo en public/img/efectivo/{dni}.jpg
            $efectivoPublicPath = dirname(__DIR__, 2) . '/public/img/efectivo/' . $dni . '.jpg';
            if (file_exists($efectivoPublicPath)) {
                $result['has_photo'] = true;
                $result['photo_pending'] = false;
                $result['photo_url'] = url('/img/efectivo/' . $dni . '.jpg');
            } else {
                $result['messages'][] = "Vehículo placa {$plate} no registrado en el padrón oficial/particular. Se tratará como vehículo visitante con autorización del Área de Seguridad.";
            }
        }

        return $result;
    }

    /**
     * Registra un movimiento peatonal o vehicular en Puerta Principal
     */
    public static function recordMovement(array $data): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $userId = Auth::id();
            $movementType = strtoupper($data['movement_type'] ?? 'ENTRADA');
            $targetType   = strtoupper($data['target_type'] ?? 'PEATONAL');
            $dni          = trim($data['dni'] ?? '');
            $personName   = trim($data['person_name'] ?? '');
            $rankOrType   = trim($data['rank_or_type'] ?? '');
            $unitName     = trim($data['unit_name'] ?? '');

            // Autocompletar datos desde MySQL si no vienen en la petición
            if (empty($personName) && !empty($dni)) {
                $stmtP = $db->prepare("
                    SELECT p.surnames, p.names, p.category, p.rank_grade, u.name AS unit_name
                    FROM personnel_demo p
                    LEFT JOIN units u ON p.unit_id = u.id
                    WHERE p.dni = :dni LIMIT 1
                ");
                $stmtP->execute(['dni' => $dni]);
                $p = $stmtP->fetch();
                if ($p) {
                    $personName = $p['surnames'] . ', ' . $p['names'];
                    $rankOrType = $p['category'] === 'MILITAR' ? ($p['rank_grade'] ?: 'Militar') : 'Personal Civil';
                    $unitName   = $p['unit_name'] ?: 'COEDE';
                    $isVisitor  = 0;
                } else {
                    $vis = VisitorAdapterMock::lookupDni($dni);
                    if ($vis['success']) {
                        $personName = $vis['data']['full_name'];
                        $rankOrType = 'VISITANTE EXTERNO';
                        $unitName   = 'Visita Externa';
                        $isVisitor  = 1;
                    } else {
                        $personName = !empty($data['person_name']) ? trim($data['person_name']) : ('Visitante DNI ' . $dni);
                        $rankOrType = 'VISITANTE EXTERNO';
                        $unitName   = 'Visita Externa';
                        $isVisitor  = 1;
                    }
                }
            }

            // Comprobar si existe fotografía del efectivo en public/img/efectivo/{dni}.jpg
            $efectivoPublicPath = dirname(__DIR__, 2) . '/public/img/efectivo/' . $dni . '.jpg';
            if (file_exists($efectivoPublicPath)) {
                $result['has_photo'] = true;
                $result['photo_pending'] = false;
                $result['photo_url'] = url('/img/efectivo/' . $dni . '.jpg');
            }

            $plate        = !empty($data['plate']) ? strtoupper(trim(str_replace([' ', '-'], '', $data['plate']))) : null;
            $vehDetails   = trim($data['vehicle_details'] ?? '');
            $destination  = trim($data['destination'] ?? '');
            $isVisitor    = !empty($data['is_visitor']) ? 1 : 0;
            $authority    = trim($data['authority_authorizer'] ?? '');
            $medium       = trim($data['auth_medium'] ?? '');
            $decision     = strtoupper($data['operator_decision'] ?? 'AUTORIZADO');
            $observation  = trim($data['observation'] ?? '');

            // Prevenir duplicados inmediatos por doble clic (últimos 10 segundos)
            $checkDup = $db->prepare("
                SELECT id FROM movements_demo 
                WHERE dni = :dni AND movement_type = :mtype AND recorded_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
                LIMIT 1
            ");
            $checkDup->execute(['dni' => $dni, 'mtype' => $movementType]);
            if ($checkDup->fetch()) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Movimiento ya registrado hace unos segundos (prevención de doble clic).'];
            }

            // Si es SALIDA, cerrar el movimiento de ENTRADA abierto previo
            $closedOpenMovId = null;
            if ($movementType === 'SALIDA') {
                $findOpen = $db->prepare("
                    SELECT id FROM movements_demo 
                    WHERE dni = :dni AND movement_type = 'ENTRADA' AND is_closed = 0
                    ORDER BY id DESC LIMIT 1
                ");
                $findOpen->execute(['dni' => $dni]);
                $openId = $findOpen->fetchColumn();
                if ($openId) {
                    $closedOpenMovId = (int)$openId;
                }
            }

            // Comprobar si existe fotografía del efectivo en public/img/efectivo/{dni}.jpg
            $efectivoPublicPath = dirname(__DIR__, 2) . '/public/img/efectivo/' . $dni . '.jpg';
            if (file_exists($efectivoPublicPath)) {
                $result['has_photo'] = true;
                $result['photo_pending'] = false;
                $result['photo_url'] = url('/img/efectivo/' . $dni . '.jpg');
            }

            // Si se suministró declaración de salida pendiente previa
            if (!empty($data['has_exit_declaration']) && !empty($data['open_movement_id'])) {
                $declStmt = $db->prepare("
                    INSERT INTO exit_declarations_demo (open_movement_id, declared_gate, approx_time, observation, status, declared_by, declared_at)
                    VALUES (:open_id, :gate, :app_time, :obs, 'PENDIENTE_REVISION', :user_id, NOW())
                ");
                $declStmt->execute([
                    'open_id'  => $data['open_movement_id'],
                    'gate'     => $data['declared_gate'] ?? 'Puerta no constatada',
                    'app_time' => $data['declared_time'] ?? 'No especificada',
                    'obs'      => $data['declared_observation'] ?? 'Declaración registrada por guardia al intentar nuevo ingreso',
                    'user_id'  => $userId
                ]);

                // Cerrar administrativamente el movimiento anterior indicando que fue por declaración
                $closeDecl = $db->prepare("UPDATE movements_demo SET is_closed = 1 WHERE id = :id");
                $closeDecl->execute(['id' => $data['open_movement_id']]);
            }

            // Insertar el nuevo movimiento
            $ins = $db->prepare("
                INSERT INTO movements_demo (
                    movement_type, target_type, is_visitor, dni, person_name, rank_or_type, unit_name,
                    plate, vehicle_details, destination, authority_authorizer, auth_medium,
                    operator_decision, observation, recorded_by, recorded_at, is_closed
                ) VALUES (
                    :movement_type, :target_type, :is_visitor, :dni, :person_name, :rank_or_type, :unit_name,
                    :plate, :vehicle_details, :destination, :authority_authorizer, :auth_medium,
                    :operator_decision, :observation, :recorded_by, NOW(), :is_closed
                )
            ");

            $isClosed = ($movementType === 'SALIDA') ? 1 : 0;

            $ins->execute([
                'movement_type'        => $movementType,
                'target_type'          => $targetType,
                'is_visitor'           => $isVisitor,
                'dni'                  => $dni,
                'person_name'          => $personName,
                'rank_or_type'         => $rankOrType,
                'unit_name'            => $unitName,
                'plate'                => $plate,
                'vehicle_details'      => $vehDetails,
                'destination'          => $destination,
                'authority_authorizer' => $authority,
                'auth_medium'          => $medium,
                'operator_decision'    => $decision,
                'observation'          => $observation,
                'recorded_by'          => $userId,
                'is_closed'            => $isClosed
            ]);

            $newMovId = (int)$db->lastInsertId();

            if ($closedOpenMovId) {
                $closeStmt = $db->prepare("UPDATE movements_demo SET is_closed = 1, exit_movement_id = :exit_id WHERE id = :id");
                $closeStmt->execute(['exit_id' => $newMovId, 'id' => $closedOpenMovId]);
            }

            $db->commit();
            AuditService::log('RECORD_MOVEMENT', 'movements_demo', $newMovId, null, [
                'type' => $movementType,
                'dni' => $dni,
                'name' => $personName,
                'decision' => $decision
            ]);

            return [
                'success' => true,
                'movement_id' => $newMovId,
                'message' => "Movimiento de {$movementType} registrado exitosamente."
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Error al registrar movimiento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Registra solicitud de fotografía cuando el personal no tiene foto en el sistema
     */
    public static function requestPhoto(int $personnelId, string $notes): array
    {
        $db = Database::getConnection();
        $userId = Auth::id();

        try {
            $stmt = $db->prepare("
                INSERT INTO photo_requests_demo (personnel_id, requested_by, status, requested_at, notes)
                VALUES (:pid, :uid, 'PENDIENTE', NOW(), :notes)
            ");
            $stmt->execute(['pid' => $personnelId, 'uid' => $userId, 'notes' => $notes]);
            
            // Crear notificación para administrador
            $notif = $db->prepare("
                INSERT INTO notifications (type, title, message, link, target_role, is_read, created_at)
                VALUES ('FOTO_PENDIENTE', 'Nueva solicitud de fotografía', 'Operador de puerta solicitó fotografía para personal ID #{$personnelId}.', '/personnel/photo-requests', 'ADMINISTRADOR', 0, NOW())
            ");
            $notif->execute();

            return ['success' => true, 'message' => 'Solicitud de fotografía registrada correctamente.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

