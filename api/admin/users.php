<?php
/**
 * Admin Users Management API Endpoint
 * Handles user CRUD operations for admin panel
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../controllers/AdminController.php';

try {
    $adminController = new AdminController();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get users list with filters
            $options = [
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'search' => $_GET['search'] ?? '',
                'role' => $_GET['role'] ?? '',
                'status' => $_GET['status'] ?? '',
                'order_by' => $_GET['order_by'] ?? 'created_at',
                'order_dir' => $_GET['order_dir'] ?? 'DESC'
            ];
            
            $result = $adminController->getUserManagement($options);
            break;
            
        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $adminController->createUser($input);
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $_GET['id'] ?? null;
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            $result = $adminController->updateUser($userId, $input);
            break;
            
        case 'DELETE':
            // Delete user
            $userId = $_GET['id'] ?? null;
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            $result = $adminController->deleteUser($userId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
    }
    
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
