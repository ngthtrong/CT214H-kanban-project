<?php
/**
 * Search Tasks Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$initialProjectId = (int) ($_GET['project_id'] ?? 0);
$pageTitle = 'Tìm kiếm task';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Tìm kiếm task</h1>
    <p class="text-muted">Lọc và tìm task theo dự án, trạng thái, ưu tiên và người thực hiện.</p>
</div>

<div class="card">
    <div class="card-body">
        <form id="searchForm" class="form-grid">
            <div class="form-row">
                <div class="form-group">
                    <label for="project_id">Dự án</label>
                    <select id="project_id" class="form-control" required>
                        <option value="">Chọn dự án</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="search">Từ khóa</label>
                    <input type="text" id="search" class="form-control" placeholder="Tìm trong tiêu đề/mô tả">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Độ ưu tiên</label>
                    <select id="priority" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="low">Thấp</option>
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_to">Người thực hiện</label>
                    <select id="assigned_to" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="unassigned">Chưa gán</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: .75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" id="resetBtn">Xóa bộ lọc</button>
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 1rem;">
    <div class="card-body" id="searchResults">
        <p class="text-muted">Chọn dự án và nhấn Tìm kiếm để xem kết quả.</p>
    </div>
</div>

<script>
(() => {
    const initialProjectId = <?php echo $initialProjectId; ?>;
    const projectSelect = document.getElementById('project_id');
    const assignedSelect = document.getElementById('assigned_to');
    const form = document.getElementById('searchForm');
    const results = document.getElementById('searchResults');

    const statusLabels = {
        todo: 'To Do',
        in_progress: 'In Progress',
        done: 'Done'
    };

    const priorityLabels = {
        low: 'Thấp',
        medium: 'Trung bình',
        high: 'Cao'
    };

    async function loadProjects() {
        const response = await fetch('api/projects.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Không thể tải danh sách dự án');
        }

        result.data.forEach(project => {
            const option = document.createElement('option');
            option.value = project.project_id;
            option.textContent = project.project_name;
            projectSelect.appendChild(option);
        });

        if (initialProjectId) {
            projectSelect.value = String(initialProjectId);
            await loadMembers(initialProjectId);
        }
    }

    async function loadMembers(projectId) {
        assignedSelect.innerHTML = '';
        assignedSelect.appendChild(new Option('Tất cả', ''));
        assignedSelect.appendChild(new Option('Chưa gán', 'unassigned'));

        if (!projectId) {
            return;
        }

        const response = await fetch(`api/members.php?project_id=${projectId}`);
        const result = await response.json();

        if (!result.success) {
            return;
        }

        result.data.forEach(member => {
            assignedSelect.appendChild(new Option(member.full_name, String(member.user_id)));
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function renderTasks(tasks) {
        if (!Array.isArray(tasks) || tasks.length === 0) {
            results.innerHTML = '<p class="text-muted">Không tìm thấy task nào phù hợp.</p>';
            return;
        }

        results.innerHTML = `
            <div class="project-grid">
                ${tasks.map(task => `
                    <div class="project-card">
                        <div class="project-card-header">
                            <h3 class="project-card-title">${escapeHtml(task.task_title)}</h3>
                            <span class="badge badge-secondary">${statusLabels[task.column_status] || task.column_status}</span>
                        </div>
                        <p class="project-card-description">${escapeHtml(task.description || 'Không có mô tả')}</p>
                        <div class="project-card-stats">
                            <div class="project-stat">Ưu tiên: ${priorityLabels[task.priority] || task.priority}</div>
                            <div class="project-stat">Người thực hiện: ${escapeHtml(task.assigned_name || 'Chưa gán')}</div>
                        </div>
                        <div style="margin-top: .75rem; display: flex; gap: .5rem;">
                            <a class="btn btn-outline btn-sm" href="project.php?id=${task.project_id}">Mở project</a>
                            <a class="btn btn-primary btn-sm" href="task.php?id=${task.task_id}">Chi tiết task</a>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async function searchTasks() {
        const projectId = projectSelect.value;
        if (!projectId) {
            showNotification('Vui lòng chọn dự án trước', 'warning');
            return;
        }

        const params = new URLSearchParams({ project_id: projectId });
        const searchValue = document.getElementById('search').value.trim();
        const status = document.getElementById('status').value;
        const priority = document.getElementById('priority').value;
        const assignedTo = assignedSelect.value;

        if (searchValue) params.append('search', searchValue);
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);
        if (assignedTo) params.append('assigned_to', assignedTo);

        results.innerHTML = '<p class="text-muted">Đang tải kết quả...</p>';

        const response = await fetch(`api/tasks.php?${params.toString()}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Không thể tìm kiếm task');
        }

        const tasks = Array.isArray(result.data) ? result.data : (result.data.tasks || []);
        renderTasks(tasks);
    }

    projectSelect.addEventListener('change', async () => {
        await loadMembers(projectSelect.value);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            await searchTasks();
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        }
    });

    document.getElementById('resetBtn').addEventListener('click', () => {
        document.getElementById('search').value = '';
        document.getElementById('status').value = '';
        document.getElementById('priority').value = '';
        assignedSelect.value = '';
        results.innerHTML = '<p class="text-muted">Đã xóa bộ lọc. Nhấn Tìm kiếm để tải lại dữ liệu.</p>';
    });

    loadProjects().catch(error => {
        results.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
