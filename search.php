<?php
/**
 * Global Search Hub
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$initialProjectId = (int) ($_GET['project_id'] ?? 0);
$initialQuery = trim((string) ($_GET['q'] ?? ''));
$pageTitle = 'Tìm kiếm toàn cục';
$additionalCss = ['css/kanban.css'];
$additionalJs = ['js/search-global.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Tìm kiếm toàn cục</h1>
            <p class="text-muted">Tìm task trên toàn bộ dự án của bạn, kèm bộ lọc nhanh theo ngữ cảnh làm việc.</p>
        </div>

        <div class="search-filter-bar"
             id="globalTaskSearchHub"
             data-initial-project-id="<?= $initialProjectId ?>"
             data-initial-query="<?= htmlspecialchars($initialQuery, ENT_QUOTES, 'UTF-8') ?>">
            <form id="globalSearchForm">
                <div class="search-filter-row">
                    <div class="search-box">
                        <label for="global_search_text" class="form-hint">Từ khóa</label>
                        <input type="text" id="global_search_text" class="form-control"
                               placeholder="Tìm theo tiêu đề, mô tả hoặc tên dự án...">
                    </div>

                    <div class="filter-select">
                        <label for="global_project_id" class="form-hint">Dự án</label>
                        <select id="global_project_id" class="form-control">
                            <option value="">Tất cả dự án</option>
                        </select>
                    </div>

                    <div class="filter-select">
                        <label for="global_assigned_to" class="form-hint">Người thực hiện</label>
                        <select id="global_assigned_to" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="me">Tôi</option>
                            <option value="unassigned">Chưa gán</option>
                        </select>
                    </div>

                    <div class="filter-select">
                        <label for="global_status" class="form-hint">Trạng thái</label>
                        <select id="global_status" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>
                    </div>

                    <div class="filter-select">
                        <label for="global_priority" class="form-hint">Độ ưu tiên</label>
                        <select id="global_priority" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="low">Thấp</option>
                            <option value="medium">Trung bình</option>
                            <option value="high">Cao</option>
                        </select>
                    </div>
                </div>

                <div class="search-filter-row" style="margin-top: .75rem;">
                    <div class="filter-select">
                        <label for="global_sort_by" class="form-hint">Sắp xếp theo</label>
                        <select id="global_sort_by" class="form-control">
                            <option value="updated_at" selected>Mới cập nhật</option>
                            <option value="created_at">Ngày tạo</option>
                            <option value="due_date">Hạn hoàn thành</option>
                            <option value="priority">Độ ưu tiên</option>
                            <option value="task_title">Tiêu đề</option>
                            <option value="project_name">Tên dự án</option>
                        </select>
                    </div>

                    <div class="filter-select">
                        <label for="global_sort_dir" class="form-hint">Thứ tự</label>
                        <select id="global_sort_dir" class="form-control">
                            <option value="desc" selected>Giảm dần</option>
                            <option value="asc">Tăng dần</option>
                        </select>
                    </div>

                    <div class="filter-select" style="display:flex; align-items:flex-end; gap:.5rem;">
                        <button type="button" class="btn btn-secondary" id="globalResetBtn">Xóa bộ lọc</button>
                        <button type="submit" class="btn btn-primary" id="globalSearchBtn">Tìm kiếm</button>
                    </div>
                </div>

                <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-top:.75rem;">
                    <span class="form-hint" style="margin:0;">Bộ lọc nhanh:</span>
                    <button type="button" class="btn btn-outline btn-sm quick-filter-btn" data-quick-filter="mine">Task của tôi</button>
                    <button type="button" class="btn btn-outline btn-sm quick-filter-btn" data-quick-filter="unassigned">Chưa gán</button>
                    <button type="button" class="btn btn-outline btn-sm quick-filter-btn" data-quick-filter="overdue">Quá hạn</button>
                    <button type="button" class="btn btn-outline btn-sm quick-filter-btn" data-quick-filter="this_week">Tuần này</button>
                    <button type="button" class="btn btn-outline btn-sm quick-filter-btn" data-quick-filter="high">Ưu tiên cao</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 1rem;">
            <div class="card-body">
                <div id="globalSearchSummary" class="text-muted">Đang chuẩn bị dữ liệu tìm kiếm...</div>
                <div id="globalSearchResults" style="margin-top: .75rem;">
                    <p class="text-muted">Đang tải dữ liệu...</p>
                </div>
                <div id="globalSearchPagination" class="pagination" style="display:none; margin-top:1rem;"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
