<?php
/**
 * Members API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/members.php?project_id={id}                    - List project members
 * DELETE /api/members.php?project_id={id}&user_id={userId}   - Remove member
 * POST   /api/members.php?action=leave                       - Leave project
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
        case 'DELETE':
            handleDelete($userId);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Members API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

function handleGet(int $userId): void
{
    if (!isset($_GET['project_id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project_id'], 400);
    }

    $projectId = (int) $_GET['project_id'];
    $result = memberGetProjectMembers($projectId, $userId);
    jsonResponse($result, $result['success'] ? 200 : 403);
}

function handlePost(int $userId): void
{
    $input = getJsonInput();
    $action = $_GET['action'] ?? $input['action'] ?? 'add';

    switch ($action) {
        case 'add':
            jsonResponse([
                'success' => false,
                'error' => 'Chuc nang them thanh vien truc tiep da duoc tat. Vui long su dung luong yeu cau tham gia du an.'
            ], 403);
            break;

        case 'leave':
            // Leave project
            $projectId = (int) ($input['project_id'] ?? 0);
            if (!$projectId) {
                jsonResponse(['success' => false, 'error' => 'Missing project_id'], 400);
            }

            $result = memberLeaveProject($projectId, $userId);
            jsonResponse($result, $result['success'] ? 200 : 400);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
}

function handleDelete(int $userId): void
{
    $projectId = (int) ($_GET['project_id'] ?? 0);
    $memberUserId = (int) ($_GET['user_id'] ?? 0);

    if (!$projectId || !$memberUserId) {
        jsonResponse(['success' => false, 'error' => 'Missing required parameters'], 400);
    }

    $result = memberRemoveMember($projectId, $userId, $memberUserId);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}
