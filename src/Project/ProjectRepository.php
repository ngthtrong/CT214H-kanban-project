<?php
/**
 * Project Repository
 * Team Kanban - CT214H Final Project
 */

namespace App\Project;

require_once __DIR__ . '/../../includes/db-connect.php';

class ProjectRepository
{
    /**
     * Create a new project
     */
    public function create(array $data): int
    {
        return dbInsert('projects', [
            'owner_id' => $data['owner_id'],
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
            'project_code' => $data['project_code']
        ]);
    }

    /**
     * Find project by ID
     */
    public function findById(int $projectId): ?array
    {
        return dbQueryOne(
            "SELECT p.*, u.username as owner_username, u.full_name as owner_name
             FROM projects p
             JOIN users u ON p.owner_id = u.user_id
             WHERE p.project_id = ?",
            [$projectId]
        );
    }

    /**
     * Find project by code
     */
    public function findByCode(string $code): ?array
    {
        return dbQueryOne(
            "SELECT p.*, u.username as owner_username, u.full_name as owner_name
             FROM projects p
             JOIN users u ON p.owner_id = u.user_id
             WHERE p.project_code = ?",
            [$code]
        );
    }

    /**
     * Get all projects for a user (owned + member of)
     */
    public function getProjectsForUser(int $userId): array
    {
        return dbQuery(
            "SELECT p.*, u.full_name as owner_name, pm.role as user_role,
                    (SELECT COUNT(*) FROM project_members WHERE project_id = p.project_id) as member_count,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.project_id) as task_count
             FROM projects p
             JOIN users u ON p.owner_id = u.user_id
             JOIN project_members pm ON p.project_id = pm.project_id AND pm.user_id = ?
             ORDER BY p.updated_at DESC",
            [$userId]
        );
    }

    /**
     * Get projects owned by user
     */
    public function getProjectsOwnedByUser(int $userId): array
    {
        return dbQuery(
            "SELECT p.*, 
                    (SELECT COUNT(*) FROM project_members WHERE project_id = p.project_id) as member_count,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.project_id) as task_count
             FROM projects p
             WHERE p.owner_id = ?
             ORDER BY p.updated_at DESC",
            [$userId]
        );
    }

    /**
     * Update project
     */
    public function update(int $projectId, array $data): int
    {
        $updateData = [];
        if (isset($data['project_name'])) {
            $updateData['project_name'] = $data['project_name'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (empty($updateData)) {
            return 0;
        }

        return dbUpdate('projects', $updateData, 'project_id = ?', [$projectId]);
    }

    /**
     * Delete project (cascade handled by DB)
     */
    public function delete(int $projectId): int
    {
        return dbDelete('projects', 'project_id = ?', [$projectId]);
    }

    /**
     * Check if user is owner of project
     */
    public function isOwner(int $projectId, int $userId): bool
    {
        $result = dbQueryOne(
            "SELECT 1 FROM projects WHERE project_id = ? AND owner_id = ?",
            [$projectId, $userId]
        );
        return $result !== null;
    }

    /**
     * Check if user is member of project
     */
    public function isMember(int $projectId, int $userId): bool
    {
        $result = dbQueryOne(
            "SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $userId]
        );
        return $result !== null;
    }

    /**
     * Check if project code exists
     */
    public function codeExists(string $code): bool
    {
        $result = dbQueryOne(
            "SELECT 1 FROM projects WHERE project_code = ?",
            [$code]
        );
        return $result !== null;
    }

    /**
     * Add owner as member when project created
     */
    public function addOwnerAsMember(int $projectId, int $userId): int
    {
        return dbInsert('project_members', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => 'owner'
        ]);
    }

    /**
     * Get project statistics
     */
    public function getProjectStats(int $projectId): array
    {
        $stats = dbQueryOne(
            "SELECT 
                (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = 'todo') as todo_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = 'in_progress') as in_progress_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = ? AND column_status = 'done') as done_count,
                (SELECT COUNT(*) FROM project_members WHERE project_id = ?) as member_count,
                (SELECT COUNT(*) FROM project_join_requests WHERE project_id = ? AND status = 'pending') as pending_requests
            ",
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
}
