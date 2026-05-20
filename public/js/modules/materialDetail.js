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
            
            // UX Fix: Tampilkan episode yang punya video ATAU dokumen PDF
            const rawSubs = Array.isArray(material.sub_materials) ? material.sub_materials : [];
            this.state.subMaterials = rawSubs.filter(s => 
                (s.video_url && s.video_url.trim() !== '') || 
                (s.document_url && s.document_url.trim() !== '')
            );
            
            this.state.quiz = quizRes.data || null;

            // Senior UX Logic: Selalu arahkan ke hal pertama yang BELUM selesai
            if (!this.state.material.is_completed) {
                this.state.activeItemId = 'main';
            } else if (this.state.subMaterials.length > 0) {
                const firstIncomplete = this.state.subMaterials.find(s => !this.state.completedEpisodes.includes(String(s.id)));
                this.state.activeItemId = firstIncomplete ? String(firstIncomplete.id) : 'main';
            } else {
                this.state.activeItemId = 'main';
            }

            // Initialize Mobile Tabs
            this.initTabs();

            this.renderSyllabus();
            this.renderContent();

            API.trackProgress(materialId).catch(() => {});
        } catch (error) {
            console.error('Material detail load error:', error);
            detailContainer.innerHTML = '<p class="text-danger">Gagal memuat detail materi.</p>';
            syllabusContainer.innerHTML = '<p class="text-muted">Tidak dapat memuat episode.</p>';
        }
    },

    initTabs() {
        const tabsContainer = document.getElementById('material-tabs');
        const shell = document.querySelector('.learning-detail-page-shell');
        if (!tabsContainer || !shell) return;

        // Default tab
        shell.setAttribute('data-active-tab', 'content');

        const buttons = tabsContainer.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.onclick = () => {
                const tab = btn.getAttribute('data-tab');
                shell.setAttribute('data-active-tab', tab);
                
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Smooth scroll to top when switching tabs
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        });
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

            let indexContent = '';
            if (isCompleted) {
                indexContent = '<i data-lucide="check" class="icon-xs"></i>';
            } else if (isLocked) {
                indexContent = '<i data-lucide="lock" class="icon-xs"></i>';
            } else if (item.isMain) {
                indexContent = '<i data-lucide="book-open" class="icon-xs"></i>';
            } else {
                indexContent = idx; // idx dimulai dari 1 untuk episode karena idx 0 adalah Main
            }

            html += `
                <button type="button" 
                    class="syllabus-item ${isActive ? 'active' : ''} ${isLocked ? 'locked' : ''} ${isCompleted ? 'completed' : ''}" 
                    data-syllabus-id="${item.id}"
                    ${isLocked ? 'disabled' : ''}>
                    <div class="syllabus-index">
                        ${indexContent}
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

    selectItem(itemId) {
        this.state.activeItemId = itemId;

        // Auto-switch to content tab on mobile after selecting an episode
        if (window.innerWidth <= 1024) {
            const shell = document.querySelector('.learning-detail-page-shell');
            const tabsContainer = document.getElementById('material-tabs');
            
            if (shell) shell.setAttribute('data-active-tab', 'content');
            if (tabsContainer) {
                tabsContainer.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.getAttribute('data-tab') === 'content');
                });
            }
        }

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
            : (active.isMain && this.state.subMaterials.length > 0 ? this.state.subMaterials[0] : null);

        const isCompleted = active.isMain ? !!this.state.material.is_completed : this.state.completedEpisodes.includes(String(active.id));

        let mediaHtml = '';
        if (embedVideo) {
            mediaHtml += `
                <div class="pdf-container mb-24">
                    <div class="pdf-header"><span class="pdf-title"><i data-lucide="play-circle"></i> Video Pembelajaran</span></div>
                    <iframe class="pdf-iframe" src="${UI.escapeHtml(embedVideo)}" title="Video materi" allowfullscreen allow="autoplay; encrypted-media" loading="lazy"></iframe>
                </div>
            `;
        } else if (active.isMain && this.state.subMaterials.length > 0) {
            // Tampilan "Peta Belajar" otomatis jika di halaman Pengenalan
            let roadmapHtml = this.state.subMaterials.map((s, i) => {
                const cleanRoadmapTitle = (s.title || '').replace(/^\d+[\.\s\-]+/, '');
                return `
                <div class="roadmap-item">
                    <div class="roadmap-num">${i + 1}</div>
                    <div class="roadmap-info">
                        <span class="roadmap-title">${UI.escapeHtml(cleanRoadmapTitle || s.title)}</span>
                    </div>
                </div>
                `;
            }).join('');

            mediaHtml = `
                <div class="roadmap-container mb-32">
                    <div class="roadmap-header">
                        <i data-lucide="map"></i>
                        <span>Apa yang akan kamu pelajari?</span>
                    </div>
                    <div class="roadmap-list">${roadmapHtml}</div>
                </div>
            `;
        }

        if (active.document_url) {
            mediaHtml += `
                <div class="pdf-container mb-24">
                    <div class="pdf-header">
                        <span class="pdf-title"><i data-lucide="file-text"></i> Dokumen PDF</span>
                        <a href="/public/uploads/documents/${UI.escapeHtml(active.document_url)}" target="_blank" class="btn-text-info btn-small" download>
                            <i data-lucide="download"></i> Download
                        </a>
                    </div>
                    <iframe class="pdf-iframe" src="/public/uploads/documents/${UI.escapeHtml(active.document_url)}" title="Dokumen materi" loading="lazy"></iframe>
                </div>
            `;
        }

        const showComments = true; // Senior UX: Discussion always active for collaboration
        
        container.innerHTML = `
            <article class="content-card">
                <div class="header-section mb-32">
                    <div class="d-flex justify-between align-center mb-12">
                        <span class="section-eyebrow">${active.isMain ? 'Ringkasan Modul' : 'Episode ' + (currentIndex + 1)}</span>
                        ${isCompleted ? '<span class="badge badge-success"><i data-lucide="check-circle" class="icon-xs"></i> Selesai</span>' : ''}
                    </div>
                    <h1 class="display-title">${UI.escapeHtml(active.title || 'Materi')}</h1>
                </div>
                
                ${mediaHtml}

                <div class="material-rich-content mb-32">
                    ${active.content || (active.isMain ? '<p class="text-muted">Selamat datang di modul ini! Silakan baca ringkasan di atas untuk mengetahui apa yang akan kita bahas.</p>' : '<p class="text-muted">Konten materi belum tersedia.</p>')}
                </div>

                <div class="action-buttons pt-24 border-top">
                    ${nextEpisode ? `
                        <button type="button" id="next-material-btn" class="btn-primary btn-lg">
                            ${active.isMain ? 'Mulai Belajar Sekarang' : 'Selesaikan & Lanjut Episode Berikutnya'} <i data-lucide="arrow-right"></i>
                        </button>
                    ` : (hasQuiz ? `
                        <button type="button" id="finish-to-quiz-btn" class="btn-accent btn-lg">
                            <i data-lucide="clipboard-check"></i> Selesaikan & Kerjakan Kuis
                        </button>
                    ` : `
                        <button type="button" id="finish-material-btn" class="btn-primary btn-lg">
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
        const maybeRedirectToMiniQuiz = async () => {
            if (active.isMain) return false;
            try {
                const quizRes = await API.getQuiz(materialId, active.id);
                const miniQuiz = quizRes?.data;
                if (!miniQuiz?.id) return false;

                const resultRes = await API.getUserQuizResults(miniQuiz.id).catch(() => ({ data: null }));
                const alreadyAttempted = !!resultRes?.data;
                if (alreadyAttempted) return false;

                localStorage.setItem('active_sub_material_id', String(active.id));
                const goQuiz = await UI.confirm(
                    'Ada kuis mini untuk episode ini. Kerjakan sekarang untuk membuka episode berikutnya?',
                    'Uji Pemahaman'
                );
                if (goQuiz) {
                    window.location.hash = 'quiz-page';
                    return true;
                }
            } catch (error) {
                console.error('Mini quiz check error:', error);
            }
            return false;
        };

        const markCurrentComplete = async (silent = false) => {
            if (isCompleted) {
                const redirected = await maybeRedirectToMiniQuiz();
                return !redirected;
            }
            try {
                const payload = { material_id: materialId };
                if (!active.isMain) payload.sub_material_id = active.id;
                await API.post('/materials/mark-completed', payload);
                
                // Update local state
                if (!active.isMain) {
                    if (!this.state.completedEpisodes.includes(String(active.id))) {
                        this.state.completedEpisodes.push(String(active.id));
                    }
                } else {
                    this.state.material.is_completed = true;
                }

                // Check mini quiz right after this episode is completed
                if (!active.isMain) {
                    const redirected = await maybeRedirectToMiniQuiz();
                    if (redirected) return false;
                }

                // Senior UX: Check if 100% completed to fire confetti
                const totalSteps = this.state.subMaterials.length + 1; // Subs + Main
                const completedSteps = this.state.completedEpisodes.length + (this.state.material.is_completed ? 1 : 0);
                
                if (completedSteps >= totalSteps) {
                    this.fireSuccessConfetti();
                }
                
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
                const proceed = await markCurrentComplete(true);
                UI.hideLoading();
                if (proceed) {
                    this.selectItem(String(nextEpisode.id));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };
        }

        const finishToQuizBtn = document.getElementById('finish-to-quiz-btn');
        if (finishToQuizBtn) {
            finishToQuizBtn.onclick = async () => {
                UI.showLoading();
                const proceed = await markCurrentComplete(true);
                UI.hideLoading();
                if (proceed) {
                    localStorage.removeItem('active_sub_material_id'); // Clear sub_id for Final Quiz
                    // Set active material ID explicitly to be sure
                    localStorage.setItem('active_material_id', materialId);
                    window.location.hash = 'quiz-page';
                }
            };
        }

        const finishBtn = document.getElementById('finish-material-btn');
        if (finishBtn) {
            finishBtn.onclick = async () => {
                UI.showLoading();
                const proceed = await markCurrentComplete();
                UI.hideLoading();
                if (proceed) {
                    this.renderSyllabus();
                    this.renderContent();
                }
            };
        }

        if (window.lucide) window.lucide.createIcons();

        if (showComments) this.loadComments(materialId);
    },

    fireSuccessConfetti() {
        if (typeof confetti !== 'function') return;
        
        const duration = 3 * 1000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10007 };

        const randomInRange = (min, max) => Math.random() * (max - min) + min;

        const interval = setInterval(function() {
            const timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            const particleCount = 50 * (timeLeft / duration);
            // since particles fall down, start a bit higher than random
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);

        UI.showNotification('🎉 Selamat! Kamu telah menyelesaikan modul ini!', 'success');
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
