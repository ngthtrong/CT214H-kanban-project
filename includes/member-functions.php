<?php
/**
 * Member and join-request functions.
 */

/**
 * Get members of a project
 */
function memberGetProjectMembers(int $projectId, int $requestingUserId): array
{
    if (!memberIsMember($projectId, $requestingUserId)) {
        return ['success' => false, 'error' => 'Ban khong co quyen truy cap du an nay'];
    }

    $members = dbQuery(
        'SELECT pm.member_id, pm.role, pm.joined_at,
                u.user_id, u.username, u.email, u.full_name, u.avatar
         FROM project_members pm
         JOIN users u ON pm.user_id = u.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.role DESC, pm.joined_at ASC',
        [$projectId]
    );

    return ['success' => true, 'data' => $members];
}

/**
 * Add member by identifier
 */
function memberAddMember(int $projectId, int $ownerId, string $identifier): array
{
    if (!memberIsOwner($projectId, $ownerId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the them thanh vien'];
    }

    $user = findUserByIdentifier(trim($identifier));
    if (!$user) {
        return ['success' => false, 'error' => 'Khong tim thay nguoi dung voi thong tin nay'];
    }

    if (memberIsMember($projectId, (int) $user['user_id'])) {
        return ['success' => false, 'error' => 'Nguoi dung da la thanh vien cua du an'];
    }

    $pendingRequest = dbQueryOne(
        'SELECT request_id FROM project_join_requests WHERE project_id = ? AND user_id = ? AND status = "pending"',
        [$projectId, $user['user_id']]
    );

    if ($pendingRequest) {
        return memberApproveJoinRequest((int) $pendingRequest['request_id'], $ownerId);
    }

    try {
        dbInsert('project_members', [
            'project_id' => $projectId,
            'user_id' => $user['user_id'],
            'role' => 'member'
        ]);

        return [
            'success' => true,
            'message' => 'Da them thanh vien thanh cong',
            'data' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'full_name' => $user['full_name']
            ]
        ];
    } catch (Exception $e) {
        error_log('Add member failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the them thanh vien'];
    }
}

/**
 * Remove member from project
 */
function memberRemoveMember(int $projectId, int $ownerId, int $memberUserId): array
{
    if (!memberIsOwner($projectId, $ownerId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the xoa thanh vien'];
    }

    if (memberIsOwner($projectId, $memberUserId)) {
        return ['success' => false, 'error' => 'Khong the xoa chu du an'];
    }

    if (!memberIsMember($projectId, $memberUserId)) {
        return ['success' => false, 'error' => 'Nguoi dung khong phai thanh vien cua du an'];
    }

    try {
        dbBeginTransaction();

        dbExecute(
            'UPDATE tasks SET assigned_to = NULL WHERE project_id = ? AND assigned_to = ?',
            [$projectId, $memberUserId]
        );

        dbDelete('project_members', 'project_id = ? AND user_id = ?', [$projectId, $memberUserId]);
        dbCommit();

        return ['success' => true, 'message' => 'Da xoa thanh vien khoi du an'];
    } catch (Exception $e) {
        dbRollback();
        error_log('Remove member failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the xoa thanh vien'];
    }
}

/**
 * Leave project
 */
function memberLeaveProject(int $projectId, int $userId): array
{
    if (memberIsOwner($projectId, $userId)) {
        return ['success' => false, 'error' => 'Chu du an khong the roi khoi du an. Hay luu tru du an neu muon.'];
    }

    if (!memberIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban khong phai thanh vien cua du an'];
    }

    try {
        dbBeginTransaction();

        dbExecute(
            'UPDATE tasks SET assigned_to = NULL WHERE project_id = ? AND assigned_to = ?',
            [$projectId, $userId]
        );

        dbDelete('project_members', 'project_id = ? AND user_id = ?', [$projectId, $userId]);
        dbCommit();

        return ['success' => true, 'message' => 'Da roi khoi du an'];
    } catch (Exception $e) {
        dbRollback();
        error_log('Leave project failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the roi khoi du an'];
    }
}

/**
 * Create join request
 */
function memberRequestJoin(int $projectId, int $userId): array
{
    $project = dbQueryOne(
        'SELECT project_id, project_name FROM projects WHERE project_id = ? AND is_archived = 0',
        [$projectId]
    );
    if (!$project) {
        return ['success' => false, 'error' => 'Du an khong ton tai hoac da duoc luu tru'];
    }

    if (memberIsMember($projectId, $userId)) {
        return ['success' => false, 'error' => 'Ban da la thanh vien cua du an nay'];
    }

    $existing = dbQueryOne(
        'SELECT request_id FROM project_join_requests WHERE project_id = ? AND user_id = ? AND status = "pending"',
        [$projectId, $userId]
    );

    if ($existing) {
        return ['success' => false, 'error' => 'Ban da gui yeu cau tham gia du an nay roi'];
    }

    try {
        dbInsert('project_join_requests', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'status' => 'pending'
        ]);

        return [
            'success' => true,
            'message' => 'Da gui yeu cau tham gia. Vui long cho chu du an phe duyet.'
        ];
    } catch (Exception $e) {
        error_log('Request join failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the gui yeu cau'];
    }
}

