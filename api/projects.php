<?php
/**
 * Projects API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/projects.php                 - List user's projects
 * GET    /api/projects.php?archived=1      - List user's archived projects
 * GET    /api/projects.php?id={id}         - Get project details
 * GET    /api/projects.php?code={code}     - Find project by code
 * POST   /api/projects.php                 - Create new project
 * PUT    /api/projects.php?id={id}         - Update project
 * PUT    /api/projects.php?id={id}&action=archive - Archive project (soft delete)
 * PUT    /api/projects.php?id={id}&action=unarchive - Unarchive project
 * DELETE /api/projects.php?id={id}         - Delete project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Check authentication
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
    error_log('Projects API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

/**
 * Handle GET requests
 */
function handleGet(int $userId): void
{
    // List archived projects
    if (isset($_GET['archived'])) {
        $result = projectGetUserArchivedProjects($userId);
        jsonResponse($result);
    }

    // Get single project by ID
    if (isset($_GET['id'])) {
        $projectId = (int) $_GET['id'];
        $result = projectGet($projectId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 404);
    }

    // Find project by code
    if (isset($_GET['code'])) {
        $code = sanitize($_GET['code']);
        $result = projectFindByCode($code);
        jsonResponse($result, $result['success'] ? 200 : 404);
    }

    // List all user's projects
    $result = projectGetUserProjects($userId);
    jsonResponse($result);
}

/**
 * Handle POST requests - Create project
 */
function handlePost(int $userId): void
{
    // CSRF check for non-AJAX requests
    $input = getJsonInput();
    
    $name = trim($input['project_name'] ?? '');
    $description = $input['description'] ?? null;

    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Tên dự án là bắt buộc'], 400);
    }

    $result = projectCreate($userId, $name, $description);
    jsonResponse($result, $result['success'] ? 201 : 400);
}

/**
 * Handle PUT requests - Update project
 */
function handlePut(int $userId): void
{
    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project ID'], 400);
    }

    $projectId = (int) $_GET['id'];
    $action = $_GET['action'] ?? '';

    if ($action === 'archive') {
        $result = projectArchive($projectId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 400);
    }

    if ($action === 'unarchive') {
        $result = projectUnarchive($projectId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 400);
    }

    $input = getJsonInput();

    $data = [];
    if (isset($input['project_name'])) {
        $data['project_name'] = $input['project_name'];
    }
    if (array_key_exists('description', $input)) {
        $data['description'] = $input['description'];
    }

    $result = projectUpdate($projectId, $userId, $data);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

/**
 * Handle DELETE requests - Delete project
 */
function handleDelete(int $userId): void
{
    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project ID'], 400);
    }

    $projectId = (int) $_GET['id'];
    $result = projectDelete($projectId, $userId);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

/**
 * Get JSON input from request body
 */
function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}
