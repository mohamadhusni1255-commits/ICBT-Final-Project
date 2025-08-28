<?php
/**
 * Video Detail API Endpoint
 * Returns detailed information about a specific video
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

require_once __DIR__ . '/../../controllers/VideoController.php';

try {
    // Get video ID from URL
    $videoId = $_GET['id'] ?? null;
    
    if (!$videoId) {
        http_response_code(400);
        echo json_encode(['error' => 'Video ID is required']);
        exit();
    }
    
    // Initialize video controller
    $videoController = new VideoController();
    
    // Get video details
    $result = $videoController->getVideo($videoId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
