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

        UI.showLoading();

        try {
            // 1. Hide all pages
            document.querySelectorAll('.page').forEach(p => {
                p.classList.add('d-none');
                p.classList.remove('active');
            });

            // 2. Load page content if not cached
            if (!this.cache[pageId]) {
                const version = window.BELAJARYUK_ASSET_VERSION || 'dev';
                const response = await fetch(`${this.routes[pageId]}?v=${encodeURIComponent(version)}`);
                if (!response.ok) throw new Error(`Failed to fetch page: ${pageId}`);
                this.cache[pageId] = await response.text();
            }

            // 3. Inject content
            const container = document.getElementById(pageId);
            if (container) {
                container.innerHTML = this.cache[pageId];
                UI.resolveAssetUrls(container);
                container.classList.remove('d-none');
                container.classList.add('active'); // Senior Fix: Add active class for display: block
                
                // Re-initialize icons for new content
                if (window.lucide) window.lucide.createIcons();
                
                // Dispatch event for page-specific JS
                window.dispatchEvent(new CustomEvent('page-loaded', { detail: { pageId } }));
            }

        } catch (error) {
            console.error('Navigation error:', error);
            UI.showNotification('Gagal memuat halaman.', 'error');
        } finally {
            UI.hideLoading();
        }
    }
};
