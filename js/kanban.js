/**
 * Kanban board orchestrator module.
 */

import { bindColumnDragDrop, resetDragDropState } from './drag-drop.js?v=20260413_3';
import { createTaskModalController } from './task-modal.js?v=20260413_3';
import { fetchProjectMembers, populateMemberSelects } from './member-modal.js';
import { bindSearchFilterEvents } from './search-filter.js?v=20260413_1';

const BOARD_STATUSES = ['todo', 'in_progress', 'done'];
const MEMBER_SELECT_IDS = ['assigned_to', 'edit_assigned_to', 'filter_assigned'];
const TASKS_PER_COLUMN_PAGE = 5;

const state = {
    tasks: [],
    members: [],
    filters: {},
    columnPages: {
        todo: 1,
        in_progress: 1,
        done: 1
    }
};

let taskModalController = null;

function escapeHtml(text) {
    if (!text) {
        return '';
    }

    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleDateString('vi-VN');
}

function formatDateTime(dateString) {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleString('vi-VN');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-notification`;
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '10000';
    toast.style.minWidth = '250px';

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

async function parseResponse(response, fallbackError = 'Có lỗi xảy ra') {
    let result;
    try {
        result = await response.json();
    } catch (error) {
        throw new Error(fallbackError);
    }

    if (!result.success) {
        throw new Error(result.error || fallbackError);
    }

    return result;
}

async function requestJson(url, options = {}, fallbackError = 'Có lỗi xảy ra') {
    const response = await fetch(url, options);
    return parseResponse(response, fallbackError);
}

function normalizeTaskList(data) {
    let tasks = [];

    if (Array.isArray(data)) {
        tasks = data;
    } else if (data && Array.isArray(data.tasks)) {
        tasks = data.tasks;
    }

    tasks.forEach((task) => {
        if (!task.assignee_name && task.assigned_name) {
            task.assignee_name = task.assigned_name;
        }
    });

    return tasks;
}

function renderTaskCard(task) {
    const priorityLabels = {
        low: 'Thấp',
        medium: 'Trung bình',
        high: 'Cao'
    };

    const priorityClassMap = {
        low: 'success',
        medium: 'warning',
        high: 'danger'
    };

    const isOverdue =
        Boolean(task.due_date) &&
        new Date(task.due_date) < new Date() &&
        task.column_status !== 'done';

    const isAssignedToCurrentUser =
        typeof CURRENT_USER_ID !== 'undefined' &&
        task.assigned_to !== null &&
        String(task.assigned_to) === String(CURRENT_USER_ID);

    const cardClassName = isAssignedToCurrentUser
        ? 'task-card task-card-assigned-to-me'
        : 'task-card';

    return `
        <div class="${cardClassName}"
             draggable="true"
             data-task-id="${task.task_id}"
             data-status="${task.column_status}">
            <div class="task-card-header">
                <h4 class="task-card-title">${escapeHtml(task.task_title)}</h4>
                <span class="badge badge-${priorityClassMap[task.priority] || 'secondary'}">
                    ${priorityLabels[task.priority] || task.priority}
                </span>
            </div>

            ${task.description ? `
                <p class="task-card-description">
                    ${escapeHtml(task.description.substring(0, 100))}${task.description.length > 100 ? '...' : ''}
                </p>
            ` : ''}

            <div class="task-card-meta">
                ${task.assignee_name ? `
                    <span class="task-assignee" title="Người thực hiện">
                        Người thực hiện: ${escapeHtml(task.assignee_name)}
                    </span>
                ` : ''}

                ${task.due_date ? `
                    <span class="task-due-date ${isOverdue ? 'overdue' : ''}" title="Hạn hoàn thành">
                        Hạn: ${formatDate(task.due_date)}
                    </span>
                ` : ''}

                ${task.attachment_path ? `
                    <span class="task-attachment" title="Có file đính kèm">Có file</span>
                ` : ''}
            </div>
        </div>
    `;
}

function getFilterKey(filters = {}) {
    return JSON.stringify({
        search: filters.search || '',
        status: filters.status || '',
        priority: filters.priority || '',
        assigned_to: filters.assigned_to || '',
        sort_by: filters.sort_by || 'priority',
        sort_dir: filters.sort_dir || 'desc'
    });
}

function compareByPriority(a, b) {
    const priorityRank = {
        low: 1,
        medium: 2,
        high: 3
    };

    const aRank = priorityRank[a.priority] || 0;
    const bRank = priorityRank[b.priority] || 0;
    return aRank - bRank;
}

function compareByDueDate(a, b) {
    const aHasDueDate = Boolean(a.due_date);
    const bHasDueDate = Boolean(b.due_date);

    if (!aHasDueDate && !bHasDueDate) {
        return 0;
    }
    if (!aHasDueDate) {
        return 1;
    }
    if (!bHasDueDate) {
        return -1;
    }

    return new Date(a.due_date).getTime() - new Date(b.due_date).getTime();
}

function compareByCreatedAt(a, b) {
    return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
}

function compareByTitle(a, b) {
    const aTitle = String(a.task_title || '');
    const bTitle = String(b.task_title || '');
    return aTitle.localeCompare(bTitle, 'vi', { sensitivity: 'base' });
}

function sortTasksByFilters(tasks, filters = {}) {
    const sortBy = filters.sort_by || 'priority';
    const sortDir = filters.sort_dir === 'asc' ? 'asc' : 'desc';

    const comparators = {
        priority: compareByPriority,
        due_date: compareByDueDate,
        created_at: compareByCreatedAt,
        task_title: compareByTitle
    };

    const comparator = comparators[sortBy] || compareByPriority;

    return [...tasks].sort((a, b) => {
        const value = comparator(a, b);
        if (value === 0) {
            return compareByCreatedAt(a, b);
        }

        return sortDir === 'asc' ? value : -value;
    });
}

function resetColumnPages() {
    state.columnPages = {
        todo: 1,
        in_progress: 1,
        done: 1
    };
}

function renderColumnPagination(status, totalItems, totalPages) {
    const column = document.querySelector(`.kanban-column[data-status="${status}"]`);
    if (!column) {
        return;
    }

    let pagination = column.querySelector('.column-pagination');
    if (!pagination) {
        pagination = document.createElement('div');
        pagination.className = 'column-pagination';
        column.appendChild(pagination);
    }

    if (totalItems <= TASKS_PER_COLUMN_PAGE) {
        pagination.innerHTML = '';
        pagination.style.display = 'none';
        return;
    }

    const currentPage = state.columnPages[status] || 1;
    pagination.style.display = 'flex';
    pagination.innerHTML = `
        <button type="button" class="column-pagination-btn" data-action="prev" ${currentPage <= 1 ? 'disabled' : ''}>
            Trước
        </button>
        <span class="column-pagination-info">Trang ${currentPage}/${totalPages}</span>
        <button type="button" class="column-pagination-btn" data-action="next" ${currentPage >= totalPages ? 'disabled' : ''}>
            Sau
        </button>
    `;

    const prevButton = pagination.querySelector('[data-action="prev"]');
    const nextButton = pagination.querySelector('[data-action="next"]');

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            state.columnPages[status] = Math.max(1, currentPage - 1);
            renderBoard();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            state.columnPages[status] = Math.min(totalPages, currentPage + 1);
            renderBoard();
        });
    }
}

function renderBoard() {
    BOARD_STATUSES.forEach((status) => {
        const columnBody = document.getElementById(`column-${status}`);
        const columnCount = document.getElementById(`count-${status}`);
        if (!columnBody || !columnCount) {
            return;
        }

        const tasksInColumn = sortTasksByFilters(
            state.tasks.filter((task) => task.column_status === status),
            state.filters
        );
        columnCount.textContent = String(tasksInColumn.length);

        const totalPages = Math.max(1, Math.ceil(tasksInColumn.length / TASKS_PER_COLUMN_PAGE));
        const currentPage = Math.min(state.columnPages[status] || 1, totalPages);
        state.columnPages[status] = currentPage;

        const startIndex = (currentPage - 1) * TASKS_PER_COLUMN_PAGE;
        const tasksOnPage = tasksInColumn.slice(startIndex, startIndex + TASKS_PER_COLUMN_PAGE);

        if (tasksOnPage.length === 0) {
            columnBody.innerHTML = `
                <div class="empty-column">
                    <p>Không có task nào</p>
                </div>
            `;
        } else {
            columnBody.innerHTML = tasksOnPage.map((task) => renderTaskCard(task)).join('');
        }

        bindColumnDragDrop(columnBody, {
            onTaskClick: (taskId) => {
                if (taskModalController) {
                    taskModalController.viewTaskDetail(taskId);
                }
            },
            onTaskDrop: handleTaskStatusDrop
        });

        renderColumnPagination(status, tasksInColumn.length, totalPages);
    });
}

async function handleTaskStatusDrop({ taskId, newStatus }) {
    const task = state.tasks.find((item) => String(item.task_id) === String(taskId));
    if (!task || task.column_status === newStatus) {
        return;
    }

    const previousStatus = task.column_status;

    try {
        task.column_status = newStatus;
        renderBoard();

        await requestJson(
            `api/tasks.php?id=${taskId}&action=status`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ column_status: newStatus })
            },
            'Không thể cập nhật trạng thái task'
        );

        showToast('Đã cập nhật trạng thái task', 'success');
    } catch (error) {
        task.column_status = previousStatus;
        renderBoard();
        showToast(`Lỗi: ${error.message}`, 'error');
    } finally {
        resetDragDropState();
    }
}

async function loadTasks(filters = {}) {
    const boardLoading = document.getElementById('boardLoading');
    const columnsWrapper = document.getElementById('kanbanColumns');

    try {
        const params = new URLSearchParams({ project_id: PROJECT_ID });

        if (filters.search) params.append('search', filters.search);
        if (filters.status) params.append('status', filters.status);
        if (filters.priority) params.append('priority', filters.priority);
        if (filters.assigned_to !== undefined && filters.assigned_to !== '') {
            params.append('assigned_to', filters.assigned_to);
        }

        const hasServerFilters = Boolean(
            filters.search ||
            filters.status ||
            filters.priority ||
            (filters.assigned_to !== undefined && filters.assigned_to !== '')
        );

        if (hasServerFilters) {
            params.append('per_page', '500');
        }

        const result = await requestJson(`api/tasks.php?${params.toString()}`, {}, 'Không thể tải danh sách task');

        const previousFilterKey = getFilterKey(state.filters);
        const nextFilterKey = getFilterKey(filters);

        state.tasks = normalizeTaskList(result.data);
        state.filters = filters;

        if (previousFilterKey !== nextFilterKey) {
            resetColumnPages();
        }

        renderBoard();

        if (boardLoading) {
            boardLoading.style.display = 'none';
        }
        if (columnsWrapper) {
            columnsWrapper.style.display = '';
        }
    } catch (error) {
        console.error('Load tasks failed:', error);

        if (boardLoading) {
            boardLoading.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Lỗi:</strong> ${escapeHtml(error.message)}
                </div>
            `;
        }
    }
}

