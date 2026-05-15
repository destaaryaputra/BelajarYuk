/** Admin shell bootstrap, dashboard stats, and charts. */
async function loadAdminPanel() {
    logger.info('Memuat panel admin...');
    
    try {
        const userDataStr = localStorage.getItem(CONFIG.STORAGE_KEYS.USER_DATA);
        if (userDataStr) {
            const user = JSON.parse(userDataStr);
            const adminNameEl = document.getElementById('admin-user-name');
            if (adminNameEl) adminNameEl.textContent = user.full_name || 'Admin';
        }

        const activeBtn = document.querySelector('.admin-sidebar-nav button.active');
        if (activeBtn) {
            const tabId = activeBtn.id.replace('btn-tab-', '');
            switchAdminTab(tabId);
        } else {
            switchAdminTab('dashboard');
        }
    } catch (error) {
        logger.error('Gagal memuat panel admin', error);
        showNotification('Panel admin gagal dimuat. Silakan muat ulang halaman.', 'error');
    } finally {
        showLoading(false);
    }
}

let adminChart = null;
let adminPieChart = null;
let adminDashboardChartCache = {
    students: [],
    materials: []
};

async function loadAdminDashboard() {
    const adminUsersStat = document.getElementById('admin-stat-users');
    const adminMaterialsStat = document.getElementById('admin-stat-materials');
    const adminRecentStat = document.getElementById('admin-stat-recent');
    
    if (adminUsersStat) adminUsersStat.textContent = '--';
    if (adminMaterialsStat) adminMaterialsStat.textContent = '--';
    if (adminRecentStat) adminRecentStat.textContent = '--';
    
    const quickActionsEl = document.getElementById('admin-quick-actions');
    const insightsEl = document.getElementById('admin-dashboard-insights');
    const recentUsersEl = document.getElementById('admin-recent-users');
    const recentMaterialsEl = document.getElementById('admin-recent-materials');
    const recentCommentsEl = document.getElementById('admin-recent-comments');

    if (quickActionsEl) quickActionsEl.innerHTML = renderAdminQuickActions();
    if (insightsEl) insightsEl.innerHTML = renderDashboardInsightSkeleton();
    if (recentUsersEl) recentUsersEl.innerHTML = '<p class="text-center p-20">Memuat siswa terbaru...</p>';
    if (recentMaterialsEl) recentMaterialsEl.innerHTML = '<p class="text-center p-20">Memuat materi terbaru...</p>';
    if (recentCommentsEl) recentCommentsEl.innerHTML = '<p class="text-center p-20">Memuat diskusi terbaru...</p>';

    try {
        const [usersRes, materialsRes, reportRes, commentsRes] = await Promise.all([
            API.getAllUsers(),
            API.getMaterials(1, 100),
            API.getAdminQuizReport().catch(error => {
                logger.error('Admin Dashboard: Gagal memuat laporan nilai', error);
                return { data: {} };
            }),
            API.getAllCommentsAdmin(5).catch(error => {
                logger.error('Admin Dashboard: Gagal memuat diskusi terbaru', error);
                return { data: [] };
            })
        ]);

        const allUsers = usersRes.data?.users || usersRes.data || [];
        const students = allUsers.filter(u => u.role === 'student');
        const materialsData = materialsRes.data || {};
        const materials = Array.isArray(materialsData.materials) ? materialsData.materials : (Array.isArray(materialsData) ? materialsData : []);
        const totalMaterials = materialsData.total || materials.length || 0;
        const report = reportRes.data || {};
        const reportSummary = report.summary || {};
        const comments = commentsRes.data || [];

        const currentMonth = new Date().getMonth();
        const currentYear = new Date().getFullYear();
        const newThisMonth = students.filter(u => {
            const d = new Date(u.created_at);
            return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
        }).length;

        animateNumber('admin-stat-users', 0, students.length, 800, '<span class="value-suffix">siswa</span>');
        animateNumber('admin-stat-recent', 0, newThisMonth, 800, '<span class="value-suffix">siswa</span>');
        animateNumber('admin-stat-materials', 0, totalMaterials, 800, '<span class="value-suffix">kursus</span>');

        if (insightsEl) insightsEl.innerHTML = renderDashboardInsights(reportSummary, comments.length);
        if (recentUsersEl) recentUsersEl.innerHTML = renderRecentStudents(students.slice(0, 5));
        if (recentMaterialsEl) recentMaterialsEl.innerHTML = renderRecentMaterials(materials.slice(0, 5));
        if (recentCommentsEl) recentCommentsEl.innerHTML = renderRecentComments(comments.slice(0, 5));
        
        // Render ulang ikon Lucide untuk elemen dinamis
        if (typeof renderIcons === 'function') renderIcons();

        if (typeof Chart !== 'undefined') {
            adminDashboardChartCache = { students, materials };
            renderRegistrationChart(students);
            renderCategoryChart(materials);
        }
    } catch (err) {
        logger.error('Admin Dashboard: Gagal memuat data utama', err);
        if (recentUsersEl) recentUsersEl.innerHTML = '<p class="text-danger">Gagal memuat data siswa.</p>';
        if (recentMaterialsEl) recentMaterialsEl.innerHTML = '<p class="text-danger">Gagal memuat data materi.</p>';
        if (recentCommentsEl) recentCommentsEl.innerHTML = '<p class="text-danger">Gagal memuat diskusi terbaru.</p>';
        if (insightsEl) insightsEl.innerHTML = '<div class="content-card"><p class="text-danger">Gagal memuat ringkasan dashboard.</p></div>';
    }
}

