-- LocalDoc Connect Database Schema
-- Run this SQL to create all necessary tables

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS medconnect_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medconnect_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NULL,
    hashed_password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'staff', 'admin') NOT NULL DEFAULT 'patient',
    specialization VARCHAR(100) NULL,
    slmc_registration VARCHAR(50) NULL,
    nic_number VARCHAR(20) NULL,
    hospital_name VARCHAR(255) NULL,
    verification_document VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Centers table
CREATE TABLE IF NOT EXISTS medical_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    hours VARCHAR(100) NOT NULL,
    services JSON NOT NULL,
    rating DECIMAL(3,1) NOT NULL,
    distance VARCHAR(50) NOT NULL,
    available BOOLEAN NOT NULL DEFAULT TRUE,
    tag VARCHAR(50) NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    doctor_available BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_area (area),
    INDEX idx_available (available),
    INDEX idx_doctor_available (doctor_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (for appointments)
CREATE TABLE IF NOT EXISTS sessions (
    id CHAR(36) PRIMARY KEY,
    clinic_id INT NOT NULL,
    doctor_name VARCHAR(255),
    date DATETIME,
    capacity INT,
    booking_type ENUM('slot', 'token'),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    delay_minutes INT NOT NULL DEFAULT 0,
    FOREIGN KEY (clinic_id) REFERENCES medical_centers(id) ON DELETE CASCADE,
    INDEX idx_clinic_id (clinic_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings table (appointments)
CREATE TABLE IF NOT EXISTS bookings (
    id CHAR(36) PRIMARY KEY,
    session_id CHAR(36) NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    token_number INT NULL,
    status ENUM('pending', 'confirmed', 'served', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending',
    appointment_date DATETIME NULL,
    notes TEXT NULL,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescriptions table
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id CHAR(36) NOT NULL,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
