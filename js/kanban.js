/**
 * Kanban Board JavaScript
 * Team Kanban - CT214H Final Project
 */

'use strict';

// State
let currentTasks = [];
let currentMembers = [];
let currentFilters = {};
let draggedTaskId = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (typeof PROJECT_ID === 'undefined') {
        console.error('PROJECT_ID not defined');
        return;
    }
    
    loadProjectData();
    initEventListeners();
});

/**
 * Load project data (tasks and members)
 */
async function loadProjectData() {
    await Promise.all([
        loadTasks(),
        loadMembers()
    ]);
}

/**
 * Load tasks from API
 */
async function loadTasks(filters = {}) {
    const boardLoading = document.getElementById('boardLoading');
    const kanbanColumns = document.getElementById('kanbanColumns');
    
    try {
        // Build query string
        const params = new URLSearchParams({ project_id: PROJECT_ID });
        
        if (filters.search) params.append('search', filters.search);
        if (filters.status) params.append('status', filters.status);
        if (filters.priority) params.append('priority', filters.priority);
        if (filters.assigned_to !== undefined && filters.assigned_to !== '') {
            params.append('assigned_to', filters.assigned_to);
        }
        
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?${params}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        // Handle both formats: array of tasks OR object with tasks property
        if (Array.isArray(result.data)) {
            currentTasks = result.data;
        } else if (result.data && Array.isArray(result.data.tasks)) {
            currentTasks = result.data.tasks;
            // Add assignee_name alias if not present
            currentTasks.forEach(task => {
                if (!task.assignee_name && task.assigned_name) {
                    task.assignee_name = task.assigned_name;
                }
            });
        } else {
            currentTasks = [];
        }
        
        currentFilters = filters;
        
        renderBoard();
        
        // Show board, hide loading
        boardLoading.style.display = 'none';
        kanbanColumns.style.display = 'flex';
        
    } catch (error) {
        console.error('Load tasks failed:', error);
        boardLoading.innerHTML = `
            <div class="alert alert-danger">
                <strong>Lỗi:</strong> ${error.message}
            </div>
        `;
    }
}

/**
 * Load project members
 */
