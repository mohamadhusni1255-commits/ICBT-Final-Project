<?php
/**
 * Admin Competitions Management API Endpoint
 * Handles competition CRUD operations for admin panel
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
            // Get competitions list
            $options = [
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? '',
                'order_by' => $_GET['order_by'] ?? 'created_at',
                'order_dir' => $_GET['order_dir'] ?? 'DESC'
            ];
            
            $result = $adminController->getCompetitionManagement($options);
            break;
            
        case 'POST':
            // Create new competition
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $adminController->createCompetition($input);
            break;
            
        case 'PUT':
            // Update competition
            $input = json_decode(file_get_contents('php://input'), true);
            $competitionId = $_GET['id'] ?? null;
            if (!$competitionId) {
                throw new Exception('Competition ID is required');
            }
            $result = $adminController->updateCompetition($competitionId, $input);
            break;
            
        case 'DELETE':
            // Delete competition
            $competitionId = $_GET['id'] ?? null;
            if (!$competitionId) {
                throw new Exception('Competition ID is required');
            }
            $result = $adminController->deleteCompetition($competitionId);
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
