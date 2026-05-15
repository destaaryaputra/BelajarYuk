/** Course discussion logic. */
function renderDiscussionBoard() {
    const detailContainer = document.getElementById('material-detail');
    if (!detailContainer) return;
    
    const discussionHtml = `
        <div class="content-card discussion-card">
            <h3 class="discussion-title">
                Forum Diskusi Kelas
            </h3>
            <form id="comment-form" onsubmit="submitComment(event)" class="comment-form">
                <textarea id="comment-input" rows="2" placeholder="Ada yang ingin ditanyakan? Yuk, diskusi di sini..." required class="comment-textarea"></textarea>
                <button type="submit" class="btn-send">Kirim</button>
            </form>
            <div id="comments-list" class="comments-list">
                <p class="text-center text-muted" style="padding: 20px;">Sedang memuat diskusi...</p>
            </div>
        </div>
    `;
    detailContainer.insertAdjacentHTML('beforeend', discussionHtml);
    loadComments(currentCourseData.id);
}

async function loadComments(materialId) {
    try {
        const res = await API.getComments(materialId);
        const comments = res.data || [];
        const listEl = document.getElementById('comments-list');
        
        if (comments.length === 0) { 
            listEl.innerHTML = `
                <div class="empty-state empty-state-small">
                    <div class="css-art-empty-list"></div>
                    <p>Belum ada diskusi nih. Yuk, mulai percakapan!</p>
                </div>`; 
            return; 
        }
        
        listEl.innerHTML = comments.map(c => {
            let badge = '';
            if (c.role === 'admin') badge = '<span class="badge badge-admin">Admin</span>';
            const initial = c.full_name ? escapeHtml(c.full_name.charAt(0).toUpperCase()) : '?';
            const date = new Date(c.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            const name = escapeHtml(c.full_name || 'Pengguna');
            const text = escapeHtml(c.comment_text || '').replace(/\n/g, '<br>');
            return `<div class="comment-item"><div class="comment-avatar">${initial}</div><div class="comment-content flex-1"><div class="comment-header"><div class="comment-name">${name} ${badge}</div><div class="comment-time text-sm text-muted">${date}</div></div><div class="comment-text">${text}</div></div></div>`;
        }).join('');
    } catch (err) { console.error(err); document.getElementById('comments-list').innerHTML = '<p class="text-danger">Ups, gagal memuat obrolan. Coba muat ulang ya.</p>'; }
}

async function submitComment(e) {
    e.preventDefault();
    const input = document.getElementById('comment-input');
    const text = input.value.trim();
    if (!text) return;
    const btn = e.target.querySelector('button');
    btn.disabled = true; btn.innerText = 'Mengirim...';
    try { await API.addComment(currentCourseData.id, text); input.value = ''; loadComments(currentCourseData.id); } 
    catch (err) { handleAPIError(err); } finally { btn.disabled = false; btn.innerText = 'Kirim'; }
}
