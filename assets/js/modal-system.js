/**
 * Modal System for Course Withdrawal System
 * Professional confirmation dialogs and alerts
 */

// Modal HTML Template
const modalHTML = `
<div id="globalModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon" id="modalIcon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="modal-title-section">
                <h3 id="modalTitle">Confirm Action</h3>
                <p id="modalSubtitle"></p>
            </div>
        </div>
        <div class="modal-body">
            <p id="modalMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="modalCancel">Cancel</button>
            <button type="button" class="modal-btn modal-btn-confirm" id="modalConfirm">Confirm</button>
        </div>
    </div>
</div>
`;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function() {
    // Insert modal HTML into body
    if (!document.getElementById('globalModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    // Setup logout confirmation
    setupLogoutConfirmation();
    
    // Setup delete confirmations
    setupDeleteConfirmations();
    
    // Setup form submissions with confirmation
    setupFormConfirmations();
    
    // Setup modal close handlers
    setupModalHandlers();
});

/**
 * Show modal with custom configuration
 */
function showModal(config) {
    const modal = document.getElementById('globalModal');
    const modalIcon = document.getElementById('modalIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalConfirm = document.getElementById('modalConfirm');
    const modalCancel = document.getElementById('modalCancel');
    
    // Set icon
    modalIcon.className = 'modal-icon ' + (config.type || 'warning');
    const icons = {
        warning: 'fa-exclamation-triangle',
        danger: 'fa-exclamation-circle',
        success: 'fa-check-circle',
        info: 'fa-info-circle'
    };
    modalIcon.querySelector('i').className = 'fas ' + icons[config.type || 'warning'];
    
    // Set text content
    modalTitle.textContent = config.title || 'Confirm Action';
    modalSubtitle.textContent = config.subtitle || '';
    modalMessage.textContent = config.message || 'Are you sure you want to proceed?';
    
    // Set button text
    modalConfirm.textContent = config.confirmText || 'Confirm';
    modalCancel.textContent = config.cancelText || 'Cancel';
    
    // Set button styles
    modalConfirm.className = 'modal-btn ' + (config.confirmClass || 'modal-btn-confirm');
    
    // Show modal
    modal.classList.add('active');
    
    // Return promise for handling confirmation
    return new Promise((resolve, reject) => {
        modalConfirm.onclick = () => {
            modal.classList.remove('active');
            resolve(true);
        };
        
        modalCancel.onclick = () => {
            modal.classList.remove('active');
            reject(false);
        };
        
        // Close on overlay click
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                reject(false);
            }
        };
        
        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.classList.remove('active');
                document.removeEventListener('keydown', escapeHandler);
                reject(false);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    });
}

/**
 * Setup logout confirmation
 */
function setupLogoutConfirmation() {
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const logoutUrl = this.href;
            
            showModal({
                type: 'warning',
                title: 'Confirm Logout',
                subtitle: 'You will be signed out',
                message: 'Are you sure you want to logout? Any unsaved changes will be lost.',
                confirmText: 'Yes, Logout',
                cancelText: 'Stay Logged In'
            }).then(() => {
                // Show loading state
                const originalText = logoutBtn.innerHTML;
                logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
                logoutBtn.style.pointerEvents = 'none';
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = logoutUrl;
                }, 500);
            }).catch(() => {
                // User cancelled
                console.log('Logout cancelled');
            });
        });
    }
}

/**
 * Setup delete button confirmations
 */
function setupDeleteConfirmations() {
    // Delete buttons in forms
    document.querySelectorAll('button[name="delete_student"], button[name="delete_teacher"], button[name="delete_course"], button[name="delete_section"], button[name="delete_assignment"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const itemType = this.name.replace('delete_', '').replace('_', ' ');
            
            showModal({
                type: 'danger',
                title: 'Delete ' + capitalizeFirst(itemType) + '?',
                subtitle: 'This action cannot be undone',
                message: `Are you sure you want to delete this ${itemType}? This action is permanent and cannot be reversed.`,
                confirmText: 'Yes, Delete',
                cancelText: 'Cancel'
            }).then(() => {
                // Disable button and show loading
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                form.submit();
            }).catch(() => {
                console.log('Deletion cancelled');
            });
        });
    });
}

/**
 * Setup form submission confirmations
 */
function setupFormConfirmations() {
    // Approve button
    document.querySelectorAll('button[name="status"][value="Approved"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            
            showModal({
                type: 'success',
                title: 'Approve Request?',
                subtitle: 'Student will be notified',
                message: 'Are you sure you want to approve this withdrawal request? The student will receive an email notification.',
                confirmText: 'Yes, Approve',
                cancelText: 'Cancel',
                confirmClass: 'modal-btn-primary'
            }).then(() => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
                form.submit();
            }).catch(() => {
                console.log('Approval cancelled');
            });
        });
    });
    
    // Reject button
    document.querySelectorAll('button[name="status"][value="Rejected"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            
            showModal({
                type: 'danger',
                title: 'Reject Request?',
                subtitle: 'Student will be notified',
                message: 'Are you sure you want to reject this withdrawal request? The student will receive an email notification.',
                confirmText: 'Yes, Reject',
                cancelText: 'Cancel'
            }).then(() => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
                form.submit();
            }).catch(() => {
                console.log('Rejection cancelled');
            });
        });
    });
}

/**
 * Setup modal handlers
 */
function setupModalHandlers() {
    const modal = document.getElementById('globalModal');
    if (modal) {
        // Close modal on overlay click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                modal.classList.remove('active');
            }
        });
    }
}

/**
 * Utility function to capitalize first letter
 */
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Show success toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '10000';
    toast.style.minWidth = '300px';
    toast.style.animation = 'slideInRight 0.3s ease';
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Add slide animations to CSS dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);

// Make functions globally available
window.showModal = showModal;
window.showToast = showToast;