<?php
require_once '../../config/database.php';
require_once '../../controllers/AdminController.php';
require_once '../../models/UserModel.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit();
    }

    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            exit();
        }
    }

    // Validate role
    $allowed_roles = ['user', 'judge', 'admin'];
    if (!in_array($input['role'], $allowed_roles)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role. Must be user, judge, or admin']);
        exit();
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }

    // Validate password strength
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters long']);
        exit();
    }

    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $input['username'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be 3-30 characters, letters, numbers and underscores only']);
        exit();
    }

    // Initialize database and admin controller
    $database = new Database();
    $adminController = new AdminController($database);
    
    // Check if current user is admin
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin role required']);
        exit();
    }

    // Create user data array
    $userData = [
        'username' => trim($input['username']),
        'email' => trim($input['email']),
        'password' => $input['password'],
        'role' => $input['role'],
        'age_group' => isset($input['age_group']) ? trim($input['age_group']) : null,
        'created_by' => $_SESSION['user_id'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Attempt to create user
    $result = $adminController->createUser($userData);
    
    if ($result['success']) {
        // Log successful user creation
        error_log("Admin user {$_SESSION['user_id']} created new {$input['role']} user: {$input['username']} ({$input['email']})");
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => "User created successfully with role: {$input['role']}",
            'user' => [
                'id' => $result['user_id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'age_group' => $userData['age_group'],
                'created_at' => $userData['created_at']
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }

} catch (Exception $e) {
    // Log error
    error_log("Admin user creation error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error. Please try again later.'
    ]);
}
?>
