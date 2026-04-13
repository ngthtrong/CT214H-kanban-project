<?php
/**
 * Task and search/filter functions.
 */

/**
 * Create task
 */
function taskCreate(int $projectId, int $userId, array $data): array
{
    if (!taskIsOwner($projectId, $userId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the tao task'];
    }

    $title = trim($data['task_title'] ?? '');
    if ($title === '') {
        return ['success' => false, 'error' => 'Tieu de task khong duoc de trong'];
    }
    if (strlen($title) > 200) {
        return ['success' => false, 'error' => 'Tieu de task khong duoc qua 200 ky tu'];
    }

    $status = $data['column_status'] ?? 'todo';
    if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
        $status = 'todo';
    }

    $priority = $data['priority'] ?? 'medium';
    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        $priority = 'medium';
    }

    $assignedTo = null;
    if (!empty($data['assigned_to'])) {
        $assignedTo = (int) $data['assigned_to'];
        if (!taskIsMember($projectId, $assignedTo)) {
            return ['success' => false, 'error' => 'Nguoi duoc gan phai la thanh vien cua du an'];
        }
    }

    $dueDateResult = _taskNormalizeDueDate($data['due_date'] ?? null);
    if (!$dueDateResult['success']) {
        return ['success' => false, 'error' => $dueDateResult['error']];
    }
    $dueDate = $dueDateResult['value'];

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
            'message' => 'Tao task thanh cong',
            'data' => ['task_id' => $taskId]
        ];
    } catch (Exception $e) {
        error_log('Create task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the tao task'];
    }
}

/**
 * Get task detail
 */
