<?php
/*
 * PHP Fallback: Submit Feedback
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

// Check authentication and role
if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['judge', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Judge or admin access required']);
    exit();
}

$config = [
    'supabase_url' => $_ENV['SUPABASE_URL'] ?? '',
    'supabase_service_key' => $_ENV['SUPABASE_SERVICE_KEY'] ?? ''
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $video_id = $input['video_id'] ?? '';
    $score_voice = (int)($input['score_voice'] ?? 0);
    $score_creativity = (int)($input['score_creativity'] ?? 0);
    $score_presentation = (int)($input['score_presentation'] ?? 0);
    $comments = trim($input['comments'] ?? '');
    $csrf_token = $input['csrf_token'] ?? '';
    
    // Validation
    if (empty($video_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Video ID is required']);
        exit();
    }
    
    $scores = [$score_voice, $score_creativity, $score_presentation];
    foreach ($scores as $score) {
        if ($score < 0 || $score > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Scores must be between 0 and 10']);
            exit();
        }
    }
    
    // CSRF validation
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
    
    // Check if video exists
    if (!videoExists($video_id, $config)) {
        http_response_code(404);
        echo json_encode(['error' => 'Video not found']);
        exit();
    }
    
    // Check if feedback already exists
    $existingFeedback = getFeedbackByVideoAndJudge($video_id, $_SESSION['user_id'], $config);
    
    if ($existingFeedback) {
        // Update existing feedback
        $updated = updateFeedback($existingFeedback['id'], [
            'score_voice' => $score_voice,
            'score_creativity' => $score_creativity,
            'score_presentation' => $score_presentation,
            'comments' => $comments
        ], $config);
        
        if ($updated) {
            echo json_encode(['message' => 'Feedback updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update feedback']);
        }
    } else {
        // Create new feedback
        $feedbackId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $feedbackData = [
            'id' => $feedbackId,
            'video_id' => $video_id,
            'judge_id' => $_SESSION['user_id'],
            'score_voice' => $score_voice,
            'score_creativity' => $score_creativity,
            'score_presentation' => $score_presentation,
            'comments' => $comments
        ];
        
        $created = createFeedback($feedbackData, $config);
        
        if ($created) {
            echo json_encode(['message' => 'Feedback submitted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create feedback']);
        }
    }
    
} catch (Exception $e) {
    error_log('Feedback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process feedback']);
}

function videoExists($videoId, $config) {
    $url = $config['supabase_url'] . '/rest/v1/videos?id=eq.' . urlencode($videoId);
    
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
        $videos = json_decode($response, true);
        return !empty($videos);
    }
    
    return false;
}

function getFeedbackByVideoAndJudge($videoId, $judgeId, $config) {
    $url = $config['supabase_url'] . '/rest/v1/feedback?video_id=eq.' . urlencode($videoId) . '&judge_id=eq.' . urlencode($judgeId);
    
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
        $feedback = json_decode($response, true);
        return $feedback[0] ?? null;
    }
    
    return null;
}

function createFeedback($feedbackData, $config) {
    $url = $config['supabase_url'] . '/rest/v1/feedback';
    
    $headers = [
        'apikey: ' . $config['supabase_service_key'],
        'Authorization: Bearer ' . $config['supabase_service_key'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($feedbackData),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 201;
}

function updateFeedback($feedbackId, $updateData, $config) {
    $url = $config['supabase_url'] . '/rest/v1/feedback?id=eq.' . urlencode($feedbackId);
    
    $headers = [
        'apikey: ' . $config['supabase_service_key'],
        'Authorization: Bearer ' . $config['supabase_service_key'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($updateData),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
?>