async function loadArchivedTasks() {
    const container = document.getElementById('archivedTasksContainer');
    if (!container) {
        return;
    }

    container.innerHTML = '<p class="text-muted">Đang tải task đã lưu trữ...</p>';

    try {
        const result = await requestJson(
            `api/tasks.php?project_id=${PROJECT_ID}&archived=1`,
            {},
            'Không thể tải task đã lưu trữ'
        );

        const archivedTasks = normalizeTaskList(result.data);
        if (archivedTasks.length === 0) {
            container.innerHTML = '<p class="text-muted">Chưa có task nào trong kho lưu trữ.</p>';
            return;
        }

        container.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                ${archivedTasks.map(task => `
                    <div class="card" style="box-shadow: var(--shadow-sm);">
                        <div class="card-body" style="padding: 1rem; display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;">
                            <div>
                                <h4 style="margin-bottom: 0.25rem;">${escapeHtml(task.task_title)}</h4>
                                <p class="text-muted" style="margin-bottom: 0.5rem;">
                                    ${task.description ? escapeHtml(task.description) : 'Không có mô tả'}
                                </p>
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.875rem; color: var(--gray-600);">
                                    <span>Ưu tiên: ${escapeHtml(task.priority || '-')}</span>
                                    <span>Trạng thái: ${escapeHtml(task.column_status || '-')}</span>
                                    <span>Người thực hiện: ${task.assignee_name ? escapeHtml(task.assignee_name) : 'Chưa gán'}</span>
                                    <span>Lưu trữ lúc: ${task.archived_at ? formatDateTime(task.archived_at) : '-'}</span>
                                </div>
                            </div>
                            ${IS_OWNER ? `
                                <button type="button"
                                        class="btn btn-outline btn-sm unarchive-task-btn"
                                        data-task-id="${task.task_id}"
                                        data-task-title="${encodeURIComponent(task.task_title || '')}">
                                    Bỏ lưu trữ
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
            ${!IS_OWNER ? '<p class="text-muted" style="margin-top: 0.75rem;">Chỉ chủ dự án mới có thể bỏ lưu trữ task.</p>' : ''}
        `;

        container.querySelectorAll('.unarchive-task-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const taskId = this.dataset.taskId;
                const taskTitle = this.dataset.taskTitle
                    ? decodeURIComponent(this.dataset.taskTitle)
                    : 'task nay';

                await unarchiveTask(taskId, taskTitle);
            });
        });
    } catch (error) {
        console.error('Load archived tasks failed:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <strong>Lỗi:</strong> ${escapeHtml(error.message)}
            </div>
        `;
    }
}

async function unarchiveTask(taskId, taskTitle) {
    if (!taskId) {
        return;
    }

    if (!window.confirm(`Bạn có chắc muốn bỏ lưu trữ "${taskTitle}"?`)) {
        return;
    }

    try {
        const result = await requestJson(
            `api/tasks.php?id=${taskId}&action=unarchive`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            },
            'Khong the khoi phuc task'
        );

        showToast(result.message || 'Đã bỏ lưu trữ task', 'success');
        await Promise.all([loadTasks(state.filters), loadArchivedTasks()]);
    } catch (error) {
        showToast(`Lỗi: ${error.message}`, 'error');
    }
}

async function loadMembers() {
    try {
        state.members = await fetchProjectMembers(PROJECT_ID);
        populateMemberSelects(state.members, MEMBER_SELECT_IDS);
    } catch (error) {
        console.error('Load members failed:', error);
        showToast(`Lỗi: ${error.message}`, 'error');
    }
}

async function loadProjectData() {
    await Promise.all([loadTasks(), loadMembers()]);
}

async function copyProjectCode(code) {
    if (!code) {
        return;
    }

    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(code);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = code;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        showToast(`Đã sao chép mã dự án: ${code}`, 'success');
    } catch (error) {
        console.error('Copy failed:', error);
        showToast('Không thể sao chép mã dự án', 'error');
    }
}

