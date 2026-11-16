-- Migración 004: Control de Movimientos en Puerta y Declaraciones de Salida
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS movements_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_type ENUM('ENTRADA', 'SALIDA') NOT NULL,
    target_type ENUM('PEATONAL', 'VEHICULAR') NOT NULL,
    is_visitor TINYINT(1) NOT NULL DEFAULT 0,
    dni CHAR(8) NOT NULL,
    person_name VARCHAR(150) NOT NULL,
    rank_or_type VARCHAR(60) NULL,
    unit_name VARCHAR(150) NULL,
    plate VARCHAR(15) NULL,
    vehicle_details VARCHAR(100) NULL,
    destination VARCHAR(150) NULL,
    authority_authorizer VARCHAR(150) NULL,
    auth_medium VARCHAR(60) NULL,
    operator_decision ENUM('AUTORIZADO', 'PENDIENTE', 'DENEGADO') NOT NULL DEFAULT 'AUTORIZADO',
    observation TEXT NULL,
    recorded_by INT NOT NULL,
    recorded_at DATETIME NOT NULL,
    is_closed TINYINT(1) NOT NULL DEFAULT 0,
    exit_movement_id INT NULL,
    INDEX idx_mov_type (movement_type),
    INDEX idx_mov_target (target_type),
    INDEX idx_mov_dni (dni),
    INDEX idx_mov_plate (plate),
    INDEX idx_mov_date (recorded_at),
    INDEX idx_mov_closed (is_closed),
    CONSTRAINT fk_mov_user FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exit_declarations_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    open_movement_id INT NOT NULL,
    declared_gate VARCHAR(100) NOT NULL,
    approx_time VARCHAR(50) NULL,
    observation TEXT NOT NULL,
    status ENUM('PENDIENTE_REVISION', 'CONFIRMADA', 'OBSERVADA') NOT NULL DEFAULT 'PENDIENTE_REVISION',
    declared_by INT NOT NULL,
    reviewed_by INT NULL,
    declared_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    review_notes TEXT NULL,
    INDEX idx_exit_decl_open_mov (open_movement_id),
    INDEX idx_exit_decl_status (status),
    CONSTRAINT fk_exit_decl_mov FOREIGN KEY (open_movement_id) REFERENCES movements_demo (id) ON DELETE CASCADE,
    CONSTRAINT fk_exit_decl_user FOREIGN KEY (declared_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_exit_decl_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS restricted_access_simulated (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('PERSONA', 'VEHICULO') NOT NULL,
    identifier VARCHAR(20) NOT NULL,
    full_name_or_detail VARCHAR(150) NOT NULL,
    reason_simulated TEXT NOT NULL,
    authority VARCHAR(100) NOT NULL,
    active_since DATE NOT NULL,
    active_until DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_restr_target (target_type, identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

