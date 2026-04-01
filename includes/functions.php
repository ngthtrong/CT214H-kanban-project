<?php
/**
 * Shared helper functions and module bootstrap.
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/db-connect.php';

/**
 * Sanitize user input
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if password meets requirements
 */
function isValidPassword(string $password): bool
{
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

/**
 * Generate random string (for project codes, tokens, etc.)
 */
function generateRandomString(int $length = 8): string
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, $max)];
    }

    return $result;
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Get asset URL
 */
function asset(string $path): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get asset URL with file version for cache busting
 */
function assetVersioned(string $path): string
{
    $url = asset($path);
    $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
    $absolutePath = APP_ROOT . '/' . $normalizedPath;

    if (is_file($absolutePath)) {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'v=' . (string) filemtime($absolutePath);
    }

    return $url;
}

/**
 * Get current URL
 */
function currentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if request is POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get POST data safely
 */
function post(string $key, $default = null)
{
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
}

/**
 * Get GET data safely
 */
function get(string $key, $default = null)
{
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

/**
 * Display flash message HTML
 */
function displayFlash(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $type = $flash['type'];
    $message = sanitize($flash['message']);
    $alertClass = match ($type) {
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info'
    };

    return <<<HTML
    <div class="alert {$alertClass}" role="alert">
        {$message}
    </div>
    HTML;
}

/**
 * Display flash message (alias for displayFlash, echoes directly)
 */
function displayFlashMessage(): void
{
    echo displayFlash();
}

/**
 * Set flash message (shorthand)
 */
function flash(string $message, string $type = 'info'): void
{
    setFlash($type, $message);
}

/**
 * Format date for display
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '';
    }

    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if (!$datetime) {
        return '';
    }

    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'vua xong';
    }

    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phut truoc';
    }

    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' gio truoc';
    }

    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ngay truoc';
    }

    return formatDate($datetime);
}

/**
 * Truncate text with ellipsis
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get avatar URL or default
 */
function avatarUrl(?string $avatar): string
{
    if ($avatar && file_exists(AVATAR_PATH . $avatar)) {
        return asset('uploads/avatars/' . $avatar);
    }

    return asset('images/default-avatar.png');
}

/**
 * JSON response helper for API
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CSRF token generation
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * CSRF token validation
 */
function verifyCSRFToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Get CSRF input field
 */
function csrfField(): string
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Domain function modules
require_once __DIR__ . '/auth-functions.php';
require_once __DIR__ . '/project-functions.php';
require_once __DIR__ . '/member-functions.php';
require_once __DIR__ . '/task-functions.php';
require_once __DIR__ . '/upload-functions.php';
