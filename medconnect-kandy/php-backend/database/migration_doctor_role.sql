-- Migration: Add doctor role and related tables
-- Run this to update existing database

-- Add doctor role to users table
ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'staff', 'admin') NOT NULL DEFAULT 'patient';
ALTER TABLE users ADD COLUMN specialization VARCHAR(100) NULL AFTER role;

-- Add doctor_id and appointment fields to bookings
ALTER TABLE bookings ADD COLUMN doctor_id INT NULL AFTER patient_id;
ALTER TABLE bookings ADD COLUMN appointment_date DATETIME NULL AFTER status;
ALTER TABLE bookings ADD COLUMN notes TEXT NULL AFTER appointment_date;
ALTER TABLE bookings ADD INDEX idx_doctor_id (doctor_id);
ALTER TABLE bookings ADD FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL;

-- Create prescriptions table
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
