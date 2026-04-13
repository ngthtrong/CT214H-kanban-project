<?php
/**
 * Project Members Management
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = (int) ($_GET['project_id'] ?? 0);
if (!$projectId) {
    flash('Thiếu project_id', 'error');
    redirect('index.php');
}

$projectResult = projectGet($projectId, $userId);

if (!$projectResult['success']) {
    flash($projectResult['error'], 'error');
    redirect('index.php');
}

$project = $projectResult['data'];
$isOwner = $project['user_role'] === 'owner';

$pageTitle = 'Thành viên - ' . $project['project_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; gap: 1rem; align-items: center;">
    <div>
        <h1>Thành viên dự án</h1>
        <p class="text-muted"><?php echo htmlspecialchars($project['project_name']); ?></p>
    </div>
    <div style="display: flex; gap: .5rem;">
        <a class="btn btn-outline" href="project.php?id=<?php echo $projectId; ?>">Quay lại Board</a>
        <?php if ($isOwner): ?>
            <a class="btn btn-secondary" href="join-requests.php?project_id=<?php echo $projectId; ?>">Yêu cầu tham gia</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isOwner): ?>
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <p class="text-muted" style="margin: 0;">
            Chức năng thêm trực tiếp thành viên đã tắt. Vui lòng duyệt thành viên qua mục Yêu cầu tham gia.
        </p>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div id="membersContainer">
            <p class="text-muted">Đang tải thành viên...</p>
        </div>

        <?php if (!$isOwner): ?>
            <div style="margin-top: 1rem;">
                <button id="leaveProjectBtn" class="btn btn-danger">Rời khỏi dự án</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const PROJECT_ID = <?php echo $projectId; ?>;
    const CURRENT_USER_ID = <?php echo (int) $userId; ?>;
    const IS_OWNER = <?php echo $isOwner ? 'true' : 'false'; ?>;

    const membersContainer = document.getElementById('membersContainer');

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function renderMembers(members) {
        if (!Array.isArray(members) || members.length === 0) {
            membersContainer.innerHTML = '<p class="text-muted">Chưa có thành viên nào.</p>';
            return;
        }

        membersContainer.innerHTML = `
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Họ tên</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Username</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Vai trò</th>
                        <th style="text-align: left; padding: .5rem; border-bottom: 1px solid var(--gray-200);">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    ${members.map(member => `
                        <tr>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">${escapeHtml(member.full_name)}</td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">${escapeHtml(member.username)}</td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">${member.role === 'owner' ? 'Chủ dự án' : 'Thành viên'}</td>
                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-100);">
                                ${IS_OWNER && member.role !== 'owner' ? `<button class="btn btn-sm btn-danger" data-remove="${member.user_id}">Xóa</button>` : ''}
                                ${!IS_OWNER && member.user_id === CURRENT_USER_ID ? '<span class="text-muted">Bạn</span>' : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        membersContainer.querySelectorAll('[data-remove]').forEach(button => {
            button.addEventListener('click', () => removeMember(button.dataset.remove));
        });
    }

    async function loadMembers() {
        const response = await fetch(`api/members.php?project_id=${PROJECT_ID}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Không thể tải danh sách thành viên');
        }

        renderMembers(result.data);
    }

    async function removeMember(memberUserId) {
        if (!confirm('Bạn có chắc muốn xóa thành viên này?')) {
            return;
        }

        const response = await fetch(`api/members.php?project_id=${PROJECT_ID}&user_id=${memberUserId}`, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (!result.success) {
            showNotification(result.error || 'Không thể xóa thành viên', 'error');
            return;
        }

        showNotification(result.message || 'Đã xóa thành viên', 'success');
        await loadMembers();
    }

    if (!IS_OWNER) {
        const leaveProjectBtn = document.getElementById('leaveProjectBtn');
        leaveProjectBtn.addEventListener('click', async () => {
            if (!confirm('Bạn có chắc muốn rời khỏi dự án này?')) {
                return;
            }

            const response = await fetch('api/members.php?action=leave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: PROJECT_ID })
            });
            const result = await response.json();

            if (!result.success) {
                showNotification(result.error || 'Không thể rời khỏi dự án', 'error');
                return;
            }

            showNotification(result.message || 'Đã rời dự án', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 600);
        });
    }

    loadMembers().catch(error => {
        membersContainer.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
