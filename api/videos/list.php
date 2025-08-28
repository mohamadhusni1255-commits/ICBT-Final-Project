<?php
/**
 * Video List API Endpoint
 * Returns list of videos with filters and pagination
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
    // Initialize video controller
    $videoController = new VideoController();
    
    // Get query parameters
    $options = [
        'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 12,
        'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
        'search' => $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? '',
        'order_by' => $_GET['order_by'] ?? 'created_at',
        'order_dir' => $_GET['order_dir'] ?? 'DESC'
    ];
    
    // Validate limit
    if ($options['limit'] > 50) {
        $options['limit'] = 50;
    }
    
    // Get video list
    $result = $videoController->getVideoList($options);
    
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
