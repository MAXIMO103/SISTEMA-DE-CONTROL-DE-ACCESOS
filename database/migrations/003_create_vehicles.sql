-- Migración 003: Vehículos Particulares y Oficiales
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS vehicles_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('PARTICULAR', 'OFICIAL') NOT NULL,
    plate VARCHAR(10) NOT NULL UNIQUE,
    owner_dni CHAR(8) NULL,
    owner_cip_civ VARCHAR(30) NULL,
    owner_name VARCHAR(150) NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    unit_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicles_plate (plate),
    INDEX idx_vehicles_owner_dni (owner_dni),
    INDEX idx_vehicles_unit (unit_id),
    INDEX idx_vehicles_category (category),
    CONSTRAINT fk_vehicles_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicle_authorizations_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    auth_start DATE NOT NULL,
    auth_end DATE NOT NULL,
    status ENUM('ACTIVO', 'POR_VENCER', 'VENCIDO', 'PENDIENTE_RENOVACION') NOT NULL DEFAULT 'ACTIVO',
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_veh_auth_vehicle (vehicle_id),
    INDEX idx_veh_auth_dates (auth_start, auth_end),
    INDEX idx_veh_auth_status (status),
    CONSTRAINT fk_veh_auth_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles_demo (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

