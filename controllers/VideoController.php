<?php
/**
 * Video Controller for PHP Backend
 * Handles video operations like upload, view, like, and feedback
 */

require_once __DIR__ . '/../models/VideoModel.php';
require_once __DIR__ . '/../models/FeedbackModel.php';
require_once __DIR__ . '/../models/LikeModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/supabase.php';

class VideoController {
    private $videoModel;
    private $feedbackModel;
    private $likeModel;
    private $authController;
    private $supabase;
    
    public function __construct() {
        $this->videoModel = new VideoModel();
        $this->feedbackModel = new FeedbackModel();
        $this->likeModel = new LikeModel();
        $this->authController = new AuthController();
        $this->supabase = new SupabaseClient();
    }
    
    /**
     * Upload video
     */
    public function uploadVideo($data, $videoFile) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Validate required fields
            if (empty($data['title']) || empty($data['description']) || empty($data['category'])) {
                throw new Exception("Title, description, and category are required");
            }
            
            // Validate video file
            if (!$videoFile || $videoFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Video file upload failed");
            }
            
            // Check file size (50MB limit)
            $maxSize = 50 * 1024 * 1024; // 50MB
            if ($videoFile['size'] > $maxSize) {
                throw new Exception("Video file size exceeds 50MB limit");
            }
            
            // Check file type
            $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
            if (!in_array($videoFile['type'], $allowedTypes)) {
                throw new Exception("Invalid video file type. Allowed: MP4, AVI, MOV, WMV");
            }
            
            // Generate unique filename
            $extension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $storagePath = 'videos/' . $currentUser['id'] . '/' . $filename;
            
            // Upload to Supabase Storage
            $uploadedPath = $this->supabase->uploadFile(
                $videoFile['tmp_name'], 
                $storagePath, 
                $videoFile['type']
            );
            
            if (!$uploadedPath) {
                throw new Exception("Failed to upload video to storage");
            }
            
            // Create video record
            $videoData = [
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'category' => $data['category'],
                'user_id' => $currentUser['id'],
                'storage_path' => $uploadedPath,
                'file_size' => $videoFile['size'],
                'duration' => $data['duration'] ?? null,
                'thumbnail_path' => $data['thumbnail_path'] ?? null
            ];
            
            $video = $this->videoModel->create($videoData);
            