/**
 * Pending requests for owner
 */
function memberGetPendingRequests(int $projectId, int $ownerId): array
{
    if (!memberIsOwner($projectId, $ownerId)) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the xem yeu cau tham gia'];
    }

    $requests = dbQuery(
        'SELECT jr.request_id, jr.requested_at,
                u.user_id, u.username, u.email, u.full_name, u.avatar
         FROM project_join_requests jr
         JOIN users u ON jr.user_id = u.user_id
         WHERE jr.project_id = ? AND jr.status = "pending"
         ORDER BY jr.requested_at ASC',
        [$projectId]
    );

    return ['success' => true, 'data' => $requests];
}

/**
 * Approve join request
 */
function memberApproveJoinRequest(int $requestId, int $ownerId): array
{
    $request = dbQueryOne(
        'SELECT jr.*, p.owner_id
         FROM project_join_requests jr
         JOIN projects p ON jr.project_id = p.project_id
         WHERE jr.request_id = ? AND p.is_archived = 0',
        [$requestId]
    );

    if (!$request) {
        return ['success' => false, 'error' => 'Yeu cau khong ton tai'];
    }

    if ((int) $request['owner_id'] !== $ownerId) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the phe duyet'];
    }

    if ($request['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Yeu cau da duoc xu ly'];
    }

    try {
        dbBeginTransaction();

        dbUpdate('project_join_requests', [
            'status' => 'approved',
            'responded_at' => date('Y-m-d H:i:s')
        ], 'request_id = ?', [$requestId]);

        dbInsert('project_members', [
            'project_id' => $request['project_id'],
            'user_id' => $request['user_id'],
            'role' => 'member'
        ]);

        dbCommit();
        return ['success' => true, 'message' => 'Da phe duyet yeu cau tham gia'];
    } catch (Exception $e) {
        dbRollback();
        error_log('Approve request failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the phe duyet yeu cau'];
    }
}

/**
 * Reject join request
 */
function memberRejectJoinRequest(int $requestId, int $ownerId): array
{
    $request = dbQueryOne(
        'SELECT jr.*, p.owner_id
         FROM project_join_requests jr
         JOIN projects p ON jr.project_id = p.project_id
         WHERE jr.request_id = ? AND p.is_archived = 0',
        [$requestId]
    );

    if (!$request) {
        return ['success' => false, 'error' => 'Yeu cau khong ton tai'];
    }

    if ((int) $request['owner_id'] !== $ownerId) {
        return ['success' => false, 'error' => 'Chi chu du an moi co the tu choi'];
    }

    if ($request['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Yeu cau da duoc xu ly'];
    }

    try {
        dbUpdate('project_join_requests', [
            'status' => 'rejected',
            'responded_at' => date('Y-m-d H:i:s')
        ], 'request_id = ?', [$requestId]);

        return ['success' => true, 'message' => 'Da tu choi yeu cau tham gia'];
    } catch (Exception $e) {
        error_log('Reject request failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Khong the tu choi yeu cau'];
    }
}

/**
 * Get user's own join requests
 */
function memberGetUserPendingRequests(int $userId): array
{
    $requests = dbQuery(
        'SELECT jr.request_id, jr.requested_at, jr.status,
                p.project_id, p.project_name, p.project_code,
                u.full_name as owner_name
         FROM project_join_requests jr
         JOIN projects p ON jr.project_id = p.project_id
         JOIN users u ON p.owner_id = u.user_id
         WHERE jr.user_id = ? AND p.is_archived = 0
         ORDER BY jr.requested_at DESC',
        [$userId]
    );

    return ['success' => true, 'data' => $requests];
}

function memberIsMember(int $projectId, int $userId): bool
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

function memberIsOwner(int $projectId, int $userId): bool
{
    $result = dbQueryOne(
        'SELECT 1 FROM projects WHERE project_id = ? AND owner_id = ? AND is_archived = 0',
        [$projectId, $userId]
    );
    return $result !== null;
}
