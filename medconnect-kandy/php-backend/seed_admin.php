<?php
/**
 * Seed Admin User
 * Run once after database setup:
 * 
 *   php seed_admin.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';

define('ADMIN_EMAIL', 'admin@localdoc.lk');
define('ADMIN_PASSWORD', 'LocalDocAdmin2024!'); // Change before production!
define('ADMIN_NAME', 'System Administrator');

try {
    $db = getDBConnection();
    
    // Check if admin already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([ADMIN_EMAIL]);
    
    if ($stmt->fetch()) {
        echo "✅ Admin already exists: " . ADMIN_EMAIL . "\n";
        exit;
    }
    
    // Create admin user
    $hashedPassword = hashPassword(ADMIN_PASSWORD);
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, hashed_password, role, is_active, is_verified, created_at)
        VALUES (?, ?, ?, 'admin', 1, 1, NOW())
    ");
    $stmt->execute([ADMIN_NAME, ADMIN_EMAIL, $hashedPassword]);
    
    echo "✅ Admin created successfully!\n";
    echo "   Email:    " . ADMIN_EMAIL . "\n";
    echo "   Password: " . ADMIN_PASSWORD . "\n";
    echo "   ⚠️  Change the password immediately in production!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