async function loadMembers() {
    try {
        const response = await fetch(`/CT214H-kanban-project/api/members.php?project_id=${PROJECT_ID}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        currentMembers = result.data;
        updateMemberDropdowns();
        
    } catch (error) {
        console.error('Load members failed:', error);
    }
}

/**
 * Update member dropdowns
 */
function updateMemberDropdowns() {
    const selects = ['assigned_to', 'edit_assigned_to', 'filter_assigned'];
    
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        // Keep first option (empty or "Tất cả")
        const firstOption = select.options[0];
        const secondOption = select.options[1]; // "Chưa gán" for filter
        
        // Clear other options
        select.innerHTML = '';
        select.appendChild(firstOption.cloneNode(true));
        if (selectId === 'filter_assigned' && secondOption) {
            select.appendChild(secondOption.cloneNode(true));
        }
        
        // Add members
        currentMembers.forEach(member => {
            const option = document.createElement('option');
            option.value = member.user_id;
            option.textContent = member.full_name;
            select.appendChild(option);
        });
    });
}

/**
 * Render Kanban board
 */
function renderBoard() {
    const columns = ['todo', 'in_progress', 'done'];
    
    columns.forEach(status => {
        const columnBody = document.getElementById(`column-${status}`);
        const columnCount = document.getElementById(`count-${status}`);
        
        const tasks = currentTasks.filter(task => task.column_status === status);
        
        // Update count
        columnCount.textContent = tasks.length;
        
        // Render tasks
        if (tasks.length === 0) {
            columnBody.innerHTML = `
                <div class="empty-column">
                    <p>Không có task nào</p>
                </div>
            `;
        } else {
            columnBody.innerHTML = tasks.map(task => renderTaskCard(task)).join('');
        }
        
        // Initialize drag-drop for this column
        initDragDrop(columnBody);
    });
}

/**
 * Render task card HTML
 */
function renderTaskCard(task) {
    const priorityColors = {
        low: 'success',
        medium: 'warning',
        high: 'danger'
    };
    
    const priorityLabels = {
        low: '🟢 Thấp',
        medium: '🟡 Trung bình',
        high: '🔴 Cao'
    };
    
    const priorityClass = priorityColors[task.priority] || 'secondary';
    const priorityLabel = priorityLabels[task.priority] || task.priority;
    
    // Check if overdue
    const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.column_status !== 'done';
    
    return `
        <div class="task-card" 
             draggable="true" 
             data-task-id="${task.task_id}"
             data-status="${task.column_status}">
            <div class="task-card-header">
                <h4 class="task-card-title">${escapeHtml(task.task_title)}</h4>
                <span class="badge badge-${priorityClass}">${priorityLabel}</span>
            </div>
            
            ${task.description ? `
                <p class="task-card-description">${escapeHtml(task.description.substring(0, 100))}${task.description.length > 100 ? '...' : ''}</p>
            ` : ''}
            
            <div class="task-card-meta">
                ${task.assignee_name ? `
                    <span class="task-assignee" title="Người thực hiện">
                        👤 ${escapeHtml(task.assignee_name)}
                    </span>
                ` : ''}
                
                ${task.due_date ? `
                    <span class="task-due-date ${isOverdue ? 'overdue' : ''}" title="Hạn hoàn thành">
                        📅 ${formatDate(task.due_date)}
                    </span>
                ` : ''}
                
                ${task.attachment_path ? `
                    <span class="task-attachment" title="Có file đính kèm">
                        📎
                    </span>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Initialize drag and drop
 */
function initDragDrop(columnBody) {
    const taskCards = columnBody.querySelectorAll('.task-card');
    
    taskCards.forEach(card => {
        // Drag start
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        
        // Click to view details
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.task-card-title')) return;
            const taskId = this.dataset.taskId;
            viewTaskDetail(taskId);
        });
    });
    
    // Drop zone
    columnBody.addEventListener('dragover', handleDragOver);
    columnBody.addEventListener('drop', handleDrop);
    columnBody.addEventListener('dragleave', handleDragLeave);
}

/**
 * Drag start handler
 */
function handleDragStart(e) {
    draggedTaskId = this.dataset.taskId;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

/**
 * Drag end handler
 */
function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    // Remove all drag-over classes
    document.querySelectorAll('.kanban-column-body').forEach(col => {
        col.classList.remove('drag-over');
    });
}

/**
 * Drag over handler
 */
function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
    
    return false;
}

/**
 * Drag leave handler
 */
function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

/**
 * Drop handler
 */
async function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    e.preventDefault();
    this.classList.remove('drag-over');
    
    const newStatus = this.closest('.kanban-column').dataset.status;
    const taskId = draggedTaskId;
    
    // Find task
    const task = currentTasks.find(t => t.task_id == taskId);
    if (!task) return;
    
    // Check if status changed
    if (task.column_status === newStatus) {
        return;
    }
    
    const oldStatus = task.column_status;
    
    try {
        // Optimistic update
        task.column_status = newStatus;
        renderBoard();
        
        // Call API
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?id=${taskId}&action=status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                column_status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Đã cập nhật trạng thái task', 'success');
        
    } catch (error) {
        console.error('Update task status failed:', error);
        
        // Rollback
        task.column_status = oldStatus;
        renderBoard();
        
        showToast('Lỗi: ' + error.message, 'error');
    }
    
    return false;
}

/**
 * View task detail
 */
async function viewTaskDetail(taskId) {
    const modal = document.getElementById('taskDetailModal');
    const modalBody = document.getElementById('taskDetailBody');
    const modalTitle = document.getElementById('taskDetailTitle');
    const editBtn = document.getElementById('editTaskBtn');
    const deleteBtn = document.getElementById('deleteTaskBtn');
    
    try {
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?id=${taskId}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const task = result.data;
        
        modalTitle.textContent = task.task_title;
        modalBody.innerHTML = renderTaskDetail(task);
        
        // Show edit/delete buttons if allowed
        if (task.can_edit) {
            editBtn.style.display = 'inline-block';
            editBtn.onclick = () => {
                closeModal(modal);
                editTask(taskId);
            };
        } else {
            editBtn.style.display = 'none';
        }
        
        if (task.can_delete) {
            deleteBtn.style.display = 'inline-block';
            deleteBtn.onclick = () => confirmDeleteTask(taskId);
        } else {
            deleteBtn.style.display = 'none';
        }
        
        openModal('taskDetailModal');
        
    } catch (error) {
        console.error('Load task detail failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Render task detail HTML
 */
function renderTaskDetail(task) {
    const priorityLabels = {
        low: '🟢 Thấp',
        medium: '🟡 Trung bình',
        high: '🔴 Cao'
    };
    
    const statusLabels = {
        todo: '📝 To Do',
        in_progress: '⚡ In Progress',
        done: '✅ Done'
    };
    
    return `
        <div class="task-detail-content">
            <div class="task-detail-section">
                <h4>Mô tả</h4>
                <p>${task.description ? escapeHtml(task.description) : '<em>Không có mô tả</em>'}</p>
            </div>
            
            <div class="task-detail-grid">
                <div class="task-detail-item">
                    <strong>Trạng thái:</strong>
                    <span>${statusLabels[task.column_status]}</span>
                </div>
                
                <div class="task-detail-item">
                    <strong>Độ ưu tiên:</strong>
                    <span>${priorityLabels[task.priority]}</span>
                </div>
                
                <div class="task-detail-item">
                    <strong>Người thực hiện:</strong>
                    <span>${task.assignee_name ? escapeHtml(task.assignee_name) : '<em>Chưa gán</em>'}</span>
                </div>
                
                <div class="task-detail-item">
                    <strong>Hạn hoàn thành:</strong>
                    <span>${task.due_date ? formatDate(task.due_date) : '<em>Chưa đặt</em>'}</span>
                </div>
            </div>
            
            ${task.attachment_path ? `
                <div class="task-detail-section">
                    <h4>File đính kèm</h4>
                    <a href="/CT214H-kanban-project/api/upload.php?file=${encodeURIComponent(task.attachment_path)}" 
                       class="btn btn-outline btn-sm" download>
                        📎 Tải xuống file
                    </a>
                </div>
            ` : ''}
            
            <div class="task-detail-section">
                <small class="text-muted">
                    Tạo lúc: ${formatDateTime(task.created_at)} | 
                    Cập nhật: ${formatDateTime(task.updated_at)}
                </small>
            </div>
        </div>
    `;
}

/**
 * Initialize event listeners
 */
function initEventListeners() {
    // Create task form
    const createTaskForm = document.getElementById('createTaskForm');
    if (createTaskForm) {
        createTaskForm.addEventListener('submit', handleCreateTask);
    }
    
    // Edit task form
    const editTaskForm = document.getElementById('editTaskForm');
    if (editTaskForm) {
        editTaskForm.addEventListener('submit', handleEditTask);
    }
    
    // Upload attachment button
    const uploadBtn = document.getElementById('uploadAttachmentBtn');
    const uploadInput = document.getElementById('edit_attachment_upload');
    if (uploadBtn && uploadInput) {
        uploadBtn.addEventListener('click', () => uploadInput.click());
        uploadInput.addEventListener('change', handleFileUpload);
    }
    
    // Search & filter
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }
    
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearFilters);
    }
}

/**
 * Handle create task
 */
async function handleCreateTask(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        project_id: PROJECT_ID,
        task_title: formData.get('task_title'),
        description: formData.get('description'),
        priority: formData.get('priority'),
        column_status: formData.get('column_status'),
        assigned_to: formData.get('assigned_to') || null,
        due_date: formData.get('due_date') || null
    };
    
    try {
        const response = await fetch('/CT214H-kanban-project/api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Tạo task thành công', 'success');
        closeModal(document.getElementById('createTaskModal'));
        this.reset();
        loadTasks(currentFilters);
        
    } catch (error) {
        console.error('Create task failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Edit task
 */
async function editTask(taskId) {
    try {
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?id=${taskId}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const task = result.data;
        
        // Populate form
        document.getElementById('edit_task_id').value = task.task_id;
        document.getElementById('edit_task_title').value = task.task_title;
        document.getElementById('edit_description').value = task.description || '';
        document.getElementById('edit_priority').value = task.priority;
        document.getElementById('edit_column_status').value = task.column_status;
        document.getElementById('edit_assigned_to').value = task.assigned_to || '';
        document.getElementById('edit_due_date').value = task.due_date || '';
        
        // Show attachment if exists
        const attachmentContainer = document.getElementById('edit_attachment_container');
        if (task.attachment_path) {
            attachmentContainer.innerHTML = `
                <div class="attachment-preview">
                    <a href="/CT214H-kanban-project/api/upload.php?file=${encodeURIComponent(task.attachment_path)}" download>
                        📎 ${escapeHtml(task.attachment_path)}
                    </a>
                    <button type="button" class="btn-remove" onclick="removeAttachment(${task.task_id})">×</button>
                </div>
            `;
        } else {
            attachmentContainer.innerHTML = '';
        }
        
        openModal('editTaskModal');
        
    } catch (error) {
        console.error('Load task for edit failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Handle edit task
 */
async function handleEditTask(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const taskId = formData.get('task_id');
    const data = {
        task_title: formData.get('task_title'),
        description: formData.get('description'),
        priority: formData.get('priority'),
        column_status: formData.get('column_status'),
        assigned_to: formData.get('assigned_to') || null,
        due_date: formData.get('due_date') || null
    };
    
    try {
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?id=${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Cập nhật task thành công', 'success');
        closeModal(document.getElementById('editTaskModal'));
        loadTasks(currentFilters);
        
    } catch (error) {
        console.error('Update task failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Confirm delete task
 */
function confirmDeleteTask(taskId) {
    if (!confirm('Bạn có chắc chắn muốn xóa task này?')) {
        return;
    }
    
    deleteTask(taskId);
}

/**
 * Delete task
 */
async function deleteTask(taskId) {
    try {
        const response = await fetch(`/CT214H-kanban-project/api/tasks.php?id=${taskId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Đã xóa task', 'success');
        closeModal(document.getElementById('taskDetailModal'));
        loadTasks(currentFilters);
        
    } catch (error) {
        console.error('Delete task failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Handle file upload
 */
async function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const taskId = document.getElementById('edit_task_id').value;
    if (!taskId) {
        showToast('Lỗi: Task ID không hợp lệ', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('attachment', file);
    
    try {
        const response = await fetch(`/CT214H-kanban-project/api/upload.php?task_id=${taskId}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Upload file thành công', 'success');
        
        // Update attachment container
        const attachmentContainer = document.getElementById('edit_attachment_container');
        attachmentContainer.innerHTML = `
            <div class="attachment-preview">
                <a href="/CT214H-kanban-project/api/upload.php?file=${encodeURIComponent(result.data.filename)}" download>
                    📎 ${escapeHtml(result.data.filename)}
                </a>
                <button type="button" class="btn-remove" onclick="removeAttachment(${taskId})">×</button>
            </div>
        `;
        
        // Reload tasks
        loadTasks(currentFilters);
        
    } catch (error) {
        console.error('Upload file failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Remove attachment
 */
async function removeAttachment(taskId) {
    if (!confirm('Bạn có chắc chắn muốn xóa file đính kèm?')) {
        return;
    }
    
    try {
        const response = await fetch(`/CT214H-kanban-project/api/upload.php?task_id=${taskId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        showToast('Đã xóa file đính kèm', 'success');
        
        // Clear attachment container
        document.getElementById('edit_attachment_container').innerHTML = '';
        
        // Reload tasks
        loadTasks(currentFilters);
        
    } catch (error) {
        console.error('Remove attachment failed:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

/**
 * Apply filters
 */
function applyFilters() {
    const filters = {
        search: document.getElementById('search_text').value.trim(),
        status: document.getElementById('filter_status').value,
        priority: document.getElementById('filter_priority').value,
        assigned_to: document.getElementById('filter_assigned').value
    };
    
    loadTasks(filters);
    closeModal(document.getElementById('searchFilterModal'));
}

/**
 * Clear filters
 */
function clearFilters() {
    document.getElementById('search_text').value = '';
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_priority').value = '';
    document.getElementById('filter_assigned').value = '';
    
    loadTasks({});
    closeModal(document.getElementById('searchFilterModal'));
}

/**
 * Copy project code
 */
function copyProjectCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        showToast('Đã sao chép mã dự án: ' + code, 'success');
    }).catch(err => {
        console.error('Copy failed:', err);
        showToast('Không thể sao chép', 'error');
    });
}

/**
 * Format date
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

/**
 * Format datetime
 */
function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('vi-VN');
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show toast notification
 */
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