function taskGet(int $taskId, int $userId): array
{
    $task = dbQueryOne(
        'SELECT t.*, u.username as assigned_username, u.full_name as assigned_name,
                p.project_name, p.owner_id
         FROM tasks t
         LEFT JOIN users u ON t.assigned_to = u.user_id
         JOIN projects p ON t.project_id = p.project_id
            WHERE t.task_id = ? AND t.is_archived = 0 AND p.is_archived = 0',
        [$taskId]
    );

    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    if (!taskIsMember((int) $task['project_id'], $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen xem task nay'];
    }

    $task['is_owner'] = ((int) $task['owner_id'] === $userId);
    $task['is_assigned'] = ((int) $task['assigned_to'] === $userId);
    $task['can_edit'] = $task['is_owner'] || $task['is_assigned'];
    $task['can_delete'] = $task['is_owner'];
    $task['can_archive'] = $task['is_owner'];
    $task['assignee_name'] = $task['assigned_name'];
    $task['attachment_files'] = taskParseAttachmentPaths($task['attachment_path'] ?? null);

    return ['success' => true, 'data' => $task];
}

/**
 * Get all tasks of a project
 */
function taskGetProjectTasks(int $projectId, int $userId): array
{
    if (!taskIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen xem tasks cua du an nay'];
    }

    $tasks = dbQuery(
        'SELECT t.*, u.username as assigned_username, u.full_name as assigned_name, u.avatar as assigned_avatar
         FROM tasks t
         LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE t.project_id = ? AND t.is_archived = 0
         ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC',
        [$projectId]
    );

    foreach ($tasks as &$task) {
        $task['assignee_name'] = $task['assigned_name'];
        $task['attachment_files'] = taskParseAttachmentPaths($task['attachment_path'] ?? null);
    }

    return ['success' => true, 'data' => $tasks];
}

/**
 * Get archived tasks of a project
 */
function taskGetArchivedProjectTasks(int $projectId, int $userId): array
{
    if (!taskIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen xem tasks cua du an nay'];
    }

    $tasks = dbQuery(
        'SELECT t.*, u.username as assigned_username, u.full_name as assigned_name, u.avatar as assigned_avatar
         FROM tasks t
         LEFT JOIN users u ON t.assigned_to = u.user_id
         WHERE t.project_id = ? AND t.is_archived = 1
         ORDER BY t.archived_at DESC, t.updated_at DESC',
        [$projectId]
    );

    foreach ($tasks as &$task) {
        $task['assignee_name'] = $task['assigned_name'];
        $task['attachment_files'] = taskParseAttachmentPaths($task['attachment_path'] ?? null);
    }

    return ['success' => true, 'data' => $tasks];
}

/**
 * Update task
 */
function taskUpdate(int $taskId, int $userId, array $data): array
{
    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    $isOwner = taskIsOwner((int) $task['project_id'], $userId);
    $isAssigned = ((int) $task['assigned_to'] === $userId);

    if (!$isOwner && !$isAssigned) {
        return ['success' => false, 'error' => 'Ban khong co quyen chinh sua task nay'];
    }

    $updateData = [];

    if (isset($data['task_title'])) {
        $title = trim((string) $data['task_title']);
        if ($title === '') {
            return ['success' => false, 'error' => 'Tieu de task khong duoc de trong'];
        }
        if (strlen($title) > 200) {
            return ['success' => false, 'error' => 'Tieu de task khong duoc qua 200 ky tu'];
        }
        $updateData['task_title'] = $title;
    }

    if (array_key_exists('description', $data)) {
        $updateData['description'] = $data['description'];
    }

    if (isset($data['column_status'])) {
        if (!in_array($data['column_status'], ['todo', 'in_progress', 'done'], true)) {
            return ['success' => false, 'error' => 'Trang thai khong hop le'];
        }
        $updateData['column_status'] = $data['column_status'];
    }

    if (isset($data['priority'])) {
        if (!in_array($data['priority'], ['low', 'medium', 'high'], true)) {
            return ['success' => false, 'error' => 'Muc do uu tien khong hop le'];
        }
        $updateData['priority'] = $data['priority'];
    }

    if (array_key_exists('due_date', $data)) {
        $dueDateResult = _taskNormalizeDueDate($data['due_date'], $task['due_date'] ?? null);
        if (!$dueDateResult['success']) {
            return ['success' => false, 'error' => $dueDateResult['error']];
        }
        $updateData['due_date'] = $dueDateResult['value'];
    }

    if (isset($data['assigned_to']) && $isOwner) {
        if ($data['assigned_to'] === null || $data['assigned_to'] === '') {
            $updateData['assigned_to'] = null;
        } else {
            $assignedTo = (int) $data['assigned_to'];
            if (!taskIsMember((int) $task['project_id'], $assignedTo)) {
                return ['success' => false, 'error' => 'Nguoi duoc gan phai la thanh vien cua du an'];
            }
            $updateData['assigned_to'] = $assignedTo;
        }
    }

    if (empty($updateData)) {
        return ['success' => false, 'error' => 'Khong co du lieu de cap nhat'];
    }

    try {
        dbUpdate('tasks', $updateData, 'task_id = ?', [$taskId]);
        return ['success' => true, 'message' => 'Cap nhat task thanh cong'];
    } catch (Exception $e) {
        error_log('Update task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the cap nhat task'];
    }
}

/**
 * Update task status
 */
function taskUpdateStatus(int $taskId, int $userId, string $newStatus): array
{
    if (!in_array($newStatus, ['todo', 'in_progress', 'done'], true)) {
        return ['success' => false, 'error' => 'Trang thai khong hop le'];
    }

    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    $isOwner = taskIsOwner((int) $task['project_id'], $userId);
    $isAssigned = ((int) $task['assigned_to'] === $userId);
    if (!$isOwner && !$isAssigned) {
        return ['success' => false, 'error' => 'Ban chi co the di chuyen task duoc gan cho minh'];
    }

    try {
        dbUpdate('tasks', ['column_status' => $newStatus], 'task_id = ?', [$taskId]);
        return [
            'success' => true,
            'message' => 'Cap nhat trang thai thanh cong',
            'data' => ['task_id' => $taskId, 'new_status' => $newStatus]
        ];
    } catch (Exception $e) {
        error_log('Update task status failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the cap nhat trang thai'];
    }
}

/**
 * Delete task
 */
function taskDelete(int $taskId, int $userId): array
{
    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    if (!taskIsOwner((int) $task['project_id'], $userId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the xoa task'];
    }

    try {
        if (!empty($task['attachment_path'])) {
            $attachmentFiles = taskParseAttachmentPaths($task['attachment_path']);
            foreach ($attachmentFiles as $attachmentFile) {
                $filePath = ATTACHMENT_PATH . $attachmentFile;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        dbDelete('tasks', 'task_id = ?', [$taskId]);
        return ['success' => true, 'message' => 'Xoa task thanh cong'];
    } catch (Exception $e) {
        error_log('Delete task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the xoa task'];
    }
}

/**
 * Archive task (soft delete)
 */
function taskArchive(int $taskId, int $userId): array
{
    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai hoac da duoc luu tru'];
    }

    if (!taskIsOwner((int) $task['project_id'], $userId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the luu tru task'];
    }

    try {
        dbUpdate('tasks', [
            'is_archived' => 1,
            'archived_at' => date('Y-m-d H:i:s')
        ], 'task_id = ?', [$taskId]);

        return ['success' => true, 'message' => 'Luu tru task thanh cong'];
    } catch (Exception $e) {
        error_log('Archive task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the luu tru task'];
    }
}

/**
 * Unarchive task
 */
function taskUnarchive(int $taskId, int $userId): array
{
    $task = dbQueryOne(
        'SELECT t.task_id, t.project_id, t.is_archived, p.owner_id
         FROM tasks t
         JOIN projects p ON t.project_id = p.project_id
         WHERE t.task_id = ? AND p.is_archived = 0',
        [$taskId]
    );

    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    if ((int) $task['owner_id'] !== $userId) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the bo luu tru task'];
    }

    if ((int) $task['is_archived'] === 0) {
        return ['success' => true, 'message' => 'Task da o trang thai hoat dong'];
    }

    try {
        dbUpdate('tasks', [
            'is_archived' => 0,
            'archived_at' => null
        ], 'task_id = ?', [$taskId]);

        return ['success' => true, 'message' => 'Khoi phuc task thanh cong'];
    } catch (Exception $e) {
        error_log('Unarchive task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the khoi phuc task'];
    }
}

/**
 * Claim task
 */
function taskClaim(int $taskId, int $userId): array
{
    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    if (!taskIsMember((int) $task['project_id'], $userId)) {
        return ['success' => false, 'error' => 'Ban khong phai thanh vien cua du an'];
    }

    if ($task['assigned_to'] !== null) {
        return ['success' => false, 'error' => 'Task da duoc gan cho nguoi khac'];
    }

    try {
        dbUpdate('tasks', ['assigned_to' => $userId], 'task_id = ?', [$taskId]);
        return ['success' => true, 'message' => 'Da nhan task thanh cong'];
    } catch (Exception $e) {
        error_log('Claim task failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the nhan task'];
    }
}

/**
 * Update task attachment path
 */
function taskUpdateAttachment(int $taskId, int $userId, ?string $attachmentPath): array
{
    $task = _taskGetBasic($taskId);
    if (!$task) {
        return ['success' => false, 'error' => 'Task khong ton tai'];
    }

    $isOwner = taskIsOwner((int) $task['project_id'], $userId);
    $isAssigned = ((int) $task['assigned_to'] === $userId);
    if (!$isOwner && !$isAssigned) {
        return ['success' => false, 'error' => 'Ban khong co quyen upload file cho task nay'];
    }

    try {
        $normalized = $attachmentPath === null
            ? null
            : taskBuildAttachmentPathValue(taskParseAttachmentPaths($attachmentPath));

        dbUpdate('tasks', ['attachment_path' => $normalized], 'task_id = ?', [$taskId]);
        return ['success' => true, 'message' => 'Cap nhat file dinh kem thanh cong'];
    } catch (Exception $e) {
        error_log('Update attachment failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the cap nhat file dinh kem'];
    }
}

function taskParseAttachmentPaths(?string $attachmentPath): array
{
    if ($attachmentPath === null) {
        return [];
    }

    $parts = preg_split('/[\n,;|]+/', (string) $attachmentPath) ?: [];
    $files = [];

    foreach ($parts as $part) {
        $normalized = trim(basename((string) $part));
        if ($normalized === '') {
            continue;
        }
        $files[$normalized] = $normalized;
    }

    return array_values($files);
}

function taskBuildAttachmentPathValue(array $attachmentPaths): ?string
{
    $normalized = [];
    foreach ($attachmentPaths as $path) {
        $file = trim(basename((string) $path));
        if ($file === '') {
            continue;
        }
        $normalized[$file] = $file;
    }

    if (empty($normalized)) {
        return null;
    }

    return implode('|', array_values($normalized));
}

function _taskGetBasic(int $taskId): ?array
{
    return dbQueryOne(
        'SELECT t.*, p.owner_id
         FROM tasks t
         JOIN projects p ON t.project_id = p.project_id
         WHERE t.task_id = ? AND t.is_archived = 0 AND p.is_archived = 0',
        [$taskId]
    );
}

function _taskNormalizeDueDate($rawDueDate, ?string $existingDueDate = null): array
{
    if ($rawDueDate === null || $rawDueDate === '') {
        return ['success' => true, 'value' => null];
    }

    $timestamp = strtotime((string) $rawDueDate);
    if ($timestamp === false) {
        return ['success' => false, 'error' => 'Han hoan thanh khong hop le'];
    }

    $normalizedDate = date('Y-m-d', $timestamp);
    $today = date('Y-m-d');

    if ($normalizedDate < $today && $normalizedDate !== $existingDueDate) {
        return ['success' => false, 'error' => 'Han hoan thanh chi duoc tu hom nay tro di'];
    }

    return ['success' => true, 'value' => $normalizedDate];
}

function taskIsMember(int $projectId, int $userId): bool
{
    $result = dbQueryOne(
        'SELECT 1
         FROM project_members pm
         JOIN projects p ON p.project_id = pm.project_id
         WHERE pm.project_id = ? AND pm.user_id = ? AND p.is_archived = 0',
        [$projectId, $userId]
    );
    return $result !== null;
}

function taskIsOwner(int $projectId, int $userId): bool
{
    $result = dbQueryOne(
        'SELECT 1 FROM projects WHERE project_id = ? AND owner_id = ? AND is_archived = 0',
        [$projectId, $userId]
    );
    return $result !== null;
}

function _taskBuildSearchOrderClause(string $sortBy, string $sortDir): string
{
    $direction = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

    switch ($sortBy) {
        case 'due_date':
            return '(t.due_date IS NULL) ASC, t.due_date ' . $direction . ', t.updated_at DESC';
        case 'created_at':
            return 't.created_at ' . $direction . ', t.updated_at DESC';
        case 'updated_at':
            return 't.updated_at ' . $direction;
        case 'task_title':
            return 't.task_title ' . $direction . ', t.updated_at DESC';
        case 'project_name':
            return 'p.project_name ' . $direction . ', t.updated_at DESC';
        case 'priority':
        default:
            return 'CASE t.priority WHEN "low" THEN 1 WHEN "medium" THEN 2 WHEN "high" THEN 3 ELSE 0 END ' . $direction . ', t.updated_at DESC';
    }
}

function searchUserTasks(int $userId, array $filters = []): array
{
    $conditions = ['pm.user_id = ?', 'p.is_archived = 0', 't.is_archived = 0'];
    $params = [$userId];

    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $conditions[] = '(t.task_title LIKE ? OR t.description LIKE ? OR p.project_name LIKE ?)';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($filters['project_id']) && is_numeric($filters['project_id'])) {
        $conditions[] = 't.project_id = ?';
        $params[] = (int) $filters['project_id'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['todo', 'in_progress', 'done'], true)) {
        $conditions[] = 't.column_status = ?';
        $params[] = $filters['status'];
    }

    if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'], true)) {
        $conditions[] = 't.priority = ?';
        $params[] = $filters['priority'];
    }

    if (array_key_exists('assigned_to', $filters)) {
        $assignedFilter = $filters['assigned_to'];

        if ($assignedFilter === 'unassigned') {
            $conditions[] = 't.assigned_to IS NULL';
        } elseif ($assignedFilter === 'me') {
            $conditions[] = 't.assigned_to = ?';
            $params[] = $userId;
        } elseif ($assignedFilter !== '' && $assignedFilter !== null && is_numeric($assignedFilter)) {
            $conditions[] = 't.assigned_to = ?';
            $params[] = (int) $assignedFilter;
        }
    }

    if (!empty($filters['due_date_from'])) {
        $conditions[] = 't.due_date >= ?';
        $params[] = date('Y-m-d', strtotime($filters['due_date_from']));
    }

    if (!empty($filters['due_date_to'])) {
        $conditions[] = 't.due_date <= ?';
        $params[] = date('Y-m-d', strtotime($filters['due_date_to']));
    }

    if (!empty($filters['overdue'])) {
        $conditions[] = 't.due_date < CURDATE() AND t.column_status != ?';
        $params[] = 'done';
    }

    if (!empty($filters['due_this_week'])) {
        $conditions[] = 't.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
    }

    $whereClause = implode(' AND ', $conditions);

    $totalResult = dbQueryOne(
        'SELECT COUNT(*) as total
         FROM tasks t
         JOIN projects p ON p.project_id = t.project_id
         JOIN project_members pm ON pm.project_id = t.project_id
         WHERE ' . $whereClause,
        $params
    );
    $total = (int) ($totalResult['total'] ?? 0);

    $projectCountResult = dbQueryOne(
        'SELECT COUNT(DISTINCT t.project_id) as total_projects
         FROM tasks t
         JOIN projects p ON p.project_id = t.project_id
         JOIN project_members pm ON pm.project_id = t.project_id
         WHERE ' . $whereClause,
        $params
    );
    $totalProjects = (int) ($projectCountResult['total_projects'] ?? 0);

    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($filters['per_page'] ?? ITEMS_PER_PAGE)));
    $offset = ($page - 1) * $perPage;
    $totalPages = (int) ceil($total / $perPage);

    $orderBy = _taskBuildSearchOrderClause(
        (string) ($filters['sort_by'] ?? 'updated_at'),
        (string) ($filters['sort_dir'] ?? 'desc')
    );

    $sql = 'SELECT t.*, u.username as assigned_username, u.full_name as assigned_name, u.avatar as assigned_avatar,
                   p.project_name, p.project_code
            FROM tasks t
            JOIN projects p ON p.project_id = t.project_id
            JOIN project_members pm ON pm.project_id = t.project_id
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE ' . $whereClause . '
            ORDER BY ' . $orderBy . '
            LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $tasks = dbQuery($sql, $params);

    $groupedMap = [];
    foreach ($tasks as &$task) {
        $task['assignee_name'] = $task['assigned_name'] ?? null;

        $projectId = (int) $task['project_id'];
        if (!isset($groupedMap[$projectId])) {
            $groupedMap[$projectId] = [
                'project_id' => $projectId,
                'project_name' => $task['project_name'] ?? '',
                'project_code' => $task['project_code'] ?? '',
                'task_count' => 0,
                'tasks' => []
            ];
        }

        $groupedMap[$projectId]['tasks'][] = $task;
        $groupedMap[$projectId]['task_count']++;
    }

    return [
        'success' => true,
        'data' => [
            'tasks' => $tasks,
            'grouped_by_project' => array_values($groupedMap),
            'summary' => [
                'total_tasks' => $total,
                'project_count' => $totalProjects
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ],
            'filters' => $filters
        ]
    ];
}

