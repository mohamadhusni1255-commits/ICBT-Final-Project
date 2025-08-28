<?php
/**
 * User Password Change API Endpoint
 * Handles secure password updates for authenticated users
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $authController = new AuthController();
    
    // Check if user is authenticated
    $user = $authController->getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($input['current_password']) || empty($input['new_password'])) {
        throw new Exception('Current password and new password are required');
    }
    
    if (strlen($input['new_password']) < 8) {
        throw new Exception('New password must be at least 8 characters long');
    }
    
    if ($input['new_password'] !== ($input['confirm_password'] ?? '')) {
        throw new Exception('New password and confirmation do not match');
    }
    
    // Change password
    $result = $authController->changePassword(
        $user['id'],
        $input['current_password'],
        $input['new_password']
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
