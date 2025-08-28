<?php
/*
 * PHP Profile Management: Update User Profile
 * Handles profile updates including password changes and basic info
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

// Start session to check authentication
session_start();

// Check if user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_password':
            $result = updatePassword($input);
            break;
        case 'update_profile':
            $result = updateProfile($input);
            break;
        case 'upload_avatar':
            $result = uploadAvatar($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action specified']);
            exit();
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Profile update failed. Please try again.']);
}

/**
 * Update user password
 */
function updatePassword($input) {
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        return ['error' => 'All password fields are required'];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['error' => 'New passwords do not match'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['error' => 'New password must be at least 6 characters long'];
    }
    
    // Verify current password
    $userId = $_SESSION['user_id'];
    $demoUsers = getDemoUsers();
    $currentUser = null;
    
    foreach ($demoUsers as $user) {
        if ($user['id'] === $userId) {
            $currentUser = $user;
            break;
        }
    }
    
    if (!$currentUser || $currentUser['password'] !== $currentPassword) {
        return ['error' => 'Current password is incorrect'];
    }
    
    // In production, this would update the database
    // For demo, we'll just return success
    return [
        'success' => true,
        'message' => 'Password updated successfully'
    ];
}

/**
 * Update basic profile information
 */
function updateProfile($input) {
    $username = trim($input['username'] ?? '');
    $ageGroup = $input['age_group'] ?? '';
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        return ['error' => 'Username must be at least 3 characters long'];
    }
    
    if (empty($ageGroup)) {
        return ['error' => 'Age group is required'];
    }
    
    // Check if username is already taken by another user
    $userId = $_SESSION['user_id'];
    $demoUsers = getDemoUsers();
    
    foreach ($demoUsers as $user) {
        if ($user['id'] !== $userId && $user['username'] === $username) {
            return ['error' => 'Username is already taken'];
        }
    }
    
    // Update session data
    $_SESSION['username'] = $username;
    $_SESSION['age_group'] = $ageGroup;
    
    // In production, this would update the database
    return [
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'username' => $username,
            'age_group' => $ageGroup
        ]
    ];
}

/**
 * Upload profile avatar
 */
function uploadAvatar($input) {
    // For demo purposes, we'll simulate avatar upload
    // In production, this would handle file upload to Supabase storage
    
    $avatarUrl = $input['avatar_url'] ?? '';
    
    if (empty($avatarUrl)) {
        return ['error' => 'Avatar URL is required'];
    }
    
    // Validate URL format
    if (!filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
        return ['error' => 'Invalid avatar URL format'];
    }
    
    // Store avatar URL in session (in production, this would go to database)
    $_SESSION['avatar_url'] = $avatarUrl;
    
    return [
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar_url' => $avatarUrl
    ];
}

/**
 * Get demo users (same as in auth_login.php)
 */
function getDemoUsers() {
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
?>
