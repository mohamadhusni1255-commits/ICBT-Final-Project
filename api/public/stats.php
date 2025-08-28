<?php
/**
 * Public Statistics API Endpoint
 * Returns public statistics for homepage display
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/VideoModel.php';
require_once __DIR__ . '/../../models/CompetitionModel.php';

try {
    $userModel = new UserModel();
    $videoModel = new VideoModel();
    $competitionModel = new CompetitionModel();
    
    // Get user statistics
    $userStats = $userModel->getStats();
    
    // Get video statistics
    $videoStats = $videoModel->getStats();
    
    // Get competition statistics
    $competitionStats = $competitionModel->getStats();
    
    // Calculate public statistics
    $stats = [
        'users' => ($userStats['total_users'] ?? 0) . '+',
        'videos' => ($videoStats['total_videos'] ?? 0) . '+',
        'judges' => ($userStats['judge_count'] ?? 0) . '+',
        'competitions' => ($competitionStats['active_competitions'] ?? 0) . '+'
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
