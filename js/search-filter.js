/**
 * Search/filter module for Kanban board modal.
 */

function getFilterElements() {
    return {
        search: document.getElementById('search_text'),
        status: document.getElementById('filter_status'),
        priority: document.getElementById('filter_priority'),
        assigned: document.getElementById('filter_assigned'),
        sortBy: document.getElementById('filter_sort_by'),
        sortDir: document.getElementById('filter_sort_dir')
    };
}

export function collectFiltersFromDom() {
    const elements = getFilterElements();

    return {
        search: elements.search?.value.trim() || '',
        status: elements.status?.value || '',
        priority: elements.priority?.value || '',
        assigned_to: elements.assigned?.value || '',
        sort_by: elements.sortBy?.value || 'priority',
        sort_dir: elements.sortDir?.value || 'desc'
    };
}

export function resetFiltersInDom() {
    const elements = getFilterElements();

    Object.values(elements).forEach((element) => {
        if (element) {
            const defaultValue = element.dataset?.default;
            element.value = typeof defaultValue === 'string' ? defaultValue : '';
        }
    });
}

export function bindSearchFilterEvents({ onApply, onClear } = {}) {
    const applyButton = document.getElementById('applyFiltersBtn');
    const clearButton = document.getElementById('clearFiltersBtn');

    if (applyButton) {
        applyButton.addEventListener('click', async () => {
            if (typeof onApply === 'function') {
                await onApply(collectFiltersFromDom());
            }
        });
    }

    if (clearButton) {
        clearButton.addEventListener('click', async () => {
            resetFiltersInDom();
            if (typeof onClear === 'function') {
                await onClear();
            }
        });
    }
}
