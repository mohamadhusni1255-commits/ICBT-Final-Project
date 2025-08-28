<?php
/**
 * Feedback Model for PHP Backend
 * Handles feedback and rating operations with Supabase database
 */

require_once __DIR__ . '/../config/database.php';

class FeedbackModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new feedback
     */
    public function create($feedbackData) {
        try {
            // Set default values
            $feedbackData['created_at'] = date('Y-m-d H:i:s');
            $feedbackData['updated_at'] = date('Y-m-d H:i:s');
            
            // Validate rating
            if (isset($feedbackData['rating'])) {
                $feedbackData['rating'] = max(1, min(5, intval($feedbackData['rating'])));
            }
            
            $feedbackId = $this->db->insert('feedback', $feedbackData);
            
            return $this->findById($feedbackId);
            
        } catch (Exception $e) {
            error_log("Feedback creation failed: " . $e->getMessage());
            throw new Exception("Failed to create feedback");
        }
    }
    
    /**
     * Find feedback by ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT f.*, u.username, u.role as user_role, v.title as video_title 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    JOIN videos v ON f.video_id = v.id 
                    WHERE f.id = :id";
            return $this->db->fetch($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Feedback find by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get feedback for a specific video
     */
    public function getByVideoId($videoId, $options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            $orderBy = $options['order_by'] ?? 'created_at';
            $orderDir = $options['order_dir'] ?? 'DESC';
            
            $sql = "SELECT f.*, u.username, u.role as user_role 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    WHERE f.video_id = :video_id 
                    ORDER BY f.{$orderBy} {$orderDir} 
                    LIMIT :limit OFFSET :offset";
            
            $feedback = $this->db->fetchAll($sql, [
                'video_id' => $videoId,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM feedback WHERE video_id = :video_id",
                ['video_id' => $videoId]
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'feedback' => $feedback,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get feedback by video ID failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve feedback");
        }
    }
    
    /**
     * Get feedback by user
     */
    public function getByUserId($userId, $options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $sql = "SELECT f.*, v.title as video_title, v.thumbnail_path 
                    FROM feedback f 
                    JOIN videos v ON f.video_id = v.id 
                    WHERE f.user_id = :user_id 
                    ORDER BY f.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $feedback = $this->db->fetchAll($sql, [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM feedback WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'feedback' => $feedback,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get feedback by user ID failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve feedback");
        }
    }
    
    /**
     * Get all feedback with pagination and filters
     */
    public function findAll($options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            $videoId = $options['video_id'] ?? null;
            $userId = $options['user_id'] ?? null;
            $rating = $options['rating'] ?? null;
            $orderBy = $options['order_by'] ?? 'created_at';
            $orderDir = $options['order_dir'] ?? 'DESC';
            
            $sql = "SELECT f.*, u.username, u.role as user_role, v.title as video_title 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    JOIN videos v ON f.video_id = v.id 
                    WHERE 1=1";
            $params = [];
            
            if ($videoId !== null) {
                $sql .= " AND f.video_id = :video_id";
                $params['video_id'] = $videoId;
            }
            
            if ($userId !== null) {
                $sql .= " AND f.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            if ($rating !== null) {
                $sql .= " AND f.rating = :rating";
                $params['rating'] = $rating;
            }
            
            // Validate order by field
            $allowedOrderFields = ['created_at', 'rating', 'updated_at'];
            if (!in_array($orderBy, $allowedOrderFields)) {
                $orderBy = 'created_at';
            }
            
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            
            $sql .= " ORDER BY f.{$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            $feedback = $this->db->fetchAll($sql, $params);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM feedback f WHERE 1=1";
            $countParams = [];
            
            if ($videoId !== null) {
                $countSql .= " AND f.video_id = :video_id";
                $countParams['video_id'] = $videoId;
            }
            
            if ($userId !== null) {
                $countSql .= " AND f.user_id = :user_id";
                $countParams['user_id'] = $userId;
            }
            
            if ($rating !== null) {
                $countSql .= " AND f.rating = :rating";
                $countParams['rating'] = $rating;
            }
            
            $countResult = $this->db->fetch($countSql, $countParams);
            $total = $countResult['total'] ?? 0;
            
            return [
                'feedback' => $feedback,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Feedback find all failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve feedback");
        }
    }
    
    /**
     * Update feedback
     */
    public function update($id, $feedbackData) {
        try {
            $feedbackData['updated_at'] = date('Y-m-d H:i:s');
            
            // Validate rating if provided
            if (isset($feedbackData['rating'])) {
                $feedbackData['rating'] = max(1, min(5, intval($feedbackData['rating'])));
            }
            
            $this->db->update('feedback', $feedbackData, 'id = :id', ['id' => $id]);
            
            return $this->findById($id);
            
        } catch (Exception $e) {
            error_log("Feedback update failed: " . $e->getMessage());
            throw new Exception("Failed to update feedback");
        }
    }
    
    /**
     * Delete feedback
     */
    public function delete($id, $userId = null) {
        try {
            // Check if user owns the feedback or is admin
            $feedback = $this->findById($id);
            if (!$feedback) {
                throw new Exception("Feedback not found");
            }
            
            if ($userId && $feedback['user_id'] != $userId) {
                // Check if user is admin
                $user = $this->db->fetch("SELECT role FROM users WHERE id = :id", ['id' => $userId]);
                if (!$user || $user['role'] !== 'admin') {
                    throw new Exception("Unauthorized to delete this feedback");
                }
            }
            
            $this->db->delete('feedback', 'id = :id', ['id' => $id]);
            return true;
            
        } catch (Exception $e) {
            error_log("Feedback deletion failed: " . $e->getMessage());
            throw new Exception("Failed to delete feedback");
        }
    }
    
    /**
     * Get feedback statistics for a video
     */
    public function getVideoStats($videoId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_feedback,
                        AVG(rating) as avg_rating,
                        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star,
                        COUNT(CASE WHEN comment IS NOT NULL AND comment != '' THEN 1 END) as with_comments
                    FROM feedback 
                    WHERE video_id = :video_id";
            
            return $this->db->fetch($sql, ['video_id' => $videoId]);
            
        } catch (Exception $e) {
            error_log("Get video feedback stats failed: " . $e->getMessage());
            return [
                'total_feedback' => 0,
                'avg_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0,
                'with_comments' => 0
            ];
        }
    }
    
    /**
     * Get overall feedback statistics
     */
    public function getOverallStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_feedback,
                        AVG(rating) as avg_rating,
                        COUNT(DISTINCT video_id) as videos_with_feedback,
                        COUNT(DISTINCT user_id) as users_giving_feedback,
                        COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as new_feedback_month
                    FROM feedback";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("Get overall feedback stats failed: " . $e->getMessage());
            return [
                'total_feedback' => 0,
                'avg_rating' => 0,
                'videos_with_feedback' => 0,
                'users_giving_feedback' => 0,
                'new_feedback_month' => 0
            ];
        }
    }
    
    /**
     * Check if user has already given feedback for a video
     */
    public function hasUserFeedback($userId, $videoId) {
        try {
            $sql = "SELECT id FROM feedback WHERE user_id = :user_id AND video_id = :video_id LIMIT 1";
            $result = $this->db->fetch($sql, [
                'user_id' => $userId,
                'video_id' => $videoId
            ]);
            
            return $result !== null;
            
        } catch (Exception $e) {
            error_log("Check user feedback failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's existing feedback for a video
     */
    public function getUserVideoFeedback($userId, $videoId) {
        try {
            $sql = "SELECT * FROM feedback WHERE user_id = :user_id AND video_id = :video_id LIMIT 1";
            return $this->db->fetch($sql, [
                'user_id' => $userId,
                'video_id' => $videoId
            ]);
        } catch (Exception $e) {
            error_log("Get user video feedback failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent feedback
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT f.*, u.username, v.title as video_title 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    JOIN videos v ON f.video_id = v.id 
                    ORDER BY f.created_at DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, ['limit' => $limit]);
            
        } catch (Exception $e) {
            error_log("Get recent feedback failed: " . $e->getMessage());
            return [];
        }
    }
}
?>