            return [
                'success' => true,
                'message' => 'Video uploaded successfully',
                'video' => $video
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video by ID
     */
    public function getVideo($id) {
        try {
            $video = $this->videoModel->findById($id);
            
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            // Check if video is approved (unless user is owner, judge, or admin)
            $currentUser = $this->authController->getCurrentUser();
            if (!$video['is_approved'] && 
                (!$currentUser || 
                 ($currentUser['id'] != $video['user_id'] && 
                  !$this->authController->isJudge()))) {
                throw new Exception("Video not available");
            }
            
            // Increment view count
            $this->videoModel->incrementViewCount($id);
            
            // Get feedback statistics
            $feedbackStats = $this->feedbackModel->getVideoStats($id);
            
            // Check if current user has liked the video
            $userLiked = false;
            if ($currentUser) {
                $userLiked = $this->likeModel->hasUserLiked($currentUser['id'], $id);
            }
            
            // Get signed URL for video playback
            $videoUrl = $this->supabase->getSignedUrl($video['storage_path']);
            
            $video['video_url'] = $videoUrl;
            $video['feedback_stats'] = $feedbackStats;
            $video['user_liked'] = $userLiked;
            
            return [
                'success' => true,
                'video' => $video
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video list with filters
     */
    public function getVideoList($options = []) {
        try {
            // Set default options
            $defaultOptions = [
                'limit' => 12,
                'offset' => 0,
                'search' => '',
                'category' => '',
                'order_by' => 'created_at',
                'order_dir' => 'DESC'
            ];
            
            $options = array_merge($defaultOptions, $options);
            
            // Only show approved videos for public access
            $options['is_approved'] = true;
            
            // Check if user is judge/admin (can see pending videos)
            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser && $this->authController->isJudge()) {
                if (isset($options['show_pending']) && $options['show_pending']) {
                    unset($options['is_approved']);
                }
            }
            
            $result = $this->videoModel->findAll($options);
            
            // Add signed URLs for videos
            foreach ($result['videos'] as &$video) {
                $video['video_url'] = $this->supabase->getSignedUrl($video['storage_path']);
                if ($video['thumbnail_path']) {
                    $video['thumbnail_url'] = $this->supabase->getPublicUrl($video['thumbnail_path']);
                }
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Search videos
     */
    public function searchVideos($query, $options = []) {
        try {
            if (empty($query)) {
                throw new Exception("Search query is required");
            }
            
            $result = $this->videoModel->search($query, $options);
            
            // Add signed URLs for videos
            foreach ($result['videos'] as &$video) {
                $video['video_url'] = $this->supabase->getSignedUrl($video['storage_path']);
                if ($video['thumbnail_path']) {
                    $video['thumbnail_url'] = $this->supabase->getPublicUrl($video['thumbnail_path']);
                }
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Toggle video like
     */
    public function toggleLike($videoId) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Check if video exists and is approved
            $video = $this->videoModel->findById($videoId);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            if (!$video['is_approved']) {
                throw new Exception("Cannot like unapproved video");
            }
            
            // Toggle like
            $result = $this->likeModel->toggleLike($currentUser['id'], $videoId);
            
            return [
                'success' => true,
                'message' => "Video {$result['action']}",
                'liked' => $result['liked']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add feedback to video
     */
    public function addFeedback($videoId, $data) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Validate required fields
            if (!isset($data['rating']) || empty($data['rating'])) {
                throw new Exception("Rating is required");
            }
            
            // Validate rating
            $rating = intval($data['rating']);
            if ($rating < 1 || $rating > 5) {
                throw new Exception("Rating must be between 1 and 5");
            }
            
            // Check if video exists and is approved
            $video = $this->videoModel->findById($videoId);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            if (!$video['is_approved']) {
                throw new Exception("Cannot add feedback to unapproved video");
            }
            
            // Check if user has already given feedback
            if ($this->feedbackModel->hasUserFeedback($currentUser['id'], $videoId)) {
                throw new Exception("You have already given feedback for this video");
            }
            
            // Create feedback
            $feedbackData = [
                'user_id' => $currentUser['id'],
                'video_id' => $videoId,
                'rating' => $rating,
                'comment' => $data['comment'] ?? null
            ];
            
            $feedback = $this->feedbackModel->create($feedbackData);
            
            return [
                'success' => true,
                'message' => 'Feedback added successfully',
                'feedback' => $feedback
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video feedback
     */
    public function getVideoFeedback($videoId, $options = []) {
        try {
            // Check if video exists
            $video = $this->videoModel->findById($videoId);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            // Check if video is approved (unless user is owner, judge, or admin)
            $currentUser = $this->authController->getCurrentUser();
            if (!$video['is_approved'] && 
                (!$currentUser || 
                 ($currentUser['id'] != $video['user_id'] && 
                  !$this->authController->isJudge()))) {
                throw new Exception("Video not available");
            }
            
            $result = $this->feedbackModel->getByVideoId($videoId, $options);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update video
     */
    public function updateVideo($videoId, $data) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Check if video exists
            $video = $this->videoModel->findById($videoId);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            // Check if user owns the video or is admin
            if ($currentUser['id'] != $video['user_id'] && !$this->authController->isAdmin()) {
                throw new Exception("Unauthorized to update this video");
            }
            
            // Validate data
            $updateData = [];
            
            if (isset($data['title'])) {
                $title = trim($data['title']);
                if (empty($title)) {
                    throw new Exception("Title cannot be empty");
                }
                $updateData['title'] = $title;
            }
            
            if (isset($data['description'])) {
                $description = trim($data['description']);
                if (empty($description)) {
                    throw new Exception("Description cannot be empty");
                }
                $updateData['description'] = $description;
            }
            
            if (isset($data['category'])) {
                $updateData['category'] = $data['category'];
            }
            
            if (empty($updateData)) {
                throw new Exception("No valid data to update");
            }
            
            // Update video
            $updatedVideo = $this->videoModel->update($videoId, $updateData);
            
            return [
                'success' => true,
                'message' => 'Video updated successfully',
                'video' => $updatedVideo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete video
     */
    public function deleteVideo($videoId) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Check if video exists
            $video = $this->videoModel->findById($videoId);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            // Check if user owns the video or is admin
            if ($currentUser['id'] != $video['user_id'] && !$this->authController->isAdmin()) {
                throw new Exception("Unauthorized to delete this video");
            }
            
            // Delete video
            $this->videoModel->delete($videoId, $currentUser['id']);
            
            return [
                'success' => true,
                'message' => 'Video deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video categories
     */
    public function getCategories() {
        try {
            $categories = $this->videoModel->getCategories();
            
            return [
                'success' => true,
                'categories' => $categories
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user's videos
     */
    public function getUserVideos($userId, $options = []) {
        try {
            // Check authentication
            $this->authController->requireAuth();
            $currentUser = $this->authController->getCurrentUser();
            
            // Check if user is viewing their own videos or is admin
            if ($currentUser['id'] != $userId && !$this->authController->isAdmin()) {
                throw new Exception("Unauthorized to view these videos");
            }
            
            $options['user_id'] = $userId;
            $result = $this->videoModel->findAll($options);
            
            // Add signed URLs for videos
            foreach ($result['videos'] as &$video) {
                $video['video_url'] = $this->supabase->getSignedUrl($video['storage_path']);
                if ($video['thumbnail_path']) {
                    $video['thumbnail_url'] = $this->supabase->getPublicUrl($video['thumbnail_path']);
                }
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video statistics
     */
    public function getVideoStats() {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $stats = $this->videoModel->getStats();
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
