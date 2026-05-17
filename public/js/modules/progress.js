/**
 * Belajaryuk - Progress & Leaderboard Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Progress = {
    async load() {
        const summaryContainer = document.getElementById('progress-summary');
        if (!summaryContainer) return;

        // 1. Loading States
        summaryContainer.innerHTML = '<div class="skeleton-box" style="height: 120px; width: 100%;"></div>';
        const catsContainer = document.getElementById('category-progress');
        const materialsContainer = document.getElementById('material-progress-list');
        if (catsContainer) catsContainer.innerHTML = '<div class="skeleton-box" style="height: 200px; width: 100%;"></div>';
        if (materialsContainer) materialsContainer.innerHTML = '<div class="skeleton-box" style="height: 200px; width: 100%;"></div>';
        document.getElementById('leaderboard-list').innerHTML = '<div class="skeleton-box" style="height: 300px; width: 100%;"></div>';

        try {
            // Fetch all data in parallel
            const [summaryRes, detailedRes, leaderboardRes, quizRes] = await Promise.all([
                API.getProgressSummary(),
                API.getProgressByCategories(),
                API.getLeaderboard(),
                API.getQuizPerformance()
            ]);

            const progressData = detailedRes.data || {};
            this.renderSummary(summaryRes.data || {});
            this.renderCategories(progressData.categories || []);
            this.renderLeaderboard(leaderboardRes.data || []);
            this.renderQuizHistory(quizRes.data || []);

            if (materialsContainer) {
                this.renderMaterials(progressData.materials || [], materialsContainer);
            }

            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error('Progress load error:', error);
            UI.showNotification('Gagal memuat data progres.', 'error');
        }
    },

    renderMaterials(materials, container) {
        if (!materials || materials.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>Belum ada progres materi.</p></div>';
            return;
        }

        let html = `
            <div class="content-card">
                <h3 class="mb-20"><i data-lucide="book-open" class="icon-md mr-8"></i> Progres Per Materi</h3>
                <div class="material-progress-grid">
        `;

        materials.forEach(m => {
            const isDone = m.percentage >= 100;
            html += `
                <div class="material-progress-card">
                    <div class="mpc-info">
                        <div class="mpc-text">
                            <span class="category-tag mb-8">${UI.escapeHtml(m.category)}</span>
                            <h4>${UI.escapeHtml(m.title)}</h4>
                        </div>
                        <div class="mpc-percentage ${isDone ? 'text-success' : ''}">${m.percentage}%</div>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: ${m.percentage}%"></div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html + '</div></div>';
    },

    renderSummary(data) {
        const container = document.getElementById('progress-summary');
        if (!container) return;

        container.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Modul Selesai</h3>
                    <div class="value">${data.completed || 0} <span class="value-suffix">dari ${data.total || 0}</span></div>
                </div>
                <div class="stat-card">
                    <h3>Total Poin</h3>
                    <div class="value">${data.total_points || 0}</div>
                </div>
                <div class="stat-card">
                    <h3>Rata-rata Nilai</h3>
                    <div class="value">${Math.round(data.avg_score || 0)}%</div>
                </div>
            </div>
        `;
    },

    renderCategories(categories) {
        const container = document.getElementById('category-progress');
        if (!container) return;

        if (categories.length === 0) {
            container.innerHTML = '<p class="text-muted">Belum ada progres per kategori.</p>';
            return;
        }

        let html = '<div class="category-progress-list">';
        categories.forEach(cat => {
            // Use percentage from API for perfect sync
            const percentage = Math.round(cat.percentage || 0);
            html += `
                <div class="category-progress-item mb-16">
                    <div class="d-flex justify-between mb-8">
                        <strong>${UI.escapeHtml(cat.category)}</strong>
                        <span>${percentage}%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html + '</div>';
    },

    renderLeaderboard(users) {
        const container = document.getElementById('leaderboard-list');
        if (!container) return;

        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted">Papan peringkat masih kosong.</p>';
            return;
        }

        // Get current user ID
        const userData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        const currentUserId = userData ? JSON.parse(userData).id : null;

        let html = '<div class="leaderboard-table-wrapper"><table class="admin-table"><thead><tr><th>Rank</th><th>Siswa</th><th>Poin</th></tr></thead><tbody>';
        
        users.forEach((user, index) => {
            const isMe = user.id == currentUserId;
            const rank = index + 1;
            let rankClass = '';
            if (rank === 1) rankClass = 'rank-gold';
            else if (rank === 2) rankClass = 'rank-silver';
            else if (rank === 3) rankClass = 'rank-bronze';

            html += `
                <tr class="${isMe ? 'is-me' : ''}">
                    <td><span class="rank-badge ${rankClass}">${rank}</span></td>
                    <td>
                        <div class="d-flex align-center gap-12">
                            <div class="admin-avatar">${(user.full_name || user.username || '?')[0].toUpperCase()}</div>
                            <strong>${UI.escapeHtml(user.full_name || user.username)} ${isMe ? '(Kamu)' : ''}</strong>
                        </div>
                    </td>
                    <td><strong>${user.total_points}</strong></td>
                </tr>
            `;
        });

        container.innerHTML = html + '</tbody></table></div>';
    },

    renderQuizHistory(results) {
        const container = document.getElementById('quiz-performance');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = '<p class="text-muted">Belum ada riwayat kuis.</p>';
            return;
        }

        let html = '<div class="quiz-history-grid">';
        results.forEach(res => {
            const attemptedAt = res.completed_at || res.submitted_at;
            const title = res.quiz_title || res.title || 'Kuis';
            const scoreValue = Number(res.percentage ?? res.score ?? 0);
            const date = attemptedAt
                ? new Date(attemptedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                : '-';
            const isPassed = scoreValue >= Number(res.passing_score || 60);
            
            html += `
                <div class="quiz-history-card">
                    <div class="qhc-header">
                        <span class="badge ${isPassed ? 'badge-success' : 'badge-danger'}">${isPassed ? 'Lulus' : 'Gagal'}</span>
                        <span class="qhc-date">${date}</span>
                    </div>
                    <h4>${UI.escapeHtml(title)}</h4>
                    <div class="qhc-score">${Math.round(scoreValue)}%</div>
                </div>
            `;
        });
        container.innerHTML = html + '</div>';
    }
};
