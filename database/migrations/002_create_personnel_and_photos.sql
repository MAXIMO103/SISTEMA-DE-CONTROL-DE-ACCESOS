-- Migración 002: Dependencias, Personal y Solicitudes de Fotografía
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('MILITAR', 'CIVIL') NOT NULL,
    dni CHAR(8) NOT NULL UNIQUE,
    cip_civ VARCHAR(30) NULL,
    rank_grade VARCHAR(50) NULL,
    surnames VARCHAR(100) NOT NULL,
    names VARCHAR(100) NOT NULL,
    unit_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_personnel_dni (dni),
    INDEX idx_personnel_unit (unit_id),
    INDEX idx_personnel_category (category),
    INDEX idx_personnel_active (is_active),
    CONSTRAINT fk_personnel_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel_authorizations_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    auth_start DATE NOT NULL,
    auth_end DATE NOT NULL,
    status ENUM('ACTIVO', 'POR_VENCER', 'VENCIDO', 'PENDIENTE_RENOVACION') NOT NULL DEFAULT 'ACTIVO',
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_auth_personnel (personnel_id),
    INDEX idx_auth_dates (auth_start, auth_end),
    INDEX idx_auth_status (status),
    CONSTRAINT fk_auth_personnel FOREIGN KEY (personnel_id) REFERENCES personnel_demo (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS photo_requests_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    requested_by INT NOT NULL,
    status ENUM('PENDIENTE', 'COMPLETADA') NOT NULL DEFAULT 'PENDIENTE',
    requested_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    INDEX idx_photo_req_personnel (personnel_id),
    INDEX idx_photo_req_status (status),
    CONSTRAINT fk_photo_req_personnel FOREIGN KEY (personnel_id) REFERENCES personnel_demo (id) ON DELETE CASCADE,
    CONSTRAINT fk_photo_req_user FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

