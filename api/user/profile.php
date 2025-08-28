<?php
/**
 * User Profile API Endpoint
 * Handles user profile management and updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/UserModel.php';

try {
    $authController = new AuthController();
    $userModel = new UserModel();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get user profile
            $user = $authController->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            
            $profile = $userModel->findById($user['id']);
            
            if ($profile['success']) {
                // Remove sensitive information
                unset($profile['data']['password']);
                unset($profile['data']['password_reset_token']);
                
                echo json_encode([
                    'success' => true,
                    'profile' => $profile['data']
                ]);
            } else {
                echo json_encode($profile);
            }
            break;
            
        case 'PUT':
            // Update user profile
            $user = $authController->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Only allow updating certain fields
            $allowedFields = ['full_name', 'bio', 'location', 'phone', 'social_links', 'profile_picture'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('No valid fields to update');
            }
            
            $result = $userModel->update($user['id'], $updateData);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
