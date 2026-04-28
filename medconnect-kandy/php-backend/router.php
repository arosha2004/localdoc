<?php
/**
 * API Router
 * Routes requests to appropriate API endpoints or serves static/PHP files
 */

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove the base path for XAMPP subdirectory
$basePath = '/medconnect-kandy/php-backend';
$uri = str_replace($basePath, '', $uri);

// Route API requests
if (strpos($uri, '/api/') === 0 || strpos($uri, 'api/') === 0) {
    $apiPath = preg_replace('/^\/?api\//', '', $uri);
    
    // Remove trailing slash
    $apiPath = rtrim($apiPath, '/');
    
    // Route to appropriate API file
    if (strpos($apiPath, 'auth') === 0) {
        // Auth API: /api/auth/register, /api/auth/login, etc.
        $_GET['path'] = preg_replace('/^auth\/?/', '', $apiPath);
        require_once __DIR__ . '/api/auth.php';
    } elseif (strpos($apiPath, 'clinics') === 0) {
        // Clinics API: /api/clinics, /api/clinics/{id}
        $_GET['path'] = preg_replace('/^clinics\/?/', '', $apiPath);
        require_once __DIR__ . '/api/clinics.php';
    } elseif ($apiPath === 'health' || $apiPath === 'health.php') {
        // Health check
        require_once __DIR__ . '/api/health.php';
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found', 'path' => $apiPath]);
    }
} else {
    // Serve PHP files directly
    $filePath = __DIR__ . $uri;
    
    // If directory or root, serve index.php
    if (is_dir($filePath) || $uri === '/' || $uri === '' || $uri === '/index.php') {
        $filePath = __DIR__ . '/index.php';
    } elseif (strpos($uri, '/doctor/') === 0 && is_dir(dirname($filePath))) {
        // Doctor dashboard directory
        $filePath = __DIR__ . $uri;
        if (is_dir($filePath)) {
            $filePath = rtrim($filePath, '/') . '/index.php';
        }
    }
    
    // Check if file exists and is PHP
    if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        // Change working directory to the file's directory
        chdir(dirname($filePath));
        require_once $filePath;
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
}
