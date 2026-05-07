<?php
/**
 * Security Helper Functions
 * JWT token creation/validation, password hashing, and security utilities
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Hash a password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Create JWT token with timing-safe operations
 */
function createJWT($data) {
    $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
    $payload = [
        'iat' => time(),
        'exp' => time() + (JWT_EXPIRE_MINUTES * 60),
        'jti' => bin2hex(random_bytes(16)), // Unique token ID to prevent replay attacks
    ];
    $payload = array_merge($payload, $data);
    $payload = json_encode($payload);
    
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Decode and verify JWT token with timing-safe signature comparison
 */
function decodeJWT($token) {
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) !== 3) {
        return null;
    }
    
    $header = base64UrlDecode($tokenParts[0]);
    $payload = base64UrlDecode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    
    // Verify algorithm in header to prevent algorithm confusion attacks
    $headerData = json_decode($header, true);
    if (!$headerData || !isset($headerData['alg']) || $headerData['alg'] !== JWT_ALGORITHM) {
        return null;
    }
    
    // Verify signature using timing-safe comparison
    $signature = hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);
    
    // Use hash_equals to prevent timing attacks
    if (!hash_equals($base64UrlSignature, $signatureProvided)) {
        return null;
    }
    
    $payload = json_decode($payload, true);
    
    if (!$payload) {
        return null;
    }
    
    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }
    
    // Check issued at (prevent tokens from future)
    if (isset($payload['iat']) && $payload['iat'] > time() + 60) {
        return null;
    }
    
    return $payload;
}

/**
 * Base64 URL encoding
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decoding
 */
function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token
 */
function regenerateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Rate limiting check
 */
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $rateKey = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = [
            'count' => 1,
            'reset' => time() + $timeWindow
        ];
        return true;
    }
    
    // Reset if time window expired
    if (time() > $_SESSION[$rateKey]['reset']) {
        $_SESSION[$rateKey] = [
            'count' => 1,
            'reset' => time() + $timeWindow
        ];
        return true;
    }
    
    // Increment counter
    $_SESSION[$rateKey]['count']++;
    
    if ($_SESSION[$rateKey]['count'] > $maxAttempts) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize output for HTML context
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate secure random string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Verify password strength
 */
function isPasswordStrong($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // At least one special character
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Get password strength message
 */
function getPasswordStrengthMessage($password) {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return 'Password must contain at least one special character';
    }
    return '';
}
