/**
 * Task modal controller module.
 */

function createJsonRequestOptions(method, payload) {
    return {
        method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    };
}

async function parseJsonResponse(response, fallbackError = 'Không thể xử lý yêu cầu') {
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
    return parseJsonResponse(response, fallbackError);
}

function normalizeTaskPayload(formData, projectId = null) {
    const payload = {
        task_title: formData.get('task_title') || '',
        description: formData.get('description') || '',
        priority: formData.get('priority') || 'medium',
        column_status: formData.get('column_status') || 'todo',
        assigned_to: formData.get('assigned_to') || null,
        due_date: formData.get('due_date') || null
    };

    if (projectId !== null) {
        payload.project_id = projectId;
    }

    return payload;
}

function renderTaskDetail(task, helpers) {
    const priorityLabels = {
        low: 'Thấp',
        medium: 'Trung bình',
        high: 'Cao'
    };

    const statusLabels = {
        todo: 'To Do',
        in_progress: 'In Progress',
        done: 'Done'
    };

    return `
        <div class="task-detail-content">
            <div class="task-detail-section">
                <h4>Mô tả</h4>
                <p>${task.description ? helpers.escapeHtml(task.description) : '<em>Không có mô tả</em>'}</p>
            </div>

            <div class="task-detail-grid">
                <div class="task-detail-item">
                    <strong>Trạng thái:</strong>
                    <span>${statusLabels[task.column_status] || task.column_status}</span>
                </div>
                <div class="task-detail-item">
                    <strong>Độ ưu tiên:</strong>
                    <span>${priorityLabels[task.priority] || task.priority}</span>
                </div>
                <div class="task-detail-item">
                    <strong>Người thực hiện:</strong>
                    <span>${task.assignee_name ? helpers.escapeHtml(task.assignee_name) : '<em>Chưa gán</em>'}</span>
                </div>
                <div class="task-detail-item">
                    <strong>Hạn hoàn thành:</strong>
                    <span>${task.due_date ? helpers.formatDate(task.due_date) : '<em>Chưa đặt</em>'}</span>
                </div>
            </div>

            ${task.attachment_path ? `
                <div class="task-detail-section">
                    <h4>File đính kèm</h4>
                    <a href="api/upload.php?file=${encodeURIComponent(task.attachment_path)}"
                       class="btn btn-outline btn-sm" download>
                        Tải xuống file
                    </a>
                </div>
            ` : ''}

            <div class="task-detail-section">
                <small class="text-muted">
                    Tạo lúc: ${helpers.formatDateTime(task.created_at)} |
                    Cập nhật: ${helpers.formatDateTime(task.updated_at)}
                </small>
            </div>
        </div>
    `;
}

