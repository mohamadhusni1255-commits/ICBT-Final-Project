<?php
/**
 * Admin Controller for PHP Backend
 * Handles admin operations like user management, video approval, and system statistics
 */

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/VideoModel.php';
require_once __DIR__ . '/../models/FeedbackModel.php';
require_once __DIR__ . '/../models/LikeModel.php';
require_once __DIR__ . '/../models/CompetitionModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';

class AdminController {
    private $userModel;
    private $videoModel;
    private $feedbackModel;
    private $likeModel;
    private $competitionModel;
    private $authController;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->videoModel = new VideoModel();
        $this->feedbackModel = new FeedbackModel();
        $this->likeModel = new LikeModel();
        $this->competitionModel = new CompetitionModel();
        $this->authController = new AuthController();
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Get user statistics
            $userStats = $this->userModel->getStats();
            
            // Get video statistics
            $videoStats = $this->videoModel->getStats();
            
            // Get feedback statistics
            $feedbackStats = $this->feedbackModel->getOverallStats();
            
            // Get like statistics
            $likeStats = $this->likeModel->getStats();
            
            // Calculate additional metrics
            $totalViews = $videoStats['total_views'] ?? 0;
            $totalVideos = $videoStats['total_videos'] ?? 0;
            $avgViewsPerVideo = $totalVideos > 0 ? round($totalViews / $totalVideos, 2) : 0;
            
            $totalUsers = $userStats['total_users'] ?? 0;
            $activeUsers = $userStats['active_users'] ?? 0;
            $userEngagement = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0;
            
            $stats = [
                'users' => $userStats,
                'videos' => $videoStats,
                'feedback' => $feedbackStats,
                'likes' => $likeStats,
                'metrics' => [
                    'avg_views_per_video' => $avgViewsPerVideo,
                    'user_engagement_rate' => $userEngagement,
                    'total_platform_activity' => $totalViews + ($likeStats['total_likes'] ?? 0) + ($feedbackStats['total_feedback'] ?? 0)
                ]
            ];
            
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
    
