/**
 * Belajaryuk - Dashboard Module
 * Standard: Senior Frontend Engineer (Namespaced & Data-driven)
 */

window.App = window.App || {};

App.Dashboard = {
    async load() {
        const dashboardPage = document.getElementById('dashboard-page');
        if (!dashboardPage) return;

        let contentContainer = document.getElementById('dynamic-dashboard-content');
        if (!contentContainer) {
            contentContainer = document.createElement('div');
            contentContainer.id = 'dynamic-dashboard-content';
            dashboardPage.appendChild(contentContainer);
        }

        // Get user session
        const userDataStr = localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);
        let user = { full_name: 'Siswa', username: 'siswa', id: 0 };
        if (userDataStr) user = JSON.parse(userDataStr);

        // 1. RENDER SHELL (Header & Placeholder Stats)
        contentContainer.innerHTML = `
            <div class="dashboard-header mb-32">
                <div>
                    <p class="section-eyebrow">Ringkasan Belajar</p>
                    <h1>Halo, ${App.Utils.escapeHtml(user.full_name || user.username || 'Siswa')}!</h1>
                </div>
                <button data-page="materials-page">
                    <i data-lucide="play-circle"></i> Lanjutkan Belajar
                </button>
            </div>

            <div class="stats-grid mb-32">
                <div class="stat-card stat-card-info">
                    <div class="stat-card-row">
                        <div>
                            <h3>Modul Selesai</h3>
                            <div class="value"><span id="dash-materials-completed">--</span><span id="dash-total-materials-suffix" class="value-suffix">...</span></div>
                        </div>
                        <div class="stat-chip"><div class="css-art-book"></div></div>
                    </div>
                </div>
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-row">
                        <div>
                            <h3>Peringkat & Poin</h3>
                            <div class="value">
                                <span id="dash-rank">--</span>
                                <span class="value-suffix" id="dash-points-suffix">... Poin</span>
                            </div>
                        </div>
                        <div class="stat-chip"><div class="css-art-star"></div></div>
                    </div>
                </div>
                <div class="stat-card stat-card-accent">
                    <div class="stat-card-row">
                        <div>
                            <h3>Konsistensi Belajar</h3>
                            <div class="value"><span id="dash-learning-streak">--</span><span class="value-suffix">Hari</span></div>
                        </div>
                        <div class="stat-chip"><div class="css-art-calendar"></div></div>
                    </div>
                </div>
            </div>

            <div id="dashboard-recent-materials-container">
                <div class="skeleton-box dashboard-feature-skeleton"></div>
            </div>
        `;

        try {
            const response = await App.Service.API.getDashboardData();
            const data = response.data || {};

            // Process Summary
            const summary = data.summary || {};
            const leaderboardData = data.leaderboard || [];
            const userIndex = leaderboardData.findIndex(u => u.id == user.id);
            const userRank = userIndex !== -1 ? userIndex + 1 : '--';
            const userPoints = userIndex !== -1 ? leaderboardData[userIndex].total_points : (summary.total_points || 0);

            const suffixEl = document.getElementById('dash-total-materials-suffix');
            if (suffixEl) suffixEl.textContent = `dari ${summary.total || 0}`;
            
            const pointsSuffixEl = document.getElementById('dash-points-suffix');
            if (pointsSuffixEl) pointsSuffixEl.textContent = `(${userPoints} Poin)`;

            App.UI.animateNumber('dash-materials-completed', 0, summary.completed || 0, 1500);
            
            const rankEl = document.getElementById('dash-rank');
            if (rankEl) rankEl.textContent = userRank !== '--' ? `#${userRank}` : '--';
            
            App.UI.animateNumber('dash-learning-streak', 0, summary.streak || 0, 1500);

            // Handle Continue Button
            const continueBtn = document.querySelector('.dashboard-header button');
            if (continueBtn && summary.last_material) {
                continueBtn.innerHTML = `<i data-lucide="play-circle"></i> Lanjut: ${App.Utils.escapeHtml(summary.last_material.title)}`;
                continueBtn.onclick = () => {
                    App.Router.showPage('materials-page');
                    setTimeout(() => window.viewMaterial && window.viewMaterial(summary.last_material.id), 300);
                };
            }

            // Render Materials
            this.renderRecentMaterials(data.recent_materials || []);
            App.UI.renderIcons();

        } catch (error) {
            console.error('Dashboard load error:', error);
            const container = document.getElementById('dashboard-recent-materials-container');
            if (container) container.innerHTML = '<p class="text-danger">Gagal memuat data dasbor.</p>';
        }
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
        const getThumb = (thumb) => thumb ? (thumb.startsWith('http') || thumb.startsWith('/') ? thumb : '/belajaryuk/public/uploads/thumbnails/' + thumb) : defaultThumb;
        
        const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };
        
        let html = `
            <div class="featured-course-card mb-32" data-page="materials-page" data-material-id="${featured.id}">
                <div class="featured-course-copy">
                    <span class="category-tag">Disarankan • ${App.Utils.escapeHtml(featured.category || 'Umum')} • ${diffMap[featured.difficulty] || 'Pemula'}${featured.duration_minutes ? ` • ${featured.duration_minutes} Menit` : ''}</span>
                    <h2>${App.Utils.escapeHtml(featured.title)}</h2>
                    <p>${App.Utils.escapeHtml(featured.description ? featured.description.substring(0, 150) + '...' : 'Tingkatkan skill kamu sekarang.')}</p>
                    <div><span class="featured-cta">Mulai Modul Ini</span></div>
                </div>
                <div class="featured-course-media">
                    <img src="${getThumb(featured.thumbnail)}" alt="${App.Utils.escapeHtml(featured.title)}">
                </div>
            </div>
        `;

        if (others.length > 0) {
            html += `<h2 class="section-title-compact mb-16">Materi Menarik Lainnya</h2>`;
            html += `<div class="dashboard-material-grid">`;
            others.forEach(m => {
                html += `
                    <div class="material-card clickable-card" data-page="materials-page" data-material-id="${m.id}">
                        <div class="img-wrapper">
                            <img src="${getThumb(m.thumbnail)}" alt="${App.Utils.escapeHtml(m.title)}">
                        </div>
                        <div class="material-card-content">
                            <span class="category-tag">${App.Utils.escapeHtml(m.category || 'Umum')} • ${diffMap[m.difficulty] || 'Pemula'}</span>
                            <h3>${App.Utils.escapeHtml(m.title)}</h3>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }
        
        container.innerHTML = html;
        
        // Bind click events for material cards
        container.querySelectorAll('[data-material-id]').forEach(el => {
            el.onclick = () => {
                const id = el.getAttribute('data-material-id');
                App.Router.showPage('materials-page');
                setTimeout(() => window.viewMaterial && window.viewMaterial(id), 300);
            };
        });
    }
};

// Polyfill for main router
window.loadDashboard = () => App.Dashboard.load();
