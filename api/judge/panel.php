<?php
/**
 * Judge Panel API Endpoint
 * Returns judge-specific data and handles judge operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/VideoModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';

try {
    $authController = new AuthController();
    
    // Check if user is judge or admin
    $user = $authController->getCurrentUser();
    if (!$user || !in_array($user['role'], ['judge', 'admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Judge access required']);
        exit();
    }
    
    $videoModel = new VideoModel();
    $feedbackModel = new FeedbackModel();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get judge dashboard data
            $options = [
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'status' => $_GET['status'] ?? 'pending',
                'category' => $_GET['category'] ?? ''
            ];
            
            // Get videos pending review
            $pendingVideos = $videoModel->findAll(array_merge($options, ['status' => 'pending']));
            
            // Get videos already reviewed by this judge
            $reviewedVideos = $videoModel->getJudgeReviewedVideos($user['id'], $options);
            
            // Get judge statistics
            $judgeStats = [
                'videos_reviewed' => count($reviewedVideos['videos'] ?? []),
                'pending_reviews' => count($pendingVideos['videos'] ?? []),
                'total_feedback_given' => $feedbackModel->getJudgeFeedbackCount($user['id'])
            ];
            
            echo json_encode([
                'success' => true,
                'pending_videos' => $pendingVideos,
                'reviewed_videos' => $reviewedVideos,
                'judge_stats' => $judgeStats
            ]);
            break;
            
        case 'POST':
            // Submit video feedback/review
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['video_id']) || empty($input['rating']) || empty($input['feedback'])) {
                throw new Exception('Video ID, rating, and feedback are required');
            }
            
            $feedbackData = [
                'video_id' => $input['video_id'],
                'judge_id' => $user['id'],
                'rating' => $input['rating'],
                'feedback' => $input['feedback'],
                'technical_score' => $input['technical_score'] ?? null,
                'creativity_score' => $input['creativity_score'] ?? null,
                'presentation_score' => $input['presentation_score'] ?? null
            ];
            
            $result = $feedbackModel->create($feedbackData);
            
            if ($result['success']) {
                // Update video status to reviewed
                $videoModel->update($input['video_id'], ['status' => 'reviewed']);
            }
            
            echo json_encode($result);
            break;
            
        case 'PUT':
            // Update existing feedback
            $input = json_decode(file_get_contents('php://input'), true);
            $feedbackId = $_GET['id'] ?? null;
            
            if (!$feedbackId) {
                throw new Exception('Feedback ID is required');
            }
            
            $result = $feedbackModel->update($feedbackId, $input);
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
