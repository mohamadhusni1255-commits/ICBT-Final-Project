<?php
/**
 * Admin Videos Management API Endpoint
 * Handles video approval, deletion, and management for admin panel
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
            // Get videos for admin management
            $options = [
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'status' => $_GET['status'] ?? '',
                'category' => $_GET['category'] ?? '',
                'search' => $_GET['search'] ?? '',
                'order_by' => $_GET['order_by'] ?? 'created_at',
                'order_dir' => $_GET['order_dir'] ?? 'DESC'
            ];
            
            $result = $adminController->getVideoManagement($options);
            break;
            
        case 'POST':
            // Bulk operations (approve, reject, delete)
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'approve':
                    $result = $adminController->approveVideos($input['video_ids'] ?? []);
                    break;
                case 'reject':
                    $result = $adminController->rejectVideos($input['video_ids'] ?? [], $input['reason'] ?? '');
                    break;
                case 'delete':
                    $result = $adminController->deleteVideos($input['video_ids'] ?? []);
                    break;
                default:
                    throw new Exception('Invalid action specified');
            }
            break;
            
        case 'PUT':
            // Update video status or details
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = $_GET['id'] ?? null;
            if (!$videoId) {
                throw new Exception('Video ID is required');
            }
            $result = $adminController->updateVideo($videoId, $input);
            break;
            
        case 'DELETE':
            // Delete single video
            $videoId = $_GET['id'] ?? null;
            if (!$videoId) {
                throw new Exception('Video ID is required');
            }
            $result = $adminController->deleteVideo($videoId);
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