export function createTaskModalController(config) {
    const {
        projectId,
        notify,
        onTasksChanged,
        openModal,
        closeModal,
        escapeHtml,
        formatDate,
        formatDateTime
    } = config;

    const elements = {
        createForm: document.getElementById('createTaskForm'),
        editForm: document.getElementById('editTaskForm'),
        taskDetailModal: document.getElementById('taskDetailModal'),
        detailBody: document.getElementById('taskDetailBody'),
        detailTitle: document.getElementById('taskDetailTitle'),
        editButton: document.getElementById('editTaskBtn'),
        deleteButton: document.getElementById('deleteTaskBtn'),
        attachmentUploadButton: document.getElementById('uploadAttachmentBtn'),
        attachmentUploadInput: document.getElementById('edit_attachment_upload'),
        attachmentContainer: document.getElementById('edit_attachment_container')
    };

    const safeNotify = (message, type = 'info') => {
        if (typeof notify === 'function') {
            notify(message, type);
        }
    };

    const refreshTasks = async () => {
        if (typeof onTasksChanged === 'function') {
            await onTasksChanged();
        }
    };

    const setAttachmentPreview = (filename, taskId) => {
        if (!elements.attachmentContainer) {
            return;
        }

        if (!filename) {
            elements.attachmentContainer.innerHTML = '';
            return;
        }

        elements.attachmentContainer.innerHTML = `
            <div class="attachment-preview">
                <a href="api/upload.php?file=${encodeURIComponent(filename)}" download>
                    ${escapeHtml(filename)}
                </a>
                <button type="button" class="btn-remove" data-remove-attachment="${taskId}">Xóa</button>
            </div>
        `;

        const removeButton = elements.attachmentContainer.querySelector('[data-remove-attachment]');
        if (removeButton) {
            removeButton.addEventListener('click', () => {
                removeAttachment(taskId);
            });
        }
    };

    const populateEditForm = (task) => {
        const mapping = [
            ['edit_task_id', task.task_id],
            ['edit_task_title', task.task_title || ''],
            ['edit_description', task.description || ''],
            ['edit_priority', task.priority || 'medium'],
            ['edit_column_status', task.column_status || 'todo'],
            ['edit_assigned_to', task.assigned_to || ''],
            ['edit_due_date', task.due_date || '']
        ];

        mapping.forEach(([id, value]) => {
            const field = document.getElementById(id);
            if (field) {
                field.value = value;
            }
        });

        setAttachmentPreview(task.attachment_path || null, task.task_id);
    };

    const handleCreateTask = async (event) => {
        event.preventDefault();
        if (!elements.createForm) {
            return;
        }

        try {
            const payload = normalizeTaskPayload(new FormData(elements.createForm), projectId);
            await requestJson('api/tasks.php', createJsonRequestOptions('POST', payload), 'Không thể tạo task');

            safeNotify('Tạo task thành công', 'success');
            closeModal('createTaskModal');
            elements.createForm.reset();
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const handleEditTask = async (event) => {
        event.preventDefault();
        if (!elements.editForm) {
            return;
        }

        const formData = new FormData(elements.editForm);
        const taskId = formData.get('task_id');
        if (!taskId) {
            safeNotify('Lỗi: Task ID không hợp lệ', 'error');
            return;
        }

        try {
            const payload = normalizeTaskPayload(formData);
            await requestJson(
                `api/tasks.php?id=${taskId}`,
                createJsonRequestOptions('PUT', payload),
                'Không thể cập nhật task'
            );

            safeNotify('Cập nhật task thành công', 'success');
            closeModal('editTaskModal');
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const handleFileUpload = async (event) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        const taskId = document.getElementById('edit_task_id')?.value;
        if (!taskId) {
            safeNotify('Lỗi: Task ID không hợp lệ', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('attachment', file);

        try {
            const response = await fetch(`api/upload.php?task_id=${taskId}`, {
                method: 'POST',
                body: formData
            });
            const result = await parseJsonResponse(response, 'Không thể upload file');

            safeNotify('Upload file thành công', 'success');
            setAttachmentPreview(result.data.filename, taskId);
            event.target.value = '';
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const removeAttachment = async (taskId) => {
        if (!window.confirm('Bạn có chắc chắn muốn xóa file đính kèm?')) {
            return;
        }

        try {
            await requestJson(
                `api/upload.php?task_id=${taskId}`,
                { method: 'DELETE' },
                'Không thể xóa file đính kèm'
            );

            safeNotify('Đã xóa file đính kèm', 'success');
            setAttachmentPreview(null, taskId);
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const deleteTask = async (taskId) => {
        if (!window.confirm('Bạn có chắc chắn muốn xóa task này?')) {
            return;
        }

        try {
            await requestJson(`api/tasks.php?id=${taskId}`, { method: 'DELETE' }, 'Không thể xóa task');
            safeNotify('Đã xóa task', 'success');
            closeModal('taskDetailModal');
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const editTask = async (taskId) => {
        try {
            const result = await requestJson(`api/tasks.php?id=${taskId}`, {}, 'Không thể tải task');
            populateEditForm(result.data);
            openModal('editTaskModal');
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const viewTaskDetail = async (taskId) => {
        try {
            const result = await requestJson(`api/tasks.php?id=${taskId}`, {}, 'Không thể tải chi tiết task');
            const task = result.data;

            if (elements.detailTitle) {
                elements.detailTitle.textContent = task.task_title;
            }

            if (elements.detailBody) {
                elements.detailBody.innerHTML = renderTaskDetail(task, {
                    escapeHtml,
                    formatDate,
                    formatDateTime
                });
            }

            if (elements.editButton) {
                elements.editButton.style.display = task.can_edit ? 'inline-block' : 'none';
                elements.editButton.onclick = task.can_edit
                    ? async () => {
                        closeModal('taskDetailModal');
                        await editTask(task.task_id);
                    }
                    : null;
            }

            if (elements.deleteButton) {
                elements.deleteButton.style.display = task.can_delete ? 'inline-block' : 'none';
                elements.deleteButton.onclick = task.can_delete
                    ? () => {
                        deleteTask(task.task_id);
                    }
                    : null;
            }

            openModal('taskDetailModal');
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const init = () => {
        if (elements.createForm) {
            elements.createForm.addEventListener('submit', handleCreateTask);
        }

        if (elements.editForm) {
            elements.editForm.addEventListener('submit', handleEditTask);
        }

        if (elements.attachmentUploadButton && elements.attachmentUploadInput) {
            elements.attachmentUploadButton.addEventListener('click', () => {
                elements.attachmentUploadInput.click();
            });

            elements.attachmentUploadInput.addEventListener('change', handleFileUpload);
        }
    };

    return {
        init,
        viewTaskDetail,
        editTask
    };
}
