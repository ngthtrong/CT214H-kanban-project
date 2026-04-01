<?php
/**
 * Tasks API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/tasks.php?project_id={id}              - Get all tasks for project
 * GET    /api/tasks.php?project_id={id}&search=...   - Search/filter tasks with pagination
 * GET    /api/tasks.php?project_id={id}&filters=1    - Get filter options
 * GET    /api/tasks.php?id={id}                      - Get single task
 * POST   /api/tasks.php                              - Create task
 * PUT    /api/tasks.php?id={id}                      - Update task
 * PUT    /api/tasks.php?id={id}&action=status        - Update status only (drag & drop)
 * PUT    /api/tasks.php?id={id}&action=claim         - Claim task
 * DELETE /api/tasks.php?id={id}                      - Delete task
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
        case 'DELETE':
            handleDelete($userId);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Tasks API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

function handleGet(int $userId): void
{
    // Get single task
    if (isset($_GET['id'])) {
        $taskId = (int) $_GET['id'];
        $result = taskGet($taskId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 404);
    }

    // Get all tasks for project
    if (isset($_GET['project_id'])) {
        $projectId = (int) $_GET['project_id'];
        
        // Get filter options
        if (isset($_GET['filters'])) {
            $result = searchGetFilterOptions($projectId, $userId);
            jsonResponse($result, $result['success'] ? 200 : 403);
        }
        
        // Search/filter with any query params
        $hasSearchParams = isset($_GET['search']) || isset($_GET['status']) || 
                          isset($_GET['priority']) || isset($_GET['assigned_to']) ||
                          isset($_GET['due_date_from']) || isset($_GET['due_date_to']) ||
                          isset($_GET['page']);
        
        if ($hasSearchParams) {
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'assigned_to' => $_GET['assigned_to'] ?? '',
                'due_date_from' => $_GET['due_date_from'] ?? '',
                'due_date_to' => $_GET['due_date_to'] ?? '',
                'overdue' => isset($_GET['overdue']),
                'due_this_week' => isset($_GET['due_this_week']),
                'page' => $_GET['page'] ?? 1,
                'per_page' => $_GET['per_page'] ?? ITEMS_PER_PAGE
            ];
            $result = searchProjectTasks($projectId, $userId, $filters);
            jsonResponse($result, $result['success'] ? 200 : 403);
        }
        
        // Default: get all tasks
        $result = taskGetProjectTasks($projectId, $userId);
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

    $result = taskCreate($projectId, $userId, $input);
    jsonResponse($result, $result['success'] ? 201 : 400);
}

function handlePut(int $userId): void
{
    $taskId = (int) ($_GET['id'] ?? 0);
    if (!$taskId) {
        jsonResponse(['success' => false, 'error' => 'Missing task ID'], 400);
    }

    $action = $_GET['action'] ?? '';
    $input = getJsonInput();

    switch ($action) {
        case 'status':
            // Update status only (for drag & drop)
            $newStatus = $input['column_status'] ?? '';
            $result = taskUpdateStatus($taskId, $userId, $newStatus);
            break;
        
        case 'claim':
            // Claim task for self
            $result = taskClaim($taskId, $userId);
            break;
        
        default:
            // Full update
            $result = taskUpdate($taskId, $userId, $input);
    }

    jsonResponse($result, $result['success'] ? 200 : 400);
}

function handleDelete(int $userId): void
{
    $taskId = (int) ($_GET['id'] ?? 0);
    if (!$taskId) {
        jsonResponse(['success' => false, 'error' => 'Missing task ID'], 400);
    }

    $result = taskDelete($taskId, $userId);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}
