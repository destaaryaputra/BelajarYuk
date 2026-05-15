/** Admin report tab backed by quiz result data. */
async function loadAdminReports() {
    const container = document.getElementById('admin-tab-laporan');
    if (!container) return;

    container.innerHTML = '<div class="content-card"><p class="text-center p-20">Memuat laporan nilai...</p></div>';

    try {
        const res = await API.getAdminQuizReport();
        const data = res.data || {};
        const summary = data.summary || {};
        const perQuiz = data.per_quiz || [];
        const recent = data.recent_results || [];

        container.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><h3>Total Pengerjaan</h3><div class="value">${summary.total_attempts || 0}</div></div>
                <div class="stat-card"><h3>Nilai Rata-rata</h3><div class="value">${summary.avg_score || 0}<span class="value-suffix">%</span></div></div>
                <div class="stat-card"><h3>Nilai Tertinggi</h3><div class="value">${summary.highest_score || 0}<span class="value-suffix">%</span></div></div>
            </div>
            <div class="content-card">
                <h3>Performa per Kuis</h3>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr><th>Kuis</th><th>Materi</th><th>Dikerjakan</th><th>Rata-rata</th><th>Lulus</th></tr></thead>
                        <tbody>${renderQuizReportRows(perQuiz)}</tbody>
                    </table>
                </div>
            </div>
            <div class="content-card">
                <h3>Riwayat Nilai Terbaru</h3>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr><th>Siswa</th><th>Kuis</th><th>Materi</th><th>Nilai</th><th>Dikirim</th></tr></thead>
                        <tbody>${renderRecentResultRows(recent)}</tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (error) {
        handleAPIError(error);
        container.innerHTML = '<div class="content-card"><p class="text-danger">Gagal memuat laporan nilai dari database.</p></div>';
    }
}

function renderQuizReportRows(items) {
    if (!items.length) {
        return '<tr><td colspan="5" style="text-align:center;">Belum ada kuis aktif atau hasil pengerjaan.</td></tr>';
    }

    return items.map(item => {
        const attempts = Number(item.attempts || 0);
        const passed = Number(item.passed_count || 0);
        return `
            <tr>
                <td><strong>${escapeAdminHtml(item.quiz_title || '-')}</strong></td>
                <td>${escapeAdminHtml(item.material_title || '-')}</td>
                <td>${attempts}</td>
                <td>${Number(item.avg_score || 0)}%</td>
                <td>${passed} dari ${attempts}</td>
            </tr>
        `;
    }).join('');
}

function renderRecentResultRows(items) {
    if (!items.length) {
        return '<tr><td colspan="5" style="text-align:center;">Belum ada hasil kuis yang tersimpan.</td></tr>';
    }

    return items.map(item => {
        const date = item.submitted_at ? new Date(item.submitted_at).toLocaleString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }) : '-';
        return `
            <tr>
                <td><strong>${escapeAdminHtml(item.full_name || '-')}</strong><br><span class="text-sm text-muted">@${escapeAdminHtml(item.username || '-')}</span></td>
                <td>${escapeAdminHtml(item.quiz_title || '-')}</td>
                <td>${escapeAdminHtml(item.material_title || '-')}</td>
                <td><strong>${Number(item.percentage || 0)}%</strong></td>
                <td>${date}</td>
            </tr>
        `;
    }).join('');
}
