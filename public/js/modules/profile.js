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

        const initials = (user.full_name || user.username || '?')[0].toUpperCase();
        const avatarUrl = user.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/public/uploads/${user.avatar}`) : null;
        
        container.innerHTML = `
            <div class="profile-header-main mb-32">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar-large" id="profile-avatar-container">
                        ${avatarUrl ? `<img src="${UI.escapeHtml(avatarUrl)}" alt="Avatar" id="profile-avatar-img" loading="lazy" decoding="async">` : initials}
                    </div>
                    <button type="button" class="btn-change-avatar" id="btn-change-avatar" title="Ubah Foto Profil">
                        <i data-lucide="camera"></i>
                    </button>
                    <input type="file" id="input-avatar" class="d-none" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="profile-info-main">
                    <h2>${UI.escapeHtml(user.full_name || user.username)}</h2>
                    <p class="text-muted">${UI.escapeHtml(user.username)}</p>
                </div>
            </div>

            <!-- Creative Initiative: Stats Grid in Profile -->
            <div class="stats-grid mb-32">
                <div class="stat-card accent-orange">
                    <div class="stat-card-row">
                        <div>
                            <h3>Semangat Belajar</h3>
                            <div class="value">${user.streak_count || 0} <span class="value-suffix">Hari</span></div>
                        </div>
                        <div class="stat-chip"><i data-lucide="zap"></i></div>
                    </div>
                </div>
                <div class="stat-card accent-blue">
                    <div class="stat-card-row">
                        <div>
                            <h3>Akumulasi Poin</h3>
                            <div class="value">${user.total_points || 0}</div>
                        </div>
                        <div class="stat-chip"><i data-lucide="award"></i></div>
                    </div>
                </div>
            </div>

            <div id="profile-display-view">
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
                        <label>Username</label>
                        <div class="detail-value">${UI.escapeHtml(user.username)}</div>
                    </div>
                    <div class="detail-group">
                        <label>Tanggal Bergabung</label>
                        <div class="detail-value">${user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}</div>
                    </div>
                </div>

                <div class="mt-32 pt-24 border-top">
                    <button type="button" class="btn-primary" id="btn-edit-profile">
                        <i data-lucide="edit"></i> Edit Profil
                    </button>
                </div>
            </div>

            <div id="profile-edit-view" class="d-none">
                <form id="form-edit-profile" class="modern-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="full_name" value="${UI.escapeHtml(user.full_name)}" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="${UI.escapeHtml(user.email)}" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="${UI.escapeHtml(user.username)}" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (Kosongkan jika tidak ganti)</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password" placeholder="••••••••">
                                <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions mt-24">
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        <button type="button" class="btn-ghost" id="btn-cancel-edit">Batal</button>
                    </div>
                </form>
            </div>
        `;

        if (window.lucide) window.lucide.createIcons();
        this.setupListeners();
    },

    setupListeners() {
        const editBtn = document.getElementById('btn-edit-profile');
        const cancelBtn = document.getElementById('btn-cancel-edit');
        const form = document.getElementById('form-edit-profile');
        const btnChangeAvatar = document.getElementById('btn-change-avatar');
        const inputAvatar = document.getElementById('input-avatar');
        
        const displayView = document.getElementById('profile-display-view');
        const editView = document.getElementById('profile-edit-view');

        if (btnChangeAvatar && inputAvatar) {
            btnChangeAvatar.onclick = () => inputAvatar.click();
            
            inputAvatar.onchange = async () => {
                if (!inputAvatar.files || !inputAvatar.files[0]) return;
                
                const file = inputAvatar.files[0];
                const formData = new FormData();
                formData.append('avatar', file);

                UI.showLoading();
                try {
                    const response = await API.post('/auth/avatar', formData);
                    UI.showNotification(response.message, 'success');
                    this.load(); // Refresh to show new avatar
                } catch (error) {
                    UI.showNotification(error.message || 'Gagal mengunggah foto.', 'error');
                } finally {
                    UI.hideLoading();
                }
            };
        }

        if (editBtn) {
            editBtn.onclick = () => {
                displayView.classList.add('d-none');
                editView.classList.remove('d-none');
            };
        }

        if (cancelBtn) {
            cancelBtn.onclick = () => {
                editView.classList.add('d-none');
                displayView.classList.remove('d-none');
            };
        }

        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                UI.showLoading();
                try {
                    const response = await API.post('/auth/profile/update', data);
                    UI.showNotification(response.message, 'success');
                    this.load();
                } catch (error) {
                    UI.showNotification(error.message || 'Gagal memperbarui profil.', 'error');
                } finally {
                    UI.hideLoading();
                }
            };
        }
    }
};
