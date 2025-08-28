<?php
/**
 * Video Search API Endpoint
 * Provides advanced search and filtering for videos
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
    $videoController = new VideoController();
    
    // Get search parameters
    $searchParams = [
        'query' => $_GET['q'] ?? $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? '',
        'duration_min' => isset($_GET['duration_min']) ? intval($_GET['duration_min']) : null,
        'duration_max' => isset($_GET['duration_max']) ? intval($_GET['duration_max']) : null,
        'rating_min' => isset($_GET['rating_min']) ? floatval($_GET['rating_min']) : null,
        'rating_max' => isset($_GET['rating_max']) ? floatval($_GET['rating_max']) : null,
        'uploader' => $_GET['uploader'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'sort_by' => $_GET['sort_by'] ?? 'created_at',
        'sort_order' => $_GET['sort_order'] ?? 'DESC',
        'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 20,
        'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0
    ];
    
    // Validate limit
    if ($searchParams['limit'] > 100) {
        $searchParams['limit'] = 100;
    }
    
    // Perform search
    $result = $videoController->searchVideos($searchParams);
    
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
