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

function normalizeTaskPayload(formData, projectId = null, options = {}) {
    const { includeAssignedTo = true } = options;

    const payload = {
        task_title: formData.get('task_title') || '',
        description: formData.get('description') || '',
        priority: formData.get('priority') || 'medium',
        column_status: formData.get('column_status') || 'todo',
        due_date: formData.get('due_date') || null
    };

    if (includeAssignedTo) {
        payload.assigned_to = formData.get('assigned_to') || null;
    }

    if (projectId !== null) {
        payload.project_id = projectId;
    }

    return payload;
}

function normalizeAttachmentFiles(attachmentPath) {
    if (!attachmentPath) {
        return [];
    }

    return String(attachmentPath)
        .split(/[\n,;|]+/)
        .map((item) => item.trim())
        .filter(Boolean)
        .map((item) => item.split('/').pop().split('\\').pop())
        .filter(Boolean);
}

function getTaskAttachmentFiles(task) {
    if (Array.isArray(task?.attachment_files)) {
        return task.attachment_files
            .map((item) => String(item || '').trim())
            .filter(Boolean)
            .map((item) => item.split('/').pop().split('\\').pop());
    }

    return normalizeAttachmentFiles(task?.attachment_path || null);
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

    const attachmentFiles = getTaskAttachmentFiles(task);

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

            ${attachmentFiles.length > 0 ? `
                <div class="task-detail-section">
                    <h4>File đính kèm</h4>
                    <ul class="task-attachment-list">
                        ${attachmentFiles.map((filename) => `
                            <li class="task-attachment-item">${helpers.escapeHtml(filename)}</li>
                        `).join('')}
                    </ul>
                    <div class="task-attachment-actions">
                        ${attachmentFiles.map((filename) => `
                            <a href="api/upload.php?file=${encodeURIComponent(filename)}"
                               class="btn btn-outline btn-sm" download>
                                Tải xuống: ${helpers.escapeHtml(filename)}
                            </a>
                        `).join('')}
                    </div>
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
        archiveButton: document.getElementById('archiveTaskBtn'),
        deleteButton: document.getElementById('deleteTaskBtn'),
        attachmentUploadButton: document.getElementById('uploadAttachmentBtn'),
        attachmentUploadInput: document.getElementById('edit_attachment_upload'),
        attachmentContainer: document.getElementById('edit_attachment_container'),
        createDueDateInput: document.getElementById('due_date'),
        editDueDateInput: document.getElementById('edit_due_date')
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

    const setAttachmentPreview = (filenames, taskId) => {
        if (!elements.attachmentContainer) {
            return;
        }

        if (!Array.isArray(filenames) || filenames.length === 0) {
            elements.attachmentContainer.innerHTML = '';
            return;
        }

        elements.attachmentContainer.innerHTML = `
            <div class="attachment-preview attachment-preview-list">
                <div class="attachment-preview-meta">Đã đính kèm ${filenames.length}/5 file</div>
                ${filenames.map((filename) => `
                    <div class="attachment-preview-item">
                        <a class="attachment-preview-link" href="api/upload.php?file=${encodeURIComponent(filename)}" download>
                            ${escapeHtml(filename)}
                        </a>
                        <div class="attachment-preview-actions">
                            <button
                                type="button"
                                class="btn-remove"
                                data-remove-attachment="${taskId}"
                                data-remove-filename="${encodeURIComponent(filename)}">
                                Xóa file này
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        elements.attachmentContainer
            .querySelectorAll('[data-remove-attachment][data-remove-filename]')
            .forEach((removeButton) => {
                removeButton.addEventListener('click', () => {
                    const encodedFilename = removeButton.dataset.removeFilename || '';
                    const filename = encodedFilename ? decodeURIComponent(encodedFilename) : '';
                    removeAttachment(taskId, filename);
                });
            });
    };

    const populateEditForm = (task) => {
        const mapping = [
            ['edit_task_id', task.task_id],
            ['edit_task_title', task.task_title || ''],
            ['edit_description', task.description || ''],
            ['edit_priority', task.priority || 'medium'],
            ['edit_column_status', task.column_status || 'todo'],
            ['edit_due_date', task.due_date || '']
        ];

        mapping.forEach(([id, value]) => {
            const field = document.getElementById(id);
            if (field) {
                field.value = value;
            }
        });

        const assigneeField = document.getElementById('edit_assigned_to');
        const assigneeGroup = assigneeField ? assigneeField.closest('.form-group') : null;

        if (assigneeField) {
            assigneeField.value = task.assigned_to || '';
            assigneeField.disabled = !task.is_owner;
        }

        if (elements.editForm) {
            elements.editForm.dataset.canEditAssignee = task.is_owner ? '1' : '0';
        }

        if (assigneeGroup) {
            assigneeGroup.style.display = task.is_owner ? '' : 'none';
        }

        if (elements.editDueDateInput) {
            const today = new Date().toISOString().split('T')[0];
            const currentDueDate = task.due_date || '';
            elements.editDueDateInput.min = currentDueDate && currentDueDate < today
                ? currentDueDate
                : today;
        }

        const attachmentFiles = getTaskAttachmentFiles(task);

        if (elements.editForm) {
            elements.editForm.dataset.attachmentCount = String(attachmentFiles.length);
        }

        setAttachmentPreview(attachmentFiles, task.task_id);
    };

    const handleCreateTask = async (event) => {
        event.preventDefault();
        if (!elements.createForm) {
            return;
        }

        try {
            const payload = normalizeTaskPayload(new FormData(elements.createForm), projectId, {
                includeAssignedTo: true
            });
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
            const canEditAssignee = elements.editForm.dataset.canEditAssignee === '1';
            const payload = normalizeTaskPayload(formData, null, {
                includeAssignedTo: canEditAssignee
            });
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
        const selectedFiles = Array.from(event.target.files || []);
        if (selectedFiles.length === 0) {
            return;
        }

        const taskId = document.getElementById('edit_task_id')?.value;
        if (!taskId) {
            safeNotify('Lỗi: Task ID không hợp lệ', 'error');
            return;
        }

        const currentCount = Number.parseInt(elements.editForm?.dataset.attachmentCount || '0', 10) || 0;
        const availableSlots = Math.max(0, 5 - currentCount);

        if (availableSlots <= 0) {
            safeNotify('Task đã đạt tối đa 5 file đính kèm', 'warning');
            event.target.value = '';
            return;
        }

        const formData = new FormData();
        selectedFiles.slice(0, availableSlots).forEach((file) => {
            formData.append('attachments[]', file);
        });

        try {
            const response = await fetch(`api/upload.php?task_id=${taskId}`, {
                method: 'POST',
                body: formData
            });
            const result = await parseJsonResponse(response, 'Không thể upload file');

            if (selectedFiles.length > availableSlots) {
                safeNotify(`Chỉ upload ${availableSlots} file do giới hạn tối đa 5 file/task`, 'warning');
            }

            safeNotify(result.message || 'Upload file thành công', 'success');
            event.target.value = '';

            const taskResult = await requestJson(`api/tasks.php?id=${taskId}`, {}, 'Không thể tải task sau khi upload');
            populateEditForm(taskResult.data);
            await refreshTasks();
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const removeAttachment = async (taskId, filename) => {
        if (!filename) {
            safeNotify('Không xác định được file cần xóa', 'error');
            return;
        }

        if (!window.confirm(`Bạn có chắc chắn muốn xóa file ${filename}?`)) {
            return;
        }

        try {
            await requestJson(
                `api/upload.php?task_id=${taskId}&file=${encodeURIComponent(filename)}`,
                { method: 'DELETE' },
                'Không thể xóa file đính kèm'
            );

            safeNotify('Đã xóa file đính kèm', 'success');

            const taskResult = await requestJson(`api/tasks.php?id=${taskId}`, {}, 'Không thể tải task sau khi xóa file');
            populateEditForm(taskResult.data);
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

    const archiveTask = async (taskId) => {
        if (!window.confirm('Bạn có chắc chắn muốn lưu trữ task này? Task sẽ bị ẩn khỏi board.')) {
            return;
        }

        try {
            await requestJson(
                `api/tasks.php?id=${taskId}&action=archive`,
                createJsonRequestOptions('PUT', {}),
                'Không thể lưu trữ task'
            );

            safeNotify('Đã lưu trữ task', 'success');
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

            if (elements.archiveButton) {
                elements.archiveButton.style.display = task.can_archive ? 'inline-block' : 'none';
                elements.archiveButton.onclick = task.can_archive
                    ? () => {
                        archiveTask(task.task_id);
                    }
                    : null;
            }

            openModal('taskDetailModal');
        } catch (error) {
            safeNotify(`Lỗi: ${error.message}`, 'error');
        }
    };

    const init = () => {
        const today = new Date().toISOString().split('T')[0];
        const assigneeField = document.getElementById('edit_assigned_to');
        const assigneeGroup = assigneeField ? assigneeField.closest('.form-group') : null;

        if (elements.createDueDateInput) {
            elements.createDueDateInput.min = today;
        }

        if (elements.editDueDateInput) {
            elements.editDueDateInput.min = today;
        }

        if (elements.editForm) {
            elements.editForm.dataset.canEditAssignee = '0';
        }

        if (assigneeField) {
            assigneeField.disabled = true;
        }

        if (assigneeGroup) {
            assigneeGroup.style.display = 'none';
        }

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
