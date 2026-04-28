-- OPD Token System Tables
-- Run this migration

-- OPD Sessions (per hospital per day)
CREATE TABLE IF NOT EXISTS opd_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT NOT NULL,
    opd_name VARCHAR(100) NOT NULL COMMENT 'e.g., General OPD, Dental OPD',
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_tokens INT NOT NULL DEFAULT 50,
    current_token INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (clinic_id) REFERENCES medical_centers(id) ON DELETE CASCADE,
    INDEX idx_clinic_date (clinic_id, session_date),
    INDEX idx_session_date (session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OPD Tokens
CREATE TABLE IF NOT EXISTS opd_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) NOT NULL COMMENT 'e.g., OPD-045',
    session_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    token_type ENUM('online', 'walk-in') NOT NULL DEFAULT 'online',
    status ENUM('pending', 'waiting', 'called', 'served', 'cancelled', 'no-show') NOT NULL DEFAULT 'pending',
    issue_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estimated_time INT NULL COMMENT 'Estimated waiting time in minutes',
    actual_time TIMESTAMP NULL COMMENT 'Actual serving time',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES opd_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_token_per_session (session_id, token_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (status),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Walk-in Token Counter (for staff)
CREATE TABLE IF NOT EXISTS walkin_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT NOT NULL,
    counter_number INT NOT NULL,
    staff_id INT NULL,
    current_token_number INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (clinic_id) REFERENCES medical_centers(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_clinic_id (clinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