    /**
     * Get user management data
     */
    public function getUserManagement($options = []) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->userModel->findAll($options);
            
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
     * Create new user
     */
    public function createUser($data) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->userModel->create($data);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $data) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->userModel->update($userId, $data);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->userModel->delete($userId);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get video management data
     */
    public function getVideoManagement($options = []) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->videoModel->findAll($options);
            
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
     * Approve videos
     */
    public function approveVideos($videoIds) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $approvedCount = 0;
            foreach ($videoIds as $videoId) {
                $result = $this->videoModel->approve($videoId);
                if ($result['success']) {
                    $approvedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "{$approvedCount} videos approved successfully",
                'approved_count' => $approvedCount
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject videos
     */
    public function rejectVideos($videoIds, $reason = '') {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $rejectedCount = 0;
            foreach ($videoIds as $videoId) {
                $result = $this->videoModel->reject($videoId, $reason);
                if ($result['success']) {
                    $rejectedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "{$rejectedCount} videos rejected successfully",
                'rejected_count' => $rejectedCount
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete videos
     */
    public function deleteVideos($videoIds) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $deletedCount = 0;
            foreach ($videoIds as $videoId) {
                $result = $this->videoModel->delete($videoId);
                if ($result['success']) {
                    $deletedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "{$deletedCount} videos deleted successfully",
                'deleted_count' => $deletedCount
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
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->videoModel->update($videoId, $data);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete single video
     */
    public function deleteVideo($videoId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->videoModel->delete($videoId);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get competition management data
     */
    public function getCompetitionManagement($options = []) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->competitionModel->findAll($options);
            
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
     * Create new competition
     */
    public function createCompetition($data) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->competitionModel->create($data);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update competition
     */
    public function updateCompetition($competitionId, $data) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->competitionModel->update($competitionId, $data);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete competition
     */
    public function deleteCompetition($competitionId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $result = $this->competitionModel->delete($competitionId);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user role
     */
    public function updateUserRole($userId, $newRole) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Validate role
            $validRoles = ['user', 'judge', 'admin'];
            if (!in_array($newRole, $validRoles)) {
                throw new Exception("Invalid role");
            }
            
            // Check if trying to change own role
            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser['id'] == $userId) {
                throw new Exception("Cannot change your own role");
            }
            
            // Update user role
            $updatedUser = $this->userModel->updateRole($userId, $newRole);
            
            return [
                'success' => true,
                'message' => "User role updated to {$newRole}",
                'user' => $updatedUser
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Toggle user active status
     */
    public function toggleUserStatus($userId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Check if trying to change own status
            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser['id'] == $userId) {
                throw new Exception("Cannot change your own status");
            }
            
            // Toggle user status
            $newStatus = $this->userModel->toggleActive($userId);
            
            $statusText = $newStatus ? 'activated' : 'deactivated';
            
            return [
                'success' => true,
                'message' => "User {$statusText} successfully",
                'is_active' => $newStatus
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Check if trying to delete self
            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser['id'] == $userId) {
                throw new Exception("Cannot delete your own account");
            }
            
            // Delete user
            $this->userModel->delete($userId);
            
            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pending video approvals
     */
    public function getPendingVideos($options = []) {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $result = $this->videoModel->getPendingApproval($options);
            
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
     * Approve or reject video
     */
    public function updateVideoApproval($videoId, $isApproved, $reason = null) {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $currentUser = $this->authController->getCurrentUser();
            
            // Update video approval status
            $updatedVideo = $this->videoModel->updateApprovalStatus($videoId, $isApproved, $currentUser['id']);
            
            $statusText = $isApproved ? 'approved' : 'rejected';
            
            return [
                'success' => true,
                'message' => "Video {$statusText} successfully",
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
     * Get video management data
     */
    public function getVideoManagement($options = []) {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $result = $this->videoModel->findAll($options);
            
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
     * Delete video (admin only)
     */
    public function deleteVideo($videoId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Delete video
            $this->videoModel->delete($videoId, null);
            
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
     * Get feedback management data
     */
    public function getFeedbackManagement($options = []) {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $result = $this->feedbackModel->findAll($options);
            
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
     * Delete feedback (admin only)
     */
    public function deleteFeedback($feedbackId) {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            // Delete feedback
            $this->feedbackModel->delete($feedbackId, null);
            
            return [
                'success' => true,
                'message' => 'Feedback deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get system health status
     */
    public function getSystemHealth() {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $health = [
                'database' => 'healthy',
                'storage' => 'healthy',
                'users' => 'healthy',
                'videos' => 'healthy',
                'feedback' => 'healthy'
            ];
            
            // Check database connection
            try {
                $this->userModel->getStats();
            } catch (Exception $e) {
                $health['database'] = 'error';
            }
            
            // Check user statistics
            try {
                $userStats = $this->userModel->getStats();
                if ($userStats['total_users'] > 10000) {
                    $health['users'] = 'warning';
                }
            } catch (Exception $e) {
                $health['users'] = 'error';
            }
            
            // Check video statistics
            try {
                $videoStats = $this->videoModel->getStats();
                if ($videoStats['pending_videos'] > 100) {
                    $health['videos'] = 'warning';
                }
            } catch (Exception $e) {
                $health['videos'] = 'error';
            }
            
            // Check feedback statistics
            try {
                $feedbackStats = $this->feedbackModel->getOverallStats();
                if ($feedbackStats['total_feedback'] > 50000) {
                    $health['feedback'] = 'warning';
                }
            } catch (Exception $e) {
                $health['feedback'] = 'error';
            }
            
            // Calculate overall health
            $overallHealth = 'healthy';
            if (in_array('error', $health)) {
                $overallHealth = 'error';
            } elseif (in_array('warning', $health)) {
                $overallHealth = 'warning';
            }
            
            return [
                'success' => true,
                'health' => $health,
                'overall_health' => $overallHealth,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up orphaned data
     */
    public function cleanupOrphanedData() {
        try {
            // Check if user is admin
            $this->authController->requireAdmin();
            
            $cleanupResults = [];
            
            // Clean up orphaned likes
            try {
                $deletedLikes = $this->likeModel->cleanupOrphanedLikes();
                $cleanupResults['orphaned_likes'] = $deletedLikes;
            } catch (Exception $e) {
                $cleanupResults['orphaned_likes'] = 'error: ' . $e->getMessage();
            }
            
            // Clean up orphaned feedback
            try {
                // This would require a method in FeedbackModel to clean up orphaned feedback
                $cleanupResults['orphaned_feedback'] = 'not implemented';
            } catch (Exception $e) {
                $cleanupResults['orphaned_feedback'] = 'error: ' . $e->getMessage();
            }
            
            return [
                'success' => true,
                'message' => 'Cleanup completed',
                'results' => $cleanupResults
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 20) {
        try {
            // Check if user is admin or judge
            $this->authController->requireJudge();
            
            $activity = [];
            
            // Get recent videos
            try {
                $recentVideos = $this->videoModel->findAll(['limit' => $limit, 'order_by' => 'created_at']);
                $activity['videos'] = $recentVideos['videos'];
            } catch (Exception $e) {
                $activity['videos'] = [];
            }
            
            // Get recent feedback
            try {
                $recentFeedback = $this->feedbackModel->getRecent($limit);
                $activity['feedback'] = $recentFeedback;
            } catch (Exception $e) {
                $activity['feedback'] = [];
            }
            
            // Get recent likes
            try {
                $recentLikes = $this->likeModel->getRecentLikes($limit);
                $activity['likes'] = $recentLikes;
            } catch (Exception $e) {
                $activity['likes'] = [];
            }
            
            return [
                'success' => true,
                'activity' => $activity
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
