<?php
/**
 * Video Upload API Endpoint
 * Handles video file uploads
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../controllers/VideoController.php';

try {
    // Check if video file was uploaded
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Video file upload failed');
    }
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $duration = $_POST['duration'] ?? null;
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($category)) {
        throw new Exception('Title, description, and category are required');
    }
    
    // Initialize video controller
    $videoController = new VideoController();
    
    // Prepare data
    $videoData = [
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'duration' => $duration
    ];
    
    // Upload video
    $result = $videoController->uploadVideo($videoData, $_FILES['video']);
    
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
