<?php
/**
 * Join Requests Management
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = (int) ($_GET['project_id'] ?? 0);
$project = null;

if ($projectId > 0) {
    $projectResult = projectGet($projectId, $userId);

    if (!$projectResult['success']) {
        flash($projectResult['error'], 'error');
        redirect('index.php');
    }

    $project = $projectResult['data'];
    if (($project['user_role'] ?? 'member') !== 'owner') {
        flash('Chỉ chủ dự án mới có thể quản lý yêu cầu tham gia.', 'error');
        redirect('project.php?id=' . $projectId);
    }
}

$pageTitle = 'Yêu cầu tham gia';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; gap: 1rem; align-items: center;">
    <div>
        <h1>Yêu cầu tham gia</h1>
        <?php if ($project): ?>
            <p class="text-muted"><?php echo htmlspecialchars($project['project_name']); ?></p>
        <?php else: ?>
            <p class="text-muted">Chọn dự án bạn làm chủ để xem yêu cầu.</p>
        <?php endif; ?>
    </div>
    <?php if ($project): ?>
        <a class="btn btn-outline" href="members.php?project_id=<?php echo $projectId; ?>">Quản lý thành viên</a>
    <?php endif; ?>
</div>

<?php if (!$project): ?>
    <div class="card">
        <div class="card-body" id="ownerProjectsContainer">
            <p class="text-muted">Đang tải danh sách dự án của bạn...</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" id="requestsContainer">
            <p class="text-muted">Đang tải yêu cầu...</p>
        </div>
    </div>
<?php endif; ?>

<script>
(() => {
    const projectId = <?php echo $projectId; ?>;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    async function loadOwnerProjects() {
        const container = document.getElementById('ownerProjectsContainer');
        const response = await fetch('api/projects.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Không thể tải dự án');
        }

        const ownerProjects = result.data.filter(project => project.user_role === 'owner');
        if (ownerProjects.length === 0) {
            container.innerHTML = '<p class="text-muted">Bạn chưa sở hữu dự án nào.</p>';
            return;
        }

        container.innerHTML = `
            <div class="project-grid">
                ${ownerProjects.map(project => `
                    <a class="project-card" href="join-requests.php?project_id=${project.project_id}" style="text-decoration: none; color: inherit;">
                        <div class="project-card-header">
                            <h3 class="project-card-title">${escapeHtml(project.project_name)}</h3>
                        </div>
                        <p class="project-card-description">${escapeHtml(project.description || 'Không có mô tả')}</p>
                        <div class="project-card-code">Mã dự án: ${escapeHtml(project.project_code)}</div>
                    </a>
                `).join('')}
            </div>
        `;
    }

    async function processRequest(requestId, action) {
        const response = await fetch(`api/join-requests.php?id=${requestId}&action=${action}`, {
            method: 'PUT'
        });
        const result = await response.json();

        if (!result.success) {
            showNotification(result.error || 'Không thể xử lý yêu cầu', 'error');
            return;
        }

        showNotification(result.message || 'Đã xử lý yêu cầu', 'success');
        await loadRequests();
    }

    async function loadRequests() {
        const container = document.getElementById('requestsContainer');
        const response = await fetch(`api/join-requests.php?project_id=${projectId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Không thể tải yêu cầu');
        }

        const requests = result.data;
        if (!Array.isArray(requests) || requests.length === 0) {
            container.innerHTML = '<p class="text-muted">Không có yêu cầu tham gia đang chờ.</p>';
            return;
        }

        container.innerHTML = `
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Người dùng</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Email</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Thời gian gửi</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    ${requests.map(request => `
                        <tr>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">
                                ${escapeHtml(request.full_name)}<br>
                                <small class="text-muted">@${escapeHtml(request.username)}</small>
                            </td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">${escapeHtml(request.email)}</td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">${escapeHtml(request.requested_at)}</td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100); display: flex; gap: .5rem;">
                                <button class="btn btn-sm btn-primary" data-action="approve" data-id="${request.request_id}">Duyệt</button>
                                <button class="btn btn-sm btn-danger" data-action="reject" data-id="${request.request_id}">Từ chối</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        container.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', async () => {
                const action = button.dataset.action;
                const id = button.dataset.id;
                await processRequest(id, action);
            });
        });
    }

    if (projectId > 0) {
        loadRequests().catch(error => {
            const container = document.getElementById('requestsContainer');
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        });
    } else {
        loadOwnerProjects().catch(error => {
            const container = document.getElementById('ownerProjectsContainer');
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
