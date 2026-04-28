<?php
/**
 * Seed OPD Sessions
 * Run: php seed_opd_sessions.php
 */

require_once __DIR__ . '/config/database.php';

$opd_sessions = [
    // Nalanda Medical Centre
    ['clinic_id' => 1, 'opd_name' => 'General OPD', 'time' => '08:00-12:00'],
    ['clinic_id' => 1, 'opd_name' => 'Dental OPD', 'time' => '13:00-15:00'],
    
    // Hemas Hospital Kandy
    ['clinic_id' => 2, 'opd_name' => 'General OPD', 'time' => '08:00-14:00'],
    ['clinic_id' => 2, 'opd_name' => 'Cardiology OPD', 'time' => '09:00-12:00'],
    ['clinic_id' => 2, 'opd_name' => 'Pediatric OPD', 'time' => '13:00-16:00'],
    
    // Durdans Kandy
    ['clinic_id' => 3, 'opd_name' => 'General OPD', 'time' => '08:00-13:00'],
    ['clinic_id' => 3, 'opd_name' => 'Orthopedic OPD', 'time' => '14:00-16:00'],
    
    // Suwasetha Medical Centre
    ['clinic_id' => 4, 'opd_name' => 'General OPD', 'time' => '08:00-12:00'],
    ['clinic_id' => 4, 'opd_name' => 'ENT OPD', 'time' => '13:00-15:00'],
    
    // Asiri Kandy
    ['clinic_id' => 5, 'opd_name' => 'General OPD', 'time' => '08:00-14:00'],
    ['clinic_id' => 5, 'opd_name' => 'Dermatology OPD', 'time' => '09:00-12:00'],
];

try {
    $db = getDBConnection();
    $today = date('Y-m-d');
    $created = 0;
    
    foreach ($opd_sessions as $session) {
        // Parse time
        list($start, $end) = explode('-', $session['time']);
        
        // Check if session already exists for today
        $stmt = $db->prepare("
            SELECT id FROM opd_sessions 
            WHERE clinic_id = ? AND opd_name = ? AND session_date = ?
        ");
        $stmt->execute([$session['clinic_id'], $session['opd_name'], $today]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("
                INSERT INTO opd_sessions (clinic_id, opd_name, session_date, start_time, end_time, max_tokens)
                VALUES (?, ?, ?, ?, ?, 50)
            ");
            $stmt->execute([
                $session['clinic_id'],
                $session['opd_name'],
                $today,
                $start . ':00',
                $end . ':00'
            ]);
            $created++;
        }
    }
    
    echo "✅ Created {$created} OPD sessions for today ({$today})\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
