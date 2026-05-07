<?php
/**
 * Seed Medical Centers
 * Run after database setup:
 * 
 *   php seed_clinics.php
 */

require_once __DIR__ . '/config/database.php';

$INITIAL_CENTERS = [
    [
        'name' => 'Kandy City OPD Clinic',
        'type' => 'OPD Center',
        'area' => 'Kandy City',
        'address' => 'No. 14 Dalada Veediya, Kandy',
        'phone' => '+94 81 222 3456',
        'hours' => '8:00 AM – 6:00 PM',
        'services' => json_encode(['OPD', 'General Medicine', 'Laboratory']),
        'rating' => 4.3,
        'distance' => '0.5 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2906,
        'lng' => 80.6337,
        'doctor_available' => true,
    ],
    [
        'name' => 'Peradeniya OPD Center',
        'type' => 'OPD Center',
        'area' => 'Peradeniya',
        'address' => 'No. 88 Peradeniya Road, Kandy',
        'phone' => '+94 81 238 5678',
        'hours' => '7:30 AM – 5:00 PM',
        'services' => json_encode(['OPD', 'Pediatrics', 'Pharmacy']),
        'rating' => 4.1,
        'distance' => '2.8 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2683,
        'lng' => 80.5966,
        'doctor_available' => false,
    ],
    [
        'name' => 'Katugastota OPD Clinic',
        'type' => 'OPD Center',
        'area' => 'Katugastota',
        'address' => 'No. 45 Katugastota Road, Kandy',
        'phone' => '+94 81 205 7890',
        'hours' => '8:00 AM – 7:00 PM',
        'services' => json_encode(['OPD', 'ENT', 'Laboratory', 'X-Ray']),
        'rating' => 4.4,
        'distance' => '3.2 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.3116,
        'lng' => 80.6278,
        'doctor_available' => true,
    ],
    [
        'name' => 'Ampitiya OPD Center',
        'type' => 'OPD Center',
        'area' => 'Ampitiya',
        'address' => 'No. 23 Ampitiya Road, Kandy',
        'phone' => '+94 81 222 8901',
        'hours' => '9:00 AM – 5:30 PM',
        'services' => json_encode(['OPD', 'General Medicine', 'Physiotherapy']),
        'rating' => 4.0,
        'distance' => '4.1 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2756,
        'lng' => 80.6512,
        'doctor_available' => false,
    ],
    [
        'name' => 'Dharmaraja OPD Clinic',
        'type' => 'OPD Center',
        'area' => 'Dharmaraja',
        'address' => 'No. 56 D.S. Senanayake Veediya, Kandy',
        'phone' => '+94 81 222 4321',
        'hours' => '8:00 AM – 8:00 PM',
        'services' => json_encode(['OPD', 'Cardiology', 'Laboratory', 'Pharmacy']),
        'rating' => 4.6,
        'distance' => '0.8 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2958,
        'lng' => 80.6368,
        'doctor_available' => true,
    ],
    [
        'name' => 'Getambe OPD Center',
        'type' => 'OPD Center',
        'area' => 'Getambe',
        'address' => 'No. 67 Getambe Road, Kandy',
        'phone' => '+94 81 238 6543',
        'hours' => '8:30 AM – 6:30 PM',
        'services' => json_encode(['OPD', 'Dermatology', 'Laboratory']),
        'rating' => 3.9,
        'distance' => '3.9 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2823,
        'lng' => 80.6189,
        'doctor_available' => false,
    ],
    [
        'name' => 'Udawatte OPD Clinic',
        'type' => 'OPD Center',
        'area' => 'Udawatte',
        'address' => 'No. 12 Udawatte Lane, Kandy',
        'phone' => '+94 81 222 7654',
        'hours' => '7:00 AM – 7:00 PM',
        'services' => json_encode(['OPD', 'Orthopedics', 'Physiotherapy', 'X-Ray']),
        'rating' => 4.5,
        'distance' => '1.9 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2945,
        'lng' => 80.6400,
        'doctor_available' => true,
    ],
    [
        'name' => 'Mahiyawa OPD Center',
        'type' => 'OPD Center',
        'area' => 'Mahiyawa',
        'address' => 'No. 78 Mahiyawa Road, Kandy',
        'phone' => '+94 81 220 9876',
        'hours' => '9:00 AM – 5:00 PM',
        'services' => json_encode(['OPD', 'Eye Care', 'Laboratory', 'Pharmacy']),
        'rating' => 3.8,
        'distance' => '5.8 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2640,
        'lng' => 80.6550,
        'doctor_available' => false,
    ],
    [
        'name' => 'Kandy Town OPD Clinic',
        'type' => 'OPD Center',
        'area' => 'Kandy Town',
        'address' => 'No. 34 Temple Street, Kandy',
        'phone' => '+94 81 222 1357',
        'hours' => '8:00 AM – 6:00 PM',
        'services' => json_encode(['OPD', 'Gynecology', 'Laboratory']),
        'rating' => 4.2,
        'distance' => '1.1 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2917,
        'lng' => 80.6345,
        'doctor_available' => true,
    ],
    [
        'name' => 'Hantana OPD Center',
        'type' => 'OPD Center',
        'area' => 'Hantana',
        'address' => 'No. 90 Hantana Road, Kandy',
        'phone' => '+94 81 224 2468',
        'hours' => '8:30 AM – 5:30 PM',
        'services' => json_encode(['OPD', 'General Medicine', 'Dental', 'Pharmacy']),
        'rating' => 4.0,
        'distance' => '2.5 km',
        'available' => true,
        'tag' => 'opd',
        'lat' => 7.2870,
        'lng' => 80.6290,
        'doctor_available' => false,
    ],
];

try {
    $db = getDBConnection();
    
    $count = 0;
    foreach ($INITIAL_CENTERS as $center) {
        // Check if center exists by name
        $stmt = $db->prepare("SELECT id FROM medical_centers WHERE name = ?");
        $stmt->execute([$center['name']]);
        
        if ($stmt->fetch()) {
            echo "Medical center {$center['name']} already exists.\n";
            continue;
        }
        
        // Insert center
        $stmt = $db->prepare("
            INSERT INTO medical_centers 
            (name, type, area, address, phone, hours, services, rating, distance, available, tag, lat, lng, doctor_available)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $center['name'],
            $center['type'],
            $center['area'],
            $center['address'],
            $center['phone'],
            $center['hours'],
            $center['services'],
            $center['rating'],
            $center['distance'],
            $center['available'] ? 1 : 0,
            $center['tag'],
            $center['lat'],
            $center['lng'],
            $center['doctor_available'] ? 1 : 0,
        ]);
        
        $count++;
        echo "✅ Added: {$center['name']}\n";
    }
    
    echo "\n✅ Successfully seeded {$count} medical centers!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
