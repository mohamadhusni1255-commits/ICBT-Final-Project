<?php
/**
 * Like Model for PHP Backend
 * Handles video like operations with Supabase database
 */

require_once __DIR__ . '/../config/database.php';

class LikeModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Toggle like for a video
     */
    public function toggleLike($userId, $videoId) {
        try {
            $this->db->beginTransaction();
            
            // Check if like already exists
            $existingLike = $this->db->fetch(
                "SELECT id FROM likes WHERE user_id = :user_id AND video_id = :video_id",
                ['user_id' => $userId, 'video_id' => $videoId]
            );
            
            if ($existingLike) {
                // Remove like
                $this->db->delete('likes', 'user_id = :user_id AND video_id = :video_id', [
                    'user_id' => $userId, 
                    'video_id' => $videoId
                ]);
                
                // Decrement video like count
                $this->db->query(
                    "UPDATE videos SET like_count = GREATEST(0, like_count - 1), updated_at = :updated_at WHERE id = :id",
                    ['updated_at' => date('Y-m-d H:i:s'), 'id' => $videoId]
                );
                
                $this->db->commit();
                return ['liked' => false, 'action' => 'unliked'];
                
            } else {
                // Add like
                $this->db->insert('likes', [
                    'user_id' => $userId,
                    'video_id' => $videoId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Increment video like count
                $this->db->query(
                    "UPDATE videos SET like_count = like_count + 1, updated_at = :updated_at WHERE id = :id",
                    ['updated_at' => date('Y-m-d H:i:s'), 'id' => $videoId]
                );
                
                $this->db->commit();
                return ['liked' => true, 'action' => 'liked'];
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Toggle like failed: " . $e->getMessage());
            throw new Exception("Failed to toggle like");
        }
    }
    
    /**
     * Check if user has liked a video
     */
    public function hasUserLiked($userId, $videoId) {
        try {
            $sql = "SELECT id FROM likes WHERE user_id = :user_id AND video_id = :video_id LIMIT 1";
            $result = $this->db->fetch($sql, [
                'user_id' => $userId,
                'video_id' => $videoId
            ]);
            
            return $result !== null;
            
        } catch (Exception $e) {
            error_log("Check user like failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get like count for a video
     */
    public function getVideoLikeCount($videoId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM likes WHERE video_id = :video_id";
            $result = $this->db->fetch($sql, ['video_id' => $videoId]);
            
            return $result['count'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Get video like count failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user's liked videos
     */
    public function getUserLikedVideos($userId, $options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $sql = "SELECT v.*, u.username, l.created_at as liked_at 
                    FROM likes l 
                    JOIN videos v ON l.video_id = v.id 
                    JOIN users u ON v.user_id = u.id 
                    WHERE l.user_id = :user_id 
                    AND v.is_approved = true 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $videos = $this->db->fetchAll($sql, [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM likes l 
                 JOIN videos v ON l.video_id = v.id 
                 WHERE l.user_id = :user_id AND v.is_approved = true",
                ['user_id' => $userId]
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'videos' => $videos,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get user liked videos failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve liked videos");
        }
    }
    
    /**
     * Get users who liked a video
     */
    public function getVideoLikedUsers($videoId, $options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $sql = "SELECT u.id, u.username, u.role, l.created_at as liked_at 
                    FROM likes l 
                    JOIN users u ON l.user_id = u.id 
                    WHERE l.video_id = :video_id 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $users = $this->db->fetchAll($sql, [
                'video_id' => $videoId,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM likes WHERE video_id = :video_id",
                ['video_id' => $videoId]
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'users' => $users,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get video liked users failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve liked users");
        }
    }
    
    /**
     * Get like statistics
     */
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_likes,
                        COUNT(DISTINCT user_id) as unique_users_liking,
                        COUNT(DISTINCT video_id) as videos_liked,
                        COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as new_likes_month,
                        AVG(likes_per_video) as avg_likes_per_video
                    FROM (
                        SELECT 
                            COUNT(*) as likes_per_video 
                        FROM likes 
                        GROUP BY video_id
                    ) as video_likes";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("Get like stats failed: " . $e->getMessage());
            return [
                'total_likes' => 0,
                'unique_users_liking' => 0,
                'videos_liked' => 0,
                'new_likes_month' => 0,
                'avg_likes_per_video' => 0
            ];
        }
    }
    
    /**
     * Get most liked videos
     */
    public function getMostLikedVideos($limit = 10) {
        try {
            $sql = "SELECT v.*, u.username, COUNT(l.id) as like_count 
                    FROM videos v 
                    JOIN users u ON v.user_id = u.id 
                    LEFT JOIN likes l ON v.id = l.video_id 
                    WHERE v.is_approved = true 
                    GROUP BY v.id, u.username 
                    ORDER BY like_count DESC, v.created_at DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, ['limit' => $limit]);
            
        } catch (Exception $e) {
            error_log("Get most liked videos failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove like (for cleanup purposes)
     */
    public function removeLike($userId, $videoId) {
        try {
            $this->db->beginTransaction();
            
            // Check if like exists
            $existingLike = $this->db->fetch(
                "SELECT id FROM likes WHERE user_id = :user_id AND video_id = :video_id",
                ['user_id' => $userId, 'video_id' => $videoId]
            );
            
            if (!$existingLike) {
                $this->db->rollback();
                return false;
            }
            
            // Remove like
            $this->db->delete('likes', 'user_id = :user_id AND video_id = :video_id', [
                'user_id' => $userId, 
                'video_id' => $videoId
            ]);
            
            // Decrement video like count
            $this->db->query(
                "UPDATE videos SET like_count = GREATEST(0, like_count - 1), updated_at = :updated_at WHERE id = :id",
                ['updated_at' => date('Y-m-d H:i:s'), 'id' => $videoId]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Remove like failed: " . $e->getMessage());
            throw new Exception("Failed to remove like");
        }
    }
    
    /**
     * Get recent likes
     */
    public function getRecentLikes($limit = 10) {
        try {
            $sql = "SELECT l.*, u.username, v.title as video_title 
                    FROM likes l 
                    JOIN users u ON l.user_id = u.id 
                    JOIN videos v ON l.video_id = v.id 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, ['limit' => $limit]);
            
        } catch (Exception $e) {
            error_log("Get recent likes failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up orphaned likes (when videos are deleted)
     */
    public function cleanupOrphanedLikes() {
        try {
            $sql = "DELETE FROM likes WHERE video_id NOT IN (SELECT id FROM videos)";
            $result = $this->db->query($sql);
            
            return $result->rowCount();
            
        } catch (Exception $e) {
            error_log("Cleanup orphaned likes failed: " . $e->getMessage());
            return 0;
        }
    }
}
?>
