/**
 * Drag-drop module for Kanban columns.
 */

let draggedTaskId = null;

function clearDropZones() {
    document.querySelectorAll('.kanban-column-body').forEach((column) => {
        column.classList.remove('drag-over');
    });
}

function bindTaskCards(columnBody) {
    const handlers = columnBody.__kanbanDragDropHandlers || {};
    const taskCards = columnBody.querySelectorAll('.task-card');

    taskCards.forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            draggedTaskId = card.dataset.taskId;
            card.classList.add('dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', draggedTaskId || '');
            }
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            draggedTaskId = null;
            clearDropZones();
        });

        card.addEventListener('click', (event) => {
            if (!event.target.closest('.task-card-title')) {
                return;
            }

            if (typeof handlers.onTaskClick === 'function') {
                handlers.onTaskClick(card.dataset.taskId);
            }
        });
    });
}

function bindDropZone(columnBody) {
    if (columnBody.dataset.dragDropBound === '1') {
        return;
    }

    columnBody.dataset.dragDropBound = '1';

    columnBody.addEventListener('dragover', (event) => {
        event.preventDefault();
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
        columnBody.classList.add('drag-over');
    });

    columnBody.addEventListener('dragleave', () => {
        columnBody.classList.remove('drag-over');
    });

    columnBody.addEventListener('drop', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        columnBody.classList.remove('drag-over');

        if (!draggedTaskId) {
            return;
        }

        const handlers = columnBody.__kanbanDragDropHandlers || {};
        const newStatus = columnBody.closest('.kanban-column')?.dataset.status;
        if (!newStatus || typeof handlers.onTaskDrop !== 'function') {
            return;
        }

        await handlers.onTaskDrop({
            taskId: draggedTaskId,
            newStatus
        });

        draggedTaskId = null;
    });
}

export function bindColumnDragDrop(columnBody, handlers = {}) {
    if (!columnBody) {
        return;
    }

    columnBody.__kanbanDragDropHandlers = handlers;
    bindDropZone(columnBody);
    bindTaskCards(columnBody);
}

export function resetDragDropState() {
    draggedTaskId = null;
    clearDropZones();
}
