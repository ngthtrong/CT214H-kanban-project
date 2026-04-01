<?php
/**
 * Project-related functions.
 */

/**
 * Create a project
 */
function projectCreate(int $ownerId, string $name, ?string $description = null): array
{
    $name = trim($name);
    if ($name === '') {
        return ['success' => false, 'error' => 'Ten du an khong duoc de trong'];
    }
    if (strlen($name) > 100) {
        return ['success' => false, 'error' => 'Ten du an khong duoc qua 100 ky tu'];
    }

    $code = _projectGenerateUniqueCode();

    try {
        dbBeginTransaction();

        $projectId = dbInsert('projects', [
            'owner_id' => $ownerId,
            'project_name' => $name,
            'description' => $description,
            'project_code' => $code
        ]);

        dbInsert('project_members', [
            'project_id' => $projectId,
            'user_id' => $ownerId,
            'role' => 'owner'
        ]);

        dbCommit();

        return [
            'success' => true,
            'data' => [
                'project_id' => $projectId,
                'project_code' => $code
            ]
        ];
    } catch (Exception $e) {
        dbRollback();
        error_log('Project creation failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the tao du an. Vui long thu lai.'];
    }
}

/**
 * Get project detail for a user
 */
function projectGet(int $projectId, int $userId): array
{
    if (!projectIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen truy cap du an nay'];
    }

    $project = dbQueryOne(
        'SELECT p.*, u.username as owner_username, u.full_name as owner_name
         FROM projects p
         JOIN users u ON p.owner_id = u.user_id
         WHERE p.project_id = ?',
        [$projectId]
    );

    if (!$project) {
        return ['success' => false, 'error' => 'Du an khong ton tai'];
    }

    $project['stats'] = projectGetStats($projectId);
    $project['is_owner'] = ((int) $project['owner_id'] === $userId);
    $project['user_role'] = $project['is_owner'] ? 'owner' : 'member';

    return ['success' => true, 'data' => $project];
}

/**
 * Get projects of a user
 */
function projectGetUserProjects(int $userId): array
{
    $projects = dbQuery(
        'SELECT p.*, u.full_name as owner_name, pm.role as user_role,
                (SELECT COUNT(*) FROM project_members WHERE project_id = p.project_id) as member_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.project_id) as task_count
         FROM projects p
         JOIN users u ON p.owner_id = u.user_id
         JOIN project_members pm ON p.project_id = pm.project_id AND pm.user_id = ?
         ORDER BY p.updated_at DESC',
        [$userId]
    );

    return ['success' => true, 'data' => $projects];
}

/**
 * Update project
 */
function projectUpdate(int $projectId, int $userId, array $data): array
{
    if (!projectIsOwner($projectId, $userId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the chinh sua'];
    }

    $updateData = [];

    if (isset($data['project_name'])) {
        $name = trim((string) $data['project_name']);
        if ($name === '') {
            return ['success' => false, 'error' => 'Ten du an khong duoc de trong'];
        }
        if (strlen($name) > 100) {
            return ['success' => false, 'error' => 'Ten du an khong duoc qua 100 ky tu'];
        }
        $updateData['project_name'] = $name;
    }

    if (array_key_exists('description', $data)) {
        $updateData['description'] = $data['description'];
    }

    try {
        if (!empty($updateData)) {
            dbUpdate('projects', $updateData, 'project_id = ?', [$projectId]);
        }
        return ['success' => true, 'message' => 'Cap nhat du an thanh cong'];
    } catch (Exception $e) {
        error_log('Project update failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the cap nhat du an'];
    }
}

/**
 * Delete project
 */
function projectDelete(int $projectId, int $userId): array
{
    if (!projectIsOwner($projectId, $userId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the xoa'];
    }

    try {
        _projectDeleteAttachments($projectId);
        dbDelete('projects', 'project_id = ?', [$projectId]);
        return ['success' => true, 'message' => 'Xoa du an thanh cong'];
    } catch (Exception $e) {
        error_log('Project deletion failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the xoa du an'];
    }
}

/**
 * Find project by code
 */
function projectFindByCode(string $code): array
{
    $code = strtoupper(trim($code));

    $project = dbQueryOne(
        'SELECT p.*, u.username as owner_username, u.full_name as owner_name
         FROM projects p
         JOIN users u ON p.owner_id = u.user_id
         WHERE p.project_code = ?',
        [$code]
    );

    if (!$project) {
        return ['success' => false, 'error' => 'Khong tim thay du an voi ma nay'];
    }

    return [
        'success' => true,
        'data' => [
            'project_id' => $project['project_id'],
            'project_name' => $project['project_name'],
            'description' => $project['description'],
            'owner_name' => $project['owner_name'],
            'project_code' => $project['project_code']
        ]
    ];
}

/**
 * Project owner check
 */
function projectIsOwner(int $projectId, int $userId): bool
{
    $result = dbQueryOne('SELECT 1 FROM projects WHERE project_id = ? AND owner_id = ?', [$projectId, $userId]);
    return $result !== null;
}

/**
 * Project membership check
 */
function projectIsMember(int $projectId, int $userId): bool
{
    $result = dbQueryOne('SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?', [$projectId, $userId]);
    return $result !== null;
}

/**
 * Project stats
 */
function projectGetStats(int $projectId): array
{
    $stats = dbQueryOne(
        'SELECT
            (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = "todo") as todo_count,
            (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = "in_progress") as in_progress_count,
            (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = "done") as done_count,
            (SELECT COUNT(*) FROM project_members WHERE project_id = ?) as member_count,
            (SELECT COUNT(*) FROM project_join_requests WHERE project_id = ? AND status = "pending") as pending_requests',
        [$projectId, $projectId, $projectId, $projectId, $projectId]
    );

    return $stats ?: [
        'todo_count' => 0,
        'in_progress_count' => 0,
        'done_count' => 0,
        'member_count' => 0,
        'pending_requests' => 0
    ];
}

function _projectCodeExists(string $code): bool
{
    $result = dbQueryOne('SELECT 1 FROM projects WHERE project_code = ?', [$code]);
    return $result !== null;
}

function _projectGenerateUniqueCode(): string
{
    do {
        $code = generateRandomString(8);
    } while (_projectCodeExists($code));

    return $code;
}

function _projectDeleteAttachments(int $projectId): void
{
    $tasks = dbQuery(
        'SELECT attachment_path FROM tasks WHERE project_id = ? AND attachment_path IS NOT NULL',
        [$projectId]
    );

    foreach ($tasks as $task) {
        $filePath = ATTACHMENT_PATH . $task['attachment_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
