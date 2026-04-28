<?php
/**
 * Seed Multiple Doctor Users
 * Run after database migration:
 * 
 *   php seed_multiple_doctors.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';

$doctors = [
    [
        'name' => 'Dr. Kamal Perera',
        'email' => 'kamal.perera@localdoc.lk',
        'password' => 'Doctor123456!',
        'phone' => '+94 77 123 4567',
        'specialization' => 'General Physician',
        'slmc' => 'SLMC/12345',
        'nic' => '123456789V',
        'hospital' => 'Kandy General Hospital'
    ],
    [
        'name' => 'Dr. Nuwan Silva',
        'email' => 'nuwan.silva@localdoc.lk',
        'password' => 'Doctor123456!',
        'phone' => '+94 71 234 5678',
        'specialization' => 'Cardiologist',
        'slmc' => 'SLMC/23456',
        'nic' => '234567890V',
        'hospital' => 'Hemas Hospital Kandy'
    ],
    [
        'name' => 'Dr. Ruwan Fernando',
        'email' => 'ruwan.fernando@localdoc.lk',
        'password' => 'Doctor123456!',
        'phone' => '+94 76 345 6789',
        'specialization' => 'Pediatrician',
        'slmc' => 'SLMC/34567',
        'nic' => '345678901V',
        'hospital' => 'Nalanda Medical Centre'
    ],
    [
        'name' => 'Dr. Sanath Jayasuriya',
        'email' => 'sanath.j@localdoc.lk',
        'password' => 'Doctor123456!',
        'phone' => '+94 70 456 7890',
        'specialization' => 'Orthopedic Surgeon',
        'slmc' => 'SLMC/45678',
        'nic' => '456789012V',
        'hospital' => 'Durdans Kandy Medical Centre'
    ],
    [
        'name' => 'Dr. Mahela Bandara',
        'email' => 'mahela.b@localdoc.lk',
        'password' => 'Doctor123456!',
        'phone' => '+94 77 567 8901',
        'specialization' => 'Dermatologist',
        'slmc' => 'SLMC/56789',
        'nic' => '567890123V',
        'hospital' => 'Suwasetha Medical Centre'
    ],
];

try {
    $db = getDBConnection();
    $created = 0;
    $skipped = 0;
    
    foreach ($doctors as $doctor) {
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$doctor['email']]);
        
        if ($stmt->fetch()) {
            // Update existing doctor with verification fields
            $stmt = $db->prepare("
                UPDATE users 
                SET role = 'doctor', 
                    specialization = ?,
                    slmc_registration = ?,
                    nic_number = ?,
                    hospital_name = ?,
                    is_active = 0,
                    is_verified = 0
                WHERE email = ? AND role != 'admin'
            ");
            $stmt->execute([
                $doctor['specialization'],
                $doctor['slmc'],
                $doctor['nic'],
                $doctor['hospital'],
                $doctor['email']
            ]);
            echo "✅ Updated: " . $doctor['name'] . " (" . $doctor['email'] . ")\n";
            $created++;
        } else {
            // Create new doctor
            $hashedPassword = hashPassword($doctor['password']);
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, phone, hashed_password, role, specialization, 
                                 slmc_registration, nic_number, hospital_name, 
                                 is_active, is_verified, created_at)
                VALUES (?, ?, ?, ?, 'doctor', ?, ?, ?, ?, 0, 0, NOW())
            ");
            $stmt->execute([
                $doctor['name'],
                $doctor['email'],
                $doctor['phone'],
                $hashedPassword,
                $doctor['specialization'],
                $doctor['slmc'],
                $doctor['nic'],
                $doctor['hospital']
            ]);
            echo "✅ Created: " . $doctor['name'] . " (" . $doctor['email'] . ")\n";
            $created++;
        }
    }
    
    echo "\n✅ Successfully registered {$created} doctors!\n";
    echo "\n📧 Login Credentials (all pending approval):\n";
    foreach ($doctors as $doctor) {
        echo "   - {$doctor['email']} / {$doctor['password']}\n";
    }
    echo "\n⚠️  All doctors require admin approval before they can login.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
