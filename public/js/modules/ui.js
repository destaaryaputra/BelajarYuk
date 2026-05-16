/**
 * Belajaryuk - UI Utilities
 */

export const UI = {
    init() {
        // Handle global navigation clicks
        document.querySelectorAll('[data-page]').forEach(el => {
            el.addEventListener('click', (e) => {
                const pageId = e.currentTarget.getAttribute('data-page');
                window.location.hash = pageId;
            });
        });
    },

    hideSplash() {
        const splash = document.getElementById('splash-screen');
        if (splash) splash.classList.add('fade-out');
        setTimeout(() => splash?.remove(), 500);
    },

    showLoading() {
        document.getElementById('loading')?.classList.remove('d-none');
    },

    hideLoading() {
        document.getElementById('loading')?.classList.add('d-none');
    },

    showNotification(message, type = 'success') {
        const toast = document.getElementById('notification-toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `notification-toast show ${type}`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
};
