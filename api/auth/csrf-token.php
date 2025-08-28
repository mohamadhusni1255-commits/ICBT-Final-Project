<?php
require_once '../../controllers/AuthController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Initialize auth controller
    $authController = new AuthController();
    
    // Generate new CSRF token
    $csrfToken = $authController->generateCSRFToken();
    
    if ($csrfToken) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'csrf_token' => $csrfToken
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate CSRF token'
        ]);
    }

} catch (Exception $e) {
    // Log error
    error_log("CSRF token generation error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error. Please try again later.'
    ]);
}
?>
