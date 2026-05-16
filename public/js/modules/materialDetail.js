/**
 * Belajaryuk - Material Detail Module
 */

import { API } from './api.js';
import { UI } from './ui.js';

export const MaterialDetail = {
    state: {
        material: null,
        subMaterials: [],
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
            
            // UX Fix: Sembunyikan episode yang tidak punya video sesuai permintaan
            const rawSubs = Array.isArray(material.sub_materials) ? material.sub_materials : [];
            this.state.subMaterials = rawSubs.filter(s => s.video_url && s.video_url.trim() !== '');
            
            this.state.quiz = quizRes.data || null;

            // Senior UX Fix: Jika materi utama tidak punya video tapi ada episode (sub-materi) yang valid,
            // otomatis pilih episode pertama agar user langsung melihat video.
            if (!material.video_url && this.state.subMaterials.length > 0) {
                this.state.activeItemId = String(this.state.subMaterials[0].id);
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

        const items = [{ id: 'main', title: this.state.material.title || 'Materi Utama' }]
            .concat(this.state.subMaterials.map((s, idx) => {
                // Bersihkan title dari awalan angka manual (misal "1. Judul" jadi "Judul")
                // karena kita sudah punya bubble nomor indeks sendiri
                const cleanTitle = (s.title || '').replace(/^\d+[\.\s\-]+/, '');
                return {
                    id: String(s.id),
                    title: cleanTitle || `Episode ${idx + 1}`
                };
            }));

        const activeItem = items.find(i => i.id === this.state.activeItemId) || items[0];
        currentLabel.textContent = activeItem.title;

        container.innerHTML = `
            <div class="syllabus-header mb-16">
                <h3>Daftar Episode</h3>
                <p class="text-muted small">${items.length} Bagian</p>
            </div>
            <div class="syllabus-list">
                ${items.map((item, idx) => `
                    <button type="button" class="syllabus-item ${this.state.activeItemId === item.id ? 'active' : ''}" data-syllabus-id="${item.id}">
                        <div class="syllabus-index">${idx + 1}</div>
                        <div class="syllabus-info">
                            <span class="syllabus-title">${UI.escapeHtml(item.title)}</span>
                        </div>
                        ${this.state.activeItemId === item.id ? '<i data-lucide="play" class="active-play-icon"></i>' : ''}
                    </button>
                `).join('')}
            </div>
        `;

        container.querySelectorAll('[data-syllabus-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.selectItem(btn.getAttribute('data-syllabus-id'));
                // Smooth scroll to top on mobile when switching
                if (window.innerWidth <= 1024) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
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
                title: this.state.material.title || 'Materi',
                content: this.state.material.content || '',
                video_url: this.state.material.video_url || '',
                document_url: '',
                isMain: true
            };
        }
        const selected = this.state.subMaterials.find(s => String(s.id) === String(this.state.activeItemId));
        return selected || {
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
                ? '<p class="text-muted">Belum ada diskusi untuk materi ini.</p>'
                : comments.map(c => `
                    <div class="quiz-history-card">
                        <div>
                            <h4 class="quiz-history-title">${UI.escapeHtml(c.full_name || c.username || 'Siswa')}</h4>
                            <p class="mb-0">${UI.escapeHtml(c.comment_text || '')}</p>
                        </div>
                        <span class="qhc-date">${c.created_at ? new Date(c.created_at).toLocaleString('id-ID') : '-'}</span>
                    </div>
                `).join('');

            return `
                <div class="content-card discussion-card">
                    <h3 class="discussion-title"><i data-lucide="messages-square"></i> Diskusi Materi</h3>
                    <form id="material-comment-form" class="comment-form">
                        <textarea id="material-comment-text" class="comment-textarea" placeholder="Tulis pertanyaan atau insight kamu..." required></textarea>
                        <button type="submit" class="btn-send"><i data-lucide="send"></i></button>
                    </form>
                    <div class="comments-list">${listHtml}</div>
                </div>
            `;
        } catch (error) {
            console.error(error);
            return '<div class="content-card discussion-card"><p class="text-muted">Diskusi belum tersedia.</p></div>';
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

        // 1. Render core content with NEW SEQUENCE: Title -> Video -> Content -> Actions
        const showComments = !!embedVideo;
        
        container.innerHTML = `
            <article class="content-card">
                <div class="header-section mb-24">
                    <span class="section-eyebrow">${embedVideo ? 'Video Pembelajaran' : 'Pengenalan Materi'}</span>
                    <h1>${UI.escapeHtml(active.title || 'Materi')}</h1>
                    <p class="text-muted">${UI.escapeHtml(this.state.material.description || '')}</p>
                </div>
                
                ${mediaHtml}

                <div class="mt-24 mb-24">
                    ${active.content || '<p class="text-muted">Konten materi belum tersedia.</p>'}
                </div>

                <div class="action-buttons pt-24 border-top">
                    <button type="button" id="mark-complete-btn" class="btn-primary">
                        <i data-lucide="check-circle-2"></i> Tandai Selesai
                    </button>
                    
                    ${nextEpisode ? `
                        <button type="button" id="next-material-btn" class="btn-outline">
                            Lanjut ke Episode Berikutnya <i data-lucide="arrow-right"></i>
                        </button>
                    ` : ''}

                    ${hasQuiz ? `
                        <button type="button" class="btn-accent" onclick="window.location.hash='quiz-page'">
                            <i data-lucide="clipboard-check"></i> Kerjakan Kuis
                        </button>
                    ` : ''}
                </div>
            </article>

            ${showComments ? `
                <div id="comments-section-container" class="mt-24">
                    <div class="content-card"><p class="text-muted">Memuat diskusi...</p></div>
                </div>
            ` : ''}
        `;

        // 2. Setup listeners
        const markBtn = document.getElementById('mark-complete-btn');
        if (markBtn) {
            markBtn.onclick = async () => {
                try {
                    await API.markMaterialCompleted(materialId);
                    UI.showNotification('Materi ditandai selesai.', 'success');
                } catch (error) {
                    UI.showNotification(error.message || 'Gagal menandai materi.', 'error');
                }
            };
        }

        const nextBtn = document.getElementById('next-material-btn');
        if (nextBtn && nextEpisode) {
            nextBtn.onclick = () => {
                this.selectItem(String(nextEpisode.id));
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        }

        if (window.lucide) window.lucide.createIcons();

        // 3. Load comments only if needed
        if (showComments) {
            this.loadComments(materialId);
        }
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
