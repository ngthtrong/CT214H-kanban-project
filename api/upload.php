<?php
/**
 * File Upload API
 * Team Kanban - CT214H Final Project
 * 
 * Endpoints:
 * POST   /api/upload.php?task_id={id}                    - Upload one or many attachments to task
 * DELETE /api/upload.php?task_id={id}&file={filename}    - Remove one attachment from task
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

    $files = collectUploadFiles();
    if (empty($files)) {
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

    $existingAttachments = taskParseAttachmentPaths($task['attachment_path'] ?? null);
    if (count($existingAttachments) + count($files) > TASK_MAX_ATTACHMENTS) {
        jsonResponse([
            'success' => false,
            'error' => 'Moi task chi duoc toi da ' . TASK_MAX_ATTACHMENTS . ' file dinh kem'
        ], 400);
    }

    $uploadedFiles = [];

    foreach ($files as $file) {
        $uploadResult = uploadAttachmentFile($file);
        if (!$uploadResult['success']) {
            foreach ($uploadedFiles as $uploadedFile) {
                deleteAttachmentFileByName($uploadedFile);
            }
            jsonResponse($uploadResult, 400);
        }

        $uploadedFiles[] = $uploadResult['data']['filename'];
    }

    $nextAttachments = array_merge($existingAttachments, $uploadedFiles);
    $attachmentValue = taskBuildAttachmentPathValue($nextAttachments);

    $updateResult = taskUpdateAttachment($taskId, $userId, $attachmentValue);
    if (!$updateResult['success']) {
        foreach ($uploadedFiles as $uploadedFile) {
            deleteAttachmentFileByName($uploadedFile);
        }
        jsonResponse($updateResult, 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Upload file thành công',
        'data' => [
            'filenames' => $uploadedFiles,
            'total_files' => count($nextAttachments)
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

    $existingAttachments = taskParseAttachmentPaths($task['attachment_path'] ?? null);
    if (empty($existingAttachments)) {
        jsonResponse(['success' => false, 'error' => 'Task không có file đính kèm'], 404);
    }

    $targetFile = isset($_GET['file']) ? basename((string) $_GET['file']) : '';
    if ($targetFile === '') {
        jsonResponse(['success' => false, 'error' => 'Vui long chon file can xoa'], 400);
    }

    if (!in_array($targetFile, $existingAttachments, true)) {
        jsonResponse(['success' => false, 'error' => 'File khong ton tai trong task nay'], 404);
    }

    $filesToDelete = [$targetFile];
    $remainingFiles = array_values(array_filter(
        $existingAttachments,
        static fn($item) => $item !== $targetFile
    ));

    $updateResult = taskUpdateAttachment($taskId, $userId, taskBuildAttachmentPathValue($remainingFiles));
    if (!$updateResult['success']) {
        jsonResponse($updateResult, 500);
    }

    foreach ($filesToDelete as $fileToDelete) {
        deleteAttachmentFileByName($fileToDelete);
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Đã xóa file đính kèm',
        'data' => [
            'remaining_files' => $remainingFiles,
            'remaining_count' => count($remainingFiles)
        ]
    ]);
}

function collectUploadFiles(): array
{
    $files = [];

    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
        $count = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $count; $i++) {
            $files[] = [
                'name' => $_FILES['attachments']['name'][$i] ?? '',
                'type' => $_FILES['attachments']['type'][$i] ?? '',
                'tmp_name' => $_FILES['attachments']['tmp_name'][$i] ?? '',
                'error' => $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['attachments']['size'][$i] ?? 0
            ];
        }
    }

    if (isset($_FILES['attachment']) && !is_array($_FILES['attachment']['name'] ?? null)) {
        $files[] = $_FILES['attachment'];
    }

    return array_values(array_filter(
        $files,
        static fn($file) => isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE
    ));
}
