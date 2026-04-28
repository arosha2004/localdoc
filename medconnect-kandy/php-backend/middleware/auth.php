<?php
/**
 * Authentication Middleware
 * Verify JWT tokens and check user roles
 */

require_once __DIR__ . '/../helpers/security.php';

/**
 * Get current authenticated user from JWT token
 */
function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        sendJsonResponse(['detail' => 'Could not validate credentials'], 401);
    }
    
    $token = $matches[1];
    $payload = decodeJWT($token);
    
    if (!$payload || !isset($payload['sub'])) {
        sendJsonResponse(['detail' => 'Could not validate credentials'], 401);
    }
    
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendJsonResponse(['detail' => 'Could not validate credentials'], 401);
    }
    
    return $user;
}

/**
 * Check if current user is admin
 */
function requireAdmin() {
    $user = getCurrentUser();
    
    if ($user['role'] !== 'admin') {
        sendJsonResponse(['detail' => 'Admin access required'], 403);
    }
    
    return $user;
}

/**
 * Check if current user is staff or admin
 */
function requireStaffOrAdmin() {
    $user = getCurrentUser();
    
    if (!in_array($user['role'], ['staff', 'admin'])) {
        sendJsonResponse(['detail' => 'Staff or Admin access required'], 403);
    }
    
    return $user;
}
