<?php
/*
 * PHP Fallback: Video Upload
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

session_start();

// Check authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$config = [
    'supabase_url' => $_ENV['SUPABASE_URL'] ?? '',
    'supabase_service_key' => $_ENV['SUPABASE_SERVICE_KEY'] ?? '',
    'storage_bucket' => $_ENV['SUPABASE_STORAGE_BUCKET'] ?? 'videos',
    'max_file_size' => 52428800 // 50MB
];

try {
    // CSRF validation
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        exit();
    }
    
    // Check file upload
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No video file uploaded or upload error']);
        exit();
    }
    
    $uploadedFile = $_FILES['video'];
    
    // Validate file size
    if ($uploadedFile['size'] > $config['max_file_size']) {
        http_response_code(400);
        echo json_encode(['error' => 'File size too large. Maximum 50MB allowed.']);
        exit();
    }
    
    // Validate file type
    $allowedTypes = ['video/mp4'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only MP4 files are allowed']);
        exit();
    }
    
    // Validate file extension
    $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if ($ext !== 'mp4') {
        http_response_code(400);
        echo json_encode(['error' => 'Only MP4 files are allowed']);
        exit();
    }
    
    // Additional MP4 signature validation
    $handle = fopen($uploadedFile['tmp_name'], 'rb');
    $header = fread($handle, 12);
    fclose($handle);
    $headerHex = bin2hex($header);
    
    if (strpos($headerHex, '667479706d70') === false && 
        strpos($headerHex, '66747970697') === false &&
        strpos($headerHex, '667479704d53') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid MP4 file format']);
        exit();
    }
    
    // Generate UUID for video
    $videoId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $storagePath = $_SESSION['user_id'] . '/' . $videoId . '.mp4';
    
    // Upload to Supabase Storage
    $uploadSuccess = uploadToSupabaseStorage($uploadedFile['tmp_name'], $storagePath, $config);
    
    if (!$uploadSuccess) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload video to storage']);
        exit();
    }
    
    // Save video metadata to database
    $videoData = [
        'id' => $videoId,
        'title' => $title,
        'description' => $description,
        'storage_path' => $storagePath,
        'uploaded_by' => $_SESSION['user_id']
    ];
    
    $video = createVideoRecord($videoData, $config);
    
    if ($video) {
        echo json_encode([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video['id'],
                'title' => $video['title'],
                'description' => $video['description'],
                'created_at' => $video['created_at']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save video metadata']);
    }
    
} catch (Exception $e) {
    error_log('Video upload error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Video upload failed']);
}

function uploadToSupabaseStorage($localPath, $storagePath, $config) {
    $url = $config['supabase_url'] . '/storage/v1/object/' . $config['storage_bucket'] . '/' . $storagePath;
    
    $fileContent = file_get_contents($localPath);
    
    $headers = [
        'Authorization: Bearer ' . $config['supabase_service_key'],
        'Content-Type: video/mp4',
        'Cache-Control: 3600'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

function createVideoRecord($videoData, $config) {
    $url = $config['supabase_url'] . '/rest/v1/videos';
    
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
        CURLOPT_POSTFIELDS => json_encode($videoData),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $videos = json_decode($response, true);
        return $videos[0] ?? null;
    }
    
    return null;
}
?>