<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;

/**
 * Login Service
 * Handles user authentication
 * Team Kanban - CT214H Final Project
 */
class LoginService
{
    private Connection $db;
    
    public function __construct(?Connection $db = null)
    {
        $this->db = $db ?? Connection::fromEnvironment();
    }
    
    /**
     * Authenticate user with username/email and password
     *
     * @param string $identifier Username or email
     * @param string $password Plain text password
     * @return array Result with 'success' boolean and 'data' or 'error' keys
     */
    public function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        $password = trim($password);
        
        // Validate input
        if (empty($identifier)) {
            return [
                'success' => false,
                'error' => 'Vui lòng nhập tên đăng nhập hoặc email'
            ];
        }
        
        if (empty($password)) {
            return [
                'success' => false,
                'error' => 'Vui lòng nhập mật khẩu'
            ];
        }
        
        // Find user by username or email
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Tài khoản không tồn tại'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'error' => 'Mật khẩu không đúng'
            ];
        }
        
        // Remove password from return data
        unset($user['password']);
        
        return [
            'success' => true,
            'data' => $user
        ];
    }
    
    /**
     * Find user by username or email
     */
    private function findUserByIdentifier(string $identifier): ?array
    {
        $sql = 'SELECT user_id, username, email, password, full_name, avatar, created_at 
                FROM users 
                WHERE username = ? OR email = ?';
        
        return $this->db->queryOne($sql, [$identifier, strtolower($identifier)]);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        $sql = 'SELECT user_id, username, email, full_name, avatar, created_at 
                FROM users 
                WHERE user_id = ?';
        
        return $this->db->queryOne($sql, [$userId]);
    }
}
