<?php
/**
 * Authentication Controller for PHP Backend
 * Handles user authentication, registration, and session management
 */

require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Handle user registration
     */
    public function register($data) {
        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Validate password strength
            if (strlen($data['password']) < 6) {
                throw new Exception("Password must be at least 6 characters long");
            }
            
            // Validate age group (optional)
            if (!empty($data['age_group'])) {
                $validAgeGroups = ['13-17', '18-25', '26-35', '36+'];
                if (!in_array($data['age_group'], $validAgeGroups)) {
                    throw new Exception("Invalid age group");
                }
            }
            
            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                throw new Exception("Email already registered");
            }
            
            // Check if username already exists
            $existingUsername = $this->userModel->findByUsername($data['username']);
            if ($existingUsername) {
                throw new Exception("Username already taken");
            }
            
            // Create user
            $userData = [
                'username' => trim($data['username']),
                'email' => strtolower(trim($data['email'])),
                'password' => $data['password'],
                'age_group' => $data['age_group'],
                'role' => 'user' // Default role
            ];
            
            $user = $this->userModel->create($userData);
            
            // Start session and log user in
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => $user
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle user login
     */
    public function login($data) {
        try {
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                throw new Exception("Email and password are required");
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Authenticate user
            $user = $this->userModel->authenticate($data['email'], $data['password']);
            
            if (!$user) {
                throw new Exception("Invalid credentials");
            }
            
            // Start session and log user in
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        try {
            session_start();
            
            // Clear all session data
            $_SESSION = array();
            
            // Destroy the session
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
            
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Logout failed'
            ];
        }
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        try {
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                return null;
            }
            
            $user = $this->userModel->findById($_SESSION['user_id']);
            
            if (!$user || !$user['is_active']) {
                // Clear invalid session
                session_destroy();
                return null;
            }
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Get current user failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        $user = $this->getCurrentUser();
        return $user !== null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if user is judge
     */
    public function isJudge() {
        return $this->hasRole('judge') || $this->hasRole('admin');
    }
    
    /**
     * Require authentication (redirect if not authenticated)
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit();
        }
    }
    
    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireRole('admin');
    }
    
    /**
     * Require judge or admin role
     */
    public function requireJudge() {
        $this->requireAuth();
        
        if (!$this->isJudge()) {
            http_response_code(403);
            echo json_encode(['error' => 'Judge or admin access required']);
            exit();
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            // Check if user is updating their own profile or is admin
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("Authentication required");
            }
            
            if ($currentUser['id'] != $userId && !$this->isAdmin()) {
                throw new Exception("Unauthorized to update this profile");
            }
            
            // Validate data
            $updateData = [];
            
            if (isset($data['username'])) {
                $username = trim($data['username']);
                if (empty($username)) {
                    throw new Exception("Username cannot be empty");
                }
                
                // Check if username is already taken by another user
                $existingUser = $this->userModel->findByUsername($username);
                if ($existingUser && $existingUser['id'] != $userId) {
                    throw new Exception("Username already taken");
                }
                
                $updateData['username'] = $username;
            }
            
            if (isset($data['age_group'])) {
                $validAgeGroups = ['13-17', '18-25', '26-35', '36+'];
                if (!in_array($data['age_group'], $validAgeGroups)) {
                    throw new Exception("Invalid age group");
                }
                $updateData['age_group'] = $data['age_group'];
            }
            
            if (isset($data['password'])) {
                if (strlen($data['password']) < 6) {
                    throw new Exception("Password must be at least 6 characters long");
                }
                $updateData['password'] = $data['password'];
            }
            
            if (empty($updateData)) {
                throw new Exception("No valid data to update");
            }
            
            // Update user
            $updatedUser = $this->userModel->update($userId, $updateData);
            
            // Update session if updating own profile
            if ($currentUser['id'] == $userId) {
                $_SESSION['username'] = $updatedUser['username'];
            }
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
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
     * Change password
     */
    public function changePassword($userId, $data) {
        try {
            // Check if user is changing their own password or is admin
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                throw new Exception("Authentication required");
            }
            
            if ($currentUser['id'] != $userId && !$this->isAdmin()) {
                throw new Exception("Unauthorized to change this password");
            }
            
            // Validate required fields
            if (empty($data['current_password']) || empty($data['new_password'])) {
                throw new Exception("Current and new password are required");
            }
            
            // Verify current password (unless admin)
            if ($currentUser['id'] == $userId) {
                $user = $this->userModel->findById($userId);
                if (!password_verify($data['current_password'], $user['password_hash'])) {
                    throw new Exception("Current password is incorrect");
                }
            }
            
            // Validate new password
            if (strlen($data['new_password']) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }
            
            // Update password
            $this->userModel->update($userId, ['password' => $data['new_password']]);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
