/**
 * Belajaryuk - UI Utilities
 */

export const UI = {
    init() {
        // Global Event Delegation for Navigation
        document.addEventListener('click', (e) => {
            const navBtn = e.target.closest('[data-page]');
            if (navBtn) {
                const pageId = navBtn.getAttribute('data-page');
                window.location.hash = pageId;
            }
        });

        // Initialize Scroll Reveal Observer
        this.initScrollReveal();

        // Update Header Visibility on Route Change
        this.updateHeaderVisibility();
        window.addEventListener('hashchange', () => this.updateHeaderVisibility());
    },

    updateHeaderVisibility() {
        const globalNav = document.getElementById('global-nav');
        if (!globalNav) return;

        const hash = window.location.hash.replace('#', '') || 'landing-page';
        const isPublicPage = ['landing-page', 'login-page', 'register-page'].includes(hash);
        const isAdminPage = hash === 'admin-page';

        // Cek apakah user sudah login dari localStorage
        const isLoggedIn = !!localStorage.getItem('belajaryuk_auth_token');

        // Navigasi siswa hanya muncul jika sudah login, bukan di halaman publik, dan bukan di panel admin
        if (isLoggedIn && !isPublicPage && !isAdminPage) {
            globalNav.classList.remove('d-none');
            document.body.classList.remove('admin-mode');

            // Update active state in student navigation
            const navButtons = globalNav.querySelectorAll('.nav-menu-main button');
            navButtons.forEach(btn => {
                const btnPage = btn.getAttribute('data-page');
                if (btnPage === hash) {
                    btn.classList.add('nav-active');
                } else {
                    btn.classList.remove('nav-active');
                }
            });
        } else {
            globalNav.classList.add('d-none');

            // Tambahkan admin-mode class ke body jika di halaman admin
            if (isAdminPage) {
                document.body.classList.add('admin-mode');
            } else {
                document.body.classList.remove('admin-mode');
            }
        }
    },
    initScrollReveal() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    // Once visible, no need to observe anymore
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Listen for page-loaded to re-observe new elements
        window.addEventListener('page-loaded', () => {
            document.querySelectorAll('.reveal-on-scroll').forEach(el => {
                observer.observe(el);
            });
        });
    },

    hideSplash() {
        const splash = document.getElementById('splash-screen');
        if (splash) splash.classList.add('hidden');
        setTimeout(() => splash?.remove(), 800);
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
    },

    getBasePath() {
        return window.location.pathname.replace(/\/(index|api)\.(php|html?)$/i, '').replace(/\/$/, '');
    }
};
