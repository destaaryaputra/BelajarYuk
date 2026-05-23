/**
 * Belajaryuk - Progress & Leaderboard Module
 */

import { API } from './api.js';
import { UI } from './ui.js';

export const Progress = {
    async load() {
        const summaryContainer = document.getElementById('progress-summary');
        if (!summaryContainer) return;

        // 1. Loading States
        summaryContainer.innerHTML = '<div class="skeleton-box" style="height: 120px; width: 100%;"></div>';
        const materialsContainer = document.getElementById('material-progress-list');
        const quizContainer = document.getElementById('quiz-performance');
        
        if (materialsContainer) materialsContainer.innerHTML = '<div class="skeleton-box" style="height: 300px; width: 100%;"></div>';
        if (quizContainer) quizContainer.innerHTML = '<div class="skeleton-box" style="height: 300px; width: 100%;"></div>';

        try {
            // Fetch all data in parallel
            const [summaryRes, detailedRes, quizRes] = await Promise.all([
                API.getProgressSummary(),
                API.getProgressByCategories(),
                API.getQuizPerformance()
            ]);

            this.renderSummary(summaryRes.data || {});
            this.renderQuizHistory(quizRes.data || []);

            if (materialsContainer) {
                this.renderMaterials(detailedRes.data?.materials || [], materialsContainer);
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
                <div class="d-flex align-center gap-12 mb-24">
                    <div class="stat-chip accent-blue-bg"><i data-lucide="book-open"></i></div>
                    <h3 class="m-0">Progres Per Materi</h3>
                </div>
                <div class="material-progress-list-modern">
        `;

        materials.forEach(m => {
            const isDone = m.percentage >= 100;
            const percentage = Math.round(m.percentage);
            html += `
                <div class="mp-item-modern">
                    <div class="mp-item-header">
                        <div class="mp-item-main">
                            <span class="category-tag mb-4">${UI.escapeHtml(m.category)}</span>
                            <h4>${UI.escapeHtml(m.title)}</h4>
                        </div>
                        <div class="mp-item-percentage ${isDone ? 'text-success' : ''}">
                            ${percentage}%
                        </div>
                    </div>
                    <div class="progress-bar-bg-modern">
                        <div class="progress-bar-fill-modern ${isDone ? 'is-done' : ''}" style="width: ${percentage}%"></div>
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
                <div class="stat-card accent-blue">
                    <div class="stat-card-row">
                        <div>
                            <h3>Modul Selesai</h3>
                            <div class="value">${data.completed || 0} <span class="value-suffix">dari ${data.total || 0}</span></div>
                        </div>
                        <div class="stat-chip">
                            <i data-lucide="book-check"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card accent-orange">
                    <div class="stat-card-row">
                        <div>
                            <h3>Total Poin</h3>
                            <div class="value">${data.total_points || 0}</div>
                        </div>
                        <div class="stat-chip">
                            <i data-lucide="trophy"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card accent-green">
                    <div class="stat-card-row">
                        <div>
                            <h3>Rata-rata Nilai</h3>
                            <div class="value">${Math.round(data.avg_score || 0)}%</div>
                        </div>
                        <div class="stat-chip">
                            <i data-lucide="trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
        if (window.lucide) window.lucide.createIcons();
    },

    renderQuizHistory(results) {
        const container = document.getElementById('quiz-performance');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = '<div class="empty-state small"><p>Belum ada riwayat kuis.</p></div>';
            return;
        }

        let html = '<div class="quiz-history-list-modern">';
        results.forEach(res => {
            const attemptedAt = res.completed_at || res.submitted_at;
            const title = res.quiz_title || res.title || 'Kuis';
            const scoreValue = Math.round(Number(res.percentage ?? res.score ?? 0));
            const date = attemptedAt
                ? new Date(attemptedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                : '-';
            const isPassed = scoreValue >= Number(res.passing_score || 60);
            
            html += `
                <div class="quiz-history-item-modern">
                    <div class="qhi-status ${isPassed ? 'is-passed' : 'is-failed'}">
                        <i data-lucide="${isPassed ? 'check-circle-2' : 'x-circle'}"></i>
                    </div>
                    <div class="qhi-content">
                        <div class="qhi-top">
                            <span class="qhi-label">${isPassed ? 'Lulus' : 'Belum Lulus'}</span>
                            <span class="qhi-date">${date}</span>
                        </div>
                        <h4 class="qhi-title">${UI.escapeHtml(title)}</h4>
                    </div>
                    <div class="qhi-score-box ${isPassed ? 'text-success' : 'text-danger'}">
                        ${scoreValue}%
                    </div>
                </div>
            `;
        });
        container.innerHTML = html + '</div>';
    }
};
