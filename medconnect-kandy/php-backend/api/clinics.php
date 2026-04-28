<?php
/**
 * Clinics API Endpoints
 * Handles: list clinics, update clinic status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Route: GET /clinics/
if ($method === 'GET' && ($path === '' || $path === '/')) {
    $db = getDBConnection();
    $stmt = $db->query("
        SELECT id, name, type, area, address, phone, hours, services, 
               rating, distance, available, tag, lat, lng, doctor_available 
        FROM medical_centers 
        ORDER BY id
    ");
    $clinics = $stmt->fetchAll();
    
    // Decode services JSON and add coords
    foreach ($clinics as &$clinic) {
        $clinic['services'] = json_decode($clinic['services'], true);
        $clinic['coords'] = [
            'lat' => (float)$clinic['lat'],
            'lng' => (float)$clinic['lng']
        ];
        $clinic['rating'] = (float)$clinic['rating'];
        $clinic['lat'] = (float)$clinic['lat'];
        $clinic['lng'] = (float)$clinic['lng'];
        $clinic['available'] = (bool)$clinic['available'];
        $clinic['doctor_available'] = (bool)$clinic['doctor_available'];
    }
    
    sendJsonResponse($clinics);
}

// Route: PUT /clinics/{clinic_id}
elseif ($method === 'PUT' && preg_match('/^(\d+)$/', $path, $matches)) {
    $clinicId = $matches[1];
    
    $body = getJsonBody();
    $db = getDBConnection();
    
    // Check if clinic exists
    $stmt = $db->prepare("SELECT id FROM medical_centers WHERE id = ?");
    $stmt->execute([$clinicId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(['detail' => 'Clinic not found'], 404);
    }
    
    // Update clinic
    if (isset($body['doctor_available'])) {
        $stmt = $db->prepare("
            UPDATE medical_centers 
            SET doctor_available = ? 
            WHERE id = ?
        ");
        $stmt->execute([$body['doctor_available'] ? 1 : 0, $clinicId]);
    }
    
    // Get updated clinic
    $stmt = $db->prepare("
        SELECT id, name, type, area, address, phone, hours, services, 
               rating, distance, available, tag, lat, lng, doctor_available 
        FROM medical_centers 
        WHERE id = ?
    ");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch();
    
    // Decode services JSON and add coords
    $clinic['services'] = json_decode($clinic['services'], true);
    $clinic['coords'] = [
        'lat' => (float)$clinic['lat'],
        'lng' => (float)$clinic['lng']
    ];
    $clinic['rating'] = (float)$clinic['rating'];
    $clinic['lat'] = (float)$clinic['lat'];
    $clinic['lng'] = (float)$clinic['lng'];
    $clinic['available'] = (bool)$clinic['available'];
    $clinic['doctor_available'] = (bool)$clinic['doctor_available'];
    
    sendJsonResponse($clinic);
}

else {
    sendJsonResponse(['detail' => 'Endpoint not found'], 404);
}
