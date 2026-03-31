<?php
/**
 * Dashboard - Project List
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Require authentication
if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = getCurrentUserId();
$user = getCurrentUser();
$pageTitle = 'Dashboard - ' . APP_NAME;

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="../css/kanban.css">

<div class="main-content">
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>Dự án của tôi</h1>
                <p class="text-muted">Quản lý các dự án và công việc nhóm</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-outline" data-modal-open="joinProjectModal">
                    <span>📥</span> Tham gia dự án
                </button>
                <button class="btn btn-primary" data-modal-open="createProjectModal">
                    <span>➕</span> Tạo dự án mới
                </button>
            </div>
        </div>

        <div id="projectsContainer">
            <div style="text-align: center; padding: 3rem;">
                <div class="spinner"></div>
                <p>Đang tải dự án...</p>
            </div>
        </div>
    </div>
</div>

<!-- Create Project Modal -->
<div id="createProjectModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tạo dự án mới</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form id="createProjectForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="project_name">Tên dự án <span class="required">*</span></label>
                    <input type="text" id="project_name" name="project_name" class="form-control" 
                           placeholder="VD: CT214H Final Project" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Mô tả ngắn về dự án"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo dự án</button>
            </div>
        </form>
    </div>
</div>

<!-- Join Project Modal -->
<div id="joinProjectModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tham gia dự án</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form id="joinProjectForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="project_code">Mã dự án <span class="required">*</span></label>
                    <input type="text" id="project_code" name="project_code" class="form-control" 
                           placeholder="Nhập mã 8 ký tự" required maxlength="8" style="text-transform: uppercase;">
                    <small class="form-hint">Nhập mã dự án mà bạn nhận được từ chủ dự án</small>
                </div>
                <div id="projectPreview" style="display: none; margin-top: 1rem; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md);">
                    <h4 id="previewProjectName"></h4>
                    <p id="previewProjectDesc" class="text-muted"></p>
                    <p><strong>Chủ dự án:</strong> <span id="previewOwnerName"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                <button type="button" class="btn btn-outline" id="findProjectBtn">Tìm dự án</button>
                <button type="submit" class="btn btn-primary" id="joinProjectBtn" style="display: none;">Gửi yêu cầu tham gia</button>
            </div>
        </form>
    </div>
</div>

<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
