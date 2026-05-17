/**
 * Belajaryuk - Material Detail Module
 */

import { API } from './api.js';
import { UI } from './ui.js';

export const MaterialDetail = {
    state: {
        material: null,
        subMaterials: [],
        completedEpisodes: [], // Track IDs of completed sub-materials
        activeItemId: 'main',
        quiz: null
    },
    dropdownBound: false,

    async load() {
        const detailContainer = document.getElementById('material-detail');
        const syllabusContainer = document.getElementById('course-syllabus');
        if (!detailContainer || !syllabusContainer) return;

        const materialId = localStorage.getItem('active_material_id');
        if (!materialId) {
            detailContainer.innerHTML = '<div class="empty-state"><h3>Materi belum dipilih</h3><p>Silakan kembali ke daftar materi lalu pilih modul terlebih dahulu.</p></div>';
            syllabusContainer.innerHTML = '<p class="text-muted">Daftar episode belum tersedia.</p>';
            return;
        }

        detailContainer.innerHTML = '<div class="skeleton-box" style="height: 260px;"></div>';
        syllabusContainer.innerHTML = '<div class="skeleton-box" style="height: 220px;"></div>';

        try {
            const [detailRes, quizRes] = await Promise.all([
                API.getMaterialDetail(materialId),
                API.getQuiz(materialId).catch(() => ({ data: null }))
            ]);

            const payload = detailRes.data || {};
            const material = payload.material || {};
            this.state.material = material;
            this.state.completedEpisodes = (payload.completed_episodes || []).map(id => String(id));
            
            // Sync completion status for main material
            if (payload.user_progress && payload.user_progress.completed_at) {
                this.state.material.is_completed = true;
            }
            
            // UX Fix: Sembunyikan episode yang tidak punya video sesuai permintaan
            const rawSubs = Array.isArray(material.sub_materials) ? material.sub_materials : [];
            this.state.subMaterials = rawSubs.filter(s => s.video_url && s.video_url.trim() !== '');
            
            this.state.quiz = quizRes.data || null;

            // Senior UX Fix: Pilih episode terakhir yang belum selesai jika memungkinkan
            if (!material.video_url && this.state.subMaterials.length > 0) {
                // Cari episode pertama yang BELUM selesai
                const firstIncomplete = this.state.subMaterials.find(s => !this.state.completedEpisodes.includes(String(s.id)));
                this.state.activeItemId = firstIncomplete ? String(firstIncomplete.id) : String(this.state.subMaterials[0].id);
            } else {
                this.state.activeItemId = 'main';
            }

            this.renderSyllabus();
            this.renderContent();
            this.bindDropdown();

            API.trackProgress(materialId).catch(() => {});
        } catch (error) {
            console.error('Material detail load error:', error);
            detailContainer.innerHTML = '<p class="text-danger">Gagal memuat detail materi.</p>';
            syllabusContainer.innerHTML = '<p class="text-muted">Tidak dapat memuat episode.</p>';
        }
    },

    renderSyllabus() {
        const container = document.getElementById('course-syllabus');
        const currentLabel = document.getElementById('current-episode-title');
        if (!container || !currentLabel || !this.state.material) return;

        const items = [{ id: 'main', title: this.state.material.title || 'Materi Utama', isMain: true }]
            .concat(this.state.subMaterials.map((s, idx) => {
                const cleanTitle = (s.title || '').replace(/^\d+[\.\s\-]+/, '');
                return {
                    id: String(s.id),
                    title: cleanTitle || `Episode ${idx + 1}`,
                    isMain: false
                };
            }));

        const activeItem = items.find(i => i.id === this.state.activeItemId) || items[0];
        currentLabel.textContent = activeItem.title;

        // Logic: Episode i terkunci jika episode i-1 belum selesai
        // Kecuali episode pertama (Main)
        let html = `
            <div class="syllabus-header mb-16">
                <h3>Daftar Episode</h3>
                <p class="text-muted small">${items.length} Bagian</p>
            </div>
            <div class="syllabus-list">
        `;

        let previousCompleted = true; // Main is always unlocked

        items.forEach((item, idx) => {
            const isCompleted = item.isMain ? !!this.state.material.is_completed : this.state.completedEpisodes.includes(item.id);
            const isActive = this.state.activeItemId === item.id;
            const isLocked = !previousCompleted && !item.isMain;

            html += `
                <button type="button" 
                    class="syllabus-item ${isActive ? 'active' : ''} ${isLocked ? 'locked' : ''} ${isCompleted ? 'completed' : ''}" 
                    data-syllabus-id="${item.id}"
                    ${isLocked ? 'disabled' : ''}>
                    <div class="syllabus-index">
                        ${isCompleted ? '<i data-lucide="check" class="icon-xs"></i>' : (isLocked ? '<i data-lucide="lock" class="icon-xs"></i>' : idx + 1)}
                    </div>
                    <div class="syllabus-info">
                        <span class="syllabus-title">${UI.escapeHtml(item.title)}</span>
                    </div>
                    ${isActive ? '<i data-lucide="play" class="active-play-icon"></i>' : ''}
                </button>
            `;

            // Update status untuk episode berikutnya
            // Jika ini Main, kita anggap selesai jika materi ditandai selesai
            // TAPI, agar user bisa lanjut ke Ep 1, kita anggap Main "selesai" untuk tujuan unlocking
            previousCompleted = isCompleted || item.isMain; 
        });

        container.innerHTML = html + '</div>';

        container.querySelectorAll('.syllabus-item:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                this.selectItem(btn.getAttribute('data-syllabus-id'));
                if (window.innerWidth <= 1024) window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        if (window.lucide) window.lucide.createIcons();
    },

    bindDropdown() {
        // Dropdown removed for cleaner UI
    },

    selectItem(itemId) {
        this.state.activeItemId = itemId;
        this.renderSyllabus();
        this.renderContent();
    },

    getActiveItem() {
        if (this.state.activeItemId === 'main') {
            return {
                id: 'main',
                title: this.state.material.title || 'Materi',
                content: this.state.material.content || '',
                video_url: this.state.material.video_url || '',
                document_url: '',
                isMain: true
            };
        }
        const selected = this.state.subMaterials.find(s => String(s.id) === String(this.state.activeItemId));
        return selected ? { ...selected, isMain: false } : {
            id: 'main',
            title: this.state.material.title || 'Materi',
            content: this.state.material.content || '',
            video_url: this.state.material.video_url || '',
            document_url: '',
            isMain: true
        };
    },

    toEmbedUrl(url) {
        if (!url) return '';
        if (url.includes('youtube.com/watch?v=')) {
            const id = url.split('v=')[1]?.split('&')[0];
            return id ? `https://www.youtube.com/embed/${id}` : url;
        }
        if (url.includes('youtu.be/')) {
            const id = url.split('youtu.be/')[1]?.split('?')[0];
            return id ? `https://www.youtube.com/embed/${id}` : url;
        }
        return url;
    },

    async renderComments(materialId) {
        try {
            const commentsRes = await API.getComments(materialId);
            const comments = commentsRes.data || [];
            const listHtml = comments.length === 0
                ? '<div class="empty-discussion"><i data-lucide="message-circle" class="icon-lg"></i><p>Jadilah yang pertama memulai diskusi!</p></div>'
                : comments.map(c => {
                    const initials = (c.full_name || c.username || '?')[0].toUpperCase();
                    const isAdmin = c.role === 'admin';
                    return `
                        <div class="comment-item">
                            <div class="comment-avatar ${isAdmin ? 'admin-avatar-bg' : ''}">${initials}</div>
                            <div class="comment-body">
                                <div class="comment-header">
                                    <span class="comment-author">${UI.escapeHtml(c.full_name || c.username)}</span>
                                    ${isAdmin ? '<span class="badge-admin">Mentor</span>' : ''}
                                    <span class="comment-time">• ${c.created_at ? new Date(c.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'}</span>
                                </div>
                                <div class="comment-text">${UI.escapeHtml(c.comment_text || '')}</div>
                            </div>
                        </div>
                    `;
                }).join('');

            return `
                <div class="content-card discussion-section">
                    <div class="discussion-header-main mb-24">
                        <div class="d-flex align-center gap-12">
                            <div class="discussion-icon-box"><i data-lucide="messages-square"></i></div>
                            <div>
                                <h3 class="mb-0">Forum Diskusi</h3>
                                <p class="text-muted small mb-0">${comments.length} Komentar</p>
                            </div>
                        </div>
                    </div>

                    <form id="material-comment-form" class="modern-comment-form mb-32">
                        <div class="input-with-btn">
                            <textarea id="material-comment-text" class="comment-textarea" placeholder="Tanyakan sesuatu atau bagikan insight kamu..." required></textarea>
                            <button type="submit" class="comment-submit-btn" title="Kirim Komentar">
                                <i data-lucide="send"></i>
                            </button>
                        </div>
                    </form>

                    <div class="comments-container">
                        ${listHtml}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error(error);
            return '<div class="content-card"><p class="text-muted text-center">Diskusi tidak dapat dimuat.</p></div>';
        }
    },

    async renderContent() {
        const container = document.getElementById('material-detail');
        if (!container || !this.state.material) return;

        const active = this.getActiveItem();
        const materialId = this.state.material.id;
        const embedVideo = this.toEmbedUrl(active.video_url || '');
        const hasQuiz = this.state.quiz && this.state.quiz.id;

        // Determine if there's a next episode
        const currentIndex = this.state.subMaterials.findIndex(s => String(s.id) === String(this.state.activeItemId));
        const nextEpisode = (currentIndex !== -1 && currentIndex < this.state.subMaterials.length - 1) 
            ? this.state.subMaterials[currentIndex + 1] 
            : null;

        const isCompleted = active.isMain ? !!this.state.material.is_completed : this.state.completedEpisodes.includes(String(active.id));

        let mediaHtml = '';
        if (embedVideo) {
            mediaHtml += `
                <div class="pdf-container mb-24">
                    <div class="pdf-header"><span class="pdf-title"><i data-lucide="play-circle"></i> Video Pembelajaran</span></div>
                    <iframe class="pdf-iframe" src="${UI.escapeHtml(embedVideo)}" title="Video materi" allowfullscreen allow="autoplay; encrypted-media" loading="lazy"></iframe>
                </div>
            `;
        }
        if (active.document_url) {
            mediaHtml += `
                <div class="pdf-container mb-24">
                    <div class="pdf-header"><span class="pdf-title"><i data-lucide="file-text"></i> Dokumen PDF</span></div>
                    <iframe class="pdf-iframe" src="/public/uploads/documents/${UI.escapeHtml(active.document_url)}" title="Dokumen materi" loading="lazy"></iframe>
                </div>
            `;
        }

        const showComments = !!embedVideo;
        
        container.innerHTML = `
            <article class="content-card">
                <div class="header-section mb-24">
                    <div class="d-flex justify-between align-center">
                        <span class="section-eyebrow">${active.isMain ? 'Pengenalan Materi' : 'Episode ' + (currentIndex + 2)}</span>
                        ${isCompleted ? '<span class="badge badge-success"><i data-lucide="check-circle" class="icon-xs"></i> Selesai</span>' : ''}
                    </div>
                    <h1>${UI.escapeHtml(active.title || 'Materi')}</h1>
                    <p class="text-muted">${UI.escapeHtml(this.state.material.description || '')}</p>
                </div>
                
                ${mediaHtml}

                <div class="mt-24 mb-24">
                    ${active.content || '<p class="text-muted">Konten materi belum tersedia.</p>'}
                </div>

                <div class="action-buttons pt-24 border-top">
                    ${nextEpisode ? `
                        <button type="button" id="next-material-btn" class="btn-primary">
                            Selesaikan & Lanjut ke Episode Berikutnya <i data-lucide="arrow-right"></i>
                        </button>
                    ` : (hasQuiz ? `
                        <button type="button" id="finish-to-quiz-btn" class="btn-accent">
                            <i data-lucide="clipboard-check"></i> Selesaikan & Kerjakan Kuis
                        </button>
                    ` : `
                        <button type="button" id="finish-material-btn" class="btn-primary">
                            <i data-lucide="check-circle-2"></i> Selesaikan Materi
                        </button>
                    `)}
                </div>
            </article>

            ${showComments ? `
                <div id="comments-section-container" class="mt-24">
                    <div class="content-card"><p class="text-muted">Memuat diskusi...</p></div>
                </div>
            ` : ''}
        `;

        // 2. Setup listeners
        const markCurrentComplete = async (silent = false) => {
            if (isCompleted) return true;
            try {
                const payload = { material_id: materialId };
                if (!active.isMain) payload.sub_material_id = active.id;
                await API.post('/materials/mark-completed', payload);
                
                // Update local state
                if (!active.isMain) this.state.completedEpisodes.push(String(active.id));
                else this.state.material.is_completed = true;
                
                if (!silent) UI.showNotification('Progres belajar disimpan!', 'success');
                return true;
            } catch (error) {
                console.error('Auto-complete error:', error);
                return false;
            }
        };

        const nextBtn = document.getElementById('next-material-btn');
        if (nextBtn && nextEpisode) {
            nextBtn.onclick = async () => {
                UI.showLoading();
                const success = await markCurrentComplete(true);
                UI.hideLoading();
                if (success) {
                    this.selectItem(String(nextEpisode.id));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };
        }

        const finishToQuizBtn = document.getElementById('finish-to-quiz-btn');
        if (finishToQuizBtn) {
            finishToQuizBtn.onclick = async () => {
                UI.showLoading();
                await markCurrentComplete(true);
                UI.hideLoading();
                window.location.hash = 'quiz-page';
            };
        }

        const finishBtn = document.getElementById('finish-material-btn');
        if (finishBtn) {
            finishBtn.onclick = async () => {
                UI.showLoading();
                await markCurrentComplete();
                UI.hideLoading();
                this.renderSyllabus();
                this.renderContent();
            };
        }

        if (window.lucide) window.lucide.createIcons();

        if (showComments) this.loadComments(materialId);
    },

    async loadComments(materialId) {
        const commentContainer = document.getElementById('comments-section-container');
        if (!commentContainer) return;

        try {
            const commentsBlock = await this.renderComments(materialId);
            commentContainer.innerHTML = commentsBlock;

            const commentForm = document.getElementById('material-comment-form');
            if (commentForm) {
                commentForm.onsubmit = async (e) => {
                    e.preventDefault();
                    const textEl = document.getElementById('material-comment-text');
                    const text = textEl?.value.trim();
                    if (!text) return;
                    try {
                        await API.addComment({ material_id: materialId, comment_text: text });
                        if (textEl) textEl.value = '';
                        await this.loadComments(materialId);
                        UI.showNotification('Komentar berhasil dikirim.', 'success');
                    } catch (error) {
                        UI.showNotification(error.message || 'Gagal mengirim komentar.', 'error');
                    }
                };
            }
            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error('Failed to load comments:', error);
            commentContainer.innerHTML = '<div class="content-card"><p class="text-muted">Diskusi tidak dapat dimuat.</p></div>';
        }
    },
};
