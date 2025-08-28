<?php
/**
 * Register API Endpoint
 * Handles user registration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $age_group = $input['age_group'] ?? '';
    $csrf_token = $input['csrf_token'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception('Username, email, and password are required');
    }
    
    // Initialize auth controller
    $authController = new AuthController();
    
    // Validate CSRF token
    if (!$authController->validateCSRFToken($csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
    
    // Attempt registration
    $result = $authController->register([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'age_group' => $age_group
    ]);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
