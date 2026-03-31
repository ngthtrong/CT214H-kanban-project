<?php

declare(strict_types=1);

namespace App\User;

use App\Database\Connection;

/**
 * Profile Service
 * Handles user profile management and avatar upload
 * Team Kanban - CT214H Final Project
 */
class ProfileService
{
    private Connection $db;
    private string $avatarPath;
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private int $maxFileSize = 5242880; // 5MB
    
    public function __construct(?Connection $db = null, ?string $avatarPath = null)
    {
        $this->db = $db ?? Connection::fromEnvironment();
        $this->avatarPath = $avatarPath ?? (defined('AVATAR_PATH') ? AVATAR_PATH : dirname(__DIR__, 2) . '/uploads/avatars/');
    }
    
    /**
     * Get user profile by ID
     */
    public function getProfile(int $userId): ?array
    {
        $sql = 'SELECT user_id, username, email, full_name, avatar, created_at 
                FROM users 
                WHERE user_id = ?';
        
        return $this->db->queryOne($sql, [$userId]);
    }
    
    /**
     * Update user profile
     *
     * @param int $userId User ID
     * @param array $data Profile data to update (full_name, email)
     * @return array Result with 'success' boolean and 'data' or 'error' keys
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Validate required fields
        if (isset($data['full_name'])) {
            $fullName = trim($data['full_name']);
            if (strlen($fullName) < 2) {
                return [
                    'success' => false,
                    'error' => 'Họ và tên phải có ít nhất 2 ký tự',
                    'field' => 'full_name'
                ];
            }
            if (strlen($fullName) > 100) {
                return [
                    'success' => false,
                    'error' => 'Họ và tên không được vượt quá 100 ký tự',
                    'field' => 'full_name'
                ];
            }
        }
        
        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Email không hợp lệ',
                    'field' => 'email'
                ];
            }
            
            // Check if email already exists (for another user)
            $existing = $this->db->queryOne(
                'SELECT user_id FROM users WHERE email = ? AND user_id != ?',
                [$email, $userId]
            );
            if ($existing) {
                return [
                    'success' => false,
                    'error' => 'Email đã được sử dụng',
                    'field' => 'email'
                ];
            }
        }
        
        // Build update data
        $updateData = [];
        if (isset($data['full_name'])) {
            $updateData['full_name'] = trim($data['full_name']);
        }
        if (isset($data['email'])) {
            $updateData['email'] = strtolower(trim($data['email']));
        }
        
        if (empty($updateData)) {
            return [
                'success' => false,
                'error' => 'Không có dữ liệu để cập nhật'
            ];
        }
        
        try {
            $this->db->update('users', $updateData, 'user_id = ?', [$userId]);
            
            return [
                'success' => true,
                'data' => $this->getProfile($userId)
            ];
        } catch (\PDOException $e) {
            error_log('Profile update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Cập nhật thất bại. Vui lòng thử lại sau.'
            ];
        }
    }
    
    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        // Get current user
        $user = $this->db->queryOne(
            'SELECT password FROM users WHERE user_id = ?',
            [$userId]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Người dùng không tồn tại'
            ];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'error' => 'Mật khẩu hiện tại không đúng',
                'field' => 'current_password'
            ];
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'error' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
                'field' => 'new_password'
            ];
        }
        
        // Hash and update
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        
        try {
            $this->db->update('users', ['password' => $hashedPassword], 'user_id = ?', [$userId]);
            
            return [
                'success' => true,
                'message' => 'Đổi mật khẩu thành công'
            ];
        } catch (\PDOException $e) {
            error_log('Password update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Đổi mật khẩu thất bại. Vui lòng thử lại sau.'
            ];
        }
    }
    
    /**
     * Upload and update avatar
     *
     * @param int $userId User ID
     * @param array $file $_FILES['avatar'] array
     * @return array Result with 'success' boolean and 'data' or 'error' keys
     */
    public function uploadAvatar(int $userId, array $file): array
    {
        // Validate file upload
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return [
                'success' => false,
                'error' => 'Vui lòng chọn file ảnh'
            ];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WEBP)'
            ];
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File không được vượt quá 5MB'
            ];
        }
        
        // Generate unique filename
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $this->avatarPath . $filename;
        
        // Ensure directory exists
        if (!is_dir($this->avatarPath)) {
            mkdir($this->avatarPath, 0755, true);
        }
        
        // Delete old avatar
        $currentProfile = $this->getProfile($userId);
        if ($currentProfile && $currentProfile['avatar']) {
            $oldFile = $this->avatarPath . $currentProfile['avatar'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'error' => 'Không thể lưu file. Vui lòng thử lại.'
            ];
        }
        
        // Update database
        try {
            $this->db->update('users', ['avatar' => $filename], 'user_id = ?', [$userId]);
            
            return [
                'success' => true,
                'data' => [
                    'avatar' => $filename,
                    'profile' => $this->getProfile($userId)
                ]
            ];
        } catch (\PDOException $e) {
            // Delete uploaded file on DB error
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            error_log('Avatar update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Cập nhật avatar thất bại. Vui lòng thử lại sau.'
            ];
        }
    }
    
    /**
     * Delete avatar
     */
    public function deleteAvatar(int $userId): array
    {
        $profile = $this->getProfile($userId);
        
        if (!$profile) {
            return [
                'success' => false,
                'error' => 'Người dùng không tồn tại'
            ];
        }
        
        if ($profile['avatar']) {
            $filepath = $this->avatarPath . $profile['avatar'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        try {
            $this->db->update('users', ['avatar' => null], 'user_id = ?', [$userId]);
            
            return [
                'success' => true,
                'message' => 'Đã xóa avatar'
            ];
        } catch (\PDOException $e) {
            error_log('Avatar delete failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Xóa avatar thất bại'
            ];
        }
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File quá lớn',
            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
            UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension',
            default => 'Lỗi upload không xác định'
        };
    }
    
    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }
}
