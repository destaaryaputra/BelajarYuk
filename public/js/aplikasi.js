/**
 * Belajaryuk - Core Application & Router
 * Standard: Senior Frontend Engineer (Modular & Event-driven)
 */

window.App = window.App || {};

App.Router = {
    privatePages: ['dashboard-page', 'materials-page', 'material-detail-page', 'progress-page', 'profile-page'],

    showPage(pageId) {
        const page = document.getElementById(pageId);
        if (!page) return;

        // Toggle Global Navigation
        const globalNav = document.getElementById('global-nav');
        if (globalNav) {
            if (this.privatePages.includes(pageId)) {
                globalNav.classList.remove('d-none');
                App.UI.setupRoleAccess();
            } else {
                globalNav.classList.add('d-none');
            }
        }

        // Stop media playback
        if (typeof stopAllPlayback === 'function') stopAllPlayback();

        // If page is already active and populated, just sync nav
        if (page.classList.contains('active') && page.innerHTML.trim() !== '') {
            this.syncNavActiveState(pageId);
            window.scrollTo(0, 0);
            return;
        }

        // Switch active class
        document.querySelectorAll('.page').forEach(p => {
            p.classList.remove('active');
            p.style.display = 'none';
        });

        page.style.display = 'block';
        page.classList.add('active');
        
        // Custom event for page changes
        window.dispatchEvent(new CustomEvent('page:changed', { detail: { pageId } }));
        
        this.syncNavActiveState(pageId);

        // Load page data
        this.loadPageData(pageId);
        
        // Refresh icons and scroll
        setTimeout(() => {
            App.UI.renderIcons();
            window.scrollTo(0, 0);
        }, 100);
    },

    syncNavActiveState(pageId) {
        document.querySelectorAll('[data-page]').forEach(el => {
            if (el.getAttribute('data-page') === pageId) {
                el.classList.add('nav-active');
            } else {
                el.classList.remove('nav-active');
            }
        });
    },

    loadPageData(pageId) {
        try {
            switch (pageId) {
                case 'dashboard-page': if (window.loadDashboard) window.loadDashboard(); break;
                case 'materials-page': if (window.loadMaterials) window.loadMaterials(); break;
                case 'progress-page': if (window.loadProgress) window.loadProgress(); break;
                case 'profile-page': if (window.loadProfile) window.loadProfile(); break;
                case 'admin-page': if (window.loadAdminPanel) window.loadAdminPanel(); break;
            }
        } catch (error) {
            console.error(`Error loading page ${pageId}:`, error);
        }
    }
};

App.UI = {
    renderIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    animateNumber(elementId, start, end, duration, suffix = '') {
        const obj = document.getElementById(elementId);
        if (!obj) return;

        const from = Number(start) || 0;
        const to = Number(end) || 0;
        const runFor = Math.max(Number(duration) || 0, 0);

        if (runFor === 0 || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            obj.innerHTML = `${to}${suffix ? ` ${suffix}` : ''}`;
            return;
        }

        const startedAt = performance.now();
        const easeOut = progress => 1 - Math.pow(1 - progress, 3);

        function tick(now) {
            const progress = Math.min((now - startedAt) / runFor, 1);
            const current = Math.round(from + (to - from) * easeOut(progress));
            obj.innerHTML = `${current}${suffix ? ` ${suffix}` : ''}`;
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    },

    setupRoleAccess() {
        const userDataStr = localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);
        if (userDataStr) {
            const user = JSON.parse(userDataStr);
            document.body.classList.toggle('admin-mode', user.role === 'admin');
        }
    },

    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
    },

    toggleTheme() {
        document.body.classList.toggle('dark-theme');
        const isDark = document.body.classList.contains('dark-theme');
        localStorage.setItem(App.Config.STORAGE_KEYS.THEME, isDark ? 'dark' : 'light');
        
        if (typeof refreshAdminDashboardCharts === 'function') {
            setTimeout(refreshAdminDashboardCharts, 50);
        }
    },

    initTheme() {
        const savedTheme = localStorage.getItem(App.Config.STORAGE_KEYS.THEME);
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-theme');
        }
    },

    bindGlobalEvents() {
        // Event delegation for navigation
        document.addEventListener('click', e => {
            const navBtn = e.target.closest('[data-page]');
            if (navBtn) {
                const pageId = navBtn.getAttribute('data-page');
                App.Router.showPage(pageId);
            }

            const actionBtn = e.target.closest('[data-action]');
            if (actionBtn) {
                const action = actionBtn.getAttribute('data-action');
                if (typeof this.actions[action] === 'function') {
                    this.actions[action](e, actionBtn);
                }
            }
        });
    },

    actions: {
        'toggle-theme': () => App.UI.toggleTheme(),
        'logout': () => window.handleLogout && window.handleLogout()
    }
};

/* --- Global Polyfills for Backward Compatibility --- */
// Senior Fix: Provide global access to new namespaced modules to prevent ReferenceErrors in legacy files
window.CONFIG = App.Config;
window.API = App.Service.API;
window.logger = console; // Fallback for old logger
window.showLoading = (show) => App.Utils.showLoading(show);
window.showNotification = (msg, type, dur) => App.Utils.showNotification(msg, type, dur);
window.handleAPIError = (err) => { console.error('API Error:', err); App.Utils.showNotification(err.message || 'Terjadi kesalahan', 'error'); };
window.escapeHtml = (str) => App.Utils.escapeHtml(str);
window.animateNumber = (...args) => App.UI.animateNumber(...args);
window.renderIcons = () => App.UI.renderIcons();
window.showPage = (id) => App.Router.showPage(id);

/* --- Inisialisasi Aplikasi --- */
document.addEventListener('DOMContentLoaded', function() {
    App.UI.renderIcons();
    App.UI.initTheme();
    App.UI.initScrollAnimations();
    App.UI.bindGlobalEvents();

    const splashScreen = document.getElementById('splash-screen');

    const initAppState = async () => {
        try {
            // Check auth status
            const userData = localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);
            const token = localStorage.getItem(App.Config.STORAGE_KEYS.AUTH_TOKEN);

            if (token && userData) {
                App.UI.setupRoleAccess();
                const user = JSON.parse(userData);
                App.Router.showPage(user.role === 'admin' ? 'admin-page' : 'dashboard-page');
            } else {
                App.Router.showPage('landing-page');
            }
        } catch (error) {
            console.error('Initialization failed', error);
            App.Router.showPage('login-page');
        } finally {
            App.Utils.showLoading(false);
        }
    };

    setTimeout(() => {
        if (splashScreen) splashScreen.classList.add('hidden');
        initAppState();
    }, 2800);
});
