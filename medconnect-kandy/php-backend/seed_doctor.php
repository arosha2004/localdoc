<?php
/**
 * Seed Doctor User
 * Run after database migration:
 * 
 *   php seed_doctor.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';

define('DOCTOR_EMAIL', 'kamal.perera@localdoc.lk');
define('DOCTOR_PASSWORD', 'Doctor123456!');
define('DOCTOR_NAME', 'Dr. Kamal Perera');
define('DOCTOR_SPECIALIZATION', 'General Physician');

try {
    $db = getDBConnection();
    
    // Check if doctor already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([DOCTOR_EMAIL]);
    
    if ($stmt->fetch()) {
        echo "✅ Doctor already exists: " . DOCTOR_EMAIL . "\n";
        exit;
    }
    
    // Create doctor user
    $hashedPassword = hashPassword(DOCTOR_PASSWORD);
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, hashed_password, role, specialization, is_active, is_verified, created_at)
        VALUES (?, ?, ?, 'doctor', ?, 1, 1, NOW())
    ");
    $stmt->execute([DOCTOR_NAME, DOCTOR_EMAIL, $hashedPassword, DOCTOR_SPECIALIZATION]);
    
    echo "✅ Doctor created successfully!\n";
    echo "   Email:    " . DOCTOR_EMAIL . "\n";
    echo "   Password: " . DOCTOR_PASSWORD . "\n";
    echo "   Specialization: " . DOCTOR_SPECIALIZATION . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
