<?php
/**
 * Join Requests API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/join-requests.php?project_id={id}         - Get pending requests (owner only)
 * GET    /api/join-requests.php?my=1                    - Get user's own requests
 * POST   /api/join-requests.php                         - Create join request
 * PUT    /api/join-requests.php?id={id}&action=approve  - Approve request
 * PUT    /api/join-requests.php?id={id}&action=reject   - Reject request
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Vui lòng đăng nhập'], 401);
}

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($userId);
            break;
        case 'POST':
            handlePost($userId);
            break;
        case 'PUT':
            handlePut($userId);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Join Requests API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

function handleGet(int $userId): void
{
    // Get user's own pending requests
    if (isset($_GET['my'])) {
        $result = memberGetUserPendingRequests($userId);
        jsonResponse($result);
    }

    // Get pending requests for a project (owner only)
    if (isset($_GET['project_id'])) {
        $projectId = (int) $_GET['project_id'];
        $result = memberGetPendingRequests($projectId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 403);
    }

    jsonResponse(['success' => false, 'error' => 'Missing parameters'], 400);
}

function handlePost(int $userId): void
{
    $input = getJsonInput();
    $projectId = (int) ($input['project_id'] ?? 0);

    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'Missing project_id'], 400);
    }

    $result = memberRequestJoin($projectId, $userId);
    jsonResponse($result, $result['success'] ? 201 : 400);
}

function handlePut(int $userId): void
{
    $requestId = (int) ($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';

    if (!$requestId) {
        jsonResponse(['success' => false, 'error' => 'Missing request ID'], 400);
    }

    switch ($action) {
        case 'approve':
            $result = memberApproveJoinRequest($requestId, $userId);
            break;
        case 'reject':
            $result = memberRejectJoinRequest($requestId, $userId);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action. Use approve or reject'], 400);
    }

    jsonResponse($result, $result['success'] ? 200 : 400);
}

function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}
