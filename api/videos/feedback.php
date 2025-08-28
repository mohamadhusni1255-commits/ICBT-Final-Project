<?php
/**
 * Video Feedback API Endpoint
 * Handles video feedback submission and retrieval
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/VideoController.php';

try {
    $authController = new AuthController();
    $videoController = new VideoController();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get feedback for a specific video
            $videoId = $_GET['video_id'] ?? null;
            if (!$videoId) {
                throw new Exception('Video ID is required');
            }
            
            $options = [
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'type' => $_GET['type'] ?? 'all' // all, judge, user
            ];
            
            $result = $videoController->getVideoFeedback($videoId, $options);
            echo json_encode($result);
            break;
            
        case 'POST':
            // Submit new feedback
            $user = $authController->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['video_id']) || empty($input['feedback'])) {
                throw new Exception('Video ID and feedback are required');
            }
            
            $feedbackData = [
                'video_id' => $input['video_id'],
                'user_id' => $user['id'],
                'feedback' => $input['feedback'],
                'rating' => $input['rating'] ?? null,
                'type' => $input['type'] ?? 'user' // user, judge
            ];
            
            // Add judge-specific fields if user is a judge
            if (in_array($user['role'], ['judge', 'admin'])) {
                $feedbackData['type'] = 'judge';
                $feedbackData['technical_score'] = $input['technical_score'] ?? null;
                $feedbackData['creativity_score'] = $input['creativity_score'] ?? null;
                $feedbackData['presentation_score'] = $input['presentation_score'] ?? null;
            }
            
            $result = $videoController->addVideoFeedback($feedbackData);
            echo json_encode($result);
            break;
            
        case 'PUT':
            // Update existing feedback
            $user = $authController->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            
            $feedbackId = $_GET['id'] ?? null;
            if (!$feedbackId) {
                throw new Exception('Feedback ID is required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $videoController->updateVideoFeedback($feedbackId, $input, $user['id']);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Delete feedback
            $user = $authController->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            
            $feedbackId = $_GET['id'] ?? null;
            if (!$feedbackId) {
                throw new Exception('Feedback ID is required');
            }
            
            $result = $videoController->deleteVideoFeedback($feedbackId, $user['id']);
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
