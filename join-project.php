<?php
/**
 * Join Project Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$pageTitle = 'Tham gia dự án';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Tham gia dự án</h1>
    <p class="text-muted">Nhập mã dự án để gửi yêu cầu tham gia.</p>
</div>

<div class="card" style="max-width: 640px; margin: 0 auto;">
    <div class="card-body">
        <form id="joinProjectPageForm">
            <div class="form-group">
                <label for="project_code">Mã dự án <span class="required">*</span></label>
                <input type="text" id="project_code" name="project_code" class="form-control"
                       placeholder="Nhập mã 8 ký tự" required maxlength="8" style="text-transform: uppercase;">
                <small class="form-hint">Ví dụ: A1B2C3D4</small>
            </div>

            <div id="projectPreview" style="display: none; margin: 1rem 0; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md);">
                <h3 id="previewProjectName"></h3>
                <p id="previewProjectDesc" class="text-muted"></p>
                <p><strong>Chủ dự án:</strong> <span id="previewOwnerName"></span></p>
            </div>

            <div style="display: flex; gap: .75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" id="findProjectBtn">Tìm dự án</button>
                <button type="submit" class="btn btn-primary" id="joinProjectBtn" style="display: none;">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('joinProjectPageForm');
    const codeInput = document.getElementById('project_code');
    const preview = document.getElementById('projectPreview');
    const findBtn = document.getElementById('findProjectBtn');
    const joinBtn = document.getElementById('joinProjectBtn');

    let foundProjectId = null;

    findBtn.addEventListener('click', async () => {
        const code = codeInput.value.trim().toUpperCase();

        if (code.length !== 8) {
            showNotification('Mã dự án phải có đúng 8 ký tự', 'error');
            return;
        }

        findBtn.disabled = true;
        findBtn.textContent = 'Đang tìm...';

        try {
            const response = await fetch(`api/projects.php?code=${encodeURIComponent(code)}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Không tìm thấy dự án');
            }

            foundProjectId = result.data.project_id;
            document.getElementById('previewProjectName').textContent = result.data.project_name;
            document.getElementById('previewProjectDesc').textContent = result.data.description || 'Không có mô tả';
            document.getElementById('previewOwnerName').textContent = result.data.owner_name;

            preview.style.display = 'block';
            joinBtn.style.display = 'inline-flex';
        } catch (error) {
            foundProjectId = null;
            preview.style.display = 'none';
            joinBtn.style.display = 'none';
            showNotification(error.message, 'error');
        } finally {
            findBtn.disabled = false;
            findBtn.textContent = 'Tìm dự án';
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!foundProjectId) {
            showNotification('Vui lòng tìm dự án trước khi gửi yêu cầu', 'error');
            return;
        }

        joinBtn.disabled = true;
        joinBtn.textContent = 'Đang gửi...';

        try {
            const response = await fetch('api/join-requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: foundProjectId })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Không thể gửi yêu cầu');
            }

            showNotification(result.message || 'Đã gửi yêu cầu thành công', 'success');
            form.reset();
            preview.style.display = 'none';
            joinBtn.style.display = 'none';
            foundProjectId = null;
        } catch (error) {
            showNotification(error.message, 'error');
        } finally {
            joinBtn.disabled = false;
            joinBtn.textContent = 'Gửi yêu cầu';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
