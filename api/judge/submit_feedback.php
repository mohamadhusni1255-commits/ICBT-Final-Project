<?php
require_once '../../config/database.php';
require_once '../../controllers/JudgeController.php';
require_once '../../models/FeedbackModel.php';

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
    $required_fields = ['video_id', 'voice_score', 'creativity_score', 'presentation_score', 'comments'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            exit();
        }
    }

    // Validate video_id
    if (!is_numeric($input['video_id']) || $input['video_id'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid video ID']);
        exit();
    }

    // Validate scores (1-10)
    $score_fields = ['voice_score', 'creativity_score', 'presentation_score'];
    foreach ($score_fields as $score_field) {
        $score = intval($input[$score_field]);
        if ($score < 1 || $score > 10) {
            http_response_code(400);
            echo json_encode(['error' => "{$score_field} must be between 1 and 10"]);
            exit();
        }
    }

    // Validate comments
    if (empty(trim($input['comments']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Comments are required']);
        exit();
    }

    // Initialize database and judge controller
    $database = new Database();
    $judgeController = new JudgeController($database);
    
    // Check if current user is judge or admin
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
        !in_array($_SESSION['user_role'], ['judge', 'admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Judge or Admin role required']);
        exit();
    }

    // Prepare feedback data
    $feedbackData = [
        'video_id' => intval($input['video_id']),
        'judge_id' => $_SESSION['user_id'],
        'voice_score' => intval($input['voice_score']),
        'creativity_score' => intval($input['creativity_score']),
        'presentation_score' => intval($input['presentation_score']),
        'comments' => trim($input['comments']),
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // Calculate average score
    $feedbackData['average_score'] = round(
        ($feedbackData['voice_score'] + $feedbackData['creativity_score'] + $feedbackData['presentation_score']) / 3, 
        2
    );

    // Attempt to submit feedback
    $result = $judgeController->submitFeedback($feedbackData);
    
    if ($result['success']) {
        // Log successful feedback submission
        error_log("Judge {$_SESSION['user_id']} submitted feedback for video {$input['video_id']} with average score {$feedbackData['average_score']}");
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'feedback' => [
                'id' => $result['feedback_id'],
                'video_id' => $feedbackData['video_id'],
                'judge_id' => $feedbackData['judge_id'],
                'voice_score' => $feedbackData['voice_score'],
                'creativity_score' => $feedbackData['creativity_score'],
                'presentation_score' => $feedbackData['presentation_score'],
                'average_score' => $feedbackData['average_score'],
                'comments' => $feedbackData['comments'],
                'submitted_at' => $feedbackData['submitted_at']
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
    error_log("Judge feedback submission error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error. Please try again later.'
    ]);
}
?>
