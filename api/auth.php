<?php
/**
 * Authentication API
 *
 * Endpoints:
 * GET  /api/auth.php              - Session status
 * POST /api/auth.php?action=login - Login by identifier/password
 * POST /api/auth.php?action=logout- Logout current session
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    jsonResponse([
        'success' => true,
        'authenticated' => isLoggedIn(),
        'user' => isLoggedIn() ? getCurrentUser() : null
    ]);
}

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = getJsonInput();
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

switch ($action) {
    case 'login':
        $identifier = trim($input['identifier'] ?? $_POST['identifier'] ?? '');
        $password = $input['password'] ?? $_POST['password'] ?? '';
        $remember = (bool) ($input['remember'] ?? $_POST['remember'] ?? false);

        $result = authenticateUser($identifier, $password);
        if (!$result['success']) {
            jsonResponse($result, 401);
        }

        loginUser($result['user'], $remember);
        jsonResponse([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => ['user' => getCurrentUser()]
        ]);
        break;

    case 'logout':
        if (isLoggedIn()) {
            logoutUser();
        }

        jsonResponse([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