function bindProjectCodeActions() {
    document.querySelectorAll('[data-copy-project-code]').forEach((button) => {
        button.addEventListener('click', async () => {
            await copyProjectCode(button.dataset.copyProjectCode || '');
        });
    });
}

async function archiveCurrentProject() {
    if (!IS_OWNER) {
        return;
    }

    const projectName = typeof PROJECT_NAME === 'string' ? PROJECT_NAME : 'du an nay';
    if (!window.confirm(`Bạn có chắc muốn lưu trữ "${projectName}"? Dự án sẽ bị ẩn khỏi danh sách.`)) {
        return;
    }

    try {
        const result = await requestJson(
            `api/projects.php?id=${PROJECT_ID}&action=archive`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            },
            'Khong the luu tru du an'
        );

        showToast(result.message || 'Da luu tru du an', 'success');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 500);
    } catch (error) {
        showToast(`Lỗi: ${error.message}`, 'error');
    }
}

function bindProjectArchiveAction() {
    const button = document.getElementById('archiveProjectBtn');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        await archiveCurrentProject();
    });
}

function bindArchivedTasksAction() {
    const button = document.getElementById('viewArchivedTasksBtn');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        await loadArchivedTasks();
    });
}

function safeOpenModal(modalId) {
    if (typeof window.openModal === 'function') {
        window.openModal(modalId);
    }
}

function safeCloseModal(modalId) {
    if (typeof window.closeModal === 'function') {
        window.closeModal(modalId);
    }
}

function bindFilters() {
    bindSearchFilterEvents({
        onApply: async (filters) => {
            await loadTasks(filters);
        },
        onClear: async () => {
            await loadTasks({});
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    if (typeof PROJECT_ID === 'undefined') {
        console.error('PROJECT_ID not defined');
        return;
    }

    taskModalController = createTaskModalController({
        projectId: PROJECT_ID,
        notify: showToast,
        onTasksChanged: async () => {
            await loadTasks(state.filters);
        },
        openModal: safeOpenModal,
        closeModal: safeCloseModal,
        escapeHtml,
        formatDate,
        formatDateTime
    });

    taskModalController.init();
    bindFilters();
    bindProjectCodeActions();
    bindProjectArchiveAction();
    bindArchivedTasksAction();

    await loadProjectData();
});
