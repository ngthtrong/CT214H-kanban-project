<?php
/**
 * Projects API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * GET    /api/projects.php                 - List user's projects
 * GET    /api/projects.php?id={id}         - Get project details
 * GET    /api/projects.php?code={code}     - Find project by code
 * POST   /api/projects.php                 - Create new project
 * PUT    /api/projects.php?id={id}         - Update project
 * DELETE /api/projects.php?id={id}         - Delete project
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

use App\Project\ProjectService;

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Vui lòng đăng nhập'], 401);
}

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$service = new ProjectService();

try {
    switch ($method) {
        case 'GET':
            handleGet($service, $userId);
            break;
        case 'POST':
            handlePost($service, $userId);
            break;
        case 'PUT':
            handlePut($service, $userId);
            break;
        case 'DELETE':
            handleDelete($service, $userId);
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
function handleGet(ProjectService $service, int $userId): void
{
    // Get single project by ID
    if (isset($_GET['id'])) {
        $projectId = (int) $_GET['id'];
        $result = $service->getProject($projectId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 404);
    }

    // Find project by code
    if (isset($_GET['code'])) {
        $code = sanitize($_GET['code']);
        $result = $service->findProjectByCode($code);
        jsonResponse($result, $result['success'] ? 200 : 404);
    }

    // List all user's projects
    $result = $service->getUserProjects($userId);
    jsonResponse($result);
}

/**
 * Handle POST requests - Create project
 */
function handlePost(ProjectService $service, int $userId): void
{
    // CSRF check for non-AJAX requests
    $input = getJsonInput();
    
    $name = trim($input['project_name'] ?? '');
    $description = $input['description'] ?? null;

    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Tên dự án là bắt buộc'], 400);
    }

    $result = $service->createProject($userId, $name, $description);
    jsonResponse($result, $result['success'] ? 201 : 400);
}

/**
 * Handle PUT requests - Update project
 */
function handlePut(ProjectService $service, int $userId): void
{
    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project ID'], 400);
    }

    $projectId = (int) $_GET['id'];
    $input = getJsonInput();

    $data = [];
    if (isset($input['project_name'])) {
        $data['project_name'] = $input['project_name'];
    }
    if (array_key_exists('description', $input)) {
        $data['description'] = $input['description'];
    }

    $result = $service->updateProject($projectId, $userId, $data);
    jsonResponse($result, $result['success'] ? 200 : 400);
}

/**
 * Handle DELETE requests - Delete project
 */
function handleDelete(ProjectService $service, int $userId): void
{
    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Missing project ID'], 400);
    }

    $projectId = (int) $_GET['id'];
    $result = $service->deleteProject($projectId, $userId);
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
