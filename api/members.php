<?php
/**
 * Members API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/members.php?project_id={id}                    - List project members
 * POST   /api/members.php                                    - Add member by identifier
 * DELETE /api/members.php?project_id={id}&user_id={userId}   - Remove member
 * POST   /api/members.php?action=leave                       - Leave project
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

use App\Member\MemberService;

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Vui lòng đăng nhập'], 401);
}

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$service = new MemberService();

try {
    switch ($method) {
        case 'GET':
            handleGet($service, $userId);
            break;
        case 'POST':
            handlePost($service, $userId);
            break;
        case 'DELETE':
            handleDelete($service, $userId);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Members API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

function handleGet(MemberService $service, int $userId): void
{
    if (!isset($_GET['project_id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project_id'], 400);
    }

    $projectId = (int) $_GET['project_id'];
    $result = $service->getProjectMembers($projectId, $userId);
    jsonResponse($result, $result['success'] ? 200 : 403);
}

function handlePost(MemberService $service, int $userId): void
{
    $input = getJsonInput();
    $action = $_GET['action'] ?? $input['action'] ?? 'add';

    switch ($action) {
        case 'add':
            // Add member to project
            $projectId = (int) ($input['project_id'] ?? 0);
            $identifier = trim($input['identifier'] ?? '');

            if (!$projectId || !$identifier) {
                jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
            }

            $result = $service->addMember($projectId, $userId, $identifier);
            jsonResponse($result, $result['success'] ? 200 : 400);
            break;

        case 'leave':
            // Leave project
            $projectId = (int) ($input['project_id'] ?? 0);
            if (!$projectId) {
                jsonResponse(['success' => false, 'error' => 'Missing project_id'], 400);
            }

            $result = $service->leaveProject($projectId, $userId);
            jsonResponse($result, $result['success'] ? 200 : 400);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
}

function handleDelete(MemberService $service, int $userId): void
{
    $projectId = (int) ($_GET['project_id'] ?? 0);
    $memberUserId = (int) ($_GET['user_id'] ?? 0);

    if (!$projectId || !$memberUserId) {
        jsonResponse(['success' => false, 'error' => 'Missing required parameters'], 400);
    }

    $result = $service->removeMember($projectId, $userId, $memberUserId);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}
