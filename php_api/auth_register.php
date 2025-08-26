<?php
/*
 * PHP Fallback: User Registration
 * This is a fallback implementation for PHP-only hosting
 * Primary implementation is Node.js - use this only if Node.js unavailable
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

// Load configuration
$config = [
    'supabase_url' => $_ENV['SUPABASE_URL'] ?? '',
    'supabase_service_key' => $_ENV['SUPABASE_SERVICE_KEY'] ?? '',
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $age_group = $input['age_group'] ?? null;
    $csrf_token = $input['csrf_token'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, email and password are required']);
        exit();
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }
    
    // CSRF validation
    session_start();
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
    
    // Check if user exists via Supabase REST API
    $existingUser = checkUserExists($email, $username, $config);
    if ($existingUser) {
        http_response_code(400);
        echo json_encode(['error' => 'User with this email or username already exists']);
        exit();
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate UUID (simple method)
    $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Create user via Supabase REST API
    $userData = [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => 'user',
        'age_group' => $age_group
    ];
    
    $user = createUser($userData, $config);
    
    if ($user) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        
        session_regenerate_id(true);
        
        echo json_encode([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'age_group' => $user['age_group']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed']);
    }
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}

function checkUserExists($email, $username, $config) {
    $url = $config['supabase_url'] . '/rest/v1/users?or=(email.eq.' . urlencode($email) . ',username.eq.' . urlencode($username) . ')';
    
    $headers = [
        'apikey: ' . $config['supabase_service_key'],
        'Authorization: Bearer ' . $config['supabase_service_key']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $users = json_decode($response, true);
        return !empty($users);
    }
    
    return false;
}

function createUser($userData, $config) {
    $url = $config['supabase_url'] . '/rest/v1/users';
    
    $headers = [
        'apikey: ' . $config['supabase_service_key'],
        'Authorization: Bearer ' . $config['supabase_service_key'],
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($userData),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $users = json_decode($response, true);
        return $users[0] ?? null;
    }
    
    return null;
}
?>