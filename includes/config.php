<?php
/**
 * Application Configuration
 * Team Kanban - CT214H Final Project
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Environment
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'kanban_db');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Team Kanban');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/CT214H-kanban-project');

// Session configuration
define('SESSION_NAME', 'kanban_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Upload configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('AVATAR_PATH', APP_ROOT . '/uploads/avatars/');
define('ATTACHMENT_PATH', APP_ROOT . '/uploads/attachments/');

// Password requirements
define('PASSWORD_MIN_LENGTH', 6);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');