function searchGetGlobalFilterOptions(int $userId): array
{
    $projects = dbQuery(
        'SELECT p.project_id, p.project_name, p.project_code
         FROM projects p
         JOIN project_members pm ON pm.project_id = p.project_id
         WHERE pm.user_id = ? AND p.is_archived = 0
         ORDER BY p.project_name ASC',
        [$userId]
    );

    $members = dbQuery(
        'SELECT DISTINCT u.user_id, u.username, u.full_name
         FROM project_members pm_me
         JOIN project_members pm ON pm.project_id = pm_me.project_id
         JOIN projects p ON p.project_id = pm.project_id
         JOIN users u ON u.user_id = pm.user_id
         WHERE pm_me.user_id = ? AND p.is_archived = 0
         ORDER BY u.full_name ASC',
        [$userId]
    );

    $statusCounts = dbQuery(
        'SELECT t.column_status, COUNT(*) as count
         FROM tasks t
         JOIN projects p ON p.project_id = t.project_id
         JOIN project_members pm ON pm.project_id = t.project_id
         WHERE pm.user_id = ? AND p.is_archived = 0 AND t.is_archived = 0
         GROUP BY t.column_status',
        [$userId]
    );

    $statusMap = [];
    foreach ($statusCounts as $row) {
        $statusMap[$row['column_status']] = (int) $row['count'];
    }

    $priorityCounts = dbQuery(
        'SELECT t.priority, COUNT(*) as count
         FROM tasks t
         JOIN projects p ON p.project_id = t.project_id
         JOIN project_members pm ON pm.project_id = t.project_id
         WHERE pm.user_id = ? AND p.is_archived = 0 AND t.is_archived = 0
         GROUP BY t.priority',
        [$userId]
    );

    $priorityMap = [];
    foreach ($priorityCounts as $row) {
        $priorityMap[$row['priority']] = (int) $row['count'];
    }

    return [
        'success' => true,
        'data' => [
            'projects' => $projects,
            'members' => $members,
            'statuses' => [
                ['value' => 'todo', 'label' => 'To Do', 'count' => $statusMap['todo'] ?? 0],
                ['value' => 'in_progress', 'label' => 'In Progress', 'count' => $statusMap['in_progress'] ?? 0],
                ['value' => 'done', 'label' => 'Done', 'count' => $statusMap['done'] ?? 0]
            ],
            'priorities' => [
                ['value' => 'high', 'label' => 'Cao', 'count' => $priorityMap['high'] ?? 0],
                ['value' => 'medium', 'label' => 'Trung binh', 'count' => $priorityMap['medium'] ?? 0],
                ['value' => 'low', 'label' => 'Thap', 'count' => $priorityMap['low'] ?? 0]
            ]
        ]
    ];
}

