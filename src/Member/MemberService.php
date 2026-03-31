<?php
/**
 * Member Service
 * Team Kanban - CT214H Final Project
 */

namespace App\Member;

require_once __DIR__ . '/../../includes/db-connect.php';
require_once __DIR__ . '/../../includes/functions.php';

class MemberService
{
    /**
     * Get all members of a project
     */
    public function getProjectMembers(int $projectId, int $requestingUserId): array
    {
        // Check if user can access project
        if (!$this->isMember($projectId, $requestingUserId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền truy cập dự án này'];
        }

        $members = dbQuery(
            "SELECT pm.member_id, pm.role, pm.joined_at,
                    u.user_id, u.username, u.email, u.full_name, u.avatar
             FROM project_members pm
             JOIN users u ON pm.user_id = u.user_id
             WHERE pm.project_id = ?
             ORDER BY pm.role DESC, pm.joined_at ASC",
            [$projectId]
        );

        return ['success' => true, 'data' => $members];
    }

    /**
     * Add member to project (by owner)
     */
    public function addMember(int $projectId, int $ownerId, string $identifier): array
    {
        // Check if requester is owner
        if (!$this->isOwner($projectId, $ownerId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể thêm thành viên'];
        }

        // Find user by username or email
        $user = $this->findUserByIdentifier($identifier);
        if (!$user) {
            return ['success' => false, 'error' => 'Không tìm thấy người dùng với thông tin này'];
        }

        // Check if already a member
        if ($this->isMember($projectId, $user['user_id'])) {
            return ['success' => false, 'error' => 'Người dùng đã là thành viên của dự án'];
        }

        // Check if there's a pending request - approve it instead
        $pendingRequest = dbQueryOne(
            "SELECT request_id FROM project_join_requests 
             WHERE project_id = ? AND user_id = ? AND status = 'pending'",
            [$projectId, $user['user_id']]
        );

        if ($pendingRequest) {
            // Approve the pending request
            return $this->approveJoinRequest($pendingRequest['request_id'], $ownerId);
        }

        // Add member directly
        try {
            dbInsert('project_members', [
                'project_id' => $projectId,
                'user_id' => $user['user_id'],
                'role' => 'member'
            ]);

            return [
                'success' => true,
                'message' => 'Đã thêm thành viên thành công',
                'data' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name']
                ]
            ];
        } catch (\Exception $e) {
            error_log('Add member failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể thêm thành viên'];
        }
    }

    /**
     * Remove member from project
     */
    public function removeMember(int $projectId, int $ownerId, int $memberUserId): array
    {
        // Check if requester is owner
        if (!$this->isOwner($projectId, $ownerId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể xóa thành viên'];
        }

        // Can't remove owner
        if ($this->isOwner($projectId, $memberUserId)) {
            return ['success' => false, 'error' => 'Không thể xóa chủ dự án'];
        }

        // Check if user is actually a member
        if (!$this->isMember($projectId, $memberUserId)) {
            return ['success' => false, 'error' => 'Người dùng không phải thành viên của dự án'];
        }

        try {
            dbBeginTransaction();

            // Set assigned tasks to NULL
            dbExecute(
                "UPDATE tasks SET assigned_to = NULL WHERE project_id = ? AND assigned_to = ?",
                [$projectId, $memberUserId]
            );

            // Remove from project_members
            dbDelete('project_members', 'project_id = ? AND user_id = ?', [$projectId, $memberUserId]);

            dbCommit();

            return ['success' => true, 'message' => 'Đã xóa thành viên khỏi dự án'];
        } catch (\Exception $e) {
            dbRollback();
            error_log('Remove member failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể xóa thành viên'];
        }
    }

    /**
     * Leave project (member can leave)
     */
    public function leaveProject(int $projectId, int $userId): array
    {
        // Owner cannot leave
        if ($this->isOwner($projectId, $userId)) {
            return ['success' => false, 'error' => 'Chủ dự án không thể rời khỏi dự án. Hãy xóa dự án nếu muốn.'];
        }

        if (!$this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không phải thành viên của dự án'];
        }

        try {
            dbBeginTransaction();

            // Set assigned tasks to NULL
            dbExecute(
                "UPDATE tasks SET assigned_to = NULL WHERE project_id = ? AND assigned_to = ?",
                [$projectId, $userId]
            );

            // Remove from project_members
            dbDelete('project_members', 'project_id = ? AND user_id = ?', [$projectId, $userId]);

            dbCommit();

            return ['success' => true, 'message' => 'Đã rời khỏi dự án'];
        } catch (\Exception $e) {
            dbRollback();
            error_log('Leave project failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể rời khỏi dự án'];
        }
    }

    /**
     * Request to join project
     */
    public function requestJoin(int $projectId, int $userId): array
    {
        // Check if project exists
        $project = dbQueryOne("SELECT project_id, project_name FROM projects WHERE project_id = ?", [$projectId]);
        if (!$project) {
            return ['success' => false, 'error' => 'Dự án không tồn tại'];
        }

        // Check if already a member
        if ($this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn đã là thành viên của dự án này'];
        }

        // Check if already has pending request
        $existing = dbQueryOne(
            "SELECT request_id, status FROM project_join_requests 
             WHERE project_id = ? AND user_id = ? AND status = 'pending'",
            [$projectId, $userId]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'Bạn đã gửi yêu cầu tham gia dự án này rồi'];
        }

        try {
            dbInsert('project_join_requests', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'status' => 'pending'
            ]);

            return [
                'success' => true,
                'message' => 'Đã gửi yêu cầu tham gia. Vui lòng chờ chủ dự án phê duyệt.'
            ];
        } catch (\Exception $e) {
            error_log('Request join failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể gửi yêu cầu'];
        }
    }

