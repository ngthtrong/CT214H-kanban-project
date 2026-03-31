<?php
/**
 * Authentication Functions
 * Team Kanban - CT214H Final Project
 * 
 * Simple procedural functions for registration, login, logout
 */

require_once __DIR__ . '/db-connect.php';
require_once __DIR__ . '/functions.php';

/**
 * Register new user
 * 
 * @param array $data User data (username, email, password, full_name)
 * @return array Result with 'success' and 'error' or 'user_id'
 */
function registerUser(array $data): array
{
    // Validate input
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $fullName = trim($data['full_name'] ?? '');
    
    // Required fields
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        return ['success' => false, 'error' => 'Vui lòng điền đầy đủ thông tin'];
    }
    
    // Validate username (3-50 chars, alphanumeric + underscore)
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Tên đăng nhập phải từ 3-50 ký tự'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Tên đăng nhập chỉ chứa chữ cái, số và dấu gạch dưới'];
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Email không hợp lệ'];
    }
    
    // Validate password
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Mật khẩu phải có ít nhất 6 ký tự'];
    }
    
    // Check if username exists
    $existing = dbQueryOne(
        "SELECT user_id FROM users WHERE username = ?",
        [$username]
    );
    if ($existing) {
        return ['success' => false, 'error' => 'Tên đăng nhập đã tồn tại'];
    }
    
    // Check if email exists
    $existing = dbQueryOne(
        "SELECT user_id FROM users WHERE email = ?",
        [$email]
    );
    if ($existing) {
        return ['success' => false, 'error' => 'Email đã được sử dụng'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    try {
        $userId = dbInsert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'full_name' => $fullName
        ]);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Đăng ký thành công'
        ];
    } catch (Exception $e) {
        error_log('Registration failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Có lỗi xảy ra, vui lòng thử lại'];
    }
}

/**
 * Authenticate user
 * 
 * @param string $identifier Username or email
 * @param string $password Plain text password
 * @return array Result with 'success' and 'error' or 'user' data
 */
function authenticateUser(string $identifier, string $password): array
{
    // Validate input
    $identifier = trim($identifier);
    $password = trim($password);
    
    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'error' => 'Vui lòng nhập tên đăng nhập và mật khẩu'];
    }
    
    // Find user by username or email
    $user = dbQueryOne(
        "SELECT user_id, username, email, password, full_name, avatar 
         FROM users 
         WHERE username = ? OR email = ?",
        [$identifier, $identifier]
    );
    
    if (!$user) {
        return ['success' => false, 'error' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
    }
    
    // Remove password from return data
    unset($user['password']);
    
    return [
        'success' => true,
        'user' => $user,
        'message' => 'Đăng nhập thành công'
    ];
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $data Update data (username, email, full_name)
 * @return array Result with 'success'
 */
function updateUserProfile(int $userId, array $data): array
{
    $updateData = [];
    
    // Username
    if (isset($data['username'])) {
        $username = trim($data['username']);
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Tên đăng nhập phải từ 3-50 ký tự'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'Tên đăng nhập chỉ chứa chữ cái, số và dấu gạch dưới'];
        }
        
        // Check duplicate
        $existing = dbQueryOne(
            "SELECT user_id FROM users WHERE username = ? AND user_id != ?",
            [$username, $userId]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'Tên đăng nhập đã tồn tại'];
        }
        
        $updateData['username'] = $username;
    }
    
    // Email
    if (isset($data['email'])) {
        $email = trim($data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email không hợp lệ'];
        }
        
        // Check duplicate
        $existing = dbQueryOne(
            "SELECT user_id FROM users WHERE email = ? AND user_id != ?",
            [$email, $userId]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'Email đã được sử dụng'];
        }
        
        $updateData['email'] = $email;
    }
    
    // Full name
    if (isset($data['full_name'])) {
        $fullName = trim($data['full_name']);
        if (strlen($fullName) < 2) {
            return ['success' => false, 'error' => 'Họ tên phải có ít nhất 2 ký tự'];
        }
        $updateData['full_name'] = $fullName;
    }
    
    if (empty($updateData)) {
        return ['success' => false, 'error' => 'Không có dữ liệu để cập nhật'];
    }
    
    try {
        dbUpdate('users', $updateData, 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Cập nhật thông tin thành công'];
    } catch (Exception $e) {
        error_log('Profile update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Có lỗi xảy ra, vui lòng thử lại'];
    }
}

/**
 * Change user password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Result with 'success'
 */
function changeUserPassword(int $userId, string $currentPassword, string $newPassword): array
{
    // Get current user
    $user = dbQueryOne(
        "SELECT password FROM users WHERE user_id = ?",
        [$userId]
    );
    
    if (!$user) {
        return ['success' => false, 'error' => 'Người dùng không tồn tại'];
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Mật khẩu hiện tại không đúng'];
    }
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Mật khẩu mới phải có ít nhất 6 ký tự'];
    }
    
    // Hash and update
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    try {
        dbUpdate('users', ['password' => $hashedPassword], 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
    } catch (Exception $e) {
        error_log('Password change failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Có lỗi xảy ra, vui lòng thử lại'];
    }
}

/**
 * Update user avatar
 * 
 * @param int $userId User ID
 * @param string|null $avatarPath Avatar file path
 * @return array Result with 'success'
 */
function updateUserAvatar(int $userId, ?string $avatarPath): array
{
    try {
        dbUpdate('users', ['avatar' => $avatarPath], 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Cập nhật avatar thành công'];
    } catch (Exception $e) {
        error_log('Avatar update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Có lỗi xảy ra, vui lòng thử lại'];
    }
}

/**
 * Get user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null
 */
function getUserById(int $userId): ?array
{
    return dbQueryOne(
        "SELECT user_id, username, email, full_name, avatar, created_at 
         FROM users 
         WHERE user_id = ?",
        [$userId]
    );
}

/**
 * Find user by username or email
 * 
 * @param string $identifier Username or email
 * @return array|null User data or null
 */
function findUserByIdentifier(string $identifier): ?array
{
    return dbQueryOne(
        "SELECT user_id, username, email, full_name, avatar 
         FROM users 
         WHERE username = ? OR email = ?",
        [$identifier, $identifier]
    );
}

/**
 * Upload avatar for user
 * 
 * @param int $userId User ID
 * @param array $file $_FILES array
 * @return array Result with 'success'
 */
function uploadUserAvatar(int $userId, array $file): array
{
    // Validate upload
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Vui lòng chọn file ảnh'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Lỗi khi upload file'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WEBP)'];
    }
    
    // Validate size (5MB)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'error' => 'File không được vượt quá 5MB'];
    }
    
    // Create uploads directory
    $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Delete old avatar
    $user = getUserById($userId);
    if ($user && $user['avatar']) {
        $oldFile = $uploadDir . $user['avatar'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Không thể lưu file'];
    }
    
    // Update database
    try {
        dbUpdate('users', ['avatar' => $filename], 'user_id = ?', [$userId]);
        return ['success' => true, 'avatar' => $filename, 'message' => 'Upload avatar thành công'];
    } catch (Exception $e) {
        error_log('Avatar upload failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Có lỗi xảy ra'];
    }
}
