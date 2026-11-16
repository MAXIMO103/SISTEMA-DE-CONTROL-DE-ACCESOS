-- Migración 005: Incidencias, Evidencias y Eventos de Seguimiento
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS incidents_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_code VARCHAR(25) NOT NULL UNIQUE,
    event_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ref_dni CHAR(8) NULL,
    ref_plate VARCHAR(15) NULL,
    action_taken TEXT NOT NULL,
    status ENUM('PENDIENTE', 'EN_REVISION', 'CERRADA') NOT NULL DEFAULT 'PENDIENTE',
    resolution_measure TEXT NULL,
    resolution_explanation TEXT NULL,
    closed_by INT NULL,
    closed_at DATETIME NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidents_code (incident_code),
    INDEX idx_incidents_status (status),
    INDEX idx_incidents_created (created_at),
    CONSTRAINT fk_incidents_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_incidents_closer FOREIGN KEY (closed_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_evidence_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(150) NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    file_size INT NOT NULL,
    captured_via ENUM('UPLOAD', 'WEBCAM') NOT NULL DEFAULT 'UPLOAD',
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL,
    INDEX idx_evidence_incident (incident_id),
    CONSTRAINT fk_evidence_incident FOREIGN KEY (incident_id) REFERENCES incidents_demo (id) ON DELETE CASCADE,
    CONSTRAINT fk_evidence_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_events_demo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id INT NOT NULL,
    event_type ENUM('CREACION', 'OBSERVACION', 'REVISION', 'CIERRE') NOT NULL,
    notes TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_inc_events_incident (incident_id),
    CONSTRAINT fk_inc_events_incident FOREIGN KEY (incident_id) REFERENCES incidents_demo (id) ON DELETE CASCADE,
    CONSTRAINT fk_inc_events_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

