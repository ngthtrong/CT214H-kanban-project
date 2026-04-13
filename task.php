<?php
/**
 * Task Detail Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$taskId = (int) ($_GET['id'] ?? 0);
if (!$taskId) {
    flash('Task không tồn tại', 'error');
    redirect('index.php');
}

$userId = getCurrentUserId();
$result = taskGet($taskId, $userId);

if (!$result['success']) {
    flash($result['error'], 'error');
    redirect('index.php');
}

$task = $result['data'];
$attachmentFiles = taskParseAttachmentPaths($task['attachment_path'] ?? null);
$pageTitle = 'Task: ' . ($task['task_title'] ?? 'Chi tiết');
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1><?php echo htmlspecialchars($task['task_title']); ?></h1>
    <p class="text-muted">Chi tiết task trong dự án</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="form-group">
            <label>Mô tả</label>
            <p><?php echo !empty($task['description']) ? nl2br(htmlspecialchars($task['description'])) : '<em>Không có mô tả</em>'; ?></p>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Trạng thái</label>
                <p><?php echo htmlspecialchars($task['column_status']); ?></p>
            </div>
            <div class="form-group">
                <label>Độ ưu tiên</label>
                <p><?php echo htmlspecialchars($task['priority']); ?></p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Người thực hiện</label>
                <p><?php echo !empty($task['assigned_name']) ? htmlspecialchars($task['assigned_name']) : '<em>Chưa gán</em>'; ?></p>
            </div>
            <div class="form-group">
                <label>Hạn hoàn thành</label>
                <p><?php echo !empty($task['due_date']) ? htmlspecialchars(formatDate($task['due_date'])) : '<em>Chưa đặt</em>'; ?></p>
            </div>
        </div>

        <?php if (!empty($attachmentFiles)): ?>
            <div class="form-group">
                <label>File đính kèm</label>
                <ul style="margin-bottom: .5rem; padding-left: 1.25rem;">
                    <?php foreach ($attachmentFiles as $attachmentFile): ?>
                        <li><?php echo htmlspecialchars($attachmentFile); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div style="display: flex; flex-wrap: wrap; gap: .5rem;">
                    <?php foreach ($attachmentFiles as $attachmentFile): ?>
                        <a class="btn btn-outline btn-sm" href="api/upload.php?file=<?php echo urlencode($attachmentFile); ?>" download>
                            Tải xuống: <?php echo htmlspecialchars($attachmentFile); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: .75rem; margin-top: 1rem;">
            <a class="btn btn-secondary" href="project.php?id=<?php echo (int) $task['project_id']; ?>">Quay lại Project</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
