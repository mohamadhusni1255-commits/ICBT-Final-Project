<?php
/**
 * User Model for PHP Backend
 * Handles user operations with Supabase database
 */

require_once __DIR__ . '/../config/database.php';

class UserModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new user
     */
    public function create($userData) {
        try {
            // Hash password
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']); // Remove plain password
            
            // Set default values
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');
            $userData['role'] = $userData['role'] ?? 'user';
            $userData['is_active'] = $userData['is_active'] ?? true;
            
            $userId = $this->db->insert('users', $userData);
            
            // Return created user without password
            return $this->findById($userId);
            
        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            throw new Exception("Failed to create user");
        }
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT id, username, email, role, age_group, is_active, created_at, updated_at 
                    FROM users WHERE id = :id";
            return $this->db->fetch($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("User find by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            return $this->db->fetch($sql, ['email' => $email]);
        } catch (Exception $e) {
            error_log("User find by email failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        try {
            $sql = "SELECT * FROM users WHERE username = :username";
            return $this->db->fetch($sql, ['username' => $username]);
        } catch (Exception $e) {
            error_log("User find by username failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Authenticate user with email and password
     */
    public function authenticate($email, $password) {
        try {
            $user = $this->findByEmail($email);
            
            if (!$user || !$user['is_active']) {
                return null;
            }
            
            if (password_verify($password, $user['password_hash'])) {
                // Remove password from response
                unset($user['password_hash']);
                return $user;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("User authentication failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function update($id, $userData) {
        try {
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            // Don't allow role updates through this method
            unset($userData['role']);
            unset($userData['is_active']);
            
            $this->db->update('users', $userData, 'id = :id', ['id' => $id]);
            
            return $this->findById($id);
            
        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            throw new Exception("Failed to update user");
        }
    }
    
    /**
     * Update user role (admin only)
     */
    public function updateRole($id, $role) {
        try {
            $allowedRoles = ['user', 'judge', 'admin'];
            
            if (!in_array($role, $allowedRoles)) {
                throw new Exception("Invalid role");
            }
            
            $this->db->update('users', 
                ['role' => $role, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );
            
            return $this->findById($id);
            
        } catch (Exception $e) {
            error_log("User role update failed: " . $e->getMessage());
            throw new Exception("Failed to update user role");
        }
    }
    
    /**
     * Get all users with pagination and filters
     */
    public function findAll($options = []) {
        try {
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            $search = $options['search'] ?? '';
            $role = $options['role'] ?? '';
            $isActive = $options['is_active'] ?? null;
            
            $sql = "SELECT id, username, email, role, age_group, is_active, created_at, updated_at 
                    FROM users WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (username ILIKE :search OR email ILIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($role)) {
                $sql .= " AND role = :role";
                $params['role'] = $role;
            }
            
            if ($isActive !== null) {
                $sql .= " AND is_active = :is_active";
                $params['is_active'] = $isActive;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            $users = $this->db->fetchAll($sql, $params);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
            $countParams = [];
            
            if (!empty($search)) {
                $countSql .= " AND (username ILIKE :search OR email ILIKE :search)";
                $countParams['search'] = "%{$search}%";
            }
            
            if (!empty($role)) {
                $countSql .= " AND role = :role";
                $countParams['role'] = $role;
            }
            
            if ($isActive !== null) {
                $countSql .= " AND is_active = :is_active";
                $countParams['is_active'] = $isActive;
            }
            
            $countResult = $this->db->fetch($countSql, $countParams);
            $total = $countResult['total'] ?? 0;
            
            return [
                'users' => $users,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("User find all failed: " . $e->getMessage());
            throw new Exception("Failed to retrieve users");
        }
    }
    
    /**
     * Get user statistics
     */
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_users,
                        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                        COUNT(CASE WHEN role = 'judge' THEN 1 END) as judge_count,
                        COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count,
                        COUNT(CASE WHEN is_active = true THEN 1 END) as active_users,
                        COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as new_users_month
                    FROM users";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("User stats failed: " . $e->getMessage());
            return [
                'total_users' => 0,
                'admin_count' => 0,
                'judge_count' => 0,
                'user_count' => 0,
                'active_users' => 0,
                'new_users_month' => 0
            ];
        }
    }
    
    /**
     * Delete user (admin only)
     */
    public function delete($id) {
        try {
            // Check if user has videos or other related data
            $videoCount = $this->db->fetch(
                "SELECT COUNT(*) as count FROM videos WHERE user_id = :user_id", 
                ['user_id' => $id]
            );
            
            if (($videoCount['count'] ?? 0) > 0) {
                throw new Exception("Cannot delete user with existing videos");
            }
            
            $this->db->delete('users', 'id = :id', ['id' => $id]);
            return true;
            
        } catch (Exception $e) {
            error_log("User deletion failed: " . $e->getMessage());
            throw new Exception("Failed to delete user");
        }
    }
    
    /**
     * Toggle user active status
     */
    public function toggleActive($id) {
        try {
            $user = $this->findById($id);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $newStatus = !$user['is_active'];
            $this->db->update('users', 
                ['is_active' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );
            
            return $newStatus;
            
        } catch (Exception $e) {
            error_log("User toggle active failed: " . $e->getMessage());
            throw new Exception("Failed to toggle user status");
        }
    }
}
?>
