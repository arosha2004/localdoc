<?php
/**
 * Health Check Endpoints
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);

// Basic health check
if (!isset($_GET['db'])) {
    echo json_encode([
        'status' => 'ok',
        'project' => 'LocalDoc Connect - Kandy (PHP)'
    ]);
    exit;
}

// Database health check
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT 1");
    echo json_encode(['database' => 'connected']);
} catch (Exception $e) {
    echo json_encode(['database' => 'error', 'detail' => $e->getMessage()]);
}
