-- Datos sintéticos de demostración institucional para COEDE
-- Contraseñas hasheadas con BCRYPT:
-- 'admin' -> 'AdminCoede2026!'
-- 'servicio' -> 'ServicioCoede2026!'

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles
INSERT INTO roles (id, name, description) VALUES
(1, 'ADMINISTRADOR', 'Control total: Personal, Vehículos, Puerta, SID/Noticias, Usuarios, Reportes, Configuración y Auditoría'),
(2, 'SERVICIO', 'Operador de guardia: Control de Puerta, Registro de Incidencias, Solicitud de Foto y Consulta SID')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- 2. Usuarios
INSERT INTO users (id, username, password_hash, name, email, role_id, is_active) VALUES
(1, 'admin', '$2y$12$N9xXhRzYpI0kM7vT3q8lOe7y6w5v4u3t2s1r0q9p8o7n6m5l4k3j2', 'CRICRI_2026', 'cricri_2026@coede.mil.pe', 1, 1),
(2, 'servicio', '$2y$12$J8wWvQyXoH9jL6uS2p7kNd6x5v4u3t2s1r0q9p8o7n6m5l4k3j2i1', 'SO2 EP Quispe Huamán, Marco Antonio', 'marco.quispe@coede.mil.pe', 2, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. Dependencias / Unidades
INSERT INTO units (id, code, name, description, is_active) VALUES
(1, 'EM-COEDE', 'Estado Mayor del COEDE', 'Comando central y planeamiento de educación y doctrina', 1),
(2, 'ESGE', 'Escuela Superior de Guerra del Ejército', 'Escuela de postgrado y formación de oficiales de Estado Mayor', 1),
(3, 'EMCH', 'Escuela Militar de Chorrillos', 'Alma máter de los oficiales del Ejército del Perú', 1),
(4, 'ETE', 'Escuela Técnica del Ejército', 'Formación de suboficiales y especialistas técnicos', 1),
(5, 'ICTE', 'Instituto Científico y Tecnológico del Ejército', 'Investigación aplicada, doctrina tecnológica e innovación', 1),
(6, 'CEDE', 'Centro de Educación a Distancia del Ejército', 'Capacitación virtual y doctrina a distancia', 1),
(7, 'BCOM-112', 'Batallón de Comunicaciones N.° 112', 'Unidad táctica de apoyo y comunicaciones', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 4. Personal Militar y Civil Sintético
INSERT INTO personnel_demo (id, category, dni, cip_civ, rank_grade, surnames, names, unit_id, is_active, photo_path) VALUES
(1, 'MILITAR', '45892134', 'CIP-104921', 'Coronel', 'CRICRI_2026', '', 1, 1, '45892134.jpg'),
(2, 'MILITAR', '71029384', 'CIP-219482', 'Capitán', 'Rojas Mendoza', 'Edgar Daniel', 2, 1, NULL),
(3, 'MILITAR', '43110294', 'CIP-184920', 'Mayor', 'Zavala Cornejo', 'Juan Fernando', 3, 1, '43110294.jpg'),
(4, 'MILITAR', '72849102', 'CIP-301928', 'Teniente', 'Gutiérrez Palacios', 'Luis Enrique', 4, 1, NULL),
(5, 'MILITAR', '41982736', 'CIP-119283', 'Suboficial 1ra', 'Quispe Huamán', 'Marco Antonio', 7, 1, NULL),
(6, 'CIVIL', '08928374', 'CIV-0482', NULL, 'Paredes Benavides', 'María Elena', 1, 1, NULL),
(7, 'CIVIL', '44819203', 'CIV-0912', NULL, 'Campos Alarcón', 'Roberto Carlos', 5, 1, NULL),
(8, 'CIVIL', '70918234', 'CIV-1104', NULL, 'Flores Tello', 'Ana Lucía', 6, 1, NULL),
(9, 'MILITAR', '40192834', 'CIP-098231', 'Teniente Coronel', 'Vargas Llosa', 'Héctor Manuel', 1, 1, NULL),
(10, 'CIVIL', '42019283', 'CIV-0551', NULL, 'Navarro Ríos', 'César Augusto', 2, 1, NULL)
ON DUPLICATE KEY UPDATE surnames=VALUES(surnames);

-- 5. Autorizaciones de Personal (Fechas sintéticas en torno a 2026-11)
INSERT INTO personnel_authorizations_demo (id, personnel_id, auth_start, auth_end, status, notes) VALUES
(1, 1, '2026-01-01', '2027-01-01', 'ACTIVO', 'Autorización anual ordinaria Comando'),
(2, 2, '2026-06-01', '2026-11-20', 'POR_VENCER', 'Alerta: vence en 5 días (cuenta regresiva activa)'),
(3, 3, '2026-01-15', '2027-01-15', 'ACTIVO', 'Autorización anual ordinaria'),
(4, 4, '2026-05-01', '2026-11-01', 'VENCIDO', 'Vencida - pendiente de renovación regular'),
(5, 5, '2026-01-01', '2026-12-31', 'ACTIVO', 'Servicio de Guardia'),
(6, 6, '2026-01-01', '2027-01-01', 'ACTIVO', 'Personal administrativo nombrado'),
(7, 7, '2026-08-01', '2026-11-18', 'POR_VENCER', 'Contrato CAS temporal por vencer en 3 días'),
(8, 8, '2026-01-01', '2026-10-31', 'VENCIDO', 'Autorización trimestral vencida'),
(9, 9, '2026-02-01', '2027-02-01', 'ACTIVO', 'Autorización anual'),
(10, 10, '2026-03-01', '2027-03-01', 'ACTIVO', 'Docente civil contratado')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 6. Solicitud de Foto previa (demostración)
INSERT INTO photo_requests_demo (id, personnel_id, requested_by, status, requested_at, notes) VALUES
(1, 2, 2, 'PENDIENTE', '2026-11-14 08:30:00', 'Verificado en guardia peatonal: se exhortó presentar fotografía tamaño carné a personal.'),
(2, 4, 2, 'PENDIENTE', '2026-11-15 07:15:00', 'Oficial sin fotografía digitalizada en ficha.')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 7. Vehículos Demostración (Oficiales y Particulares)
INSERT INTO vehicles_demo (id, category, plate, owner_dni, owner_cip_civ, owner_name, make, model, unit_id, is_active) VALUES
(1, 'OFICIAL', 'EP-12401', NULL, NULL, 'COEDE - Comando Central', 'Toyota', 'Hilux 4x4', 1, 1),
(2, 'OFICIAL', 'EP-08912', NULL, NULL, 'Escuela Superior de Guerra', 'Nissan', 'Frontier', 2, 1),
(3, 'OFICIAL', 'EP-15204', NULL, NULL, 'Escuela Militar de Chorrillos', 'Mercedes-Benz', 'Sprinter', 3, 1),
(4, 'PARTICULAR', 'B6A-912', '45892134', 'CIP-104921', 'CRICRI_2026', 'Hyundai', 'Tucson', 1, 1),
(5, 'PARTICULAR', 'F8T-443', '43110294', 'CIP-184920', 'Zavala Cornejo, Juan Fernando', 'Kia', 'Sportage', 3, 1),
(6, 'PARTICULAR', 'C2K-115', '08928374', 'CIV-0482', 'Paredes Benavides, María Elena', 'Toyota', 'Yaris', 1, 1),
(7, 'PARTICULAR', 'A9X-780', '44819203', 'CIV-0912', 'Campos Alarcón, Roberto Carlos', 'Chevrolet', 'Sail', 5, 1)
ON DUPLICATE KEY UPDATE make=VALUES(make);

-- 8. Autorizaciones de Vehículos
INSERT INTO vehicle_authorizations_demo (id, vehicle_id, auth_start, auth_end, status, notes) VALUES
(1, 1, '2026-01-01', '2027-01-01', 'ACTIVO', 'Vehículo de comandancia'),
(2, 2, '2026-01-01', '2027-01-01', 'ACTIVO', 'Vehículo asignado a ESGE'),
(3, 3, '2026-01-01', '2026-11-21', 'POR_VENCER', 'Por vencer en 6 días'),
(4, 4, '2026-01-01', '2027-01-01', 'ACTIVO', 'Vehículo particular CRICRI_2026'),
(5, 5, '2026-06-01', '2026-11-19', 'POR_VENCER', 'Por vencer en 4 días'),
(6, 6, '2026-01-01', '2026-10-30', 'VENCIDO', 'Vigencia de estacionamiento vencida'),
(7, 7, '2026-05-01', '2027-05-01', 'ACTIVO', 'Autorizado estacionamiento Este')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 9. Lista de Acceso Restringido Simulada (SOLO EJEMPLOS SINTÉTICOS DE PRUEBA)
INSERT INTO restricted_access_simulated (id, target_type, identifier, full_name_or_detail, reason_simulated, authority, active_since, is_active) VALUES
(1, 'PERSONA', '00112233', 'Gómez Salcedo, Walter (Sintético)', 'PROTOTIPO DEMO: Suspensión preventiva temporal de acceso por proceso administrativo sumario N.° 044-2026.', 'Inspectoría COEDE', '2026-10-01', 1),
(2, 'VEHICULO', 'X1X-999', 'Camioneta Negra Polarizada (Sintético)', 'PROTOTIPO DEMO: Placa involucrada en incidente de seguridad perimétrico en otra guarnición.', 'G2 Inteligencia', '2026-09-15', 1)
ON DUPLICATE KEY UPDATE full_name_or_detail=VALUES(full_name_or_detail);

-- 10. Movimientos Históricos de Prueba en Puerta Principal
INSERT INTO movements_demo (id, movement_type, target_type, is_visitor, dni, person_name, rank_or_type, unit_name, plate, vehicle_details, destination, authority_authorizer, auth_medium, operator_decision, observation, recorded_by, recorded_at, is_closed) VALUES
(1, 'ENTRADA', 'PEATONAL', 0, '45892134', 'CRICRI_2026', 'Coronel', 'Estado Mayor del COEDE', NULL, NULL, 'Comandancia', NULL, NULL, 'AUTORIZADO', 'Ingreso matutino ordinario', 2, '2026-11-15 07:45:00', 1),
(2, 'SALIDA', 'PEATONAL', 0, '45892134', 'CRICRI_2026', 'Coronel', 'Estado Mayor del COEDE', NULL, NULL, 'Exterior', NULL, NULL, 'AUTORIZADO', 'Salida a reunión Cuartel General', 2, '2026-11-15 12:30:00', 1),
(3, 'ENTRADA', 'VEHICULAR', 0, '43110294', 'Zavala Cornejo, Juan Fernando', 'Mayor', 'Escuela Militar de Chorrillos', 'F8T-443', 'Kia Sportage', 'EMCH Pabellón B', NULL, NULL, 'AUTORIZADO', 'Ingreso vehicular autorizado', 2, '2026-11-15 08:10:00', 0),
(4, 'ENTRADA', 'PEATONAL', 1, '10293847', 'Salazar Pinedo, Jorge Luis (Visitante)', 'Civil Visitante', 'Visita Externa', NULL, NULL, 'Jefatura de Doctrina', 'Tte. Crl. Vargas', 'Teléfono IP 4402', 'AUTORIZADO', 'Visita por coordinación de imprenta de textos doctrinales', 2, '2026-11-15 09:20:00', 1),
(5, 'SALIDA', 'PEATONAL', 1, '10293847', 'Salazar Pinedo, Jorge Luis (Visitante)', 'Civil Visitante', 'Visita Externa', NULL, NULL, 'Exterior', NULL, NULL, 'AUTORIZADO', 'Salida de visita', 2, '2026-11-15 11:40:00', 1),
(6, 'ENTRADA', 'PEATONAL', 0, '71029384', 'Rojas Mendoza, Edgar Daniel', 'Capitán', 'Escuela Superior de Guerra', NULL, NULL, 'Aulas ESGE', NULL, NULL, 'AUTORIZADO', 'Ingreso registrado en guardia (quedó abierto como caso de prueba para salida pendiente)', 2, '2026-11-15 08:00:00', 0)
ON DUPLICATE KEY UPDATE person_name=VALUES(person_name);

-- 11. Incidencia de Ejemplo
INSERT INTO incidents_demo (id, incident_code, event_type, description, ref_dni, ref_plate, action_taken, status, resolution_measure, resolution_explanation, closed_by, closed_at, created_by, created_at) VALUES
(1, 'INC-2026-0001', 'Documento de Autorización Vencido', 'Personal civil se presentó en la puerta principal con autorización de acceso vencida desde el 31 de octubre.', '70918234', NULL, 'Se verificó con la Jefatura de la CEDE vía anexo telefónico. Se autorizó pase de visita por única vez y se exhortó a regularizar su prórroga en mesa de partes.', 'CERRADA', 'Emisión de memorándum de regularización administrativa a la CEDE.', 'El departamento de Recursos Humanos de la CEDE regularizó la adenda contractual y emitió la vigencia correspondiente.', 1, '2026-11-15 16:00:00', 2, '2026-11-15 09:15:00')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 12. Configuración Fija
INSERT INTO settings (id, setting_key, setting_value, description) VALUES
(1, 'institution_name', 'Comando de Educación y Doctrina del Ejército', 'Nombre oficial del comando rector'),
(2, 'short_name', 'COEDE', 'Acrónimo institucional'),
(3, 'session_timeout_minutes', '15', 'Tiempo de inactividad de sesión'),
(4, 'session_warning_minutes', '14', 'Tiempo previo de advertencia de cierre'),
(5, 'backup_retention_days', '90', 'Plazo de conservación de copias de seguridad'),
(6, 'demo_mode', '1', 'Modo demostración con datos sintéticos activo')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- 13. Notificaciones Iniciales
INSERT INTO notifications (id, type, title, message, link, target_role, is_read, created_at) VALUES
(1, 'VENCIMIENTO_AUTORIZACION', 'Personal con autorización por vencer', 'El Cap. EP Rojas Mendoza tiene su autorización por vencer en 5 días.', '/personnel', 'ADMINISTRADOR', 0, '2026-11-15 08:00:00'),
(2, 'FOTO_PENDIENTE', 'Solicitudes de fotografía pendientes', 'Existen 2 solicitudes de registro fotográfico emitidas desde la guardia.', '/personnel/photo-requests', 'ADMINISTRADOR', 0, '2026-11-15 08:35:00')
ON DUPLICATE KEY UPDATE title=VALUES(title);

SET FOREIGN_KEY_CHECKS = 1;

