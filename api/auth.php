<?php
/**
 * Authentication API
 * RESTful API endpoints for authentication
 * Team Kanban - CT214H Final Project
 *
 * @deprecated This endpoint is kept for backward compatibility.
 *             Current app flow uses public auth pages with includes/auth.php.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');
header('X-Deprecated-Endpoint: true');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Track backward-compatibility usage so this endpoint can be safely removed later.
error_log('[DEPRECATED] api/auth.php endpoint called.');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get request body as array
 */
function getRequestBody(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

// Get action from query string or URL path
$action = $_GET['action'] ?? '';

// Route to appropriate handler
switch ($action) {
    case 'register':
        handleRegister();
        break;
        
    case 'login':
        handleLogin();
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    case 'me':
    case 'profile':
        handleProfile();
        break;
        
    case 'update-profile':
        handleUpdateProfile();
        break;
        
    case 'change-password':
        handleChangePassword();
        break;
        
    case 'check':
        handleCheck();
        break;
        
    default:
        jsonResponse([
            'success' => false,
            'error' => 'Invalid action',
            'available_actions' => ['register', 'login', 'logout', 'me', 'profile', 'update-profile', 'change-password', 'check']
        ], 400);
}

/**
 * POST /api/auth.php?action=register
 * Register new user
 */
function handleRegister(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    $data = getRequestBody();
    
    $result = registerUser([
        'username' => $data['username'] ?? '',
        'email' => $data['email'] ?? '',
        'full_name' => $data['full_name'] ?? '',
        'password' => $data['password'] ?? ''
    ]);
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'data' => ['user_id' => $result['user_id']],
            'message' => $result['message']
        ], 201);
    } else {
        jsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

/**
 * POST /api/auth.php?action=login
 * Login user
 */
function handleLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    $data = getRequestBody();
    
    $result = authenticateUser(
        $data['identifier'] ?? $data['username'] ?? $data['email'] ?? '',
        $data['password'] ?? ''
    );
    
    if ($result['success']) {
        // Create session
        $remember = $data['remember'] ?? false;
        loginUser($result['user'], $remember);
        
        jsonResponse([
            'success' => true,
            'data' => $result['user'],
            'message' => $result['message']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 401);
    }
}

/**
 * POST /api/auth.php?action=logout
 * Logout user
 */
function handleLogout(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    logoutUser();
    
    jsonResponse([
        'success' => true,
        'message' => 'Đăng xuất thành công'
    ]);
}

/**
 * GET /api/auth.php?action=me
 * GET /api/auth.php?action=profile
 * Get current user profile
 */
function handleProfile(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
    }
    
    $user = getCurrentUser();
    $profile = getUserById($user['user_id']);
    
    if ($profile) {
        jsonResponse([
            'success' => true,
            'data' => $profile
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'User not found'], 404);
    }
}

/**
 * PUT /api/auth.php?action=update-profile
 * Update user profile
 */
function handleUpdateProfile(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
    }
    
    $user = getCurrentUser();
    $data = getRequestBody();
    
    $result = updateUserProfile($user['user_id'], [
        'full_name' => $data['full_name'] ?? null,
        'email' => $data['email'] ?? null
    ]);
    
    if ($result['success']) {
        // Update session
        $updatedUser = getUserById($user['user_id']);
        $_SESSION['user'] = $updatedUser;
        
        jsonResponse([
            'success' => true,
            'data' => $updatedUser,
            'message' => $result['message']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

/**
 * POST /api/auth.php?action=change-password
 * Change user password
 */
function handleChangePassword(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
    }
    
    $user = getCurrentUser();
    $data = getRequestBody();
    
    $result = changeUserPassword(
        $user['user_id'],
        $data['current_password'] ?? '',
        $data['new_password'] ?? ''
    );
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

/**
 * GET /api/auth.php?action=check
 * Check if user is logged in
 */
function handleCheck(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
    jsonResponse([
        'success' => true,
        'logged_in' => isLoggedIn(),
        'user' => isLoggedIn() ? getCurrentUser() : null
    ]);
}