function refreshAdminDashboardCharts() {
    if (typeof Chart === 'undefined') return;

    renderRegistrationChart(adminDashboardChartCache.students || []);
    renderCategoryChart(adminDashboardChartCache.materials || []);
}

function renderAdminQuickActions() {
    return `
        <button type="button" onclick="switchAdminTab('materi'); toggleAdminForm(true)">
            <i data-lucide="plus"></i>
            <div><span>Tambah</span>Materi</div>
        </button>
        <button type="button" class="btn-outline" onclick="switchAdminTab('pengguna')">
            <i data-lucide="users"></i>
            <div><span>Kelola</span>Siswa</div>
        </button>
        <button type="button" class="btn-outline" onclick="switchAdminTab('laporan')">
            <i data-lucide="file-text"></i>
            <div><span>Laporan</span>Nilai</div>
        </button>
        <button type="button" class="btn-outline" onclick="switchAdminTab('diskusi')">
            <i data-lucide="message-square"></i>
            <div><span>Moderasi</span>Diskusi</div>
        </button>
    `;
}

function renderDashboardInsightSkeleton() {
    return `
        <div class="stat-card"><h3>Total Pengerjaan</h3><div class="value">--</div></div>
        <div class="stat-card"><h3>Nilai Rata-rata</h3><div class="value">--</div></div>
        <div class="stat-card"><h3>Diskusi Terbaru</h3><div class="value">--</div></div>
    `;
}

function renderDashboardInsights(summary, latestCommentsCount) {
    return `
        <div class="stat-card stat-card-primary">
            <h3>Total Pengerjaan Kuis</h3>
            <div class="value">${Number(summary.total_attempts || 0)}</div>
        </div>
        <div class="stat-card stat-card-info">
            <h3>Nilai Rata-rata</h3>
            <div class="value">${Number(summary.avg_score || 0)}<span class="value-suffix">%</span></div>
        </div>
        <div class="stat-card stat-card-accent">
            <h3>Diskusi Terbaru</h3>
            <div class="value">${Number(latestCommentsCount || 0)}</div>
        </div>
    `;
}

