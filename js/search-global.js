'use strict';

const SEARCH_PER_PAGE = 20;

const QUICK_FILTER_LABELS = {
	mine: 'Task của tôi',
	unassigned: 'Chưa gán',
	overdue: 'Quá hạn',
	this_week: 'Tuần này',
	high: 'Ưu tiên cao'
};

const state = {
	quickFilter: '',
	filters: {
		search: '',
		project_id: '',
		assigned_to: '',
		status: '',
		priority: '',
		sort_by: 'updated_at',
		sort_dir: 'desc',
		overdue: false,
		due_this_week: false,
		page: 1,
		per_page: SEARCH_PER_PAGE
	}
};

document.addEventListener('DOMContentLoaded', () => {
	initGlobalSearchHub().catch((error) => {
		console.error('Init global search hub failed:', error);
		const results = document.getElementById('globalSearchResults');
		if (results) {
			results.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message || 'Không thể khởi tạo trang tìm kiếm')}</div>`;
		}
	});
});

function getDom() {
	return {
		hub: document.getElementById('globalTaskSearchHub'),
		form: document.getElementById('globalSearchForm'),
		searchInput: document.getElementById('global_search_text'),
		projectSelect: document.getElementById('global_project_id'),
		assignedSelect: document.getElementById('global_assigned_to'),
		statusSelect: document.getElementById('global_status'),
		prioritySelect: document.getElementById('global_priority'),
		sortBySelect: document.getElementById('global_sort_by'),
		sortDirSelect: document.getElementById('global_sort_dir'),
		resetButton: document.getElementById('globalResetBtn'),
		summary: document.getElementById('globalSearchSummary'),
		results: document.getElementById('globalSearchResults'),
		pagination: document.getElementById('globalSearchPagination'),
		quickFilterButtons: Array.from(document.querySelectorAll('.quick-filter-btn'))
	};
}

async function initGlobalSearchHub() {
	const dom = getDom();
	if (!dom.hub || !dom.form) {
		return;
	}

	const initialProjectId = String(dom.hub.dataset.initialProjectId || '').trim();
	const initialQuery = String(dom.hub.dataset.initialQuery || '').trim();

	if (initialProjectId && initialProjectId !== '0') {
		state.filters.project_id = initialProjectId;
	}
	state.filters.search = initialQuery;

	if (dom.searchInput) {
		dom.searchInput.value = initialQuery;
	}

	await loadFilterOptions(dom);
	bindEvents(dom);
	updateQuickFilterButtons(dom);
	await performSearch(dom, 1);
}

async function requestJson(url, fallbackError) {
	const response = await fetch(url);
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

async function loadFilterOptions(dom) {
	const result = await requestJson('api/tasks.php?global=1&filters=1', 'Không thể tải bộ lọc tìm kiếm');
	const data = result.data || {};

	const projects = Array.isArray(data.projects) ? data.projects : [];
	const members = Array.isArray(data.members) ? data.members : [];

	if (dom.projectSelect) {
		const previousProjectValue = state.filters.project_id || dom.projectSelect.value || '';
		dom.projectSelect.innerHTML = '';
		dom.projectSelect.appendChild(new Option('Tất cả dự án', ''));

		projects.forEach((project) => {
			dom.projectSelect.appendChild(
				new Option(project.project_name, String(project.project_id))
			);
		});

		if ([...dom.projectSelect.options].some((option) => option.value === previousProjectValue)) {
			dom.projectSelect.value = previousProjectValue;
		}
	}

	if (dom.assignedSelect) {
		const previousAssignedValue = state.filters.assigned_to || dom.assignedSelect.value || '';
		dom.assignedSelect.innerHTML = '';
		dom.assignedSelect.appendChild(new Option('Tất cả', ''));
		dom.assignedSelect.appendChild(new Option('Tôi', 'me'));
		dom.assignedSelect.appendChild(new Option('Chưa gán', 'unassigned'));

		members.forEach((member) => {
			dom.assignedSelect.appendChild(
				new Option(member.full_name, String(member.user_id))
			);
		});

		if ([...dom.assignedSelect.options].some((option) => option.value === previousAssignedValue)) {
			dom.assignedSelect.value = previousAssignedValue;
		}
	}
}

function bindEvents(dom) {
	dom.form.addEventListener('submit', async (event) => {
		event.preventDefault();
		await performSearch(dom, 1);
	});

	if (dom.resetButton) {
		dom.resetButton.addEventListener('click', async () => {
			resetFilters(dom);
			await performSearch(dom, 1);
		});
	}

	dom.quickFilterButtons.forEach((button) => {
		button.addEventListener('click', async () => {
			const nextFilter = button.dataset.quickFilter || '';
			state.quickFilter = state.quickFilter === nextFilter ? '' : nextFilter;
			applyQuickFilterToForm(dom, state.quickFilter);
			updateQuickFilterButtons(dom);
			await performSearch(dom, 1);
		});
	});

	const clearQuickFilter = () => {
		if (state.quickFilter) {
			state.quickFilter = '';
			updateQuickFilterButtons(dom);
		}
	};

	[dom.searchInput, dom.projectSelect, dom.assignedSelect, dom.statusSelect, dom.prioritySelect, dom.sortBySelect, dom.sortDirSelect]
		.filter(Boolean)
		.forEach((element) => {
			element.addEventListener('change', clearQuickFilter);
		});
}

function resetFilters(dom) {
	state.quickFilter = '';
	updateQuickFilterButtons(dom);

	if (dom.searchInput) dom.searchInput.value = '';
	if (dom.projectSelect) dom.projectSelect.value = '';
	if (dom.assignedSelect) dom.assignedSelect.value = '';
	if (dom.statusSelect) dom.statusSelect.value = '';
	if (dom.prioritySelect) dom.prioritySelect.value = '';
	if (dom.sortBySelect) dom.sortBySelect.value = 'updated_at';
	if (dom.sortDirSelect) dom.sortDirSelect.value = 'desc';
}

function applyQuickFilterToForm(dom, quickFilter) {
	if (quickFilter === 'mine' && dom.assignedSelect) {
		dom.assignedSelect.value = 'me';
	}
	if (quickFilter === 'unassigned' && dom.assignedSelect) {
		dom.assignedSelect.value = 'unassigned';
	}
	if (quickFilter === 'high' && dom.prioritySelect) {
		dom.prioritySelect.value = 'high';
	}
}

function updateQuickFilterButtons(dom) {
	dom.quickFilterButtons.forEach((button) => {
		const isActive = button.dataset.quickFilter === state.quickFilter;
		button.classList.toggle('btn-primary', isActive);
		button.classList.toggle('btn-outline', !isActive);
	});
}

function collectFilters(dom) {
	const filters = {
		search: dom.searchInput?.value.trim() || '',
		project_id: dom.projectSelect?.value || '',
		assigned_to: dom.assignedSelect?.value || '',
		status: dom.statusSelect?.value || '',
		priority: dom.prioritySelect?.value || '',
		sort_by: dom.sortBySelect?.value || 'updated_at',
		sort_dir: dom.sortDirSelect?.value || 'desc',
		overdue: false,
		due_this_week: false
	};

	if (state.quickFilter === 'mine') {
		filters.assigned_to = 'me';
	}
	if (state.quickFilter === 'unassigned') {
		filters.assigned_to = 'unassigned';
	}
	if (state.quickFilter === 'high') {
		filters.priority = 'high';
	}
	if (state.quickFilter === 'overdue') {
		filters.overdue = true;
		filters.due_this_week = false;
	}
	if (state.quickFilter === 'this_week') {
		filters.due_this_week = true;
		filters.overdue = false;
	}

	return filters;
}

async function performSearch(dom, page = 1) {
	const filters = collectFilters(dom);
	filters.page = page;
	filters.per_page = SEARCH_PER_PAGE;
	state.filters = filters;

	if (dom.results) {
		dom.results.innerHTML = '<p class="text-muted">Đang tải kết quả tìm kiếm...</p>';
	}

	const params = new URLSearchParams({
		global: '1',
		page: String(filters.page),
		per_page: String(filters.per_page)
	});

	if (filters.search) params.append('search', filters.search);
	if (filters.project_id) params.append('project_id', filters.project_id);
	if (filters.assigned_to) params.append('assigned_to', filters.assigned_to);
	if (filters.status) params.append('status', filters.status);
	if (filters.priority) params.append('priority', filters.priority);
	if (filters.sort_by) params.append('sort_by', filters.sort_by);
	if (filters.sort_dir) params.append('sort_dir', filters.sort_dir);
	if (filters.overdue) params.append('overdue', '1');
	if (filters.due_this_week) params.append('due_this_week', '1');

	try {
		const result = await requestJson(`api/tasks.php?${params.toString()}`, 'Không thể tìm kiếm task');
		const data = result.data || {};

		renderSummary(dom, data.summary || {}, data.pagination || {});
		renderResults(dom, data.grouped_by_project || []);
		renderPagination(dom, data.pagination || {});
	} catch (error) {
		if (dom.summary) {
			dom.summary.textContent = 'Có lỗi trong quá trình tìm kiếm';
		}

		if (dom.results) {
			dom.results.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
		}

		if (dom.pagination) {
			dom.pagination.innerHTML = '';
			dom.pagination.style.display = 'none';
		}
	}
}

function renderSummary(dom, summary, pagination) {
	if (!dom.summary) {
		return;
	}

	const totalTasks = Number(summary.total_tasks || 0);
	const projectCount = Number(summary.project_count || 0);
	const currentPage = Number(pagination.current_page || 1);
	const totalPages = Number(pagination.total_pages || 1);
	const quickFilterText = state.quickFilter ? ` | Bộ lọc nhanh: ${QUICK_FILTER_LABELS[state.quickFilter] || state.quickFilter}` : '';

	dom.summary.textContent = `${totalTasks} task trong ${projectCount} dự án | Trang ${currentPage}/${totalPages}${quickFilterText}`;
}

function renderResults(dom, groupedProjects) {
	if (!dom.results) {
		return;
	}

	if (!Array.isArray(groupedProjects) || groupedProjects.length === 0) {
		dom.results.innerHTML = '<p class="text-muted">Không tìm thấy task phù hợp với điều kiện hiện tại.</p>';
		return;
	}

	dom.results.innerHTML = groupedProjects
		.map((group) => `
			<div class="card" style="margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
				<div class="card-body" style="padding: 1rem;">
					<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem;">
						<div>
							<h3 style="margin:0 0 .25rem;">${escapeHtml(group.project_name)}</h3>
							<small class="text-muted">${Number(group.task_count || 0)} task phù hợp</small>
						</div>
						<a class="btn btn-outline btn-sm" href="project.php?id=${group.project_id}">Mở project</a>
					</div>

					<div style="display:flex; flex-direction:column; gap:.5rem;">
						${(group.tasks || []).map((task) => renderTaskItem(task)).join('')}
					</div>
				</div>
			</div>
		`)
		.join('');
}

function renderTaskItem(task) {
	const statusLabels = {
		todo: 'To Do',
		in_progress: 'In Progress',
		done: 'Done'
	};

	const priorityLabels = {
		low: 'Thấp',
		medium: 'Trung bình',
		high: 'Cao'
	};

	const assignee = task.assigned_name || task.assignee_name || 'Chưa gán';
	const dueDateText = task.due_date ? formatDate(task.due_date) : 'Chưa đặt hạn';

	return `
		<div style="border:1px solid var(--gray-200); border-radius: var(--radius-md); padding:.75rem; display:flex; justify-content:space-between; gap:.75rem; flex-wrap:wrap;">
			<div style="min-width:260px; flex:1;">
				<div style="font-weight:600; color:var(--gray-900); margin-bottom:.2rem;">${escapeHtml(task.task_title)}</div>
				<div class="text-muted" style="font-size:.875rem;">
					Người thực hiện: ${escapeHtml(assignee)} | Ưu tiên: ${priorityLabels[task.priority] || task.priority} | Hạn: ${dueDateText}
				</div>
			</div>
			<div style="display:flex; align-items:flex-start; gap:.5rem; flex-wrap:wrap;">
				<span class="badge badge-secondary">${statusLabels[task.column_status] || task.column_status}</span>
				<a class="btn btn-primary btn-sm" href="task.php?id=${task.task_id}">Chi tiết task</a>
			</div>
		</div>
	`;
}

function renderPagination(dom, pagination) {
	if (!dom.pagination) {
		return;
	}

	const currentPage = Number(pagination.current_page || 1);
	const totalPages = Number(pagination.total_pages || 1);

	if (totalPages <= 1) {
		dom.pagination.style.display = 'none';
		dom.pagination.innerHTML = '';
		return;
	}

	dom.pagination.style.display = 'flex';
	dom.pagination.innerHTML = `
		<button type="button" class="pagination-btn" data-page-action="prev" ${currentPage <= 1 ? 'disabled' : ''}>Trước</button>
		<span class="pagination-info">Trang ${currentPage}/${totalPages}</span>
		<button type="button" class="pagination-btn" data-page-action="next" ${currentPage >= totalPages ? 'disabled' : ''}>Sau</button>
	`;

	dom.pagination.querySelector('[data-page-action="prev"]')?.addEventListener('click', async () => {
		await performSearch(dom, Math.max(1, currentPage - 1));
	});

	dom.pagination.querySelector('[data-page-action="next"]')?.addEventListener('click', async () => {
		await performSearch(dom, Math.min(totalPages, currentPage + 1));
	});
}

function formatDate(dateString) {
	if (!dateString) {
		return '';
	}

	return new Date(dateString).toLocaleDateString('vi-VN');
}

function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text || '';
	return div.innerHTML;
}
