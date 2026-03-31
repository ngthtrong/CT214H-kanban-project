<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Contracts\UserRepositoryInterface;
use App\Database\Connection;

/**
 * Login Service
 * Handles user authentication
 * Supports both repository pattern (for testing) and database connection (for production)
 * Team Kanban - CT214H Final Project
 */
class LoginService
{
    private ?UserRepositoryInterface $repository = null;
    private ?Connection $db = null;
    
    public function __construct(UserRepositoryInterface|Connection|null $dependency = null)
    {
        if ($dependency instanceof UserRepositoryInterface) {
            $this->repository = $dependency;
        } elseif ($dependency instanceof Connection) {
            $this->db = $dependency;
        } else {
            $this->db = Connection::fromEnvironment();
        }
    }
    
    /**
     * Authenticate user with username/email and password
     *
     * @param array|string $identifierOrPayload Either array with 'identifier' and 'password' keys, or string identifier
     * @param string|null $password Plain text password (required if first param is string)
     * @return array Result with 'success', 'errors'/'error', and 'user'/'data' keys
     */
    public function login(array|string $identifierOrPayload, ?string $password = null): array
    {
        // Handle both array and separate params
        if (is_array($identifierOrPayload)) {
            $identifier = trim($identifierOrPayload['identifier'] ?? '');
            $password = trim($identifierOrPayload['password'] ?? '');
        } else {
            $identifier = trim($identifierOrPayload);
            $password = trim($password ?? '');
        }
        
        // Validate input
        if (empty($identifier)) {
            return [
                'success' => false,
                'errors' => ['identifier' => 'Vui lòng nhập tên đăng nhập hoặc email'],
                'error' => 'Vui lòng nhập tên đăng nhập hoặc email'
            ];
        }
        
        if (empty($password)) {
            return [
                'success' => false,
                'errors' => ['password' => 'Vui lòng nhập mật khẩu'],
                'error' => 'Vui lòng nhập mật khẩu'
            ];
        }
        
        // Find user by username or email
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['credentials' => 'Tài khoản không tồn tại'],
                'error' => 'Tài khoản không tồn tại'
            ];
        }
        
        // Get password hash field (support both formats)
        $passwordHash = $user['password_hash'] ?? $user['password'] ?? '';
        
        // Verify password
        if (!password_verify($password, $passwordHash)) {
            return [
                'success' => false,
                'errors' => ['credentials' => 'Mật khẩu không đúng'],
                'error' => 'Mật khẩu không đúng'
            ];
        }
        
        // Remove password from return data
        unset($user['password'], $user['password_hash']);
        
        return [
            'success' => true,
            'errors' => [],
            'user' => $user,
            'data' => $user
        ];
    }
    
    /**
     * Find user by username or email
     */
    private function findUserByIdentifier(string $identifier): ?array
    {
        if ($this->repository) {
            return $this->repository->findByIdentifier($identifier);
        }
        
        $sql = 'SELECT user_id as id, username, email, password, full_name, avatar, created_at 
                FROM users 
                WHERE username = ? OR email = ?';
        
        return $this->db->queryOne($sql, [$identifier, strtolower($identifier)]);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        if ($this->repository) {
            // Repository pattern doesn't have findById, return null
            return null;
        }
        
        $sql = 'SELECT user_id as id, username, email, full_name, avatar, created_at 
                FROM users 
                WHERE user_id = ?';
        
        return $this->db->queryOne($sql, [$userId]);
    }
}
