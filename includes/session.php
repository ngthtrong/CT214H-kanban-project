<?php
/**
 * Session Management
 * Team Kanban - CT214H Final Project
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Set session name
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'kanban_session');
    
    // Set session lifetime
    $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user data
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'avatar' => $_SESSION['avatar'] ?? null
    ];
}

/**
 * Get current user ID
 */
function getCurrentUserId(): ?int
{
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Login user - set session data
 */
function loginUser(array $userData, bool $remember = false): void
{
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Support both 'id' and 'user_id' field names
    $_SESSION['user_id'] = $userData['user_id'] ?? $userData['id'] ?? null;
    $_SESSION['username'] = $userData['username'] ?? '';
    $_SESSION['email'] = $userData['email'] ?? '';
    $_SESSION['full_name'] = $userData['full_name'] ?? '';
    $_SESSION['avatar'] = $userData['avatar'] ?? null;
    $_SESSION['logged_in_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Update session user data (after profile update)
 */
function updateSessionUser(array $userData): void
{
    if (!isLoggedIn()) {
        return;
    }
    
    if (isset($userData['username'])) {
        $_SESSION['username'] = $userData['username'];
    }
    if (isset($userData['email'])) {
        $_SESSION['email'] = $userData['email'];
    }
    if (isset($userData['full_name'])) {
        $_SESSION['full_name'] = $userData['full_name'];
    }
    if (array_key_exists('avatar', $userData)) {
        $_SESSION['avatar'] = $userData['avatar'];
    }
}

/**
 * Logout user - destroy session
 */
function logoutUser(): void
{
    // Clear session data
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Vui lòng đăng nhập để tiếp tục');
        redirect('login.php');
    }
    
    // Check session timeout
    if (isset($_SESSION['logged_in_at'])) {
        $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
        if (time() - $_SESSION['logged_in_at'] > $lifetime) {
            logoutUser();
            setFlash('warning', 'Phiên đăng nhập đã hết hạn');
            redirect(APP_URL . '/login.php');
        }
    }
    
    // Check session hijacking (IP or User-Agent changed)
    if (
        (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) ||
        (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'])
    ) {
        logoutUser();
        setFlash('error', 'Phiên đăng nhập không hợp lệ');
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Require guest - redirect to dashboard if already logged in
 */
function requireGuest(): void
{
    if (isLoggedIn()) {
        redirect(APP_URL . '/index.php');
    }
}
