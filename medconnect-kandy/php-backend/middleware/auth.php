<?php
/**
 * Authentication Middleware
 * Verify JWT tokens and check user roles
 */

require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/functions.php';

/**
 * Get current authenticated user from JWT token
 */
function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        sendJsonResponse(['error' => 'Authorization token required'], 401);
    }
    
    $token = $matches[1];
    $payload = decodeJWT($token);
    
    if (!$payload || !isset($payload['sub'])) {
        sendJsonResponse(['error' => 'Invalid or expired token'], 401);
    }
    
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendJsonResponse(['error' => 'User not found or deactivated'], 401);
    }
    
    return $user;
}

/**
 * Check if current user is admin
 */
function requireAdmin() {
    $user = getCurrentUser();
    
    if ($user['role'] !== 'admin') {
        sendJsonResponse(['error' => 'Admin access required'], 403);
    }
    
    return $user;
}

/**
 * Check if current user is staff or admin
 */
function requireStaffOrAdmin() {
    $user = getCurrentUser();
    
    if (!in_array($user['role'], ['staff', 'admin'])) {
        sendJsonResponse(['error' => 'Staff or Admin access required'], 403);
    }
    
    return $user;
}

/**
 * Check if current user is doctor
 */
function requireDoctor() {
    $user = getCurrentUser();
    
    if ($user['role'] !== 'doctor') {
        sendJsonResponse(['error' => 'Doctor access required'], 403);
    }
    
    if (!$user['is_verified']) {
        sendJsonResponse(['error' => 'Doctor account not verified'], 403);
    }
    
    return $user;
}

/**
 * Check if current user is patient
 */
function requirePatient() {
    $user = getCurrentUser();
    
    if ($user['role'] !== 'patient') {
        sendJsonResponse(['error' => 'Patient access required'], 403);
    }
    
    return $user;
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.tailwindcss.com; style-src \'self\' \'unsafe-inline\';');
}

/**
 * Verify session-based authentication for web pages
 */
function requireSessionAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user'])) {
        header('Location: /localdoc/medconnect-kandy/php-backend/index.php');
        exit;
    }
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    return $_SESSION['user'];
}

/**
 * Verify user has specific role for session auth
 */
function requireSessionRole($role) {
    $user = requireSessionAuth();
    
    if ($user['role'] !== $role) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
    
    return $user;
}
