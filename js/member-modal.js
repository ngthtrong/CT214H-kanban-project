/**
 * Members module for fetching and binding project members.
 */

function sortMembers(members) {
    return [...members].sort((a, b) => {
        const nameA = (a.full_name || '').toLowerCase();
        const nameB = (b.full_name || '').toLowerCase();
        return nameA.localeCompare(nameB, 'vi');
    });
}

export async function fetchProjectMembers(projectId) {
    const response = await fetch(`api/members.php?project_id=${projectId}`);
    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Không thể tải thành viên dự án');
    }

    return Array.isArray(result.data) ? result.data : [];
}

export function populateMemberSelects(members, selectIds = []) {
    const normalizedMembers = Array.isArray(members) ? sortMembers(members) : [];

    selectIds.forEach((selectId) => {
        const select = document.getElementById(selectId);
        if (!select) {
            return;
        }

        const previousValue = select.value;
        select.innerHTML = '';

        if (selectId === 'filter_assigned') {
            select.appendChild(new Option('Tất cả', ''));
            select.appendChild(new Option('Chưa gán', 'unassigned'));
        } else {
            select.appendChild(new Option('Chưa gán', ''));
        }

        normalizedMembers.forEach((member) => {
            select.appendChild(new Option(member.full_name, String(member.user_id)));
        });

        if ([...select.options].some((option) => option.value === previousValue)) {
            select.value = previousValue;
        }
    });
}
