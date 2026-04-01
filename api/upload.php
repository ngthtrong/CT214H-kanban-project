<?php
/**
 * File Upload API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * POST   /api/upload.php?task_id={id}    - Upload attachment to task
 * DELETE /api/upload.php?task_id={id}    - Remove attachment from task
 * GET    /api/upload.php?file={filename} - Download attachment
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Auth check
if (!isLoggedIn()) {
    if (isAjax()) {
        header('Content-Type: application/json');
        jsonResponse(['success' => false, 'error' => 'Vui lòng đăng nhập'], 401);
    } else {
        redirect('login.php');
    }
}

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleDownload();
            break;
        case 'POST':
            handleUpload($userId);
            break;
        case 'DELETE':
            handleDelete($userId);
            break;
        default:
            header('Content-Type: application/json');
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Upload API Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    jsonResponse(['success' => false, 'error' => 'Có lỗi xảy ra'], 500);
}

function handleDownload(): void
{
    $filename = $_GET['file'] ?? '';
    if (empty($filename)) {
        header('Content-Type: application/json');
        jsonResponse(['success' => false, 'error' => 'Missing filename'], 400);
    }

    // Sanitize filename to prevent directory traversal
    $filename = basename($filename);
    
    $fileInfo = getAttachmentFileInfo($filename);
    
    if (!$fileInfo) {
        header('Content-Type: application/json');
        jsonResponse(['success' => false, 'error' => 'File not found'], 404);
    }

    // Send file for download
    header('Content-Type: ' . $fileInfo['mime_type']);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $fileInfo['size']);
    header('Cache-Control: no-cache');
    
    readfile($fileInfo['filepath']);
    exit;
}

function handleUpload(int $userId): void
{
    header('Content-Type: application/json');
    
    $taskId = (int) ($_GET['task_id'] ?? $_POST['task_id'] ?? 0);
    if (!$taskId) {
        jsonResponse(['success' => false, 'error' => 'Missing task_id'], 400);
    }

    // Check if file was uploaded
    if (!isset($_FILES['attachment'])) {
        jsonResponse(['success' => false, 'error' => 'Không có file được tải lên'], 400);
    }

    // Check user can upload to this task
    $taskResult = taskGet($taskId, $userId);
    if (!$taskResult['success']) {
        jsonResponse($taskResult, 403);
    }

    $task = $taskResult['data'];
    $isOwner = $task['is_owner'];
    $isAssigned = $task['is_assigned'];

    if (!$isOwner && !$isAssigned) {
        jsonResponse(['success' => false, 'error' => 'Bạn không có quyền upload file cho task này'], 403);
    }

    // Upload file
    $uploadResult = uploadAttachmentFile($_FILES['attachment']);
    if (!$uploadResult['success']) {
        jsonResponse($uploadResult, 400);
    }

    // Update task with attachment path
    $updateResult = taskUpdateAttachment($taskId, $userId, $uploadResult['data']['filename']);
    if (!$updateResult['success']) {
        // Rollback: delete uploaded file
        deleteAttachmentFileByName($uploadResult['data']['filename']);
        jsonResponse($updateResult, 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Upload file thành công',
        'data' => [
            'filename' => $uploadResult['data']['filename'],
            'original_name' => $uploadResult['data']['original_name'],
            'size' => $uploadResult['data']['size']
        ]
    ]);
}

function handleDelete(int $userId): void
{
    header('Content-Type: application/json');
    
    $taskId = (int) ($_GET['task_id'] ?? 0);
    if (!$taskId) {
        jsonResponse(['success' => false, 'error' => 'Missing task_id'], 400);
    }

    // Check user can modify this task
    $taskResult = taskGet($taskId, $userId);
    if (!$taskResult['success']) {
        jsonResponse($taskResult, 403);
    }

    $task = $taskResult['data'];
    $isOwner = $task['is_owner'];
    $isAssigned = $task['is_assigned'];

    if (!$isOwner && !$isAssigned) {
        jsonResponse(['success' => false, 'error' => 'Bạn không có quyền xóa file của task này'], 403);
    }

    // Delete file from filesystem
    if ($task['attachment_path']) {
        deleteAttachmentFileByName($task['attachment_path']);
    }

    // Update task to remove attachment reference
    $updateResult = taskUpdateAttachment($taskId, $userId, null);
    
    jsonResponse([
        'success' => true,
        'message' => 'Đã xóa file đính kèm'
    ]);
}
