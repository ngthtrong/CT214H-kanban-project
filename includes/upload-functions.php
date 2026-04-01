<?php
/**
 * Attachment upload functions.
 */

/**
 * Upload attachment file
 */
function uploadAttachmentFile(array $file): array
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf'];
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Loi upload file'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'Khong co file duoc tai len'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'File vuot qua dung luong cho phep'];
        default:
            return ['success' => false, 'error' => 'Loi khong xac dinh khi tai file'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File khong duoc vuot qua 5MB'];
    }

    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'error' => 'Chi chap nhan file: ' . implode(', ', $allowedExtensions)];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'error' => 'Loai file khong duoc phep'];
    }

    if (!is_dir(ATTACHMENT_PATH)) {
        mkdir(ATTACHMENT_PATH, 0755, true);
    }

    $newFilename = _uploadGenerateUniqueFilename($extension);
    $destination = ATTACHMENT_PATH . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Khong the luu file'];
    }

    return [
        'success' => true,
        'data' => [
            'filename' => $newFilename,
            'original_name' => $originalName,
            'size' => $file['size'],
            'mime_type' => $mimeType
        ]
    ];
}

/**
 * Delete attachment file
 */
function deleteAttachmentFileByName(string $filename): bool
{
    $filepath = ATTACHMENT_PATH . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get attachment file info
 */
function getAttachmentFileInfo(string $filename): ?array
{
    $filepath = ATTACHMENT_PATH . $filename;
    if (!file_exists($filepath)) {
        return null;
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($filepath);

    return [
        'filename' => $filename,
        'filepath' => $filepath,
        'size' => filesize($filepath),
        'mime_type' => $mimeType,
        'extension' => $extension
    ];
}

function _uploadGenerateUniqueFilename(string $extension): string
{
    return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}
