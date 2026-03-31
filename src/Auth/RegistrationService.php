<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;
use PDO;

/**
 * Registration Service
 * Handles user registration with validation and security
 * Team Kanban - CT214H Final Project
 */
class RegistrationService
{
    private Connection $db;
    
    /**
     * Minimum password length requirement
     */
    private const PASSWORD_MIN_LENGTH = 6;
    
    /**
     * Constructor
     */
    public function __construct(?Connection $db = null)
    {
        $this->db = $db ?? Connection::fromEnvironment();
    }
    
    /**
     * Register a new user
     *
     * @param array $payload User registration data
     *                       - username: string (required)
     *                       - email: string (required)
     *                       - password: string (required)
     *                       - full_name: string (required)
     * @return array Result with 'success' boolean and 'data' or 'error' keys
     */
    public function register(array $payload): array
    {
        // Validate required fields
        $validation = $this->validatePayload($payload);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'field' => $validation['field'] ?? null
            ];
        }
        
        // Sanitize input
        $username = trim($payload['username']);
        $email = strtolower(trim($payload['email']));
        $password = $payload['password'];
        $fullName = trim($payload['full_name']);
        
        // Check for duplicate username
        if ($this->usernameExists($username)) {
            return [
                'success' => false,
                'error' => 'Username đã tồn tại',
                'field' => 'username'
            ];
        }
        
        // Check for duplicate email
        if ($this->emailExists($email)) {
            return [
                'success' => false,
                'error' => 'Email đã được sử dụng',
                'field' => 'email'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Insert user
        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName
                ]
            ];
        } catch (\PDOException $e) {
            // Log error in production
            error_log('Registration failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Đăng ký thất bại. Vui lòng thử lại sau.'
            ];
        }
    }
    
    /**
     * Validate registration payload
     */
    private function validatePayload(array $payload): array
    {
        // Required fields check
        $requiredFields = ['username', 'email', 'password', 'full_name'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field]) || trim($payload[$field]) === '') {
                return [
                    'valid' => false,
                    'error' => $this->getFieldLabel($field) . ' là bắt buộc',
                    'field' => $field
                ];
            }
        }
        
        // Username validation
        $username = trim($payload['username']);
        if (strlen($username) < 3) {
            return [
                'valid' => false,
                'error' => 'Username phải có ít nhất 3 ký tự',
                'field' => 'username'
            ];
        }
        if (strlen($username) > 50) {
            return [
                'valid' => false,
                'error' => 'Username không được vượt quá 50 ký tự',
                'field' => 'username'
            ];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return [
                'valid' => false,
                'error' => 'Username chỉ chứa chữ cái, số và dấu gạch dưới',
                'field' => 'username'
            ];
        }
        
        // Email validation
        $email = trim($payload['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Email không hợp lệ',
                'field' => 'email'
            ];
        }
        if (strlen($email) > 100) {
            return [
                'valid' => false,
                'error' => 'Email không được vượt quá 100 ký tự',
                'field' => 'email'
            ];
        }
        
        // Password validation
        $password = $payload['password'];
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Mật khẩu phải có ít nhất ' . self::PASSWORD_MIN_LENGTH . ' ký tự',
                'field' => 'password'
            ];
        }
        
        // Full name validation
        $fullName = trim($payload['full_name']);
        if (strlen($fullName) < 2) {
            return [
                'valid' => false,
                'error' => 'Họ và tên phải có ít nhất 2 ký tự',
                'field' => 'full_name'
            ];
        }
        if (strlen($fullName) > 100) {
            return [
                'valid' => false,
                'error' => 'Họ và tên không được vượt quá 100 ký tự',
                'field' => 'full_name'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get human-readable field label
     */
    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'username' => 'Tên đăng nhập',
            'email' => 'Email',
            'password' => 'Mật khẩu',
            'full_name' => 'Họ và tên',
            default => $field
        };
    }
    
    /**
     * Check if username already exists
     */
    public function usernameExists(string $username): bool
    {
        $result = $this->db->queryOne(
            'SELECT user_id FROM users WHERE username = ?',
            [$username]
        );
        return $result !== null;
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->queryOne(
            'SELECT user_id FROM users WHERE email = ?',
            [strtolower($email)]
        );
        return $result !== null;
    }
}
