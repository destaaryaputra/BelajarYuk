/**
 * Belajaryuk - UI Utilities
 */

export const UI = {
    _scriptPromises: {},
    _stylePromises: {},

    init() {
        // Global Event Delegation for Navigation
        document.addEventListener('click', (e) => {
            const navBtn = e.target.closest('[data-page]');
            if (navBtn) {
                const pageId = navBtn.getAttribute('data-page');
                window.location.hash = pageId;
                
                // Close sidebar if a link is clicked
                this.closeSidebar();
            }

            // Theme Toggle Listener
            const themeBtn = e.target.closest('[data-action="toggle-theme"]');
            if (themeBtn) {
                this.toggleTheme();
            }

            // Sidebar Toggle Listener
            const sidebarBtn = e.target.closest('[data-action="toggle-sidebar"]');
            const overlay = e.target.closest('#sidebar-overlay');
            if (sidebarBtn || overlay) {
                this.toggleSidebar();
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
        window.addEventListener('page-loaded', () => this.updateHeaderVisibility());
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
        document.querySelectorAll('[data-action="toggle-theme"] [data-lucide]').forEach(icon => {
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
            const navButtons = globalNav.querySelectorAll('.nav-menu-main button, .nav-menu-vertical button');
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
            this.closeSidebar(); // Ensure sidebar is closed when nav is hidden

            // Tambahkan admin-mode class ke body jika di halaman admin
            if (isAdminPage) {
                document.body.classList.add('admin-mode');
            } else {
                document.body.classList.remove('admin-mode');
            }
        }
    },

    toggleSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar || !overlay) return;

        const isActive = sidebar.classList.toggle('is-active');
        overlay.classList.toggle('is-active', isActive);
        
        // Lock both html and body to be safe on mobile
        document.documentElement.classList.toggle('sidebar-open', isActive);
        document.body.classList.toggle('sidebar-open', isActive);
    },

    closeSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar || !overlay) return;

        sidebar.classList.remove('is-active');
        overlay.classList.remove('is-active');
        
        document.documentElement.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-open');
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
        if (!splash) return;
        const fadeDelay = 180;
        const removeDelay = 980;
        
        // 1. Trigger the cinematic Zoom & Blur animation
        splash.classList.add('zoom-blur');

        // 2. Fade out the entire overlay slightly after the zoom starts
        setTimeout(() => {
            splash.classList.add('hidden');
        }, fadeDelay);

        // 3. Completely remove from DOM after all animations finish
        setTimeout(() => {
            splash.remove();
        }, removeDelay);
 
    },

    isSplashVisible() {
        const splash = document.getElementById('splash-screen');
        return splash && !splash.classList.contains('hidden');
    },

    showLoading(message = 'Mohon tunggu...') {
        // Guard: Don't show loading if splash screen is still active to prevent "collision"
        if (this.isSplashVisible()) return;
        
        const loadingEl = document.getElementById('loading');
        const loadingTextEl = document.getElementById('loading-text');
        
        if (loadingEl) {
            if (loadingTextEl) loadingTextEl.textContent = message;
            loadingEl.classList.remove('d-none');
        }
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

    getAssetPath(path) {
        const cleanPath = String(path || '').replace(/^\/+/, '');
        const base = this.getBasePath();
        return `${base}/${cleanPath}`;
    },

    getThumbnailUrl(thumbnail) {
        const value = String(thumbnail || '').trim();
        if (!value) return this.getAssetPath('assets/course-placeholder.svg');
        if (/^(https?:)?\/\//i.test(value) || value.startsWith('data:') || value.startsWith('blob:')) {
            return this.optimizeImageUrl(value, 720);
        }
        if (value.startsWith('/')) return value;
        return this.getAssetPath(`uploads/thumbnails/${value}`);
    },

    getImageFallbackAttribute() {
        return `onerror="this.onerror=null;this.src='${this.getAssetPath('assets/course-placeholder.svg')}'"`;
    },

    optimizeImageUrl(src, width = 720) {
        try {
            const url = new URL(src, window.location.origin);
            if (url.hostname.includes('images.unsplash.com')) {
                url.searchParams.set('auto', 'format');
                url.searchParams.set('fit', 'crop');
                url.searchParams.set('q', '65');
                url.searchParams.set('w', String(width));
                return url.toString();
            }
        } catch (error) {
            return src;
        }
        return src;
    },

    resolveAssetUrls(root = document) {
        root.querySelectorAll('[data-asset-src]').forEach(el => {
            const assetPath = el.getAttribute('data-asset-src');
            if (assetPath) el.setAttribute('src', this.getAssetPath(assetPath));
        });

        root.querySelectorAll('[data-asset-href]').forEach(el => {
            const assetPath = el.getAttribute('data-asset-href');
            if (assetPath) el.setAttribute('href', this.getAssetPath(assetPath));
        });
    },

    loadScript(src, globalName = null) {
        if (globalName && window[globalName]) {
            return Promise.resolve(window[globalName]);
        }

        if (this._scriptPromises[src]) {
            return this._scriptPromises[src];
        }

        this._scriptPromises[src] = new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(globalName ? window[globalName] : true), { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve(globalName ? window[globalName] : true);
            script.onerror = () => reject(new Error(`Gagal memuat script: ${src}`));
            document.head.appendChild(script);
        });

        return this._scriptPromises[src];
    },

    loadStyle(href) {
        if (document.querySelector(`link[href="${href}"]`)) {
            return Promise.resolve(true);
        }

        if (this._stylePromises[href]) {
            return this._stylePromises[href];
        }

        this._stylePromises[href] = new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = () => resolve(true);
            link.onerror = () => reject(new Error(`Gagal memuat stylesheet: ${href}`));
            document.head.appendChild(link);
        });

        return this._stylePromises[href];
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
