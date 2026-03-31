<?php
/**
 * Task Service
 * Team Kanban - CT214H Final Project
 */

namespace App\Task;

require_once __DIR__ . '/../../includes/db-connect.php';
require_once __DIR__ . '/../../includes/functions.php';

class TaskService
{
    private const VALID_STATUSES = ['todo', 'in_progress', 'done'];
    private const VALID_PRIORITIES = ['low', 'medium', 'high'];

    /**
     * Create a new task
     */
    public function createTask(int $projectId, int $userId, array $data): array
    {
        // Validate user has access to project
        if (!$this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền tạo task trong dự án này'];
        }

        // Validate title
        $title = trim($data['task_title'] ?? '');
        if (empty($title)) {
            return ['success' => false, 'error' => 'Tiêu đề task không được để trống'];
        }
        if (strlen($title) > 200) {
            return ['success' => false, 'error' => 'Tiêu đề task không được quá 200 ký tự'];
        }

        // Validate status
        $status = $data['column_status'] ?? 'todo';
        if (!in_array($status, self::VALID_STATUSES)) {
            $status = 'todo';
        }

        // Validate priority
        $priority = $data['priority'] ?? 'medium';
        if (!in_array($priority, self::VALID_PRIORITIES)) {
            $priority = 'medium';
        }

        // Validate assigned_to (must be a project member)
        $assignedTo = null;
        if (!empty($data['assigned_to'])) {
            $assignedTo = (int) $data['assigned_to'];
            if (!$this->isMember($projectId, $assignedTo)) {
                return ['success' => false, 'error' => 'Người được gán phải là thành viên của dự án'];
            }
        }

        // Validate due_date
        $dueDate = null;
        if (!empty($data['due_date'])) {
            $dueDate = date('Y-m-d', strtotime($data['due_date']));
        }

        try {
            $taskId = dbInsert('tasks', [
                'project_id' => $projectId,
                'assigned_to' => $assignedTo,
                'task_title' => $title,
                'description' => $data['description'] ?? null,
                'column_status' => $status,
                'priority' => $priority,
                'due_date' => $dueDate
            ]);

            return [
                'success' => true,
                'message' => 'Tạo task thành công',
                'data' => ['task_id' => $taskId]
            ];
        } catch (\Exception $e) {
            error_log('Create task failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể tạo task'];
        }
    }

    /**
     * Get single task
     */
    public function getTask(int $taskId, int $userId): array
    {
        $task = dbQueryOne(
            "SELECT t.*, 
                    u.username as assigned_username, u.full_name as assigned_name,
                    p.project_name, p.owner_id
             FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.user_id
             JOIN projects p ON t.project_id = p.project_id
             WHERE t.task_id = ?",
            [$taskId]
        );

        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        // Check access
        if (!$this->isMember($task['project_id'], $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền xem task này'];
        }

        $task['is_owner'] = ($task['owner_id'] == $userId);
        $task['is_assigned'] = ($task['assigned_to'] == $userId);

        return ['success' => true, 'data' => $task];
    }

    /**
     * Get all tasks for a project
     */
    public function getProjectTasks(int $projectId, int $userId): array
    {
        if (!$this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền xem tasks của dự án này'];
        }

        $tasks = dbQuery(
            "SELECT t.*, 
                    u.username as assigned_username, u.full_name as assigned_name, u.avatar as assigned_avatar
             FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.user_id
             WHERE t.project_id = ?
             ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC",
            [$projectId]
        );

        // Group by status for Kanban view
        $grouped = [
            'todo' => [],
            'in_progress' => [],
            'done' => []
        ];

        foreach ($tasks as $task) {
            $grouped[$task['column_status']][] = $task;
        }

        return [
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'grouped' => $grouped
            ]
        ];
    }

    /**
     * Update task
     */
    public function updateTask(int $taskId, int $userId, array $data): array
    {
        $task = $this->getTaskBasic($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        $isOwner = $this->isOwner($task['project_id'], $userId);
        $isAssigned = ($task['assigned_to'] == $userId);

        // Permission check: Owner can edit all, Member can only edit assigned tasks
        if (!$isOwner && !$isAssigned) {
            return ['success' => false, 'error' => 'Bạn không có quyền chỉnh sửa task này'];
        }

        $updateData = [];

        // Title
        if (isset($data['task_title'])) {
            $title = trim($data['task_title']);
            if (empty($title)) {
                return ['success' => false, 'error' => 'Tiêu đề task không được để trống'];
            }
            if (strlen($title) > 200) {
                return ['success' => false, 'error' => 'Tiêu đề task không được quá 200 ký tự'];
            }
            $updateData['task_title'] = $title;
        }

        // Description
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        // Status
        if (isset($data['column_status'])) {
            if (!in_array($data['column_status'], self::VALID_STATUSES)) {
                return ['success' => false, 'error' => 'Trạng thái không hợp lệ'];
            }
            $updateData['column_status'] = $data['column_status'];
        }

        // Priority
        if (isset($data['priority'])) {
            if (!in_array($data['priority'], self::VALID_PRIORITIES)) {
                return ['success' => false, 'error' => 'Mức độ ưu tiên không hợp lệ'];
            }
            $updateData['priority'] = $data['priority'];
        }

        // Due date
        if (array_key_exists('due_date', $data)) {
            $updateData['due_date'] = $data['due_date'] ? date('Y-m-d', strtotime($data['due_date'])) : null;
        }

        // Assigned to (only owner can change)
        if (isset($data['assigned_to']) && $isOwner) {
            if ($data['assigned_to'] === null || $data['assigned_to'] === '') {
                $updateData['assigned_to'] = null;
            } else {
                $assignedTo = (int) $data['assigned_to'];
                if (!$this->isMember($task['project_id'], $assignedTo)) {
                    return ['success' => false, 'error' => 'Người được gán phải là thành viên của dự án'];
                }
                $updateData['assigned_to'] = $assignedTo;
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'error' => 'Không có dữ liệu để cập nhật'];
        }

        try {
            dbUpdate('tasks', $updateData, 'task_id = ?', [$taskId]);
            return ['success' => true, 'message' => 'Cập nhật task thành công'];
        } catch (\Exception $e) {
            error_log('Update task failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật task'];
        }
    }

    /**
     * Update task status (for drag & drop)
     */
    public function updateTaskStatus(int $taskId, int $userId, string $newStatus): array
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            return ['success' => false, 'error' => 'Trạng thái không hợp lệ'];
        }

