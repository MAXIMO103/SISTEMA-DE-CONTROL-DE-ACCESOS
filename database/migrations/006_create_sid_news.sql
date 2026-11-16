-- Migración 006: Síntesis de Información Diaria (SID), Fuentes y Control Editorial
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS news_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(50) NOT NULL,
    threat_type VARCHAR(100) NOT NULL,
    results_count INT NOT NULL DEFAULT 0,
    performed_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_searches_query (department, threat_type),
    CONSTRAINT fk_searches_user FOREIGN KEY (performed_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(50) NOT NULL,
    threat_type VARCHAR(100) NOT NULL,
    headline VARCHAR(255) NOT NULL,
    synthesis TEXT NOT NULL,
    original_headline VARCHAR(255) NOT NULL,
    original_synthesis TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    photo_url VARCHAR(255) NULL,
    photo_credit VARCHAR(150) NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    hide_reason VARCHAR(255) NULL,
    hidden_by INT NULL,
    hidden_at DATETIME NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_news_query (department, threat_type, event_date),
    INDEX idx_news_hidden (is_hidden),
    CONSTRAINT fk_news_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_news_hider FOREIGN KEY (hidden_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_event_id INT NOT NULL,
    media_name VARCHAR(100) NOT NULL,
    source_url VARCHAR(500) NOT NULL,
    canonical_hash CHAR(64) NOT NULL,
    published_at DATETIME NOT NULL,
    photo_url VARCHAR(255) NULL,
    photo_credit VARCHAR(150) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_sources_event (news_event_id),
    INDEX idx_sources_hash (canonical_hash),
    CONSTRAINT fk_sources_event FOREIGN KEY (news_event_id) REFERENCES news_events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_event_id INT NOT NULL,
    version_num INT NOT NULL,
    headline VARCHAR(255) NOT NULL,
    synthesis TEXT NOT NULL,
    edited_by INT NOT NULL,
    edit_notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_versions_event (news_event_id),
    CONSTRAINT fk_versions_event FOREIGN KEY (news_event_id) REFERENCES news_events (id) ON DELETE CASCADE,
    CONSTRAINT fk_versions_user FOREIGN KEY (edited_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

