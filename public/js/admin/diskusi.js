/** Admin discussion moderation backed by komentar_materi. */
async function loadAdminDiscussions() {
    const container = document.getElementById('admin-tab-diskusi');
    if (!container) return;

    container.innerHTML = '<div class="content-card"><p class="text-center p-20">Memuat diskusi siswa...</p></div>';

    try {
        const res = await API.getAllCommentsAdmin(150);
        const comments = res.data || [];
        container.innerHTML = `
            <div class="content-card">
                <div class="d-flex justify-between align-center mb-16">
                    <h3>Moderasi Forum Diskusi</h3>
                    <button type="button" class="btn-outline btn-small" onclick="loadAdminDiscussions()">Muat Ulang</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr><th>Siswa</th><th>Materi</th><th>Komentar</th><th>Waktu</th><th style="text-align:right;">Aksi</th></tr></thead>
                        <tbody>${renderAdminCommentRows(comments)}</tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (error) {
        handleAPIError(error);
        container.innerHTML = '<div class="content-card"><p class="text-danger">Gagal memuat diskusi dari database.</p></div>';
    }
}

function renderAdminCommentRows(comments) {
    if (!comments.length) {
        return '<tr><td colspan="5" style="text-align:center;">Belum ada komentar diskusi.</td></tr>';
    }

    return comments.map(comment => {
        const date = comment.created_at ? new Date(comment.created_at).toLocaleString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }) : '-';
        return `
            <tr>
                <td><strong>${escapeAdminHtml(comment.full_name || '-')}</strong><br><span class="text-sm text-muted">@${escapeAdminHtml(comment.username || '-')}</span></td>
                <td>${escapeAdminHtml(comment.material_title || '-')}</td>
                <td style="white-space: normal; min-width: 280px;">${escapeAdminHtml(comment.comment_text || '')}</td>
                <td>${date}</td>
                <td class="text-right">
                    <button type="button" class="btn-outline btn-text-danger" onclick="handleDeleteCommentAdmin(${comment.id})">Hapus</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function handleDeleteCommentAdmin(id) {
    if (!confirm('Yakin ingin menghapus komentar ini dari forum diskusi?')) return;

    showLoading(true);
    try {
        await API.deleteCommentAdmin(id);
        showNotification('Komentar berhasil dihapus.', 'success');
        loadAdminDiscussions();
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}
