/**
 * ============================================
 * UTILITY FUNCTIONS
 * ============================================
 * 
 * Reusable utility functions for the application
 * 
 * Functions:
 * - formatCurrency: Format number as currency
 * - formatDate: Format date string
 * - showNotification: Show toast notification
 * - confirmDelete: Confirm before delete action
 * - debounce: Debounce function calls
 * - throttle: Throttle function calls
 */

/**
 * Format Number as Currency (IDR)
 * @param {number} amount - Amount to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

/**
 * Format Date String
 * @param {string|Date} date - Date to format
 * @param {string} format - Format type ('short', 'long', 'time')
 * @returns {string} Formatted date string
 */
function formatDate(date, format = 'short') {
    const d = new Date(date);
    
    if (isNaN(d.getTime())) {
        return 'Invalid Date';
    }
    
    const options = {
        short: { year: 'numeric', month: '2-digit', day: '2-digit' },
        long: { year: 'numeric', month: 'long', day: 'numeric' },
        time: { hour: '2-digit', minute: '2-digit', second: '2-digit' }
    };
    
    return new Intl.DateTimeFormat('id-ID', options[format] || options.short).format(d);
}

/**
 * Show Toast Notification
 * @param {string} message - Message to display
 * @param {string} type - Notification type ('success', 'error', 'warning', 'info')
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Remove existing notifications
    const existing = document.querySelector('.toast-notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification element
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${getToastIcon(type)}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close">&times;</button>
    `;
    
    // Add to body
    document.body.appendChild(toast);
    
    // Show with animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Close button handler
    toast.querySelector('.toast-close').addEventListener('click', () => {
        closeToast(toast);
    });
    
    // Auto dismiss
    setTimeout(() => {
        closeToast(toast);
    }, duration);
}

/**
 * Get Toast Icon by Type
 * @param {string} type - Notification type
 * @returns {string} Icon emoji
 */
function getToastIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

/**
 * Close Toast Notification
 * @param {HTMLElement} toast - Toast element to close
 */
function closeToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
}

/**
 * Confirm Delete Action
 * @param {string} message - Confirmation message
 * @param {Function} onConfirm - Callback when confirmed
 * @param {Function} onCancel - Callback when cancelled
 */
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?', onConfirm, onCancel) {
    const modal = createConfirmModal(message);
    document.body.appendChild(modal);
    
    // Show modal
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Confirm button
    modal.querySelector('.confirm-yes').addEventListener('click', () => {
        closeModal(modal);
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    // Cancel button
    modal.querySelector('.confirm-no').addEventListener('click', () => {
        closeModal(modal);
        if (typeof onCancel === 'function') {
            onCancel();
        }
    });
    
    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modal);
            if (typeof onCancel === 'function') {
                onCancel();
            }
        }
    });
}

/**
 * Create Confirm Modal Element
 * @param {string} message - Confirmation message
 * @returns {HTMLElement} Modal element
 */
function createConfirmModal(message) {
    const modal = document.createElement('div');
    modal.className = 'confirm-modal';
    modal.innerHTML = `
        <div class="confirm-content">
            <div class="confirm-icon">⚠️</div>
            <h3 class="confirm-title">Konfirmasi</h3>
            <p class="confirm-message">${message}</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary confirm-no">Batal</button>
                <button class="btn btn-error confirm-yes">Hapus</button>
            </div>
        </div>
    `;
    return modal;
}

/**
 * Close Modal
 * @param {HTMLElement} modal - Modal element to close
 */
function closeModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 300);
}

/**
 * Debounce Function
 * Delays function execution until after wait time has elapsed
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 300) {
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

/**
 * Throttle Function
 * Limits function execution to once per specified time period
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} Throttled function
 */
function throttle(func, limit = 300) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Copy to Clipboard
 * @param {string} text - Text to copy
 * @returns {Promise<void>}
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Berhasil disalin ke clipboard', 'success');
    } catch (err) {
        showNotification('Gagal menyalin ke clipboard', 'error');
    }
}

/**
 * Validate Form
 * @param {HTMLFormElement} form - Form element to validate
 * @returns {boolean} True if valid
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Serialize Form Data
 * @param {HTMLFormElement} form - Form element
 * @returns {Object} Form data as object
 */
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

// Export functions to window object for global access
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showNotification = showNotification;
window.confirmDelete = confirmDelete;
window.debounce = debounce;
window.throttle = throttle;
window.copyToClipboard = copyToClipboard;
window.validateForm = validateForm;
window.serializeForm = serializeForm;
