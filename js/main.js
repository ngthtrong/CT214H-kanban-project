/**
 * Team Kanban - Main JavaScript
 * CT214H Web Programming Final Project
 * =========================================================
 */

'use strict';

// =========================================================
// DOM Ready
// =========================================================
document.addEventListener('DOMContentLoaded', function() {
    initNavbarToggle();
    initFormValidation();
    initAlertDismiss();
    initDropdowns();
    initModals();
    initAvatarUpload();
});

// =========================================================
// Navbar Toggle (Mobile)
// =========================================================
function initNavbarToggle() {
    const toggle = document.querySelector('.navbar-toggle');
    const nav = document.querySelector('.navbar-nav');
    
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            // Update aria-expanded
            const expanded = nav.classList.contains('active');
            toggle.setAttribute('aria-expanded', expanded);
        });
        
        // Close nav when clicking outside
        document.addEventListener('click', function(e) {
            if (!toggle.contains(e.target) && !nav.contains(e.target)) {
                nav.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
}

// =========================================================
// Form Validation
// =========================================================
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation on blur
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            // Clear error on input
            input.addEventListener('input', function() {
                clearFieldError(input);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(function(input) {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const rules = field.dataset;
    let isValid = true;
    let errorMessage = '';
    
    // Required check
    if (field.hasAttribute('required') && value === '') {
        isValid = false;
        errorMessage = 'Trường này là bắt buộc';
    }
    
    // Email validation
    if (isValid && field.type === 'email' && value !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Email không hợp lệ';
        }
    }
    
    // Min length
    if (isValid && rules.minLength && value.length < parseInt(rules.minLength)) {
        isValid = false;
        errorMessage = `Tối thiểu ${rules.minLength} ký tự`;
    }
    
    // Max length
    if (isValid && rules.maxLength && value.length > parseInt(rules.maxLength)) {
        isValid = false;
        errorMessage = `Tối đa ${rules.maxLength} ký tự`;
    }
    
    // Password match (for confirm password fields)
    if (isValid && rules.match) {
        const matchField = document.getElementById(rules.match) || document.querySelector(rules.match);
        if (matchField && value !== matchField.value) {
            isValid = false;
            errorMessage = 'Mật khẩu không khớp';
        }
    }
    
    // Pattern validation
    if (isValid && rules.pattern && value !== '') {
        const regex = new RegExp(rules.pattern);
        if (!regex.test(value)) {
            isValid = false;
            errorMessage = rules.patternMessage || 'Định dạng không hợp lệ';
        }
    }
    
    // Username validation (alphanumeric + underscore)
    if (isValid && field.name === 'username' && value !== '') {
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        if (!usernameRegex.test(value)) {
            isValid = false;
            errorMessage = 'Username chỉ chứa chữ cái, số và dấu gạch dưới';
        }
    }
    
    // Display result
    if (isValid) {
        setFieldValid(field);
    } else {
        setFieldError(field, errorMessage);
    }
    
    return isValid;
}

function setFieldError(field, message) {
    field.classList.remove('is-valid');
    field.classList.add('is-invalid');
    
    // Remove existing feedback
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Add error message
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    field.parentNode.appendChild(feedback);
}

function setFieldValid(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    // Remove error message
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
}

function clearFieldError(field) {
    field.classList.remove('is-invalid', 'is-valid');
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
}

// =========================================================
// Alert Dismiss
// =========================================================
function initAlertDismiss() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    
    alerts.forEach(function(alert) {
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 200);
            });
        }
        
        // Auto dismiss after 5 seconds (optional)
        if (alert.dataset.autoDismiss) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 200);
            }, 5000);
        }
    });
}

// =========================================================
// Dropdowns
// =========================================================
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(function(d) {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        dropdowns.forEach(function(dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
    
    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdowns.forEach(function(dropdown) {
                dropdown.classList.remove('active');
            });
        }
    });
}

// =========================================================
// Modals
// =========================================================
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal-open]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modalOpen;
            openModal(modalId);
        });
    });
    
    // Close modal
    document.querySelectorAll('[data-modal-close]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal-backdrop');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Close on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                closeModal(backdrop);
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-backdrop.active');
            if (activeModal) {
                closeModal(activeModal);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        const firstInput = modal.querySelector('input, textarea, select');
        if (firstInput) {
            setTimeout(function() {
                firstInput.focus();
            }, 100);
        }
    }
}

function closeModal(modal) {
    const modalElement = typeof modal === 'string' ? document.getElementById(modal) : modal;
    if (!modalElement) {
        return;
    }

    modalElement.classList.remove('active');
    document.body.style.overflow = '';
}

// =========================================================
// Avatar Upload Preview
// =========================================================
function initAvatarUpload() {
    const avatarInputs = document.querySelectorAll('input[type="file"][data-avatar-preview]');
    
    avatarInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewId = this.dataset.avatarPreview;
            const preview = document.getElementById(previewId);
            
            if (file && preview) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WEBP)', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('File không được vượt quá 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// =========================================================
// Notifications
// =========================================================
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification-toast');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button type="button" class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Add styles if not exists
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                color: white;
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            }
            .notification-info { background: #3b82f6; }
            .notification-success { background: #22c55e; }
            .notification-warning { background: #f59e0b; }
            .notification-error { background: #ef4444; }
            .notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.25rem;
                cursor: pointer;
                opacity: 0.7;
            }
            .notification-close:hover { opacity: 1; }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.remove();
    });
    
    // Auto remove
    setTimeout(function() {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// =========================================================
// AJAX Helper
// =========================================================
async function fetchApi(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaults, ...options };
    
    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        config.headers['X-CSRF-Token'] = csrfToken.content;
    }
    
    try {
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// =========================================================
// Utility Functions
// =========================================================

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Format date
function formatDate(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Copy to clipboard
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Đã sao chép!', 'success');
    } catch (err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('Đã sao chép!', 'success');
    }
}
