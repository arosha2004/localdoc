<?php
/**
 * Create Doctor Session (Active & Verified)
 * Run from command line:
 * 
 *   php create_doctor_session.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';

define('DOCTOR_EMAIL', 'doctor@localdoc.lk');
define('DOCTOR_PASSWORD', 'Doctor2024!');
define('DOCTOR_NAME', 'Dr. Test Physician');
define('DOCTOR_SPECIALIZATION', 'General Physician');
define('DOCTOR_PHONE', '+94 77 999 8888');
define('DOCTOR_SLMC', 'SLMC/99999');
define('DOCTOR_NIC', '999999999V');
define('DOCTOR_HOSPITAL', 'LocalDoc Test Hospital');

try {
    $db = getDBConnection();
    
    // Check if doctor already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([DOCTOR_EMAIL]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update to active & verified
        $stmt = $db->prepare("
            UPDATE users 
            SET role = 'doctor', 
                specialization = ?,
                phone = ?,
                slmc_registration = ?,
                nic_number = ?,
                hospital_name = ?,
                is_active = 1,
                is_verified = 1
            WHERE email = ?
        ");
        $stmt->execute([
            DOCTOR_SPECIALIZATION,
            DOCTOR_PHONE,
            DOCTOR_SLMC,
            DOCTOR_NIC,
            DOCTOR_HOSPITAL,
            DOCTOR_EMAIL
        ]);
        echo "✅ Doctor session updated: " . DOCTOR_EMAIL . "\n";
    } else {
        // Create new active doctor
        $hashedPassword = hashPassword(DOCTOR_PASSWORD);
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, hashed_password, role, specialization, 
                             slmc_registration, nic_number, hospital_name, 
                             is_active, is_verified, created_at)
            VALUES (?, ?, ?, ?, 'doctor', ?, ?, ?, ?, 1, 1, NOW())
        ");
        $stmt->execute([
            DOCTOR_NAME,
            DOCTOR_EMAIL,
            DOCTOR_PHONE,
            $hashedPassword,
            DOCTOR_SPECIALIZATION,
            DOCTOR_SLMC,
            DOCTOR_NIC,
            DOCTOR_HOSPITAL
        ]);
        echo "✅ Doctor session created: " . DOCTOR_EMAIL . "\n";
    }
    
    echo "\n📧 Login Credentials:\n";
    echo "   Email:    " . DOCTOR_EMAIL . "\n";
    echo "   Password: " . DOCTOR_PASSWORD . "\n";
    echo "\n🔗 Login URL: http://localhost/localdoc/medconnect-kandy/php-backend/\n";
    echo "   → Doctor dashboard will load automatically after login\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
