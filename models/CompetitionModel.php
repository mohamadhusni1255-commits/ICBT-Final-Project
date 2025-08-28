<?php
/**
 * Competition Model for PHP Backend
 * Handles competition-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class CompetitionModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new competition
     */
    public function create($data) {
        try {
            $requiredFields = ['title', 'description', 'start_date', 'end_date', 'prize_pool', 'max_participants'];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            $competitionData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'prize_pool' => $data['prize_pool'],
                'max_participants' => $data['max_participants'],
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $competitionId = $this->db->insert('competitions', $competitionData);
            
            return [
                'success' => true,
                'id' => $competitionId,
                'message' => 'Competition created successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find competition by ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT * FROM competitions WHERE id = :id";
            $result = $this->db->fetch($sql, ['id' => $id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Competition not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find all competitions with filters
     */
    public function findAll($options = []) {
        try {
            $limit = $options['limit'] ?? 50;
            $offset = $options['offset'] ?? 0;
            $status = $options['status'] ?? '';
            $search = $options['search'] ?? '';
            $orderBy = $options['order_by'] ?? 'created_at';
            $orderDir = $options['order_dir'] ?? 'DESC';
            
            $whereConditions = [];
            $params = [];
            
            if (!empty($status)) {
                $whereConditions[] = "status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($search)) {
                $whereConditions[] = "(title ILIKE :search OR description ILIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT * FROM competitions {$whereClause} ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            $result = $this->db->fetchAll($sql, $params);
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM competitions {$whereClause}";
            $countResult = $this->db->fetch($countSql, array_diff_key($params, ['limit' => '', 'offset' => '']));
            $total = $countResult['total'] ?? 0;
            
            return [
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
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
    public function update($id, $data) {
        try {
            $updateData = [];
            $allowedFields = ['title', 'description', 'start_date', 'end_date', 'prize_pool', 'max_participants', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('No valid fields to update');
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->update('competitions', $updateData, 'id = :id', ['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Competition updated successfully'
            ];
            
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
    public function delete($id) {
        try {
            $this->db->delete('competitions', 'id = :id', ['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Competition deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get competition statistics
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total competitions
            $totalSql = "SELECT COUNT(*) as total FROM competitions";
            $totalResult = $this->db->fetch($totalSql);
            $stats['total_competitions'] = $totalResult['total'] ?? 0;
            
            // Active competitions
            $activeSql = "SELECT COUNT(*) as total FROM competitions WHERE status = 'active' AND end_date >= NOW()";
            $activeResult = $this->db->fetch($activeSql);
            $stats['active_competitions'] = $activeResult['total'] ?? 0;
            
            // Upcoming competitions
            $upcomingSql = "SELECT COUNT(*) as total FROM competitions WHERE status = 'active' AND start_date > NOW()";
            $upcomingResult = $this->db->fetch($upcomingSql);
            $stats['upcoming_competitions'] = $upcomingResult['total'] ?? 0;
            
            // Completed competitions
            $completedSql = "SELECT COUNT(*) as total FROM competitions WHERE status = 'completed' OR end_date < NOW()";
            $completedResult = $this->db->fetch($completedSql);
            $stats['completed_competitions'] = $completedResult['total'] ?? 0;
            
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
     * Get active competitions for public display
     */
    public function getActiveCompetitions() {
        try {
            $sql = "SELECT * FROM competitions WHERE status = 'active' AND end_date >= NOW() ORDER BY start_date ASC";
            $result = $this->db->fetchAll($sql);
            
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
}
?>