    /**
     * Get pending join requests for a project
     */
    public function getPendingRequests(int $projectId, int $ownerId): array
    {
        if (!$this->isOwner($projectId, $ownerId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể xem yêu cầu tham gia'];
        }

        $requests = dbQuery(
            "SELECT jr.request_id, jr.requested_at,
                    u.user_id, u.username, u.email, u.full_name, u.avatar
             FROM project_join_requests jr
             JOIN users u ON jr.user_id = u.user_id
             WHERE jr.project_id = ? AND jr.status = 'pending'
             ORDER BY jr.requested_at ASC",
            [$projectId]
        );

        return ['success' => true, 'data' => $requests];
    }

    /**
     * Approve join request
     */
    public function approveJoinRequest(int $requestId, int $ownerId): array
    {
        $request = dbQueryOne(
            "SELECT jr.*, p.owner_id 
             FROM project_join_requests jr
             JOIN projects p ON jr.project_id = p.project_id
             WHERE jr.request_id = ?",
            [$requestId]
        );

        if (!$request) {
            return ['success' => false, 'error' => 'Yêu cầu không tồn tại'];
        }

        if ($request['owner_id'] != $ownerId) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể phê duyệt'];
        }

        if ($request['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Yêu cầu đã được xử lý'];
        }

        try {
            dbBeginTransaction();

            // Update request status
            dbUpdate('project_join_requests', [
                'status' => 'approved',
                'responded_at' => date('Y-m-d H:i:s')
            ], 'request_id = ?', [$requestId]);

            // Add as member
            dbInsert('project_members', [
                'project_id' => $request['project_id'],
                'user_id' => $request['user_id'],
                'role' => 'member'
            ]);

            dbCommit();

            return ['success' => true, 'message' => 'Đã phê duyệt yêu cầu tham gia'];
        } catch (\Exception $e) {
            dbRollback();
            error_log('Approve request failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể phê duyệt yêu cầu'];
        }
    }

    /**
     * Reject join request
     */
    public function rejectJoinRequest(int $requestId, int $ownerId): array
    {
        $request = dbQueryOne(
            "SELECT jr.*, p.owner_id 
             FROM project_join_requests jr
             JOIN projects p ON jr.project_id = p.project_id
             WHERE jr.request_id = ?",
            [$requestId]
        );

        if (!$request) {
            return ['success' => false, 'error' => 'Yêu cầu không tồn tại'];
        }

        if ($request['owner_id'] != $ownerId) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể từ chối'];
        }

        if ($request['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Yêu cầu đã được xử lý'];
        }

        try {
            dbUpdate('project_join_requests', [
                'status' => 'rejected',
                'responded_at' => date('Y-m-d H:i:s')
            ], 'request_id = ?', [$requestId]);

            return ['success' => true, 'message' => 'Đã từ chối yêu cầu tham gia'];
        } catch (\Exception $e) {
            error_log('Reject request failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể từ chối yêu cầu'];
        }
    }

    /**
     * Get user's pending requests (for user's own view)
     */
    public function getUserPendingRequests(int $userId): array
    {
        $requests = dbQuery(
            "SELECT jr.request_id, jr.requested_at, jr.status,
                    p.project_id, p.project_name, p.project_code,
                    u.full_name as owner_name
             FROM project_join_requests jr
             JOIN projects p ON jr.project_id = p.project_id
             JOIN users u ON p.owner_id = u.user_id
             WHERE jr.user_id = ?
             ORDER BY jr.requested_at DESC",
            [$userId]
        );

        return ['success' => true, 'data' => $requests];
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

    /**
     * Helper: Find user by username or email
     */
    private function findUserByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);
        return dbQueryOne(
            "SELECT user_id, username, email, full_name FROM users 
             WHERE username = ? OR email = ?",
            [$identifier, $identifier]
        );
    }
}
