<?php
/**
 * Video Model for PHP Backend
 * Handles video operations with Supabase database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/supabase.php';

class VideoModel {
    private $db;
    private $supabase;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->supabase = new SupabaseClient();
    }
    
    /**
     * Create new video
     */
    public function create($videoData) {
        try {
            $this->db->beginTransaction();
            
            // Set default values
            $videoData['created_at'] = date('Y-m-d H:i:s');
            $videoData['updated_at'] = date('Y-m-d H:i:s');
            $videoData['is_approved'] = $videoData['is_approved'] ?? false;
            $videoData['view_count'] = $videoData['view_count'] ?? 0;
            $videoData['like_count'] = $videoData['like_count'] ?? 0;
            
            $videoId = $this->db->insert('videos', $videoData);
            
            $this->db->commit();
            
            return $this->findById($videoId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Video creation failed: " . $e->getMessage());
            throw new Exception("Failed to create video");
        }
    }
    
    /**
     * Find video by ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT v.*, u.username, u.role as user_role 
                    FROM videos v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE v.id = :id";
            return $this->db->fetch($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Video find by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all videos with pagination and filters
     */
    public function findAll($options = []) {
        try {
            $limit = $options['limit'] ?? 12;
            $offset = $options['offset'] ?? 0;
            $search = $options['search'] ?? '';
            $category = $options['category'] ?? '';
            $isApproved = $options['is_approved'] ?? null;
            $userId = $options['user_id'] ?? null;
            $orderBy = $options['order_by'] ?? 'created_at';
            $orderDir = $options['order_dir'] ?? 'DESC';
            
            $sql = "SELECT v.*, u.username, u.role as user_role 
                    FROM videos v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (v.title ILIKE :search OR v.description ILIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($category)) {
                $sql .= " AND v.category = :category";
                $params['category'] = $category;
            }
            
            if ($isApproved !== null) {
                $sql .= " AND v.is_approved = :is_approved";
                $params['is_approved'] = $isApproved;
            }
            
            if ($userId !== null) {
                $sql .= " AND v.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            // Validate order by field
            $allowedOrderFields = ['created_at', 'title', 'view_count', 'like_count', 'updated_at'];
            if (!in_array($orderBy, $allowedOrderFields)) {
                $orderBy = 'created_at';
            }
            
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            
            $sql .= " ORDER BY v.{$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            $videos = $this->db->fetchAll($sql, $params);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM videos v WHERE 1=1";
            $countParams = [];
            
            if (!empty($search)) {
                $countSql .= " AND (v.title ILIKE :search OR v.description ILIKE :search)";
                $countParams['search'] = "%{$search}%";
            }
            
            if (!empty($category)) {
                $countSql .= " AND v.category = :category";
                $countParams['category'] = $category;
            }
            
            if ($isApproved !== null) {
                $countSql .= " AND v.is_approved = :is_approved";
                $countParams['is_approved'] = $isApproved;
            }
            
            if ($userId !== null) {
                $countSql .= " AND v.user_id = :user_id";
                $countParams['user_id'] = $userId;
            }
            
            $countResult = $this->db->fetch($countSql, $countParams);
            $total = $countResult['total'] ?? 0;
            
            return [
                'videos' => $videos,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Video find all failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve videos");
        }
    }
    
    /**
     * Get videos for approval (judge/admin)
     */
    public function getPendingApproval($options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $sql = "SELECT v.*, u.username, u.role as user_role 
                    FROM videos v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE v.is_approved = false 
                    ORDER BY v.created_at ASC 
                    LIMIT :limit OFFSET :offset";
            
            $videos = $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM videos WHERE is_approved = false"
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'videos' => $videos,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get pending approval failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve pending videos");
        }
    }
    
    /**
     * Update video
     */
    public function update($id, $videoData) {
        try {
            $videoData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('videos', $videoData, 'id = :id', ['id' => $id]);
            
            return $this->findById($id);
            
        } catch (Exception $e) {
            error_log("Video update failed: " . $e->getMessage());
            throw new Exception("Failed to update video");
        }
    }
    
    /**
     * Approve/reject video
     */
    public function updateApprovalStatus($id, $isApproved, $adminId = null) {
        try {
            $updateData = [
                'is_approved' => $isApproved,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($adminId) {
                $updateData['approved_by'] = $adminId;
                $updateData['approved_at'] = date('Y-m-d H:i:s');
            }
            
            $this->db->update('videos', $updateData, 'id = :id', ['id' => $id]);
            
            return $this->findById($id);
            
        } catch (Exception $e) {
            error_log("Video approval update failed: " . $e->getMessage());
            throw new Exception("Failed to update video approval status");
        }
    }
    
    /**
     * Increment view count
     */
    public function incrementViewCount($id) {
        try {
            $sql = "UPDATE videos SET view_count = view_count + 1, updated_at = :updated_at WHERE id = :id";
            $this->db->query($sql, [
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Video view count increment failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update like count
     */
    public function updateLikeCount($id, $increment = true) {
        try {
            $operator = $increment ? '+' : '-';
            $sql = "UPDATE videos SET like_count = GREATEST(0, like_count {$operator} 1), updated_at = :updated_at WHERE id = :id";
            $this->db->query($sql, [
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Video like count update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete video
     */
    public function delete($id, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            // Check if user owns the video or is admin
            $video = $this->findById($id);
            if (!$video) {
                throw new Exception("Video not found");
            }
            
            if ($userId && $video['user_id'] != $userId) {
                // Check if user is admin
                $user = $this->db->fetch("SELECT role FROM users WHERE id = :id", ['id' => $userId]);
                if (!$user || $user['role'] !== 'admin') {
                    throw new Exception("Unauthorized to delete this video");
                }
            }
            
            // Delete from storage first
            if (!empty($video['storage_path'])) {
                try {
                    $this->supabase->deleteFile($video['storage_path']);
                } catch (Exception $e) {
                    error_log("Failed to delete video file: " . $e->getMessage());
                }
            }
            
            // Delete related data
            $this->db->delete('likes', 'video_id = :video_id', ['video_id' => $id]);
            $this->db->delete('feedback', 'video_id = :video_id', ['video_id' => $id]);
            
            // Delete video record
            $this->db->delete('videos', 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Video deletion failed: " . $e->getMessage());
            throw new Exception("Failed to delete video");
        }
    }
    
    /**
     * Get video statistics
     */
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_videos,
                        COUNT(CASE WHEN is_approved = true THEN 1 END) as approved_videos,
                        COUNT(CASE WHEN is_approved = false THEN 1 END) as pending_videos,
                        SUM(view_count) as total_views,
                        SUM(like_count) as total_likes,
                        COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as new_videos_month,
                        AVG(view_count) as avg_views,
                        AVG(like_count) as avg_likes
                    FROM videos";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("Video stats failed: " . $e->getMessage());
            return [
                'total_videos' => 0,
                'approved_videos' => 0,
                'pending_videos' => 0,
                'total_views' => 0,
                'total_likes' => 0,
                'new_videos_month' => 0,
                'avg_views' => 0,
                'avg_likes' => 0
            ];
        }
    }
    
    /**
     * Get video categories
     */
    public function getCategories() {
        try {
            $sql = "SELECT DISTINCT category, COUNT(*) as count 
                    FROM videos 
                    WHERE category IS NOT NULL 
                    GROUP BY category 
                    ORDER BY count DESC";
            
            return $this->db->fetchAll($sql);
            
        } catch (Exception $e) {
            error_log("Get video categories failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search videos by text
     */
    public function search($query, $options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $sql = "SELECT v.*, u.username, u.role as user_role 
                    FROM videos v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE v.is_approved = true 
                    AND (v.title ILIKE :query OR v.description ILIKE :query OR u.username ILIKE :query)
                    ORDER BY v.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $videos = $this->db->fetchAll($sql, [
                'query' => "%{$query}%",
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Get total count
            $countResult = $this->db->fetch(
                "SELECT COUNT(*) as total FROM videos v 
                 JOIN users u ON v.user_id = u.id 
                 WHERE v.is_approved = true 
                 AND (v.title ILIKE :query OR v.description ILIKE :query OR u.username ILIKE :query)",
                ['query' => "%{$query}%"]
            );
            $total = $countResult['total'] ?? 0;
            
            return [
                'videos' => $videos,
                'total' => $total,
                'query' => $query
            ];
            
        } catch (Exception $e) {
            error_log("Video search failed: " . $e->getMessage());
            throw new Exception("Failed to search videos");
        }
    }
}
?>
