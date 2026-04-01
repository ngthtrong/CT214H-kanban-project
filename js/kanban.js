/**
 * Kanban board orchestrator module.
 */

import { bindColumnDragDrop, resetDragDropState } from './drag-drop.js';
import { createTaskModalController } from './task-modal.js';
import { fetchProjectMembers, populateMemberSelects } from './member-modal.js';
import { bindSearchFilterEvents } from './search-filter.js';

const BOARD_STATUSES = ['todo', 'in_progress', 'done'];
const MEMBER_SELECT_IDS = ['assigned_to', 'edit_assigned_to', 'filter_assigned'];

const state = {
    tasks: [],
    members: [],
    filters: {}
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

    return `
        <div class="task-card"
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

function renderBoard() {
    BOARD_STATUSES.forEach((status) => {
        const columnBody = document.getElementById(`column-${status}`);
        const columnCount = document.getElementById(`count-${status}`);
        if (!columnBody || !columnCount) {
            return;
        }

        const tasksInColumn = state.tasks.filter((task) => task.column_status === status);
        columnCount.textContent = String(tasksInColumn.length);

        if (tasksInColumn.length === 0) {
            columnBody.innerHTML = `
                <div class="empty-column">
                    <p>Không có task nào</p>
                </div>
            `;
        } else {
            columnBody.innerHTML = tasksInColumn.map((task) => renderTaskCard(task)).join('');
        }

        bindColumnDragDrop(columnBody, {
            onTaskClick: (taskId) => {
                if (taskModalController) {
                    taskModalController.viewTaskDetail(taskId);
                }
            },
            onTaskDrop: handleTaskStatusDrop
        });
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

        const result = await requestJson(`api/tasks.php?${params.toString()}`, {}, 'Không thể tải danh sách task');

        state.tasks = normalizeTaskList(result.data);
        state.filters = filters;

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
            safeCloseModal('searchFilterModal');
        },
        onClear: async () => {
            await loadTasks({});
            safeCloseModal('searchFilterModal');
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

    await loadProjectData();
});
