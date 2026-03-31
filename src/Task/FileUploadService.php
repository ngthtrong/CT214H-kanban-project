<?php
/**
 * File Upload Service
 * Team Kanban - CT214H Final Project
 */

namespace App\Task;

require_once __DIR__ . '/../../includes/config.php';

class FileUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    /**
     * Upload attachment file for task
     */
    public function uploadAttachment(array $file): array
    {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Lỗi upload file'];
        }

        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'Không có file được tải lên'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'File vượt quá dung lượng cho phép'];
            default:
                return ['success' => false, 'error' => 'Lỗi không xác định khi tải file'];
        }

        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'File không được vượt quá 5MB'];
        }

        // Check extension
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'error' => 'Chỉ chấp nhận file: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            ];
        }

        // Check MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return ['success' => false, 'error' => 'Loại file không được phép'];
        }

        // Create upload directory if not exists
        if (!is_dir(ATTACHMENT_PATH)) {
            mkdir(ATTACHMENT_PATH, 0755, true);
        }

        // Generate unique filename
        $newFilename = $this->generateUniqueFilename($extension);
        $destination = ATTACHMENT_PATH . $newFilename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Không thể lưu file'];
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
    public function deleteAttachment(string $filename): bool
    {
        $filepath = ATTACHMENT_PATH . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get file download info
     */
    public function getFileInfo(string $filename): ?array
    {
        $filepath = ATTACHMENT_PATH . $filename;
        if (!file_exists($filepath)) {
            return null;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $extension): string
    {
        $timestamp = date('Ymd_His');
        $random = bin2hex(random_bytes(8));
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get max file size in human readable format
     */
    public static function getMaxFileSizeReadable(): string
    {
        return '5MB';
    }

    /**
     * Get allowed extensions
     */
    public static function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }
}
