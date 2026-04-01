<?php
/**
 * Project Board - Kanban View
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

// Require authentication
if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = getCurrentUserId();
$user = getCurrentUser();

// Get project ID from URL
$projectId = (int) ($_GET['id'] ?? 0);
if (!$projectId) {
    flash('Project không tồn tại', 'error');
    redirect('index.php');
}

// Load project info and check access
$projectResult = projectGet($projectId, $userId);

if (!$projectResult['success']) {
    flash($projectResult['error'], 'error');
    redirect('index.php');
}

$project = $projectResult['data'];
$isOwner = $project['user_role'] === 'owner';

$pageTitle = htmlspecialchars($project['project_name']) . ' - ' . APP_NAME;
$additionalCss = ['css/kanban.css'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Project Header -->
        <div class="project-header">
            <div class="project-header-left">
                <a href="index.php" class="btn-back" title="Quay lại Dashboard">
                    Dashboard
                </a>
                <div class="project-info">
                    <h1 class="project-title"><?= htmlspecialchars($project['project_name']) ?></h1>
                    <?php if ($project['description']): ?>
                        <p class="project-description"><?= htmlspecialchars($project['description']) ?></p>
                    <?php endif; ?>
                    <div class="project-meta">
                        <span class="project-code-badge">
                            Mã dự án: <strong id="projectCodeDisplay"><?= htmlspecialchars($project['project_code']) ?></strong>
                            <button type="button" class="btn-copy-inline" data-copy-project-code="<?= htmlspecialchars($project['project_code']) ?>" title="Sao chép mã">
                                Copy
                            </button>
                        </span>
                        <span class="project-role-badge <?= $isOwner ? 'owner' : 'member' ?>">
                            <?= $isOwner ? 'Chủ dự án' : 'Thành viên' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="project-header-right">
                <button class="btn btn-outline" data-modal-open="searchFilterModal">
                    Tìm kiếm & Lọc
                </button>
                <?php if ($isOwner): ?>
                    <a class="btn btn-outline" href="members.php?project_id=<?= $projectId ?>">
                        Quản lý thành viên
                    </a>
                    <a class="btn btn-outline" href="join-requests.php?project_id=<?= $projectId ?>">
                        Yêu cầu tham gia
                    </a>
                <?php endif; ?>
                <button class="btn btn-primary" data-modal-open="createTaskModal">
                    Tạo task mới
                </button>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="kanban-board" id="kanbanBoard">
            <!-- Loading State -->
            <div id="boardLoading" class="board-loading">
                <div class="spinner"></div>
                <p>Đang tải công việc...</p>
            </div>

            <!-- Columns will be rendered here by JavaScript -->
            <div id="kanbanColumns" class="kanban-columns" style="display: none;">
                <!-- To Do Column -->
                <div class="kanban-column" data-status="todo">
                    <div class="kanban-column-header">
                        <h3 class="kanban-column-title">To Do</h3>
                        <span class="kanban-column-count" id="count-todo">0</span>
                    </div>
                    <div class="kanban-column-body" id="column-todo">
                        <!-- Task cards will be rendered here -->
                    </div>
                </div>

                <!-- In Progress Column -->
                <div class="kanban-column" data-status="in_progress">
                    <div class="kanban-column-header">
                        <h3 class="kanban-column-title">In Progress</h3>
                        <span class="kanban-column-count" id="count-in_progress">0</span>
                    </div>
                    <div class="kanban-column-body" id="column-in_progress">
                        <!-- Task cards will be rendered here -->
                    </div>
                </div>

                <!-- Done Column -->
                <div class="kanban-column" data-status="done">
                    <div class="kanban-column-header">
                        <h3 class="kanban-column-title">Done</h3>
                        <span class="kanban-column-count" id="count-done">0</span>
                    </div>
                    <div class="kanban-column-body" id="column-done">
                        <!-- Task cards will be rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="createTaskModal" class="modal-backdrop">
    <div class="modal modal-large">
        <div class="modal-header">
            <h3 class="modal-title">Tạo task mới</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form id="createTaskForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="task_title">Tiêu đề <span class="required">*</span></label>
                        <input type="text" id="task_title" name="task_title" class="form-control" 
                               placeholder="VD: Thiết kế database" required maxlength="200">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Mô tả chi tiết về task"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Độ ưu tiên</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low">Thấp</option>
                            <option value="medium" selected>Trung bình</option>
                            <option value="high">Cao</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="column_status">Trạng thái</label>
                        <select id="column_status" name="column_status" class="form-control">
                            <option value="todo" selected>To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assigned_to">Gán cho</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">Chưa gán</option>
                            <!-- Members will be loaded by JS -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date">Hạn hoàn thành</label>
                        <input type="date" id="due_date" name="due_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo task</button>
            </div>
        </form>
    </div>
</div>

<!-- Task Detail Modal -->
<div id="taskDetailModal" class="modal-backdrop">
    <div class="modal modal-large">
        <div class="modal-header">
            <h3 class="modal-title" id="taskDetailTitle">Chi tiết task</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" id="taskDetailBody">
            <!-- Content will be loaded by JS -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Đóng</button>
            <button type="button" class="btn btn-outline" id="editTaskBtn" style="display: none;">Chỉnh sửa</button>
            <button type="button" class="btn btn-danger" id="deleteTaskBtn" style="display: none;">Xóa task</button>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="modal-backdrop">
    <div class="modal modal-large">
        <div class="modal-header">
            <h3 class="modal-title">Chỉnh sửa task</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form id="editTaskForm">
            <input type="hidden" id="edit_task_id" name="task_id">
            <div class="modal-body">
                <!-- Same form fields as create -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_task_title">Tiêu đề <span class="required">*</span></label>
                        <input type="text" id="edit_task_title" name="task_title" class="form-control" required maxlength="200">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Mô tả</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_priority">Độ ưu tiên</label>
                        <select id="edit_priority" name="priority" class="form-control">
                            <option value="low">Thấp</option>
                            <option value="medium">Trung bình</option>
                            <option value="high">Cao</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_column_status">Trạng thái</label>
                        <select id="edit_column_status" name="column_status" class="form-control">
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_assigned_to">Gán cho</label>
                        <select id="edit_assigned_to" name="assigned_to" class="form-control">
                            <option value="">Chưa gán</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_due_date">Hạn hoàn thành</label>
                        <input type="date" id="edit_due_date" name="due_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>File đính kèm</label>
                    <div id="edit_attachment_container">
                        <!-- Attachment UI will be loaded here -->
                    </div>
                    <input type="file" id="edit_attachment_upload" style="display: none;" 
                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    <button type="button" class="btn btn-outline btn-sm" id="uploadAttachmentBtn">
                        Upload file
                    </button>
                    <small class="form-hint">Chấp nhận: JPG, PNG, PDF, DOC, DOCX (tối đa 5MB)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Search & Filter Modal -->
<div id="searchFilterModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tìm kiếm & Lọc</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="search_text">Tìm kiếm</label>
                <input type="text" id="search_text" class="form-control" 
                       placeholder="Tìm trong tiêu đề và mô tả...">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="filter_status">Trạng thái</label>
                    <select id="filter_status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter_priority">Độ ưu tiên</label>
                    <select id="filter_priority" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="low">Thấp</option>
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="filter_assigned">Người thực hiện</label>
                <select id="filter_assigned" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="unassigned">Chưa gán</option>
                    <!-- Members loaded by JS -->
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="clearFiltersBtn">Xóa bộ lọc</button>
            <button type="button" class="btn btn-primary" id="applyFiltersBtn">Áp dụng</button>
        </div>
    </div>
</div>

<script>
// Pass project data to JavaScript
const PROJECT_ID = <?= $projectId ?>;
const IS_OWNER = <?= $isOwner ? 'true' : 'false' ?>;
const CURRENT_USER_ID = <?= $userId ?>;
</script>

<script type="module" src="<?= asset('js/kanban.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
