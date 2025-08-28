<?php
/**
 * User Dashboard API Endpoint
 * Returns user-specific statistics and data for dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/VideoModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';
require_once __DIR__ . '/../../models/LikeModel.php';

try {
    $authController = new AuthController();
    
    // Check if user is authenticated
    $user = $authController->getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }
    
    $videoModel = new VideoModel();
    $feedbackModel = new FeedbackModel();
    $likeModel = new LikeModel();
    
    // Get user-specific statistics
    $userVideos = $videoModel->getUserVideos($user['id']);
    $userFeedback = $feedbackModel->getUserFeedback($user['id']);
    $userLikes = $likeModel->getUserLikes($user['id']);
    
    // Calculate dashboard statistics
    $stats = [
        'videos_uploaded' => count($userVideos['videos'] ?? []),
        'total_views' => array_sum(array_column($userVideos['videos'] ?? [], 'views')),
        'total_likes' => array_sum(array_column($userVideos['videos'] ?? [], 'likes')),
        'feedback_received' => count($userFeedback['feedback'] ?? []),
        'videos_liked' => count($userLikes['videos'] ?? [])
    ];
    
    // Get recent activity
    $recentVideos = array_slice($userVideos['videos'] ?? [], 0, 5);
    $recentFeedback = array_slice($userFeedback['feedback'] ?? [], 0, 5);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_videos' => $recentVideos,
        'recent_feedback' => $recentFeedback
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
