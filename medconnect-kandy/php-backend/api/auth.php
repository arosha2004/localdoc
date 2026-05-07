<?php
/**
 * Authentication API Endpoints
 * Login, Register, Logout
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/functions.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Remove trailing slash
$path = rtrim($path, '/');

// Handle preflight OPTIONS request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Route: POST /api/auth/login
if ($path === 'login' && $method === 'POST') {
    $body = getJsonBody();
    $email = sanitizeInput($body['email'] ?? '');
    $password = $body['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendJsonResponse(['error' => 'Email and password are required'], 400);
    }
    
    if (!isValidEmail($email)) {
        sendJsonResponse(['error' => 'Invalid email format'], 400);
    }
    
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($password, $user['hashed_password'])) {
            sendJsonResponse(['error' => 'Invalid email or password'], 401);
        }
        
        if (!$user['is_active']) {
            if ($user['role'] === 'doctor' && !$user['is_verified']) {
                sendJsonResponse(['error' => 'Your doctor account is pending admin verification'], 403);
            } else {
                sendJsonResponse(['error' => 'Your account has been deactivated'], 403);
            }
        }
        
        // Create JWT token
        $token = createJWT([
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['full_name']
        ]);
        
        sendJsonResponse([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'specialization' => $user['specialization'] ?? null
            ]
        ], 200);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Login failed'], 500);
    }
}

// Route: POST /api/auth/register
elseif ($path === 'register' && $method === 'POST') {
    $body = getJsonBody();
    $full_name = sanitizeInput($body['full_name'] ?? '');
    $email = sanitizeInput($body['email'] ?? '');
    $phone = sanitizeInput($body['phone'] ?? '');
    $password = $body['password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($password)) {
        sendJsonResponse(['error' => 'All required fields must be filled'], 400);
    }
    
    if (!isValidEmail($email)) {
        sendJsonResponse(['error' => 'Invalid email format'], 400);
    }
    
    if (strlen($password) < 8) {
        sendJsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }
    
    try {
        $db = getDBConnection();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJsonResponse(['error' => 'An account with this email already exists'], 409);
        }
        
        // Check if phone exists (if provided)
        if (!empty($phone)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                sendJsonResponse(['error' => 'An account with this phone number already exists'], 409);
            }
        }
        
        // Create user
        $hashedPassword = hashPassword($password);
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, hashed_password, role, is_active, is_verified, created_at)
            VALUES (?, ?, ?, ?, 'patient', 1, 0, NOW())
        ");
        $stmt->execute([$full_name, $email, $phone, $hashedPassword]);
        
        $userId = $db->lastInsertId();
        
        // Create JWT token
        $token = createJWT([
            'sub' => $userId,
            'email' => $email,
            'role' => 'patient',
            'name' => $full_name
        ]);
        
        sendJsonResponse([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'full_name' => $full_name,
                'email' => $email,
                'role' => 'patient'
            ]
        ], 201);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Registration failed'], 500);
    }
}

// Route: GET /api/auth/me (Get current user)
elseif ($path === 'me' && $method === 'GET') {
    require_once __DIR__ . '/../middleware/auth.php';
    $user = getCurrentUser();
    
    sendJsonResponse([
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone'] ?? null,
            'specialization' => $user['specialization'] ?? null
        ]
    ], 200);
}

// Route not found
else {
    sendJsonResponse(['error' => 'Authentication endpoint not found'], 404);
}