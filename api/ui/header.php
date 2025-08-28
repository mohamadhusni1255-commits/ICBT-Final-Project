<?php
require_once '../../config/database.php';
require_once '../../controllers/UIController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Initialize database and UI controller
    $database = new Database();
    $uiController = new UIController($database);
    
    // Get header data based on user authentication status
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']);
    $userRole = $isLoggedIn ? $_SESSION['user_role'] : null;
    
    // Get header configuration
    $headerData = $uiController->getHeaderData($isLoggedIn, $userRole);
    
    if ($headerData['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'header' => $headerData['header'],
            'navigation' => $headerData['navigation'],
            'user_menu' => $headerData['user_menu'],
            'is_logged_in' => $isLoggedIn,
            'user_role' => $userRole
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $headerData['error']
        ]);
    }

} catch (Exception $e) {
    // Log error
    error_log("Header API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error. Please try again later.'
    ]);
}
?>
