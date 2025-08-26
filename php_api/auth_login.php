<?php
/*
 * PHP Fallback: User Login
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
    'database_url' => $_ENV['SUPABASE_DB_URL'] ?? ''
];

if (empty($config['supabase_url']) || empty($config['supabase_service_key'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $csrf_token = $input['csrf_token'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        exit();
    }
    
    // CSRF validation (simplified for fallback)
    session_start();
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
    
    // Option 1: Use Supabase REST API
    $user = authenticateWithSupabaseRest($email, $password, $config);
    
    // Option 2: Direct PostgreSQL connection (if preferred)
    // $user = authenticateWithPostgres($email, $password, $config);
    
    if ($user) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        echo json_encode([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'age_group' => $user['age_group']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login failed']);
}

/**
 * Authenticate using Supabase REST API
 */
function authenticateWithSupabaseRest($email, $password, $config) {
    // Query user by email
    $url = $config['supabase_url'] . '/rest/v1/users?email=eq.' . urlencode($email);
    
    $headers = [
        'apikey: ' . $config['supabase_service_key'],
        'Authorization: Bearer ' . $config['supabase_service_key'],
        'Content-Type: application/json'
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
    
    if ($httpCode !== 200) {
        return false;
    }
    
    $users = json_decode($response, true);
    if (empty($users)) {
        return false;
    }
    
    $user = $users[0];
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        return $user;
    }
    
    return false;
}

/**
 * Authenticate using direct PostgreSQL connection
 */
function authenticateWithPostgres($email, $password, $config) {
    // Extract connection details from DATABASE_URL
    $dbUrl = parse_url($config['database_url']);
    
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $dbUrl['host'],
        $dbUrl['port'] ?? 5432,
        ltrim($dbUrl['path'], '/')
    );
    
    $pdo = new PDO($dsn, $dbUrl['user'], $dbUrl['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Prepared statement to prevent SQL injection
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    
    return false;
}
?>