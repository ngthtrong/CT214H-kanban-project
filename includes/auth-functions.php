<?php
/**
 * Authentication and profile functions.
 */

/**
 * Register new user
 */
function registerUser(array $data): array
{
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $fullName = trim($data['full_name'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        return ['success' => false, 'error' => 'Vui long dien day du thong tin'];
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Ten dang nhap phai tu 3-50 ky tu'];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Ten dang nhap chi chua chu cai, so va dau gach duoi'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Email khong hop le'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Mat khau phai co it nhat 6 ky tu'];
    }

    $existingUsername = dbQueryOne('SELECT user_id FROM users WHERE username = ?', [$username]);
    if ($existingUsername) {
        return ['success' => false, 'error' => 'Ten dang nhap da ton tai'];
    }

    $existingEmail = dbQueryOne('SELECT user_id FROM users WHERE email = ?', [$email]);
    if ($existingEmail) {
        return ['success' => false, 'error' => 'Email da duoc su dung'];
    }

    try {
        $userId = dbInsert('users', [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $fullName
        ]);

        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Dang ky thanh cong'
        ];
    } catch (Exception $e) {
        error_log('Registration failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Co loi xay ra, vui long thu lai'];
    }
}

/**
 * Authenticate user
 */
function authenticateUser(string $identifier, string $password): array
{
    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'error' => 'Vui long nhap ten dang nhap va mat khau'];
    }

    $user = dbQueryOne(
        'SELECT user_id, username, email, password, full_name, avatar FROM users WHERE username = ? OR email = ?',
        [$identifier, $identifier]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Ten dang nhap hoac mat khau khong dung'];
    }

    unset($user['password']);

    return [
        'success' => true,
        'user' => $user,
        'message' => 'Dang nhap thanh cong'
    ];
}

/**
 * Update user profile
 */
function updateUserProfile(int $userId, array $data): array
{
    $updateData = [];

    if (isset($data['username'])) {
        $username = trim($data['username']);
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Ten dang nhap phai tu 3-50 ky tu'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'Ten dang nhap chi chua chu cai, so va dau gach duoi'];
        }

        $existing = dbQueryOne('SELECT user_id FROM users WHERE username = ? AND user_id != ?', [$username, $userId]);
        if ($existing) {
            return ['success' => false, 'error' => 'Ten dang nhap da ton tai'];
        }

        $updateData['username'] = $username;
    }

    if (isset($data['email'])) {
        $email = trim($data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email khong hop le'];
        }

        $existing = dbQueryOne('SELECT user_id FROM users WHERE email = ? AND user_id != ?', [$email, $userId]);
        if ($existing) {
            return ['success' => false, 'error' => 'Email da duoc su dung'];
        }

        $updateData['email'] = $email;
    }

    if (isset($data['full_name'])) {
        $fullName = trim($data['full_name']);
        if (strlen($fullName) < 2) {
            return ['success' => false, 'error' => 'Ho ten phai co it nhat 2 ky tu'];
        }
        $updateData['full_name'] = $fullName;
    }

    if (empty($updateData)) {
        return ['success' => false, 'error' => 'Khong co du lieu de cap nhat'];
    }

    try {
        dbUpdate('users', $updateData, 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Cap nhat thong tin thanh cong'];
    } catch (Exception $e) {
        error_log('Profile update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Co loi xay ra, vui long thu lai'];
    }
}

/**
 * Change user password
 */
function changeUserPassword(int $userId, string $currentPassword, string $newPassword): array
{
    $user = dbQueryOne('SELECT password FROM users WHERE user_id = ?', [$userId]);
    if (!$user) {
        return ['success' => false, 'error' => 'Nguoi dung khong ton tai'];
    }

    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Mat khau hien tai khong dung'];
    }

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Mat khau moi phai co it nhat 6 ky tu'];
    }

    try {
        dbUpdate('users', ['password' => password_hash($newPassword, PASSWORD_BCRYPT)], 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Doi mat khau thanh cong'];
    } catch (Exception $e) {
        error_log('Password change failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Co loi xay ra, vui long thu lai'];
    }
}

/**
 * Update user avatar path
 */
function updateUserAvatar(int $userId, ?string $avatarPath): array
{
    try {
        dbUpdate('users', ['avatar' => $avatarPath], 'user_id = ?', [$userId]);
        return ['success' => true, 'message' => 'Cap nhat avatar thanh cong'];
    } catch (Exception $e) {
        error_log('Avatar update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Co loi xay ra, vui long thu lai'];
    }
}

/**
 * Get user by ID
 */
function getUserById(int $userId): ?array
{
    return dbQueryOne(
        'SELECT user_id, username, email, full_name, avatar, created_at FROM users WHERE user_id = ?',
        [$userId]
    );
}

/**
 * Find user by identifier (username/email)
 */
function findUserByIdentifier(string $identifier): ?array
{
    return dbQueryOne(
        'SELECT user_id, username, email, full_name, avatar FROM users WHERE username = ? OR email = ?',
        [$identifier, $identifier]
    );
}

/**
 * Upload avatar for user
 */
function uploadUserAvatar(int $userId, array $file): array
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Vui long chon file anh'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Loi khi upload file'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Chi chap nhan file anh (JPEG, PNG, GIF, WEBP)'];
    }

    if ($file['size'] > 5242880) {
        return ['success' => false, 'error' => 'File khong duoc vuot qua 5MB'];
    }

    if (!is_dir(AVATAR_PATH)) {
        mkdir(AVATAR_PATH, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $filepath = AVATAR_PATH . $filename;

    $user = getUserById($userId);
    if ($user && !empty($user['avatar'])) {
        $oldFile = AVATAR_PATH . $user['avatar'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Khong the luu file'];
    }

    try {
        dbUpdate('users', ['avatar' => $filename], 'user_id = ?', [$userId]);
        return ['success' => true, 'avatar' => $filename, 'message' => 'Upload avatar thanh cong'];
    } catch (Exception $e) {
        error_log('Avatar upload failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Co loi xay ra'];
    }
}