        $task = $this->getTaskBasic($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        $isOwner = $this->isOwner($task['project_id'], $userId);
        $isAssigned = ($task['assigned_to'] == $userId);

        // Member can only drag their own tasks
        if (!$isOwner && !$isAssigned) {
            return ['success' => false, 'error' => 'Bạn chỉ có thể di chuyển task được gán cho mình'];
        }

        try {
            dbUpdate('tasks', ['column_status' => $newStatus], 'task_id = ?', [$taskId]);
            return [
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => [
                    'task_id' => $taskId,
                    'new_status' => $newStatus
                ]
            ];
        } catch (\Exception $e) {
            error_log('Update task status failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật trạng thái'];
        }
    }

    /**
     * Delete task
     */
    public function deleteTask(int $taskId, int $userId): array
    {
        $task = $this->getTaskBasic($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        // Only owner can delete tasks
        if (!$this->isOwner($task['project_id'], $userId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể xóa task'];
        }

        try {
            // Delete attachment file if exists
            if ($task['attachment_path']) {
                $filePath = ATTACHMENT_PATH . $task['attachment_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            dbDelete('tasks', 'task_id = ?', [$taskId]);
            return ['success' => true, 'message' => 'Xóa task thành công'];
        } catch (\Exception $e) {
            error_log('Delete task failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể xóa task'];
        }
    }

    /**
     * Claim task (assign to self)
     */
    public function claimTask(int $taskId, int $userId): array
    {
        $task = $this->getTaskBasic($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        if (!$this->isMember($task['project_id'], $userId)) {
            return ['success' => false, 'error' => 'Bạn không phải thành viên của dự án'];
        }

        if ($task['assigned_to'] !== null) {
            return ['success' => false, 'error' => 'Task đã được gán cho người khác'];
        }

        try {
            dbUpdate('tasks', ['assigned_to' => $userId], 'task_id = ?', [$taskId]);
            return ['success' => true, 'message' => 'Đã nhận task thành công'];
        } catch (\Exception $e) {
            error_log('Claim task failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể nhận task'];
        }
    }

    /**
     * Update task attachment
     */
    public function updateAttachment(int $taskId, int $userId, ?string $attachmentPath): array
    {
        $task = $this->getTaskBasic($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task không tồn tại'];
        }

        $isOwner = $this->isOwner($task['project_id'], $userId);
        $isAssigned = ($task['assigned_to'] == $userId);

        if (!$isOwner && !$isAssigned) {
            return ['success' => false, 'error' => 'Bạn không có quyền upload file cho task này'];
        }

        try {
            // Delete old attachment if exists and new one is being set
            if ($task['attachment_path'] && $attachmentPath !== null) {
                $oldFilePath = ATTACHMENT_PATH . $task['attachment_path'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            dbUpdate('tasks', ['attachment_path' => $attachmentPath], 'task_id = ?', [$taskId]);
            return ['success' => true, 'message' => 'Cập nhật file đính kèm thành công'];
        } catch (\Exception $e) {
            error_log('Update attachment failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật file đính kèm'];
        }
    }

    /**
     * Helper: Get basic task info
     */
    private function getTaskBasic(int $taskId): ?array
    {
        return dbQueryOne(
            "SELECT t.*, p.owner_id FROM tasks t 
             JOIN projects p ON t.project_id = p.project_id 
             WHERE t.task_id = ?",
            [$taskId]
        );
    }

    /**
     * Helper: Check if user is member
     */
    private function isMember(int $projectId, int $userId): bool
    {
        $result = dbQueryOne(
            "SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $userId]
        );
        return $result !== null;
    }

    /**
     * Helper: Check if user is owner
     */
    private function isOwner(int $projectId, int $userId): bool
    {
        $result = dbQueryOne(
            "SELECT 1 FROM projects WHERE project_id = ? AND owner_id = ?",
            [$projectId, $userId]
        );
        return $result !== null;
    }
}
