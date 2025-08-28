<?php
/*
 * PHP Authentication: User Login
 * Connects to Supabase and handles user authentication
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

// Start session for authentication
session_start();

// Load configuration
$config = [
    'supabase_url' => $_ENV['SUPABASE_URL'] ?? 'https://demo-project.supabase.co',
    'supabase_service_key' => $_ENV['SUPABASE_SERVICE_KEY'] ?? 'demo-service-key',
    'database_url' => $_ENV['SUPABASE_DB_URL'] ?? ''
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }
    
    // For demo purposes, check against localStorage-simulated database
    // In production, this would connect to Supabase
    $user = authenticateUser($email, $password);
    
    if ($user) {
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['age_group'] = $user['age_group'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'age_group' => $user['age_group']
            ],
            'redirect' => getRedirectUrl($user['role'])
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login failed. Please try again.']);
}

/**
 * Authenticate user against demo database
 * In production, this would connect to Supabase
 */
function authenticateUser($email, $password) {
    // Check if user exists in our demo database
    $demoUsers = getDemoUsers();
    
    foreach ($demoUsers as $user) {
        if ($user['email'] === $email) {
            // In production, verify password hash
            // For demo, check plain text password
            if ($user['password'] === $password) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'age_group' => $user['age_group']
                ];
            }
        }
    }
    
    return false;
}

/**
 * Get demo users from localStorage (simulated database)
 * In production, this would query Supabase
 */
function getDemoUsers() {
    // For demo purposes, return some test users
    return [
        [
            'id' => 'admin-001',
            'username' => 'admin',
            'email' => 'admin@talentup.lk',
            'password' => 'admin123',
            'role' => 'admin',
            'age_group' => '31-40'
        ],
        [
            'id' => 'judge-001',
            'username' => 'judge',
            'email' => 'judge@talentup.lk',
            'password' => 'judge123',
            'role' => 'judge',
            'age_group' => '41+'
        ],
        [
            'id' => 'user-001',
            'username' => 'user',
            'email' => 'user@talentup.lk',
            'password' => 'user123',
            'role' => 'user',
            'age_group' => '20-30'
        ]
    ];
}

/**
 * Get redirect URL based on user role
 */
function getRedirectUrl($role) {
    switch ($role) {
        case 'admin':
            return 'admin_panel.html';
        case 'judge':
            return 'judge_panel.html';
        default:
            return 'dashboard_user.html';
    }
}
?>