/**
 * Search/filter tasks in a project
 */
function searchProjectTasks(int $projectId, int $userId, array $filters = []): array
{
    if (!taskIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen truy cap du an nay'];
    }

    $conditions = ['t.project_id = ?', 't.is_archived = 0'];
    $params = [$projectId];

    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $conditions[] = '(t.task_title LIKE ? OR t.description LIKE ?)';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['todo', 'in_progress', 'done'], true)) {
        $conditions[] = 't.column_status = ?';
        $params[] = $filters['status'];
    }

    if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'], true)) {
        $conditions[] = 't.priority = ?';
        $params[] = $filters['priority'];
    }

    if (array_key_exists('assigned_to', $filters)) {
        $assignedFilter = $filters['assigned_to'];

        if ($assignedFilter === 'unassigned') {
            $conditions[] = 't.assigned_to IS NULL';
        } elseif ($assignedFilter === 'me') {
            $conditions[] = 't.assigned_to = ?';
            $params[] = $userId;
        } elseif ($assignedFilter !== '' && $assignedFilter !== null && is_numeric($assignedFilter)) {
            $conditions[] = 't.assigned_to = ?';
            $params[] = (int) $assignedFilter;
        }
    }

    if (!empty($filters['due_date_from'])) {
        $conditions[] = 't.due_date >= ?';
        $params[] = date('Y-m-d', strtotime($filters['due_date_from']));
    }

    if (!empty($filters['due_date_to'])) {
        $conditions[] = 't.due_date <= ?';
        $params[] = date('Y-m-d', strtotime($filters['due_date_to']));
    }

    if (!empty($filters['overdue'])) {
        $conditions[] = 't.due_date < CURDATE() AND t.column_status != ?';
        $params[] = 'done';
    }

    if (!empty($filters['due_this_week'])) {
        $conditions[] = 't.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
    }

    $whereClause = implode(' AND ', $conditions);

    $totalResult = dbQueryOne('SELECT COUNT(*) as total FROM tasks t WHERE ' . $whereClause, $params);
    $total = (int) ($totalResult['total'] ?? 0);

    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = min(50, max(1, (int) ($filters['per_page'] ?? ITEMS_PER_PAGE)));
    $offset = ($page - 1) * $perPage;
    $totalPages = (int) ceil($total / $perPage);

    $sql = 'SELECT t.*, u.username as assigned_username, u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE ' . $whereClause . '
            ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC
            LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $tasks = dbQuery($sql, $params);

    $grouped = ['todo' => [], 'in_progress' => [], 'done' => []];
    foreach ($tasks as $task) {
        $grouped[$task['column_status']][] = $task;
    }

    return [
        'success' => true,
        'data' => [
            'tasks' => $tasks,
            'grouped' => $grouped,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ],
            'filters' => $filters
        ]
    ];
}

