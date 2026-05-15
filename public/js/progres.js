/** Progress page logic. */
async function loadProgress() {
    const summaryContainer = document.getElementById('progress-summary');
    const categoryContainer = document.getElementById('category-progress');
    const quizContainer = document.getElementById('quiz-performance');
    const leaderboardContainer = document.getElementById('leaderboard-list');
    
    if (!summaryContainer || !categoryContainer || !quizContainer) return;

    App.Utils.showLoading(true);

    try {
        // Parallel fetching using Promise.all for maximum speed
        const [summaryRes, catRes, leaderboardRes, quizRes] = await Promise.all([
            App.Service.API.getProgressSummary(),
            App.Service.API.getProgressByCategory(),
            App.Service.API.getLeaderboard(5),
            App.Service.API.getQuizPerformance(5)
        ]);

        const summary = summaryRes.data || {};
        
        summaryContainer.innerHTML = `
            <div class="stats-grid mb-0">
                <div class="stat-card stat-card-info">
                    <div class="stat-card-row">
                        <div>
                            <h3>Modul Selesai</h3>
                            <div class="value"><span id="prog-materials-completed">0</span><span class="value-suffix">dari ${summary.total || 0}</span></div>
                        </div>
                        <div class="stat-chip"><div class="css-art-book"></div></div>
                    </div>
                </div>
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-row">
                        <div>
                            <h3>Total Poin</h3>
                            <div class="value"><span id="prog-total-points">0</span><span class="value-suffix">Poin</span></div>
                        </div>
                        <div class="stat-chip"><div class="css-art-star"></div></div>
                    </div>
                </div>
                <div class="stat-card stat-card-accent">
                    <div class="stat-card-row">
                        <div>
                            <h3>Konsistensi Belajar</h3>
                            <div class="value"><span id="prog-learning-streak">0</span><span class="value-suffix">Hari</span></div>
                        </div>
                        <div class="stat-chip"><div class="css-art-calendar"></div></div>
                    </div>
                </div>
            </div>
        `;

        // Update animasi untuk poin juga
        const leaderboardData = leaderboardRes?.data || [];
        const userStr = localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);
        const user = userStr ? JSON.parse(userStr) : {};
        const currentUserData = leaderboardData.find(u => u.id == user.id);
        const totalPoints = currentUserData ? currentUserData.total_points : 0;

        // Safe animation trigger
        setTimeout(() => {
            if (typeof App.UI.animateNumber === 'function') {
                App.UI.animateNumber('prog-materials-completed', 0, summary.completed || 0, 1500);
                App.UI.animateNumber('prog-total-points', 0, totalPoints, 1500);
                App.UI.animateNumber('prog-learning-streak', 0, summary.streak || 0, 1500);
            } else {
                document.getElementById('prog-materials-completed').textContent = summary.completed || 0;
                document.getElementById('prog-total-points').textContent = totalPoints;
                document.getElementById('prog-learning-streak').textContent = summary.streak || 0;
            }
        }, 300);

        // Render Categories
        const categories = Array.isArray(catRes.data) ? catRes.data : [];
        let catHtml = `
            <div class="empty-state empty-state-small">
                <div class="css-art-empty-list"></div>
                <p>Kamu belum memulai pelajaran apa pun. Yuk, mulai belajar sekarang!</p>
            </div>`;
            
        if (categories.length > 0) {
            catHtml = categories.map(cat => {
                const total = Number(cat.total) || 0;
                const completed = Number(cat.completed) || 0;
                const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                return `
                <div class="progress-item">
                    <div class="d-flex justify-between align-center mb-8">
                        <span class="progress-title">${App.Utils.escapeHtml(cat.category || 'Umum')}</span>
                        <span class="progress-stats">${percentage}% (${completed}/${total} Materi)</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: ${percentage}%;"></div>
                    </div>
                </div>
                `;
            }).join('');
        }
        categoryContainer.innerHTML = catHtml;

        // Render Leaderboard
        renderLeaderboardToDOM(leaderboardRes.data || []);

        // Render Quiz Results
        const quizzes = Array.isArray(quizRes.data) ? quizRes.data : [];
        let quizHtml = `
            <div class="empty-state empty-state-small">
                <div class="css-art-empty-box"></div>
                <p>Kamu belum menyelesaikan kuis apa pun nih.</p>
            </div>`;
            
        if (quizzes.length > 0) {
            quizHtml = quizzes.map(quiz => {
                const percentage = Number(quiz.percentage) || 0;
                const passing = Number(quiz.passing_score) || 60;
                const isPassed = percentage >= passing;
                const colorClass = isPassed ? 'text-success' : 'text-danger';
                return `
                <div class="quiz-history-card">
                    <div>
                        <h4 class="quiz-history-title">${App.Utils.escapeHtml(quiz.title || 'Kuis')}</h4>
                        <span class="quiz-history-date">${quiz.submitted_at ? new Date(quiz.submitted_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-'}</span>
                    </div>
                    <div class="quiz-history-score-wrapper">
                        <div class="quiz-history-score ${colorClass}">
                            ${percentage}%
                        </div>
                        <span class="quiz-history-status ${colorClass}">${isPassed ? 'Lulus' : 'Gagal'}</span>
                    </div>
                </div>
                `;
            }).join('');
        }
        quizContainer.innerHTML = quizHtml;

    } catch (error) {
        App.Utils.showNotification(error.message, 'error');
        summaryContainer.innerHTML = '<p class="text-danger">Gagal memuat ringkasan progres.</p>';
    } finally {
        App.Utils.showLoading(false);
    }
}

/**
 * Helper to render leaderboard to DOM
 */
function renderLeaderboardToDOM(data) {
    const leaderboardContainer = document.getElementById('leaderboard-list');
    if (!leaderboardContainer) return;

    if (data.length === 0) {
        leaderboardContainer.innerHTML = `
            <div class="empty-state empty-state-small">
                <p>Belum ada data peringkat saat ini.</p>
            </div>`;
        return;
    }

    let html = `
        <div class="leaderboard-wrapper">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th>Siswa</th>
                        <th class="text-center">Materi</th>
                        <th class="text-right">Total Poin</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.forEach((user, index) => {
        const isTop3 = index < 3;
        const rankClass = isTop3 ? `rank-${index + 1}` : '';
        const avatar = user.avatar ? `/public/uploads/avatars/${user.avatar}` : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.full_name) + '&background=6366f1&color=fff';

        html += `
            <tr class="${rankClass}">
                <td class="rank-col">
                    <div class="rank-badge">${index + 1}</div>
                </td>
                <td class="user-col">
                    <div class="d-flex align-center gap-12">
                        <img src="${avatar}" alt="${App.Utils.escapeHtml(user.full_name)}" class="leaderboard-avatar">
                        <div class="user-info">
                            <span class="user-name">${App.Utils.escapeHtml(user.full_name)}</span>
                            <span class="user-username">@${App.Utils.escapeHtml(user.username)}</span>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <span class="materi-count">${user.materials_completed}</span>
                </td>
                <td class="text-right">
                    <span class="points-value">${user.total_points}</span>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    leaderboardContainer.innerHTML = html;
}

