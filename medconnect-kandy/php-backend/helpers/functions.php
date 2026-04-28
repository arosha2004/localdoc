<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get JSON request body
 */
function getJsonBody() {
    $body = file_get_contents('php://input');
    return json_decode($body, true);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if ($data === null) {
        return null;
    }
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
