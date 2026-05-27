/**
 * Belajaryuk - Frontend Router
 */

import { UI } from './ui.js';

export const Router = {
    get routes() {
        const base = UI.getBasePath();
        return {
            'landing-page': `${base}/pages/landing.html`,
            'login-page': `${base}/pages/login.html`,
            'register-page': `${base}/pages/register.html`,
            'dashboard-page': `${base}/pages/dashboard.html`,
            'materials-page': `${base}/pages/materials.html`,
            'material-detail-page': `${base}/pages/material-detail.html`,
            'quiz-page': `${base}/pages/quiz.html`,
            'progress-page': `${base}/pages/progress.html`,
            'leaderboard-page': `${base}/pages/leaderboard.html`,
            'profile-page': `${base}/pages/profile.html`,
            'admin-page': `${base}/pages/admin.html`
        };
    },

    cache: {},
    pendingFetches: {},
    pendingPageId: null,

    init() {
        window.addEventListener('hashchange', () => this.handleRoute());
    },

    async handleInitialRoute() {
        const hash = window.location.hash.replace('#', '') || 'landing-page';
        await this.navigateTo(hash);
    },

    async handleRoute() {
        const hash = window.location.hash.replace('#', '') || 'landing-page';
        await this.navigateTo(hash);
    },

    async navigateTo(pageId) {
        if (!this.routes[pageId]) {
            console.error(`Route not found: ${pageId}`);
            return;
        }

        this.pendingPageId = pageId;
        const shouldDelayLoading = !this.cache[pageId] && !UI.isSplashVisible();
        let loadingTimer = null;
        let didShowLoading = false;

        if (shouldDelayLoading) {
            loadingTimer = setTimeout(() => {
                didShowLoading = true;
                UI.showLoading();
            }, 160);
        }

        try {
            const html = await this.fetchPage(pageId);

            if (this.pendingPageId !== pageId) {
                return;
            }

            document.querySelectorAll('.page').forEach(p => {
                p.classList.add('d-none');
                p.classList.remove('active');
            });

            const container = document.getElementById(pageId);
            if (container) {
                container.innerHTML = html;
                UI.resolveAssetUrls(container);
                container.classList.remove('d-none');
                container.classList.add('active');
                
                // Re-initialize icons for new content
                if (window.lucide) window.lucide.createIcons();
                
                // Dispatch event for page-specific JS
                window.dispatchEvent(new CustomEvent('page-loaded', { detail: { pageId } }));
            }

        } catch (error) {
            console.error('Navigation error:', error);
            UI.showNotification('Gagal memuat halaman.', 'error');
        } finally {
            if (loadingTimer) clearTimeout(loadingTimer);
            if (didShowLoading) UI.hideLoading();
        }
    },

    fetchPage(pageId) {
        if (this.cache[pageId]) {
            return Promise.resolve(this.cache[pageId]);
        }

        if (this.pendingFetches[pageId]) {
            return this.pendingFetches[pageId];
        }

        const version = window.BELAJARYUK_ASSET_VERSION || 'dev';
        const route = `${this.routes[pageId]}?v=${encodeURIComponent(version)}`;

        this.pendingFetches[pageId] = fetch(route)
            .then(response => {
                if (!response.ok) throw new Error(`Failed to fetch page: ${pageId}`);
                return response.text();
            })
            .then(html => {
                this.cache[pageId] = html;
                return html;
            })
            .finally(() => {
                delete this.pendingFetches[pageId];
            });

        return this.pendingFetches[pageId];
    },

    preloadRoutes(pageIds = []) {
        const preload = () => {
            pageIds.forEach(pageId => {
                if (this.routes[pageId] && !this.cache[pageId]) {
                    this.fetchPage(pageId).catch(() => null);
                }
            });
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(preload, { timeout: 2000 });
        } else {
            setTimeout(preload, 500);
        }
    }
};
