<?php
require_once '../../config/database.php';
require_once '../../controllers/JudgeController.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="judge_scores_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo "Method not allowed";
    exit();
}

try {
    // Initialize database and judge controller
    $database = new Database();
    $judgeController = new JudgeController($database);
    
    // Check if current user is judge or admin
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
        !in_array($_SESSION['user_role'], ['judge', 'admin'])) {
        http_response_code(403);
        echo "Access denied. Judge or Admin role required";
        exit();
    }

    // Get filter parameters
    $video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : null;
    $judge_id = isset($_GET['judge_id']) ? intval($_GET['judge_id']) : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    $min_score = isset($_GET['min_score']) ? floatval($_GET['min_score']) : null;
    $max_score = isset($_GET['max_score']) ? floatval($_GET['max_score']) : null;

    // Get scores data
    $scores = $judgeController->getScoresForExport([
        'video_id' => $video_id,
        'judge_id' => $judge_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'min_score' => $min_score,
        'max_score' => $max_score
    ]);

    if (!$scores['success']) {
        http_response_code(400);
        echo "Error: " . $scores['error'];
        exit();
    }

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write CSV headers
    fputcsv($output, [
        'Video ID',
        'Video Title',
        'Uploader',
        'Judge ID',
        'Judge Username',
        'Voice Score',
        'Creativity Score',
        'Presentation Score',
        'Average Score',
        'Comments',
        'Submitted Date'
    ]);

    // Write data rows
    foreach ($scores['data'] as $score) {
        fputcsv($output, [
            $score['video_id'],
            $score['video_title'] ?? 'N/A',
            $score['uploader_username'] ?? 'N/A',
            $score['judge_id'],
            $score['judge_username'] ?? 'N/A',
            $score['voice_score'],
            $score['creativity_score'],
            $score['presentation_score'],
            $score['average_score'],
            $score['comments'],
            $score['submitted_at']
        ]);
    }

    fclose($output);

    // Log successful export
    error_log("Judge {$_SESSION['user_id']} exported scores CSV with " . count($scores['data']) . " records");

} catch (Exception $e) {
    // Log error
    error_log("Judge scores export error: " . $e->getMessage());
    
    http_response_code(500);
    echo "Internal server error. Please try again later.";
}
?>
