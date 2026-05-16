/**
 * Belajaryuk - Profile Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Profile = {
    async load() {
        const container = document.getElementById('profile-content');
        if (!container) return;

        container.innerHTML = '<div class="skeleton-box" style="height: 300px; width: 100%;"></div>';

        try {
            // Get user data from API to ensure it's fresh
            const response = await API.getCurrentUser();
            const user = response.data || {};
            
            // Sync local storage
            localStorage.setItem(Config.STORAGE_KEYS.USER_DATA, JSON.stringify(user));

            this.render(user);
        } catch (error) {
            console.error('Profile load error:', error);
            // Fallback to local storage
            const localData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
            if (localData) {
                this.render(JSON.parse(localData));
            } else {
                container.innerHTML = '<p class="text-danger">Gagal memuat profil.</p>';
            }
        }
    },

    render(user) {
        const container = document.getElementById('profile-content');
        if (!container) return;

        container.innerHTML = `
            <div class="profile-header-main mb-32">
                <div class="profile-avatar-large">
                    ${(user.full_name || user.username || '?')[0].toUpperCase()}
                </div>
                <div class="profile-info-main">
                    <h2>${UI.escapeHtml(user.full_name || user.username)}</h2>
                    <p class="text-muted">@${UI.escapeHtml(user.username)} • Siswa Belajaryuk</p>
                </div>
            </div>

            <div class="profile-details-grid">
                <div class="detail-group">
                    <label>Nama Lengkap</label>
                    <div class="detail-value">${UI.escapeHtml(user.full_name)}</div>
                </div>
                <div class="detail-group">
                    <label>Email</label>
                    <div class="detail-value">${UI.escapeHtml(user.email)}</div>
                </div>
                <div class="detail-group">
                    <label>Tanggal Bergabung</label>
                    <div class="detail-value">${user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}</div>
                </div>
                <div class="detail-group">
                    <label>Role</label>
                    <div class="detail-value badge badge-student">${user.role === 'admin' ? 'Administrator' : 'Siswa'}</div>
                </div>
            </div>

            <div class="mt-32 pt-24 border-top">
                <button type="button" class="btn-outline btn-text-danger" data-action="logout">
                    <i data-lucide="log-out"></i> Keluar dari Akun
                </button>
            </div>
        `;

        if (window.lucide) window.lucide.createIcons();
    }
};