/**
 * Get filter options for a project
 */
function searchGetFilterOptions(int $projectId, int $userId): array
{
    if (!taskIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen truy cap du an nay'];
    }

    $members = dbQuery(
        'SELECT u.user_id, u.username, u.full_name
         FROM project_members pm
         JOIN users u ON pm.user_id = u.user_id
         WHERE pm.project_id = ?
         ORDER BY u.full_name',
        [$projectId]
    );

    $statusCounts = dbQuery(
        'SELECT column_status, COUNT(*) as count
         FROM tasks WHERE project_id = ? AND is_archived = 0
         GROUP BY column_status',
        [$projectId]
    );

    $statusMap = [];
    foreach ($statusCounts as $row) {
        $statusMap[$row['column_status']] = (int) $row['count'];
    }

    $priorityCounts = dbQuery(
        'SELECT priority, COUNT(*) as count
         FROM tasks WHERE project_id = ? AND is_archived = 0
         GROUP BY priority',
        [$projectId]
    );

    $priorityMap = [];
    foreach ($priorityCounts as $row) {
        $priorityMap[$row['priority']] = (int) $row['count'];
    }

    return [
        'success' => true,
        'data' => [
            'members' => $members,
            'statuses' => [
                ['value' => 'todo', 'label' => 'To Do', 'count' => $statusMap['todo'] ?? 0],
                ['value' => 'in_progress', 'label' => 'In Progress', 'count' => $statusMap['in_progress'] ?? 0],
                ['value' => 'done', 'label' => 'Done', 'count' => $statusMap['done'] ?? 0]
            ],
            'priorities' => [
                ['value' => 'high', 'label' => 'Cao', 'count' => $priorityMap['high'] ?? 0],
                ['value' => 'medium', 'label' => 'Trung binh', 'count' => $priorityMap['medium'] ?? 0],
                ['value' => 'low', 'label' => 'Thap', 'count' => $priorityMap['low'] ?? 0]
            ]
        ]
    ];
}
