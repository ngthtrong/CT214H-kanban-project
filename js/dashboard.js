/**
 * Dashboard JavaScript
 * Team Kanban - CT214H Final Project
 */

'use strict';

// Load projects on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    initCreateProjectForm();
    initJoinProjectForm();
});

/**
 * Load user's projects
 */
async function loadProjects() {
    const container = document.getElementById('projectsContainer');
    
    try {
        const response = await fetch('api/projects.php');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const projects = result.data;
        
        if (projects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon empty-state-icon-text">Dự án</div>
                    <h3>Chưa có dự án nào</h3>
                    <p>Tạo dự án mới hoặc tham gia dự án có sẵn để bắt đầu</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div class="project-grid">
                ${projects.map(project => renderProjectCard(project)).join('')}
            </div>
        `;
        
        // Add click handlers
        document.querySelectorAll('.project-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-copy')) {
                    const projectId = this.dataset.projectId;
                    window.location.href = `project.php?id=${projectId}`;
                }
            });
        });
        
    } catch (error) {
        console.error('Load projects failed:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <strong>Lỗi:</strong> ${error.message}
            </div>
        `;
    }
}

/**
 * Render project card HTML
 */
function renderProjectCard(project) {
    const roleClass = project.user_role === 'owner' ? 'owner' : 'member';
    const roleLabel = project.user_role === 'owner' ? 'Chủ dự án' : 'Thành viên';
    
    return `
        <div class="project-card" data-project-id="${project.project_id}">
            <div class="project-card-header">
                <div>
                    <h3 class="project-card-title">${escapeHtml(project.project_name)}</h3>
                    <span class="project-role-badge ${roleClass}">${roleLabel}</span>
                </div>
                <button class="btn-copy" onclick="copyProjectCode('${project.project_code}'); event.stopPropagation();" 
                        title="Sao chép mã dự án">
                    <span>Copy</span>
                </button>
            </div>
            
            ${project.description ? `
                <p class="project-card-description">${escapeHtml(project.description)}</p>
            ` : ''}
            
            <div class="project-card-stats">
                <div class="project-stat">
                    <span class="project-stat-label">Thành viên:</span>
                    <span>${project.member_count || 0}</span>
                </div>
                <div class="project-stat">
                    <span class="project-stat-label">Công việc:</span>
                    <span>${project.task_count || 0}</span>
                </div>
            </div>
            
            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
                <span class="project-card-code">${project.project_code}</span>
                <small class="text-muted">${timeAgo(project.updated_at)}</small>
            </div>
        </div>
    `;
}

/**
 * Initialize create project form
 */
function initCreateProjectForm() {
    const form = document.getElementById('createProjectForm');
    if (!form) {
        return;
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Đang tạo...';
        
        const formData = {
            project_name: form.project_name.value.trim(),
            description: form.description.value.trim() || null
        };
        
        try {
            const response = await fetch('api/projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            showNotification('Tạo dự án thành công!', 'success');
            closeModal('createProjectModal');
            form.reset();
            
            // Redirect to new project
            setTimeout(() => {
                window.location.href = `project.php?id=${result.data.project_id}`;
            }, 500);
            
        } catch (error) {
            console.error('Create project failed:', error);
            showNotification(error.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

/**
 * Initialize join project form
 */
function initJoinProjectForm() {
    const form = document.getElementById('joinProjectForm');
    const findBtn = document.getElementById('findProjectBtn');
    const joinBtn = document.getElementById('joinProjectBtn');
    const codeInput = document.getElementById('project_code');
    const preview = document.getElementById('projectPreview');

    if (!form || !findBtn || !joinBtn || !codeInput || !preview) {
        return;
    }
    
    let foundProjectId = null;
    
    // Find project by code
    findBtn.addEventListener('click', async function() {
        const code = codeInput.value.trim().toUpperCase();
        
        if (!code || code.length !== 8) {
            showNotification('Mã dự án phải có 8 ký tự', 'error');
            return;
        }
        
        findBtn.disabled = true;
        findBtn.textContent = 'Đang tìm...';
        
        try {
            const response = await fetch(`api/projects.php?code=${code}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const project = result.data;
            foundProjectId = project.project_id;
            
            // Show preview
            document.getElementById('previewProjectName').textContent = project.project_name;
            document.getElementById('previewProjectDesc').textContent = project.description || 'Không có mô tả';
            document.getElementById('previewOwnerName').textContent = project.owner_name;
            preview.style.display = 'block';
            
            findBtn.style.display = 'none';
            joinBtn.style.display = 'inline-flex';
            codeInput.disabled = true;
            
        } catch (error) {
            console.error('Find project failed:', error);
            showNotification(error.message, 'error');
        } finally {
            findBtn.disabled = false;
            findBtn.textContent = 'Tìm dự án';
        }
    });
    
    // Submit join request
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!foundProjectId) {
            showNotification('Vui lòng tìm dự án trước', 'error');
            return;
        }
        
        joinBtn.disabled = true;
        joinBtn.innerHTML = '<span class="spinner"></span> Đang gửi...';
        
        try {
            const response = await fetch('api/join-requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ project_id: foundProjectId })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            showNotification(result.message, 'success');
            closeModal('joinProjectModal');
            
            // Reset form
            form.reset();
            preview.style.display = 'none';
            findBtn.style.display = 'inline-flex';
            joinBtn.style.display = 'none';
            codeInput.disabled = false;
            foundProjectId = null;
            
        } catch (error) {
            console.error('Join project failed:', error);
            showNotification(error.message, 'error');
            joinBtn.disabled = false;
            joinBtn.textContent = 'Gửi yêu cầu tham gia';
        }
    });
}

/**
 * Copy project code to clipboard
 */
function copyProjectCode(code) {
    copyToClipboard(code);
    showNotification(`Đã sao chép mã: ${code}`, 'success');
}

/**
 * Time ago helper
 */
function timeAgo(datetime) {
    const time = new Date(datetime).getTime();
    const now = Date.now();
    const diff = (now - time) / 1000; // seconds
    
    if (diff < 60) return 'vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';
    
    return new Date(datetime).toLocaleDateString('vi-VN');
}
