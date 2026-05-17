/**
 * Belajaryuk - Dashboard Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Dashboard = {
    rafHandles: {},

    async load() {
        const contentContainer = document.getElementById('dynamic-dashboard-content');
        if (!contentContainer) return;

        // Get user session
        const userData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        let user = { full_name: 'Siswa', username: 'siswa', id: 0 };
        if (userData) user = JSON.parse(userData);

        // 1. RENDER SHELL with SKELETONS
        contentContainer.innerHTML = `
            <div class="dashboard-header mb-32">
                <div>
                    <p class="section-eyebrow">Ringkasan Belajar</p>
                    <h1>Halo, ${UI.escapeHtml(user.full_name || user.username || 'Siswa')}!</h1>
                </div>
                <div id="dashboard-cta-container">
                    <div class="skeleton-box" style="height: 48px; width: 200px; border-radius: 12px;"></div>
                </div>
            </div>

            <div class="stats-grid mb-32">
                ${Array(3).fill('<div class="skeleton-box" style="height: 140px; border-radius: 20px;"></div>').join('')}
            </div>

            <div id="dashboard-recent-materials-container">
                <div class="skeleton-box mb-24" style="height: 300px; border-radius: 24px;"></div>
                <div class="dashboard-material-grid">
                    ${Array(3).fill('<div class="skeleton-box" style="height: 240px; border-radius: 16px;"></div>').join('')}
                </div>
            </div>
        `;

        try {
            const response = await API.getDashboardData();
            const data = response.data || {};
            
            // 2. RENDER ACTUAL CONTENT
            this.renderActualDashboard(data, user);
            
            if (window.lucide) window.lucide.createIcons();

        } catch (error) {
            console.error('Dashboard load error:', error);
            contentContainer.innerHTML = '<p class="text-danger p-24">Gagal memuat data Beranda. Silakan refresh halaman.</p>';
        }
    },

    renderActualDashboard(data, user) {
        const contentContainer = document.getElementById('dynamic-dashboard-content');
        if (!contentContainer) return;

        const summary = data.summary || {};
        const leaderboardData = data.leaderboard || [];
        const userIndex = leaderboardData.findIndex(u => u.id == user.id);
        const userRank = userIndex !== -1 ? userIndex + 1 : '--';
        const userPoints = userIndex !== -1 ? leaderboardData[userIndex].total_points : (summary.total_points || 0);

        contentContainer.innerHTML = `
            <div class="dashboard-header mb-32">
                <div>
                    <p class="section-eyebrow">Ringkasan Belajar</p>
                    <h1>Halo, ${UI.escapeHtml(user.full_name || user.username || 'Siswa')}!</h1>
                </div>
                <button type="button" class="btn-p" id="btn-continue-learning">
                    <i data-lucide="play-circle"></i> Lanjutkan Belajar
                </button>
            </div>

            <div class="stats-grid mb-32">
                <div class="stat-card accent-blue">
                    <div class="stat-card-row">
                        <div>
                            <h3>Pencapaian Modul</h3>
                            <div class="value"><span id="dash-materials-completed">0</span><span class="value-suffix"> dari ${summary.total || 0}</span></div>
                        </div>
                        <div class="stat-chip"><i data-lucide="book-check"></i></div>
                    </div>
                </div>
                <div class="stat-card accent-orange">
                    <div class="stat-card-row">
                        <div>
                            <h3>Peringkat Global</h3>
                            <div class="value">
                                <span>${userRank !== '--' ? '#' + userRank : '--'}</span>
                                <span class="value-suffix"> (${userPoints} Poin)</span>
                            </div>
                        </div>
                        <div class="stat-chip"><i data-lucide="trophy"></i></div>
                    </div>
                </div>
                <div class="stat-card accent-green">
                    <div class="stat-card-row">
                        <div>
                            <h3>Semangat Belajar</h3>
                            <div class="value"><span id="dash-learning-streak">0</span><span class="value-suffix"> Hari</span></div>
                        </div>
                        <div class="stat-chip"><i data-lucide="flame"></i></div>
                    </div>
                </div>
            </div>

            <div id="dashboard-recent-materials-container"></div>
        `;

        this.animateNumber('dash-materials-completed', 0, summary.completed || 0, 1000);
        this.animateNumber('dash-learning-streak', 0, summary.streak || 0, 1000);

        const continueBtn = document.getElementById('btn-continue-learning');
        if (continueBtn && summary.last_material) {
            continueBtn.innerHTML = `<i data-lucide="play-circle"></i> Lanjut: ${UI.escapeHtml(summary.last_material.title)}`;
            continueBtn.onclick = () => {
                localStorage.setItem('pending_material_id', summary.last_material.id);
                window.location.hash = 'materials-page';
            };
        } else if (continueBtn) {
            continueBtn.onclick = () => window.location.hash = 'materials-page';
        }

        this.renderRecentMaterials(data.recent_materials || []);
    },

    animateNumber(id, start, end, duration) {
        const obj = document.getElementById(id);
        if (!obj) return;

        if (this.rafHandles[id]) {
            window.cancelAnimationFrame(this.rafHandles[id]);
        }
        
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                this.rafHandles[id] = window.requestAnimationFrame(step);
            } else {
                delete this.rafHandles[id];
            }
        };
        this.rafHandles[id] = window.requestAnimationFrame(step);
    },

    renderRecentMaterials(materials) {
        const container = document.getElementById('dashboard-recent-materials-container');
        if (!container) return;
        
        if (materials.length === 0) {
            container.innerHTML = `
                <div class="empty-state bg-white">
                    <div class="css-art-empty-box"></div>
                    <p>Belum ada materi baru nih. Pantau terus ya!</p>
                </div>`;
            return;
        }

        const featured = materials[0];
        const others = materials.slice(1);
        const defaultThumb = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80';
        const getThumb = (thumb) => thumb ? (thumb.startsWith('http') || thumb.startsWith('/') ? thumb : '/public/uploads/thumbnails/' + thumb) : defaultThumb;
        
        const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };
        
        let html = `
            <div class="featured-course-card mb-32 clickable-card" data-material-id="${featured.id}">
                <div class="featured-course-copy">
                    <span class="category-tag">Disarankan • ${UI.escapeHtml(featured.category || 'Umum')} • ${diffMap[featured.difficulty] || 'Pemula'}${featured.duration_minutes ? ` • ${featured.duration_minutes} Menit` : ''}</span>
                    <h2>${UI.escapeHtml(featured.title)}</h2>
                    <p>${UI.escapeHtml(featured.description ? featured.description.substring(0, 150) + '...' : 'Tingkatkan skill kamu sekarang.')}</p>
                    <div><span class="featured-cta">Mulai Modul Ini</span></div>
                </div>
                <div class="featured-course-media">
                    <img src="${getThumb(featured.thumbnail)}" alt="${UI.escapeHtml(featured.title)}">
                </div>
            </div>
        `;

        if (others.length > 0) {
            html += `<h2 class="section-title-compact mb-16">Materi Menarik Lainnya</h2>`;
            html += `<div class="dashboard-material-grid">`;
            others.forEach(m => {
                html += `
                    <div class="material-card clickable-card" data-material-id="${m.id}">
                        <div class="img-wrapper">
                            <img src="${getThumb(m.thumbnail)}" alt="${UI.escapeHtml(m.title)}">
                        </div>
                        <div class="material-card-content">
                            <span class="category-tag">${UI.escapeHtml(m.category || 'Umum')} • ${diffMap[m.difficulty] || 'Pemula'}</span>
                            <h3>${UI.escapeHtml(m.title)}</h3>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }
        
        container.innerHTML = html;
        
        // Bind click events
        container.querySelectorAll('[data-material-id]').forEach(el => {
            el.addEventListener('click', () => {
                const id = el.getAttribute('data-material-id');
                localStorage.setItem('pending_material_id', id);
                window.location.hash = 'materials-page';
            });
        });
    }
};
