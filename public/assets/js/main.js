// public/assets/js/main.js

/**
 * Hiển thị Toast Notification
 * @param {string} message 
 * @param {string} type ('success', 'danger', 'warning', 'info')
 */
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container');
    
    // Icon mapping
    const icons = {
        'success': '<i class="bi bi-check-circle-fill"></i>',
        'danger': '<i class="bi bi-exclamation-triangle-fill"></i>',
        'warning': '<i class="bi bi-exclamation-circle-fill"></i>',
        'info': '<i class="bi bi-info-circle-fill"></i>'
    };

    const toastHtml = `
        <div class="toast align-items-center text-bg-${type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${icons[type] || ''} ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Tự động xóa sau 3s
    const toastElement = toastContainer.lastElementChild;
    setTimeout(() => {
        toastElement.classList.remove('show');
        setTimeout(() => toastElement.remove(), 300);
    }, 3000);
}
