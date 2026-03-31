<?php
/**
 * Project Service
 * Team Kanban - CT214H Final Project
 */

namespace App\Project;

require_once __DIR__ . '/../../includes/functions.php';

class ProjectService
{
    private ProjectRepository $repository;

    public function __construct()
    {
        $this->repository = new ProjectRepository();
    }

    /**
     * Create a new project
     */
    public function createProject(int $ownerId, string $name, ?string $description = null): array
    {
        // Validate project name
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'error' => 'Tên dự án không được để trống'];
        }
        if (strlen($name) > 100) {
            return ['success' => false, 'error' => 'Tên dự án không được quá 100 ký tự'];
        }

        // Generate unique project code
        $code = $this->generateUniqueCode();

        try {
            dbBeginTransaction();

            // Create project
            $projectId = $this->repository->create([
                'owner_id' => $ownerId,
                'project_name' => $name,
                'description' => $description,
                'project_code' => $code
            ]);

            // Add owner as member
            $this->repository->addOwnerAsMember($projectId, $ownerId);

            dbCommit();

            return [
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'project_code' => $code
                ]
            ];
        } catch (\Exception $e) {
            dbRollback();
            error_log('Project creation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể tạo dự án. Vui lòng thử lại.'];
        }
    }

    /**
     * Get project details
     */
    public function getProject(int $projectId, int $userId): array
    {
        // Check if user has access
        if (!$this->repository->isMember($projectId, $userId)) {
            return ['success' => false, 'error' => 'Bạn không có quyền truy cập dự án này'];
        }

        $project = $this->repository->findById($projectId);
        if (!$project) {
            return ['success' => false, 'error' => 'Dự án không tồn tại'];
        }

        // Add stats
        $project['stats'] = $this->repository->getProjectStats($projectId);
        $project['is_owner'] = ($project['owner_id'] == $userId);

        return ['success' => true, 'data' => $project];
    }

    /**
     * Get all projects for user
     */
    public function getUserProjects(int $userId): array
    {
        $projects = $this->repository->getProjectsForUser($userId);
        return ['success' => true, 'data' => $projects];
    }

    /**
     * Update project
     */
    public function updateProject(int $projectId, int $userId, array $data): array
    {
        // Only owner can update
        if (!$this->repository->isOwner($projectId, $userId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể chỉnh sửa'];
        }

        // Validate
        if (isset($data['project_name'])) {
            $data['project_name'] = trim($data['project_name']);
            if (empty($data['project_name'])) {
                return ['success' => false, 'error' => 'Tên dự án không được để trống'];
            }
            if (strlen($data['project_name']) > 100) {
                return ['success' => false, 'error' => 'Tên dự án không được quá 100 ký tự'];
            }
        }

        try {
            $this->repository->update($projectId, $data);
            return ['success' => true, 'message' => 'Cập nhật dự án thành công'];
        } catch (\Exception $e) {
            error_log('Project update failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật dự án'];
        }
    }

    /**
     * Delete project
     */
    public function deleteProject(int $projectId, int $userId): array
    {
        // Only owner can delete
        if (!$this->repository->isOwner($projectId, $userId)) {
            return ['success' => false, 'error' => 'Chỉ chủ dự án mới có thể xóa'];
        }

        try {
            // Delete attachments from filesystem before deleting project
            $this->deleteProjectAttachments($projectId);
            
            $this->repository->delete($projectId);
            return ['success' => true, 'message' => 'Xóa dự án thành công'];
        } catch (\Exception $e) {
            error_log('Project deletion failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể xóa dự án'];
        }
    }

    /**
     * Find project by code (for joining)
     */
    public function findProjectByCode(string $code): array
    {
        $code = strtoupper(trim($code));
        $project = $this->repository->findByCode($code);

        if (!$project) {
            return ['success' => false, 'error' => 'Không tìm thấy dự án với mã này'];
        }

        // Return limited info for join preview
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
     * Check if user can access project
     */
    public function canAccess(int $projectId, int $userId): bool
    {
        return $this->repository->isMember($projectId, $userId);
    }

    /**
     * Check if user is owner
     */
    public function isOwner(int $projectId, int $userId): bool
    {
        return $this->repository->isOwner($projectId, $userId);
    }

    /**
     * Generate unique 8-character project code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = generateRandomString(8);
        } while ($this->repository->codeExists($code));

        return $code;
    }

    /**
     * Delete all attachments associated with project tasks
     */
    private function deleteProjectAttachments(int $projectId): void
    {
        $tasks = dbQuery(
            "SELECT attachment_path FROM tasks WHERE project_id = ? AND attachment_path IS NOT NULL",
            [$projectId]
        );

        foreach ($tasks as $task) {
            $filePath = ATTACHMENT_PATH . $task['attachment_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
