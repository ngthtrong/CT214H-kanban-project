<?php
/**
 * Search Service with Pagination
 * Team Kanban - CT214H Final Project
 */

namespace App\Search;

require_once __DIR__ . '/../../includes/db-connect.php';
require_once __DIR__ . '/../../includes/config.php';

class SearchService
{
    private const DEFAULT_PAGE_SIZE = 10;

    /**
     * Search and filter tasks
     * 
     * @param int $projectId Project to search in
     * @param int $userId User making the request
     * @param array $filters Filter criteria:
     *   - search: Text search in title/description
     *   - status: Column status (todo, in_progress, done)
     *   - priority: Task priority (low, medium, high)
     *   - assigned_to: User ID of assignee
     *   - due_date_from: Tasks due after this date
     *   - due_date_to: Tasks due before this date
     *   - page: Page number (1-indexed)
     *   - per_page: Items per page
     */
    public function searchTasks(int $projectId, int $userId, array $filters = []): array
    {
        // Check access
        if (!$this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền truy cập dự án này'];
        }

        // Build query
        $conditions = ['t.project_id = ?'];
        $params = [$projectId];

        // Text search (title + description)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $conditions[] = '(t.task_title LIKE ? OR t.description LIKE ?)';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Status filter
        if (!empty($filters['status']) && in_array($filters['status'], ['todo', 'in_progress', 'done'])) {
            $conditions[] = 't.column_status = ?';
            $params[] = $filters['status'];
        }

        // Priority filter
        if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'])) {
            $conditions[] = 't.priority = ?';
            $params[] = $filters['priority'];
        }

        // Assigned to filter
        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === '' || $filters['assigned_to'] === 'unassigned') {
                $conditions[] = 't.assigned_to IS NULL';
            } elseif (is_numeric($filters['assigned_to'])) {
                $conditions[] = 't.assigned_to = ?';
                $params[] = (int) $filters['assigned_to'];
            }
        }

        // Due date filters
        if (!empty($filters['due_date_from'])) {
            $conditions[] = 't.due_date >= ?';
            $params[] = date('Y-m-d', strtotime($filters['due_date_from']));
        }

        if (!empty($filters['due_date_to'])) {
            $conditions[] = 't.due_date <= ?';
            $params[] = date('Y-m-d', strtotime($filters['due_date_to']));
        }

        // Overdue filter
        if (!empty($filters['overdue'])) {
            $conditions[] = 't.due_date < CURDATE() AND t.column_status != ?';
            $params[] = 'done';
        }

        // Due this week filter
        if (!empty($filters['due_this_week'])) {
            $conditions[] = 't.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
        }

        $whereClause = implode(' AND ', $conditions);

        // Count total for pagination
        $countSql = "SELECT COUNT(*) as total FROM tasks t WHERE {$whereClause}";
        $totalResult = dbQueryOne($countSql, $params);
        $total = (int) ($totalResult['total'] ?? 0);

        // Pagination
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE)));
        $offset = ($page - 1) * $perPage;
        $totalPages = (int) ceil($total / $perPage);

        // Get tasks with pagination
        $sql = "SELECT t.*, 
                       u.username as assigned_username, 
                       u.full_name as assigned_name,
                       u.avatar as assigned_avatar
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.user_id
                WHERE {$whereClause}
                ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $tasks = dbQuery($sql, $params);

        // Group by status (for Kanban view compatibility)
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
    public function getFilterOptions(int $projectId, int $userId): array
    {
        if (!$this->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền truy cập dự án này'];
        }

        // Get project members for assignee filter
        $members = dbQuery(
            "SELECT u.user_id, u.username, u.full_name
             FROM project_members pm
             JOIN users u ON pm.user_id = u.user_id
             WHERE pm.project_id = ?
             ORDER BY u.full_name",
            [$projectId]
        );

        // Get task counts by status
        $statusCounts = dbQuery(
            "SELECT column_status, COUNT(*) as count
             FROM tasks WHERE project_id = ?
             GROUP BY column_status",
            [$projectId]
        );

        $statusMap = [];
        foreach ($statusCounts as $row) {
            $statusMap[$row['column_status']] = (int) $row['count'];
        }

        // Get task counts by priority
        $priorityCounts = dbQuery(
            "SELECT priority, COUNT(*) as count
             FROM tasks WHERE project_id = ?
             GROUP BY priority",
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
                    ['value' => 'medium', 'label' => 'Trung bình', 'count' => $priorityMap['medium'] ?? 0],
                    ['value' => 'low', 'label' => 'Thấp', 'count' => $priorityMap['low'] ?? 0]
                ]
            ]
        ];
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
}
