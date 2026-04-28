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
        'name' => 'Nalanda Medical Centre',
        'type' => 'Private Clinic',
        'area' => 'Peradeniya',
        'address' => 'No. 42 Peradeniya Road, Kandy',
        'phone' => '+94 81 238 7890',
        'hours' => '8:00 AM – 8:00 PM',
        'services' => json_encode(['OPD', 'Gynaecology', 'Dental', 'Laboratory']),
        'rating' => 4.7,
        'distance' => '2.3 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.2683,
        'lng' => 80.5966,
        'doctor_available' => false,
    ],
    [
        'name' => 'Hemas Hospital Kandy',
        'type' => 'Private Hospital',
        'area' => 'Katugastota',
        'address' => 'No. 289 Katugastota Road, Kandy',
        'phone' => '+94 81 205 5150',
        'hours' => '24 Hours',
        'services' => json_encode(['Emergency', 'OPD', 'Radiology', 'Orthopaedics', 'ICU']),
        'rating' => 4.8,
        'distance' => '3.1 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.3116,
        'lng' => 80.6278,
        'doctor_available' => true,
    ],
    [
        'name' => 'Durdans Kandy Medical Centre',
        'type' => 'Private Clinic',
        'area' => 'Kandy City',
        'address' => 'No. 100 Yatinuwara Veediya, Kandy',
        'phone' => '+94 81 220 0050',
        'hours' => '7:00 AM – 10:00 PM',
        'services' => json_encode(['OPD', 'Cardiology', 'Neurology', 'Laboratory', 'Pharmacy']),
        'rating' => 4.6,
        'distance' => '1.2 km',
        'available' => false,
        'tag' => 'private',
        'lat' => 7.2974,
        'lng' => 80.6358,
        'doctor_available' => false,
    ],
    [
        'name' => 'Suwasetha Medical Centre',
        'type' => 'Private Clinic',
        'area' => 'Ampitiya',
        'address' => 'No. 15 Ampitiya Road, Kandy',
        'phone' => '+94 81 222 6688',
        'hours' => '8:30 AM – 6:00 PM',
        'services' => json_encode(['OPD', 'Paediatrics', 'Dental', 'Physiotherapy']),
        'rating' => 4.3,
        'distance' => '4.0 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.2756,
        'lng' => 80.6512,
        'doctor_available' => true,
    ],
    [
        'name' => 'Asiri Kandy Medical Hub',
        'type' => 'Private Hospital',
        'area' => 'Dharmaraja Junction',
        'address' => 'No. 67 D.S. Senanayake Veediya, Kandy',
        'phone' => '+94 81 222 4545',
        'hours' => '24 Hours',
        'services' => json_encode(['Emergency', 'OPD', 'Dermatology', 'ENT', 'Oncology']),
        'rating' => 4.9,
        'distance' => '0.5 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.2958,
        'lng' => 80.6368,
        'doctor_available' => true,
    ],
    [
        'name' => 'Kandy Lifecare Clinic',
        'type' => 'Private Clinic',
        'area' => 'Getambe',
        'address' => 'No. 22 Getambe Road, Kandy',
        'phone' => '+94 81 238 9922',
        'hours' => '9:00 AM – 7:00 PM',
        'services' => json_encode(['OPD', 'Gynaecology', 'Laboratory', 'Pharmacy']),
        'rating' => 4.4,
        'distance' => '3.7 km',
        'available' => false,
        'tag' => 'private',
        'lat' => 7.2823,
        'lng' => 80.6189,
        'doctor_available' => false,
    ],
    [
        'name' => 'Udawatte Medical Centre',
        'type' => 'Private Clinic',
        'area' => 'Udawatte',
        'address' => 'No. 8 Udawatte Lane, Kandy',
        'phone' => '+94 81 222 1133',
        'hours' => '8:00 AM – 9:00 PM',
        'services' => json_encode(['OPD', 'Dental', 'Orthopaedics', 'Physiotherapy']),
        'rating' => 4.5,
        'distance' => '2.0 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.2945,
        'lng' => 80.6400,
        'doctor_available' => true,
    ],
    [
        'name' => 'Mahiyawa Medical Centre',
        'type' => 'Private Clinic',
        'area' => 'Mahiyawa',
        'address' => 'No. 36 Mahiyawa Road, Kandy',
        'phone' => '+94 81 220 7755',
        'hours' => '9:00 AM – 6:30 PM',
        'services' => json_encode(['OPD', 'Laboratory', 'Eye Care', 'Pharmacy']),
        'rating' => 4.1,
        'distance' => '6.2 km',
        'available' => true,
        'tag' => 'private',
        'lat' => 7.2640,
        'lng' => 80.6550,
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