function renderRecentStudents(students) {
    if (!students.length) {
        return '<div class="empty-state empty-state-small"><p>Belum ada siswa terdaftar.</p></div>';
    }

    return `
        <div class="admin-table-wrapper">
            <div class="admin-mini-list">
                ${students.map(student => {
                    const date = formatAdminDate(student.created_at);
                    return `
                        <div class="admin-mini-item">
                            <div class="admin-avatar">${getInitials(student.full_name || student.username || 'S')}</div>
                            <div class="admin-mini-main">
                                <strong>${adminDashEscape(student.full_name || '-')}</strong>
                                <span>${adminDashEscape(student.email || '-')}</span>
                            </div>
                            <div class="admin-mini-meta">${date}</div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

function renderRecentMaterials(materials) {
    if (!materials.length) {
        return '<div class="empty-state empty-state-small"><p>Belum ada materi tersedia.</p></div>';
    }

    return `
        <div class="admin-table-wrapper">
            <div class="admin-mini-list">
                ${materials.map(material => `
                    <div class="admin-mini-item">
                        <div class="admin-course-mark">${adminDashEscape((material.category || 'U').charAt(0).toUpperCase())}</div>
                        <div class="admin-mini-main">
                            <strong>${adminDashEscape(material.title || '-')}</strong>
                            <span>${adminDashEscape(material.category || 'Umum')} • ${Number(material.duration_minutes || 0)} menit</span>
                        </div>
                        <div class="admin-mini-meta">${adminDashEscape(formatDifficulty(material.difficulty))}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function renderRecentComments(comments) {
    if (!comments.length) {
        return '<div class="empty-state empty-state-small"><p>Belum ada diskusi siswa.</p></div>';
    }

    return `
        <div class="admin-comment-list">
            ${comments.map(comment => `
                <div class="admin-comment-preview">
                    <div>
                        <strong>${adminDashEscape(comment.full_name || '-')}</strong>
                        <span class="text-sm text-muted">@${adminDashEscape(comment.username || '-')} • ${formatAdminDateTime(comment.created_at)}</span>
                    </div>
                    <p>${adminDashEscape(comment.comment_text || '')}</p>
                    <span class="text-sm text-muted">${adminDashEscape(comment.material_title || '-')}</span>
                </div>
            `).join('')}
        </div>
    `;
}

function formatAdminDate(value) {
    return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}

function formatAdminDateTime(value) {
    return value ? new Date(value).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-';
}

function formatDifficulty(value) {
    const map = { beginner: 'Pemula', intermediate: 'Menengah', advanced: 'Mahir' };
    return map[value] || value || '-';
}

function adminDashEscape(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function getInitials(value) {
    return String(value || '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map(part => part.charAt(0).toUpperCase())
        .join('') || 'S';
}

// Helper: Render Grafik Registrasi
function renderRegistrationChart(students) {
    const palette = getAdminChartPalette();
    const monthCounts = {};
    const monthLabels = [];
    
    for(let i = 5; i >= 0; i--) {
        const d = new Date();
        d.setMonth(d.getMonth() - i);
        const monthName = d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        monthLabels.push(monthName);
        monthCounts[monthName] = 0;
    }

    students.forEach(u => {
        const d = new Date(u.created_at);
        const monthName = d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        if (monthCounts[monthName] !== undefined) {
            monthCounts[monthName]++;
        }
    });

    const ctx = document.getElementById('adminRegistrationChart');
    if (ctx) {
        if (adminChart) adminChart.destroy();
        adminChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Siswa Baru',
                    data: monthLabels.map(m => monthCounts[m]),
                    borderColor: palette.primary,
                    backgroundColor: palette.primarySoft,
                    pointBackgroundColor: palette.primary,
                    pointBorderColor: palette.card,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: context => `${context.parsed.y || 0} siswa baru`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: palette.axis, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.max(...Object.values(monthCounts), 1),
                        ticks: { stepSize: 1, color: palette.axis, precision: 0 },
                        grid: { color: palette.grid }
                    }
                },
                animation: { duration: 900, easing: 'easeOutQuart' }
            }
        });
    }
}

// Helper: Render Grafik Kategori
function renderCategoryChart(materials) {
    const palette = getAdminChartPalette();
    const categoryCounts = {};
    materials.forEach(m => {
        const cat = m.category || 'Umum';
        categoryCounts[cat] = (categoryCounts[cat] || 0) + 1;
    });
    
    const pieCtx = document.getElementById('adminCategoryChart');
    if (pieCtx) {
        if (adminPieChart) adminPieChart.destroy();
        const entries = Object.entries(categoryCounts)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 8);
        const labels = entries.map(([label]) => label);
        const values = entries.map(([, count]) => count);
        const bgColors = palette.series;

        adminPieChart = new Chart(pieCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Materi',
                    data: values,
                    backgroundColor: bgColors.slice(0, labels.length),
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 18
                }]
            },
            options: { 
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: context => `${context.parsed.x || 0} materi`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        suggestedMax: Math.max(...values, 1),
                        ticks: { stepSize: 1, color: palette.axis, precision: 0 },
                        grid: { color: palette.grid }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: palette.axisStrong }
                    }
                },
                animation: { duration: 900, easing: 'easeOutQuart' }
            }
        });
    }
}

function getAdminChartPalette() {
    const styles = getComputedStyle(document.body);
    const isDark = document.body.classList.contains('dark-theme');

    return {
        primary: isDark ? '#2dd4bf' : '#0f766e',
        primarySoft: isDark ? 'rgba(45, 212, 191, 0.16)' : 'rgba(15, 118, 110, 0.12)',
        card: styles.getPropertyValue('--admin-card').trim() || (isDark ? '#111827' : '#ffffff'),
        axis: styles.getPropertyValue('--admin-chart-axis').trim() || (isDark ? '#94a3b8' : '#64748b'),
        axisStrong: styles.getPropertyValue('--admin-chart-axis-strong').trim() || (isDark ? '#cbd5e1' : '#334155'),
        grid: styles.getPropertyValue('--admin-chart-grid').trim() || 'rgba(148, 163, 184, 0.2)',
        series: isDark
            ? ['#2dd4bf', '#38bdf8', '#fbbf24', '#a78bfa', '#f472b6', '#bef264', '#fb923c', '#94a3b8']
            : ['#0f766e', '#0ea5e9', '#f59e0b', '#6366f1', '#ec4899', '#84cc16', '#f97316', '#64748b']
    };
}

