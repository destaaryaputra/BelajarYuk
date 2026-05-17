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

            // Theme Toggle Listener
            const themeBtn = e.target.closest('[data-action="toggle-theme"]');
            if (themeBtn) {
                this.toggleTheme();
            }

            // Password Toggle Listener
            const passToggleBtn = e.target.closest('.password-toggle-btn');
            if (passToggleBtn) {
                this.togglePasswordVisibility(passToggleBtn);
            }
        });

        // Initialize Theme
        this.applyTheme();

        // Initialize Scroll Reveal Observer
        this.initScrollReveal();

        // Update Header Visibility on Route Change
        this.updateHeaderVisibility();
        window.addEventListener('hashchange', () => this.updateHeaderVisibility());
    },

    applyTheme() {
        const savedTheme = localStorage.getItem('belajaryuk_theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
        this.updateThemeIcons();
    },

    toggleTheme() {
        const isDark = document.body.classList.toggle('dark-theme');
        localStorage.setItem('belajaryuk_theme', isDark ? 'dark' : 'light');
        this.updateThemeIcons();
        
        // Notification for feedback
        this.showNotification(`Mode ${isDark ? 'Gelap' : 'Terang'} diaktifkan`, 'info');
    },

    updateThemeIcons() {
        const isDark = document.body.classList.contains('dark-theme');
        document.querySelectorAll('[data-action="toggle-theme"] i').forEach(icon => {
            if (isDark) {
                icon.setAttribute('data-lucide', 'sun');
            } else {
                icon.setAttribute('data-lucide', 'moon');
            }
        });
        if (window.lucide) window.lucide.createIcons();
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
        
        // Define icons based on type
        const icons = {
            success: 'check-circle',
            error: 'alert-circle',
            warning: 'alert-triangle',
            info: 'info'
        };

        toast.innerHTML = `
            <div class="toast-icon-wrapper">
                <i data-lucide="${icons[type] || 'info'}"></i>
            </div>
            <div class="toast-message-text">${message}</div>
        `;
        
        toast.className = `notification-toast show ${type}`;
        if (window.lucide) window.lucide.createIcons();
        
        // Auto hide
        if (this._toastTimer) clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    },

    /**
     * Modern Alert Dialog
     */
    alert(message, title = 'Informasi') {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-modal');
            const iconBox = document.getElementById('modal-icon');
            const titleEl = document.getElementById('modal-title');
            const msgEl = document.getElementById('modal-message');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');

            if (!modal) return resolve(alert(message));

            iconBox.className = 'modal-icon';
            iconBox.innerHTML = '<i data-lucide="info"></i>';
            titleEl.textContent = title;
            msgEl.textContent = message;
            
            cancelBtn.classList.add('d-none');
            confirmBtn.textContent = 'Tutup';

            modal.classList.remove('d-none');
            if (window.lucide) window.lucide.createIcons();

            confirmBtn.onclick = () => {
                modal.classList.add('d-none');
                resolve(true);
            };
        });
    },

    /**
     * Modern Confirmation Dialog
     */
    confirm(message, title = 'Konfirmasi Tindakan', isDanger = false) {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-modal');
            const iconBox = document.getElementById('modal-icon');
            const titleEl = document.getElementById('modal-title');
            const msgEl = document.getElementById('modal-message');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');

            if (!modal) return resolve(confirm(message));

            iconBox.className = `modal-icon ${isDanger ? 'danger' : ''}`;
            iconBox.innerHTML = `<i data-lucide="${isDanger ? 'trash-2' : 'help-circle'}"></i>`;
            titleEl.textContent = title;
            msgEl.textContent = message;
            
            cancelBtn.classList.remove('d-none');
            confirmBtn.textContent = 'Ya, Lanjutkan';
            confirmBtn.className = isDanger ? 'btn-danger' : 'btn-primary';

            modal.classList.remove('d-none');
            if (window.lucide) window.lucide.createIcons();

            confirmBtn.onclick = () => {
                modal.classList.add('d-none');
                resolve(true);
            };

            cancelBtn.onclick = () => {
                modal.classList.add('d-none');
                resolve(false);
            };
        });
    },

    /**
     * Modern Prompt Dialog
     */
    prompt(message, placeholder = '', title = 'Input Diperlukan') {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-modal');
            const iconBox = document.getElementById('modal-icon');
            const titleEl = document.getElementById('modal-title');
            const msgEl = document.getElementById('modal-message');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');

            if (!modal) return resolve(prompt(message, placeholder));

            iconBox.className = 'modal-icon';
            iconBox.innerHTML = '<i data-lucide="edit-3"></i>';
            titleEl.textContent = title;
            msgEl.innerHTML = `
                <div class="mb-16">${message}</div>
                <input type="text" id="modal-prompt-input" class="form-control" placeholder="${placeholder}" style="width: 100%; margin-top: 10px;">
            `;
            
            cancelBtn.classList.remove('d-none');
            confirmBtn.textContent = 'Kirim';
            confirmBtn.className = 'btn-primary';

            modal.classList.remove('d-none');
            const input = document.getElementById('modal-prompt-input');
            input?.focus();
            if (window.lucide) window.lucide.createIcons();

            confirmBtn.onclick = () => {
                const val = input?.value || '';
                modal.classList.add('d-none');
                resolve(val);
            };

            cancelBtn.onclick = () => {
                modal.classList.add('d-none');
                resolve(null);
            };

            // Support Enter key
            input?.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') confirmBtn.click();
            });
        });
    },

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    },

    getBasePath() {
        return window.location.pathname.replace(/\/(index|api)\.(php|html?)$/i, '').replace(/\/$/, '');
    },

    togglePasswordVisibility(btn) {
        const wrapper = btn.closest('.password-input-wrapper');
        if (!wrapper) return;
        
        const input = wrapper.querySelector('input');
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
            btn.classList.add('is-active');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
            btn.classList.remove('is-active');
        }
        
        if (window.lucide) window.lucide.createIcons();
    }
};
