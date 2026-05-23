/**
 * Belajaryuk - Admin Module
 * Consolidated and refactored for ES Modules
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Admin = {
    charts: {
        registration: null,
        category: null,
        reports: null
    },
    editors: {
        matQuill: null,
        submatQuill: null
    },
    currentSubMaterials: [],
    rafHandles: {},

    async load() {
        const userData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        if (userData) {
            const user = JSON.parse(userData);
            const adminNameEl = document.getElementById('admin-user-name');
            if (adminNameEl) adminNameEl.textContent = user.full_name || 'Admin';
        }

        this.setupFormListeners();
        this.setupTheme();
        this.switchTab('dashboard');
    },

    setupTheme() {
        const themeBtn = document.querySelector('.admin-topbar .theme-toggle-btn');
        if (!themeBtn) return;

        const updateIcon = (isDark) => {
            themeBtn.innerHTML = isDark ? '<i data-lucide="sun"></i>' : '<i data-lucide="moon"></i>';
            if (window.lucide) window.lucide.createIcons();
        };

        // Initial Theme State Sync
        const savedTheme = localStorage.getItem('belajaryuk_theme') || 'light';
        updateIcon(savedTheme === 'dark');
    },

    setupFormListeners() {
        const matForm = document.getElementById('create-material-form');
        if (matForm) matForm.onsubmit = (e) => this.handleSaveMaterial(e);

        const submatForm = document.getElementById('create-submaterial-form');
        if (submatForm) submatForm.onsubmit = (e) => this.handleSaveSubMaterial(e);

        const quizForm = document.getElementById('create-quiz-form');
        if (quizForm) quizForm.onsubmit = (e) => this.handleCreateQuiz(e);

        const qForm = document.getElementById('create-question-form');
        if (qForm) qForm.onsubmit = (e) => this.handleSaveQuestion(e);
    },

    switchTab(tabId) {
        const allTabs = ['dashboard', 'materi', 'pengguna', 'laporan', 'diskusi', 'profil', 'pengaturan'];
        allTabs.forEach(t => {
            document.getElementById(`btn-tab-${t}`)?.classList.remove('active');
            document.getElementById(`admin-tab-${t}`)?.classList.add('d-none');
        });

        if (tabId !== 'dashboard') {
            this.destroyCharts();
        }
        
        document.getElementById(`btn-tab-${tabId}`)?.classList.add('active');
        document.getElementById(`admin-tab-${tabId}`)?.classList.remove('d-none');
        
        const titles = {
            'dashboard': 'Ringkasan & Analitik',
            'materi': 'Kelola Materi',
            'pengguna': 'Data Siswa',
            'laporan': 'Laporan & Analitik Nilai',
            'diskusi': 'Moderasi Forum',
            'profil': 'Profil Administrator',
            'pengaturan': 'Pengaturan Platform'
        };
        const titleEl = document.getElementById('admin-page-title');
        if (titleEl) titleEl.innerText = titles[tabId] || 'Panel Admin';
        
        if (tabId === 'dashboard') this.loadDashboard();
        else if (tabId === 'materi') this.loadMaterials();
        else if (tabId === 'pengguna') this.loadUsers();
        else if (tabId === 'laporan') this.loadReports();
        else if (tabId === 'diskusi') this.loadDiscussions();
        else if (tabId === 'profil') this.loadProfile();
        else if (tabId === 'pengaturan') this.loadSettings();
        
        // Auto close sidebar on mobile
        this.toggleSidebar(false);
        
        if (window.lucide) window.lucide.createIcons();
    },

    toggleSidebar(show) {
        const sidebar = document.querySelector('.admin-sidebar');
        const layout = document.querySelector('.admin-layout');
        if (!sidebar || !layout) return;

        if (show) {
            sidebar.classList.add('is-active');
            layout.classList.add('sidebar-active');
        } else {
            sidebar.classList.remove('is-active');
            layout.classList.remove('sidebar-active');
        }
    },

    async loadProfile() {
        const container = document.getElementById('admin-profile-content');
        if (!container) return;

        try {
            const response = await API.getCurrentUser();
            const user = response.data || {};
            
            const initials = (user.full_name || user.username || '?')[0].toUpperCase();
            const avatarUrl = user.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/public/uploads/${user.avatar}`) : null;

            container.innerHTML = `
                <div class="profile-header-main mb-32">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar-large" id="admin-avatar-container">
                            ${avatarUrl ? `<img src="${UI.escapeHtml(avatarUrl)}" alt="Avatar">` : initials}
                        </div>
                        <button type="button" class="btn-change-avatar" id="btn-admin-change-avatar" title="Ubah Foto Profil">
                            <i data-lucide="camera"></i>
                        </button>
                        <input type="file" id="input-admin-avatar" class="d-none" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="profile-info-main">
                        <h2>${UI.escapeHtml(user.full_name || user.username)}</h2>
                        <p class="text-muted">Administrator • ${UI.escapeHtml(user.email)}</p>
                    </div>
                </div>

                <div class="profile-details-grid">
                    <div class="detail-group">
                        <label>Nama Lengkap</label>
                        <div class="detail-value">${UI.escapeHtml(user.full_name)}</div>
                    </div>
                    <div class="detail-group">
                        <label>Email</label>
                        <div class="detail-value">${UI.escapeHtml(user.email)}</div>
                    </div>
                    <div class="detail-group">
                        <label>Username</label>
                        <div class="detail-value">${UI.escapeHtml(user.username)}</div>
                    </div>
                    <div class="detail-group">
                        <label>Role</label>
                        <div class="detail-value">Administrator</div>
                    </div>
                </div>
            `;

            // Setup Admin Avatar Upload
            const btnChange = document.getElementById('btn-admin-change-avatar');
            const inputAvatar = document.getElementById('input-admin-avatar');
            if (btnChange && inputAvatar) {
                btnChange.onclick = () => inputAvatar.click();
                inputAvatar.onchange = async () => {
                    if (!inputAvatar.files || !inputAvatar.files[0]) return;
                    const formData = new FormData();
                    formData.append('avatar', inputAvatar.files[0]);
                    UI.showLoading();
                    try {
                        const res = await API.post('/auth/avatar', formData);
                        UI.showNotification(res.message, 'success');
                        this.loadProfile();
                        // Update name in topbar if needed
                        const adminNameEl = document.getElementById('admin-user-name');
                        if (adminNameEl) adminNameEl.textContent = user.full_name || 'Admin';
                    } catch (e) {
                        UI.showNotification(e.message || 'Gagal mengunggah foto.', 'error');
                    } finally {
                        UI.hideLoading();
                    }
                };
            }

            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error(error);
            container.innerHTML = '<p class="text-danger">Gagal memuat profil admin.</p>';
        }
    },

    // --- DASHBOARD ---
    async loadDashboard() {
        const quickActions = document.getElementById('admin-quick-actions');
        if (quickActions) {
            quickActions.innerHTML = `
                <button type="button" class="btn-p" onclick="Admin.switchTab('materi'); Admin.toggleMaterialForm(true)">
                    <i data-lucide="plus"></i>
                    <div><span>Tambah</span> Materi</div>
                </button>
                <button type="button" class="btn-outline" onclick="Admin.switchTab('pengguna')">
                    <i data-lucide="users"></i>
                    <div><span>Kelola</span> Siswa</div>
                </button>
            `;
            if (window.lucide) window.lucide.createIcons();
        }

        try {
            const [usersRes, materialsRes] = await Promise.all([
                API.get('/auth/users'),
                API.getMaterials(1, 100)
            ]);

            const allUsers = usersRes.data?.users || usersRes.data || [];
            const students = allUsers.filter(u => u.role === 'student');
            const materials = materialsRes.data?.materials || [];
            const studentsThisMonth = students.filter(u => {
                if (!u.created_at) return false;
                const joined = new Date(u.created_at);
                const now = new Date();
                return joined.getFullYear() === now.getFullYear() && joined.getMonth() === now.getMonth();
            }).length;

            this.animateNumber('admin-stat-users', 0, students.length, 800);
            this.animateNumber('admin-stat-materials', 0, materials.length, 800);
            this.animateNumber('admin-stat-recent', 0, studentsThisMonth, 800);
            
            // Populate Mini Lists
            this.renderRecentUsers(students.slice(0, 5));
            this.renderRecentMaterials(materials.slice(0, 5));
            await this.renderRecentComments();
            
            this.renderCharts(students, materials);
        } catch (error) { console.error(error); }
    },

    renderRecentUsers(users) {
        const container = document.getElementById('admin-recent-users');
        if (!container) return;
        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted">Belum ada siswa.</p>';
            return;
        }
        let html = '<div class="admin-mini-list">';
        users.forEach(u => {
            const initial = (u.full_name || u.username || '?')[0].toUpperCase();
            html += `
                <div class="admin-mini-item">
                    <div class="admin-avatar">${UI.escapeHtml(initial)}</div>
                    <div class="admin-mini-main">
                        <strong>${UI.escapeHtml(u.full_name || u.username)}</strong>
                        <span>${UI.escapeHtml(u.email)}</span>
                    </div>
                    <div class="text-muted small ml-auto desktop-only">
                        <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
                    </div>
                </div>`;
        });
        container.innerHTML = html + '</div>';
        if (window.lucide) window.lucide.createIcons();
    },

    renderRecentMaterials(materials) {
        const container = document.getElementById('admin-recent-materials');
        if (!container) return;
        if (materials.length === 0) {
            container.innerHTML = '<p class="text-muted">Belum ada materi.</p>';
            return;
        }
        let html = '<div class="admin-mini-list">';
        materials.forEach(m => {
            html += `
                <div class="admin-mini-item">
                    <span class="admin-course-mark"><i data-lucide="book-open"></i></span>
                    <div class="admin-mini-main">
                        <strong>${UI.escapeHtml(m.title)}</strong>
                        <span>${UI.escapeHtml(m.category || 'Umum')}</span>
                    </div>
                </div>`;
        });
        container.innerHTML = html + '</div>';
        if (window.lucide) window.lucide.createIcons();
    },

    async renderRecentComments() {
        const container = document.getElementById('admin-recent-comments');
        if (!container) return;

        try {
            const res = await API.getAllCommentsAdmin(5);
            const comments = res.data || [];
            if (comments.length === 0) {
                container.innerHTML = '<p class="text-muted">Belum ada diskusi terbaru.</p>';
                return;
            }

            let html = '<div class="admin-mini-list">';
            comments.forEach(c => {
                const author = c.full_name || c.username || 'Siswa';
                const initial = (author && author[0] ? author[0] : '?').toUpperCase();
                html += `
                    <div class="admin-mini-item">
                        <div class="admin-avatar admin-avatar-bg"><i data-lucide="message-square" style="width: 14px; height: 14px;"></i></div>
                        <div class="admin-mini-main">
                            <div class="d-flex align-center gap-8">
                                <strong>${UI.escapeHtml(author)}</strong>
                                <span class="text-muted small">• ${c.created_at ? new Date(c.created_at).toLocaleDateString() : ''}</span>
                            </div>
                            <span>${UI.escapeHtml(c.comment_text || '').slice(0, 80)}</span>
                        </div>
                    </div>`;
            });
            container.innerHTML = html + '</div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error(error);
            container.innerHTML = '<p class="text-muted">Gagal memuat diskusi terbaru.</p>';
        }
    },

    // --- MATERIALS ---
    async loadMaterials() {
        UI.showLoading();
        try {
            const res = await API.getMaterials(1, 100);
            const materials = res.data?.materials || res.data || [];
            const container = document.getElementById('admin-materials-table');
            if (!container) return;

            let html = '<div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>No</th><th>Judul</th><th>Kategori</th><th class="text-right">Aksi</th></tr></thead><tbody>';
            if (materials.length === 0) html += '<tr><td colspan="4" class="text-center">Kosong.</td></tr>';
            else {
                materials.forEach((m, i) => {
                    html += `
                        <tr>
                            <td class="text-muted">${i + 1}</td>
                            <td class="font-medium">${UI.escapeHtml(m.title)}</td>
                            <td><span class="badge badge-student">${UI.escapeHtml(m.category || 'Umum')}</span></td>
                            <td class="text-right">
                                <div class="d-flex justify-end gap-8">
                                    <button class="btn-outline btn-small" onclick="Admin.openQuizView(${m.id}, '${UI.escapeHtml(m.title)}')" title="Kelola Kuis"><i data-lucide="help-circle"></i> <span class="btn-label">Kuis</span></button>
                                    <button class="btn-outline btn-small" onclick="Admin.openSubMaterialView(${m.id}, '${UI.escapeHtml(m.title)}')" title="Kelola Episode"><i data-lucide="layers"></i> <span class="btn-label">Episode</span></button>
                                    <button class="btn-outline btn-small" onclick="Admin.handleEditMaterial(${m.id})" title="Edit Materi"><i data-lucide="edit-3"></i> <span class="btn-label">Edit</span></button>
                                    <button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteMaterial(${m.id})" title="Hapus Materi"><i data-lucide="trash-2"></i> <span class="btn-label">Hapus</span></button>
                                </div>
                            </td>
                        </tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    toggleMaterialForm(show) {
        document.getElementById('admin-list-view')?.classList.toggle('d-none', show);
        document.getElementById('admin-form-view')?.classList.toggle('d-none', !show);
        if (show) this.initQuill();
        else {
            document.getElementById('create-material-form')?.reset();
            document.getElementById('mat-id').value = '';
            if (this.editors.matQuill) this.editors.matQuill.setContents([]);
        }
    },

    async handleEditMaterial(id) {
        UI.showLoading();
        try {
            const res = await API.getMaterialDetail(id);
            const m = res.data?.material || res.data;
            document.getElementById('mat-id').value = m.id;
            document.getElementById('mat-title').value = m.title || '';
            document.getElementById('mat-category').value = m.category || '';
            document.getElementById('mat-difficulty').value = m.difficulty || 'beginner';
            document.getElementById('mat-duration').value = m.duration_minutes || 0;
            document.getElementById('mat-video').value = m.video_url || '';
            document.getElementById('mat-desc').value = m.description || '';
            this.toggleMaterialForm(true);
            document.getElementById('admin-form-title').innerText = 'Edit Materi: ' + m.title;
            if (this.editors.matQuill) this.editors.matQuill.clipboard.dangerouslyPasteHTML(m.content || '');
        } catch (error) { UI.showNotification(error.message, 'error'); } finally { UI.hideLoading(); }
    },

    async handleSaveMaterial(e) {
        e.preventDefault();
        const matId = document.getElementById('mat-id').value;
        const formData = new FormData(e.target);
        formData.append('content', this.editors.matQuill ? this.editors.matQuill.root.innerHTML : '');
        UI.showLoading();
        try {
            if (matId) await API.post(`/materials/update?id=${matId}`, formData);
            else await API.post('/materials/create', formData);
            UI.showNotification('Berhasil!', 'success');
            this.toggleMaterialForm(false);
            this.loadMaterials();
        } catch (error) { UI.showNotification(error.message, 'error'); } finally { UI.hideLoading(); }
    },

    async handleDeleteMaterial(id) {
        const confirm = await UI.confirm('Apakah Anda yakin ingin menghapus materi ini beserta seluruh isinya?', 'Hapus Materi', true);
        if (!confirm) return;
        
        UI.showLoading();
        try { await API.deleteMaterial(id); this.loadMaterials(); } catch (error) { UI.showNotification(error.message, 'error'); } finally { UI.hideLoading(); }
    },

    // --- EPISODES ---
    async openSubMaterialView(materialId, title) {
        document.getElementById('admin-list-view').classList.add('d-none');
        document.getElementById('admin-submaterial-view').classList.remove('d-none');
        document.getElementById('admin-submat-title').innerText = `Episode: ${title}`;
        document.getElementById('submat-material-id').value = materialId;
        this.loadSubMaterials(materialId);
    },

    async loadSubMaterials(materialId) {
        UI.showLoading();
        try {
            const res = await API.getSubMaterialsAdmin(materialId);
            this.currentSubMaterials = res.data || [];
            const container = document.getElementById('admin-submaterials-table');
            let html = '<div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Part</th><th>Judul</th><th class="text-right">Aksi</th></tr></thead><tbody>';
            if (this.currentSubMaterials.length === 0) html += '<tr><td colspan="3" class="text-center">Kosong.</td></tr>';
            else {
                this.currentSubMaterials.forEach((s, i) => {
                    html += `<tr><td class="text-muted">${i+1}</td><td class="font-medium">${UI.escapeHtml(s.title)}</td><td class="text-right">
                        <div class="d-flex justify-end gap-8">
                            <button class="btn-outline btn-small" onclick="Admin.handleEditSubMat(${s.id})" title="Edit Episode"><i data-lucide="edit-3"></i><span class="btn-label">Edit</span></button>
                            <button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteSubMat(${s.id}, ${materialId})" title="Hapus Episode"><i data-lucide="trash-2"></i><span class="btn-label">Hapus</span></button>
                        </div>
                    </td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    handleEditSubMat(id) {
        const s = this.currentSubMaterials.find(x => x.id == id);
        if (!s) return;
        document.getElementById('admin-submaterial-form-container').classList.remove('d-none');
        document.getElementById('submat-id').value = s.id;
        document.getElementById('submat-title').value = s.title || '';
        document.getElementById('submat-video').value = s.video_url || '';
        this.initQuill();
        if (this.editors.submatQuill) this.editors.submatQuill.clipboard.dangerouslyPasteHTML(s.content || '');
    },

    async handleSaveSubMaterial(e) {
        e.preventDefault();
        const matId = document.getElementById('submat-material-id').value;
        const subId = document.getElementById('submat-id').value;
        const formData = new FormData(e.target);
        formData.append('content', this.editors.submatQuill ? this.editors.submatQuill.root.innerHTML : '');
        UI.showLoading();
        try {
            if (subId) await API.post(`/materials/sub/update?id=${subId}`, formData);
            else await API.post('/materials/sub/create', formData);
            UI.showNotification('Berhasil!', 'success');
            document.getElementById('admin-submaterial-form-container').classList.add('d-none');
            this.loadSubMaterials(matId);
        } catch (error) { UI.showNotification(error.message, 'error'); } finally { UI.hideLoading(); }
    },

    async handleDeleteSubMat(id, matId) {
        const confirmed = await UI.confirm('Hapus episode ini?', 'Konfirmasi Hapus', true);
        if (!confirmed) return;
        UI.showLoading();
        try { await API.deleteSubMaterial(id); this.loadSubMaterials(matId); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    // --- QUIZ (REFACTORED FOR MULTI-TIER) ---
    async openQuizView(materialId, title) {
        document.getElementById('admin-list-view').classList.add('d-none');
        document.getElementById('admin-quiz-view').classList.remove('d-none');
        document.getElementById('admin-quiz-title').innerText = `Kuis: ${title}`;
        document.getElementById('quiz-material-id').value = materialId;
        
        this.hideQuizForm();
        document.getElementById('admin-quiz-manage').classList.add('d-none');
        
        await this.loadQuizList(materialId);
    },

    async loadQuizList(materialId) {
        const container = document.getElementById('admin-quiz-list-container');
        if (!container) return;
        
        container.innerHTML = '<div class="skeleton-box" style="height: 100px;"></div>';
        
        try {
            const res = await API.getQuizzesAdmin(materialId);
            const quizzes = res.data || [];
            
            if (quizzes.length === 0) {
                container.innerHTML = `
                    <div class="empty-state p-24 border dashed rounded-16 text-center">
                        <p class="text-muted mb-12">Materi ini belum memiliki kuis.</p>
                        <button type="button" class="btn-outline btn-small" onclick="Admin.showCreateQuizForm()">Buat Kuis Pertama</button>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="admin-table-wrapper mt-16">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Judul Kuis</th>
                                <th>Konteks</th>
                                <th>Soal</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            quizzes.forEach(q => {
                const typeLabel = q.quiz_type === 'mini' ? '<span class="badge badge-accent">Mini</span>' : '<span class="badge badge-primary">Final</span>';
                const context = q.quiz_type === 'mini' ? `Episode: ${UI.escapeHtml(q.sub_material_title || 'Unknown')}` : 'Modul Utama';
                
                html += `
                    <tr>
                        <td>${typeLabel}</td>
                        <td class="font-bold">${UI.escapeHtml(q.title)}</td>
                        <td class="text-muted small">${context}</td>
                        <td>${q.total_questions} Soal</td>
                        <td class="text-right">
                            <div class="d-flex justify-end gap-8">
                                <button class="btn-outline btn-small" onclick="Admin.manageQuizQuestions(${q.id}, '${UI.escapeHtml(q.title)}')" title="Kelola Soal"><i data-lucide="list"></i><span class="btn-label">Soal</span></button>
                                <button class="btn-outline btn-small" onclick="Admin.editQuizConfig(${q.id})" title="Pengaturan Kuis"><i data-lucide="settings"></i><span class="btn-label">Setting</span></button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            container.innerHTML = html + '</tbody></table></div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error(error);
            container.innerHTML = '<p class="text-danger">Gagal memuat daftar kuis.</p>';
        }
    },

    async showCreateQuizForm() {
        const matId = document.getElementById('quiz-material-id').value;
        const form = document.getElementById('create-quiz-form');
        form.reset();
        document.getElementById('quiz-id-edit').value = '';
        document.getElementById('admin-quiz-setup-title').innerText = 'Buat Kuis Baru';
        
        // Load episodes for the dropdown
        const subRes = await API.getSubMaterialsAdmin(matId);
        const subs = subRes.data || [];
        const subSelect = document.getElementById('quiz-sub-material-id');
        subSelect.innerHTML = subs.map(s => `<option value="${s.id}">${UI.escapeHtml(s.title)}</option>`).join('');
        
        document.getElementById('admin-quiz-setup').classList.remove('d-none');
        document.getElementById('admin-quiz-list-container').classList.add('d-none');
        document.getElementById('admin-quiz-manage').classList.add('d-none');
        this.handleQuizTypeChange();
    },

    hideQuizForm() {
        document.getElementById('admin-quiz-setup').classList.add('d-none');
        document.getElementById('admin-quiz-list-container').classList.remove('d-none');
    },

    handleQuizTypeChange() {
        const type = document.getElementById('quiz-type').value;
        const group = document.getElementById('sub-material-select-group');
        group.classList.toggle('d-none', type !== 'mini');
    },

    async editQuizConfig(quizId) {
        UI.showLoading();
        try {
            // Fetch all quizzes for this material to find the one to edit
            const matId = document.getElementById('quiz-material-id').value;
            const res = await API.getQuizzesAdmin(matId);
            const quizzes = res.data || [];
            const q = quizzes.find(x => x.id == quizId);
            
            if (!q) throw new Error('Kuis tidak ditemukan.');

            const form = document.getElementById('create-quiz-form');
            form.reset();
            document.getElementById('quiz-id-edit').value = q.id;
            document.getElementById('quiz-type').value = q.quiz_type;
            document.getElementById('quiz-title').value = q.title;
            document.getElementById('quiz-desc').value = q.description || '';
            document.getElementById('quiz-passing').value = q.passing_score;
            document.getElementById('quiz-time').value = q.time_limit_minutes || 0;

            // Load episodes for the dropdown
            const subRes = await API.getSubMaterialsAdmin(matId);
            const subs = subRes.data || [];
            const subSelect = document.getElementById('quiz-sub-material-id');
            subSelect.innerHTML = subs.map(s => `<option value="${s.id}" ${s.id == q.sub_material_id ? 'selected' : ''}>${UI.escapeHtml(s.title)}</option>`).join('');
            
            this.handleQuizTypeChange();
            
            document.getElementById('admin-quiz-setup-title').innerText = 'Edit Pengaturan Kuis';
            document.getElementById('admin-quiz-setup').classList.remove('d-none');
            document.getElementById('admin-quiz-list-container').classList.add('d-none');
            document.getElementById('admin-quiz-manage').classList.add('d-none');

        } catch (error) {
            UI.showNotification(error.message, 'error');
        } finally {
            UI.hideLoading();
        }
    },

    async handleCreateQuiz(e) {
        e.preventDefault();
        const matId = document.getElementById('quiz-material-id').value;
        const quizId = document.getElementById('quiz-id-edit').value;
        
        const data = {
            material_id: matId,
            quiz_id: quizId,
            quiz_type: document.getElementById('quiz-type').value,
            sub_material_id: document.getElementById('quiz-type').value === 'mini' ? document.getElementById('quiz-sub-material-id').value : null,
            title: document.getElementById('quiz-title').value,
            description: document.getElementById('quiz-desc').value,
            passing_score: document.getElementById('quiz-passing').value,
            time_limit_minutes: document.getElementById('quiz-time').value
        };

        UI.showLoading();
        try {
            // NOTE: Current API only supports create. Update would need a new endpoint.
            // For now we assume create.
            await API.post('/quiz/create', data);
            UI.showNotification('Kuis berhasil disimpan!', 'success');
            this.hideQuizForm();
            this.loadQuizList(matId);
        } catch (error) {
            UI.showNotification(error.message, 'error');
        } finally {
            UI.hideLoading();
        }
    },

    async manageQuizQuestions(quizId, title) {
        document.getElementById('admin-quiz-list-container').classList.add('d-none');
        document.getElementById('admin-quiz-manage').classList.remove('d-none');
        document.getElementById('active-quiz-title').innerText = title;
        document.getElementById('q-quiz-id').value = quizId;
        
        this.loadQuestions(quizId);
    },

    backToQuizList() {
        const matId = document.getElementById('quiz-material-id').value;
        document.getElementById('admin-quiz-manage').classList.add('d-none');
        document.getElementById('admin-quiz-list-container').classList.remove('d-none');
        this.loadQuizList(matId);
    },

    async loadQuestions(quizId) {
        const container = document.getElementById('admin-questions-table');
        container.innerHTML = '<div class="skeleton-box" style="height: 100px;"></div>';
        
        try {
            const res = await API.getQuizQuestions(quizId);
            const qs = res.data || [];
            let html = '<div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>No</th><th>Soal</th><th class="text-right">Aksi</th></tr></thead><tbody>';
            
            if (qs.length === 0) html += '<tr><td colspan="3" class="text-center p-24 text-muted">Belum ada soal. Klik Tambah Soal untuk memulai.</td></tr>';
            else {
                qs.forEach((q, i) => {
                    html += `<tr><td class="text-muted">${i+1}</td><td>${UI.escapeHtml(q.question_text)}</td><td class="text-right"><button class="btn-outline btn-text-danger btn-small" onclick="Admin.deleteQuestion(${q.id}, ${quizId})" title="Hapus Soal"><i data-lucide="trash-2"></i><span class="btn-label">Hapus</span></button></td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) { 
            console.error(error);
            container.innerHTML = '<p class="text-danger">Gagal memuat soal.</p>';
        }
    },

    async handleSaveQuestion(e) {
        e.preventDefault();
        const quizId = document.getElementById('q-quiz-id').value;
        const data = {
            quiz_id: quizId,
            question_text: document.getElementById('q-text').value,
            opt_a: document.getElementById('q-opt-a').value,
            opt_b: document.getElementById('q-opt-b').value,
            opt_c: document.getElementById('q-opt-c').value,
            opt_d: document.getElementById('q-opt-d').value,
            correct_opt: document.getElementById('q-correct').value
        };
        UI.showLoading();
        try { 
            await API.post('/quiz/questions/add', data); 
            document.getElementById('admin-question-form-container').classList.add('d-none'); 
            e.target.reset();
            this.loadQuestions(quizId); 
            UI.showNotification('Soal ditambahkan!', 'success');
        } catch (error) { 
            UI.showNotification(error.message, 'error'); 
        } finally { 
            UI.hideLoading(); 
        }
    },

    async deleteQuestion(id, qid) {
        const confirmed = await UI.confirm('Hapus soal ini?', 'Konfirmasi Hapus', true);
        if (!confirmed) return;
        UI.showLoading();
        try { 
            await API.post('/quiz/questions/delete', { id }); 
            this.loadQuestions(qid); 
            UI.showNotification('Soal dihapus', 'success');
        } catch (error) { 
            console.error(error); 
        } finally { 
            UI.hideLoading(); 
        }
    },

    async handleDeleteQuiz() {
        const confirmed = await UI.confirm('Hapus seluruh kuis ini beserta semua soal dan hasil nilainya? Tindakan ini tidak bisa dibatalkan.', 'Hapus Kuis', true);
        if (!confirmed) return;
        const qid = document.getElementById('q-quiz-id').value;
        UI.showLoading();
        try { 
            await API.post('/quiz/delete', { id: qid }); 
            UI.showNotification('Kuis dihapus', 'success');
            this.backToQuizList();
        } catch (error) { 
            UI.showNotification(error.message, 'error'); 
        } finally { 
            UI.hideLoading(); 
        }
    },

    // --- USERS ---
    async loadUsers() {
        UI.showLoading();
        try {
            const res = await API.getAllUsers();
            const allUsers = (res.data?.users || res.data || []).filter(u => u.role === 'student');
            const container = document.getElementById('admin-users-table');
            if (!container) return;

            // Creative Initiative: Live Search for Students
            container.innerHTML = `
                <div class="mb-16">
                    <div class="input-with-icon">
                        <i data-lucide="search"></i>
                        <input type="text" id="student-search" placeholder="Cari nama atau username siswa..." class="admin-search-input">
                    </div>
                </div>
                <div id="student-list-container"></div>
            `;

            this.renderStudentList(allUsers);

            const searchInput = document.getElementById('student-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    const filtered = allUsers.filter(u => 
                        (u.full_name || '').toLowerCase().includes(query) || 
                        (u.username || '').toLowerCase().includes(query) ||
                        (u.email || '').toLowerCase().includes(query)
                    );
                    this.renderStudentList(filtered);
                });
            }

            if (window.lucide) window.lucide.createIcons();
        } catch (error) { 
            console.error(error); 
        } finally { 
            UI.hideLoading(); 
        }
    },

    renderStudentList(users) {
        const container = document.getElementById('student-list-container');
        if (!container) return;

        let html = '<div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>No</th><th>Siswa</th><th>Email</th><th class="text-right">Aksi</th></tr></thead><tbody>';
        if (users.length === 0) {
            html += '<tr><td colspan="4" class="text-center">Siswa tidak ditemukan.</td></tr>';
        } else {
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
            users.forEach((u, i) => {
                const color = colors[u.id % colors.length];
                html += `
                    <tr>
                        <td class="text-muted">${i + 1}</td>
                        <td>
                            <div class="d-flex align-center gap-12">
                                <div class="admin-avatar" style="background: ${color}20; color: ${color}; border-color: ${color}40">
                                    ${UI.escapeHtml((u.full_name || u.username || '?')[0].toUpperCase())}
                                </div>
                                <div>
                                    <div class="font-bold">${UI.escapeHtml(u.full_name || u.username)}</div>
                                    <div class="text-muted small">@${UI.escapeHtml(u.username)}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="text-muted">${UI.escapeHtml(u.email)}</span></td>
                        <td class="text-right">
                            <button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteUser(${u.id})" title="Hapus Siswa"><i data-lucide="user-minus"></i><span class="btn-label">Hapus</span></button>
                        </td>
                    </tr>`;
            });
        }
        container.innerHTML = html + '</tbody></table></div>';
        if (window.lucide) window.lucide.createIcons();
    },

    async handleDeleteUser(id) {
        const confirmed = await UI.confirm('Hapus akun siswa secara permanen? Tindakan ini tidak bisa dibatalkan.', 'Hapus Siswa', true);
        if (!confirmed) return;
        UI.showLoading();
        try { await API.post('/auth/users/delete', { user_id: id }); this.loadUsers(); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    // --- OTHERS ---
    async loadDiscussions() {
        try {
            const res = await API.getAllCommentsAdmin(50);
            const comments = res.data || [];
            const container = document.getElementById('admin-tab-diskusi');
            if (!container) return;
            let html = '<div class="content-card"><h3>Moderasi Forum</h3><p class="text-muted mb-16">Pantau komentar siswa dan hapus konten yang tidak sesuai.</p><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Siswa</th><th>Komentar</th><th class="text-right">Aksi</th></tr></thead><tbody>';
            if (comments.length === 0) html += '<tr><td colspan="3" class="text-center">Kosong.</td></tr>';
            else {
                comments.forEach(c => {
                    html += `<tr><td class="font-bold">${UI.escapeHtml(c.username)}</td><td>${UI.escapeHtml(c.comment_text)}</td>
                    <td class="text-right"><button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteComment(${c.id})" title="Hapus Komentar"><i data-lucide="trash-2"></i><span class="btn-label">Hapus</span></button></td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div></div>';
            if (window.lucide) window.lucide.createIcons();
        } catch (error) { console.error(error); }
    },

    async loadReports() {
        const container = document.getElementById('admin-tab-laporan');
        if (!container) return;

        container.innerHTML = '<div class="content-card"><div class="skeleton-box" style="height: 220px;"></div></div>';
        try {
            const res = await API.getAdminQuizReport();
            const data = res.data || {};
            const summary = data.summary || {};
            const perQuiz = data.per_quiz || [];
            const recent = data.recent_results || [];
            const topQuiz = perQuiz.reduce((best, row) => Number(row.avg_score || 0) > Number(best.avg_score || 0) ? row : best, {});
            const totalPassed = perQuiz.reduce((sum, row) => sum + Number(row.passed_count || 0), 0);
            const averageAttempts = perQuiz.length > 0
                ? Math.round(perQuiz.reduce((sum, row) => sum + Number(row.attempts || 0), 0) / perQuiz.length)
                : 0;

            let html = `
                <div class="stats-grid admin-primary-metrics mb-16">
                    <div class="stat-card">
                        <div class="stat-card-row">
                            <div><h3>Total Percobaan</h3><div class="value">${Number(summary.total_attempts || 0)}</div></div>
                            <div class="stat-chip accent-blue"><i data-lucide="activity"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-row">
                            <div><h3>Rata-rata Nilai</h3><div class="value">${Math.round(Number(summary.avg_score || 0))}%</div></div>
                            <div class="stat-chip accent-green"><i data-lucide="line-chart"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-row">
                            <div><h3>Nilai Tertinggi</h3><div class="value">${Math.round(Number(summary.highest_score || 0))}%</div></div>
                            <div class="stat-chip accent-orange"><i data-lucide="trophy"></i></div>
                        </div>
                    </div>
                </div>
                <div class="admin-dashboard-grid mb-16">
                    <div class="content-card chart-card">
                        <h3>Tren Nilai per Kuis</h3>
                        <canvas id="adminReportsChart"></canvas>
                    </div>
                    <div class="content-card">
                        <h3>Insight Cepat</h3>
                        <div class="admin-mini-list">
                            <div class="admin-mini-item">
                                <span class="admin-course-mark accent-orange-bg"><i data-lucide="award"></i></span>
                                <div class="admin-mini-main">
                                    <strong>${UI.escapeHtml(topQuiz.quiz_title || 'Belum ada data')}</strong>
                                    <span>Kuis dengan rata-rata tertinggi (${Math.round(Number(topQuiz.avg_score || 0))}%)</span>
                                </div>
                            </div>
                            <div class="admin-mini-item">
                                <span class="admin-course-mark accent-green-bg"><i data-lucide="check-circle-2"></i></span>
                                <div class="admin-mini-main">
                                    <strong>${totalPassed}</strong>
                                    <span>Total kelulusan dari seluruh kuis</span>
                                </div>
                            </div>
                            <div class="admin-mini-item">
                                <span class="admin-course-mark accent-blue-bg"><i data-lucide="users"></i></span>
                                <div class="admin-mini-main">
                                    <strong>${averageAttempts}</strong>
                                    <span>Rata-rata percobaan per kuis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-card mb-16">
                    <h3>Performa per Kuis</h3>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead><tr><th>Kuis</th><th>Materi</th><th>Percobaan</th><th>Rata-rata</th><th>Lulus</th></tr></thead>
                            <tbody>
            `;

            if (perQuiz.length === 0) {
                html += '<tr><td colspan="5" class="text-center">Belum ada data kuis.</td></tr>';
            } else {
                perQuiz.forEach(row => {
                    html += `
                        <tr>
                            <td>${UI.escapeHtml(row.quiz_title || '-')}</td>
                            <td>${UI.escapeHtml(row.material_title || '-')}</td>
                            <td>${Number(row.attempts || 0)}</td>
                            <td>${Math.round(Number(row.avg_score || 0))}%</td>
                            <td>${Number(row.passed_count || 0)}</td>
                        </tr>
                    `;
                });
            }

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="content-card">
                    <h3>Hasil Kuis Terbaru</h3>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead><tr><th>Siswa</th><th>Kuis</th><th>Materi</th><th>Skor</th><th>Waktu</th></tr></thead>
                            <tbody>
            `;

            if (recent.length === 0) {
                html += '<tr><td colspan="5" class="text-center">Belum ada hasil kuis.</td></tr>';
            } else {
                recent.slice(0, 20).forEach(row => {
                    const name = row.full_name || row.username || 'Siswa';
                    const time = row.submitted_at ? new Date(row.submitted_at).toLocaleString('id-ID') : '-';
                    html += `
                        <tr>
                            <td>${UI.escapeHtml(name)}</td>
                            <td>${UI.escapeHtml(row.quiz_title || '-')}</td>
                            <td>${UI.escapeHtml(row.material_title || '-')}</td>
                            <td>${Math.round(Number(row.percentage || 0))}%</td>
                            <td>${time}</td>
                        </tr>
                    `;
                });
            }

            html += '</tbody></table></div></div>';
            container.innerHTML = html;
            this.renderReportsChart(perQuiz);
            if (window.lucide) window.lucide.createIcons();
        } catch (error) {
            console.error(error);
            container.innerHTML = '<div class="content-card"><p class="text-danger">Gagal memuat laporan kuis.</p></div>';
        }
    },

    async handleDeleteComment(id) {
        const confirmed = await UI.confirm('Hapus komentar ini?', 'Konfirmasi Hapus', true);
        if (!confirmed) return;
        UI.showLoading();
        try { await API.post('/materials/comments/delete', { id }); this.loadDiscussions(); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async loadSettings() {
        const container = document.getElementById('admin-tab-pengaturan');
        if (!container) return;
        container.innerHTML = `
            <div class="content-card">
                <h3>Pengaturan Platform</h3>
                <p class="text-muted mb-12">Ringkasan konfigurasi sistem yang aktif saat ini.</p>
                <div class="admin-mini-list">
                    <div class="admin-mini-item">
                        <span class="admin-course-mark"><i data-lucide="server"></i></span>
                        <div class="admin-mini-main">
                            <strong>API Endpoint</strong>
                            <span>${UI.escapeHtml(Config.API_BASE_URL)}</span>
                        </div>
                    </div>
                    <div class="admin-mini-item">
                        <span class="admin-course-mark"><i data-lucide="shield-check"></i></span>
                        <div class="admin-mini-main">
                            <strong>Mode Operasional</strong>
                            <span>Konfigurasi produksi aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        if (window.lucide) window.lucide.createIcons();
    },

    async generateCourseWithAI() {
        const topic = await UI.prompt("Masukkan topik materi yang ingin dibuat oleh AI (misal: 'Dasar Pemrograman Go')", "Misal: React Context API", "Buat Materi dengan AI");
        if (!topic) return;
        UI.showLoading();
        UI.showNotification('AI sedang menyusun materi...', 'info');
        try {
            const res = await API.post('/ai/generate-course', { topic });
            const d = res.data?.course || res.data;
            document.getElementById('mat-title').value = d.title;
            document.getElementById('mat-category').value = d.category;
            document.getElementById('mat-desc').value = d.description;
            this.initQuill();
            if (this.editors.matQuill) this.editors.matQuill.clipboard.dangerouslyPasteHTML(d.content || '');
            UI.showNotification('Selesai!', 'success');
        } catch (error) { UI.showNotification('Gagal.', 'error'); } finally { UI.hideLoading(); }
    },

    initQuill() {
        if (typeof Quill === 'undefined') return;
        const options = { theme: 'snow', modules: { toolbar: [['bold', 'italic'], ['link', 'code-block'], [{ 'list': 'ordered'}, { 'list': 'bullet' }]] } };
        if (document.getElementById('mat-content-editor') && !this.editors.matQuill) this.editors.matQuill = new Quill('#mat-content-editor', options);
        if (document.getElementById('submat-content-editor') && !this.editors.submatQuill) this.editors.submatQuill = new Quill('#submat-content-editor', options);
    },

    animateNumber(id, start, end, duration) {
        const obj = document.getElementById(id);
        if (!obj) return;

        if (this.rafHandles[id]) {
            window.cancelAnimationFrame(this.rafHandles[id]);
        }
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                this.rafHandles[id] = window.requestAnimationFrame(step);
            } else {
                delete this.rafHandles[id];
            }
        };
        this.rafHandles[id] = window.requestAnimationFrame(step);
    },

    destroyCharts() {
        if (this.charts.registration) {
            this.charts.registration.destroy();
            this.charts.registration = null;
        }
        if (this.charts.category) {
            this.charts.category.destroy();
            this.charts.category = null;
        }
        if (this.charts.reports) {
            this.charts.reports.destroy();
            this.charts.reports = null;
        }
    },

    renderReportsChart(perQuiz) {
        if (typeof Chart === 'undefined') return;
        const canvas = document.getElementById('adminReportsChart');
        if (!canvas) return;

        if (this.charts.reports) {
            this.charts.reports.destroy();
        }

        // Sort by attempts DESC and take top 12 for better readability
        const sortedData = [...perQuiz]
            .filter(row => Number(row.attempts) > 0)
            .sort((a, b) => Number(b.attempts) - Number(a.attempts))
            .slice(0, 12);

        const labels = sortedData.map(row => {
            const title = row.quiz_title || 'Kuis';
            return title.length > 20 ? title.substring(0, 17) + '...' : title;
        });
        const scores = sortedData.map(row => Math.round(Number(row.avg_score || 0)));
        const attempts = sortedData.map(row => Number(row.attempts || 0));

        if (labels.length === 0) {
            // Show empty state in canvas parent
            const ctx = canvas.getContext('2d');
            ctx.font = '14px Inter';
            ctx.fillStyle = '#94a3b8';
            ctx.textAlign = 'center';
            ctx.fillText('Belum ada data aktivitas kuis.', canvas.width / 2, canvas.height / 2);
            return;
        }

        this.charts.reports = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Rata-rata Nilai (%)',
                        data: scores,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Jumlah Percobaan',
                        data: attempts,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        hoverBackgroundColor: '#3b82f6',
                        borderRadius: 6,
                        maxBarThickness: 40,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        max: 100,
                        grid: { borderDash: [5, 5], color: '#e2e8f0' },
                        ticks: { color: '#64748b', font: { size: 11 } },
                        title: { display: true, text: 'Rata-rata Skor (%)', color: '#64748b', font: { weight: 'bold', size: 10 } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#64748b', font: { size: 11 }, precision: 0 },
                        title: { display: true, text: 'Total Percobaan', color: '#64748b', font: { weight: 'bold', size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 10 }, maxRotation: 45, minRotation: 45 }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '500' }, padding: 20 }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: (context) => {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                label += context.parsed.y + (context.datasetIndex === 0 ? '%' : ' kali');
                                return label;
                            }
                        }
                    }
                }
            }
        });
    },

    buildRegistrationTrend(students, monthCount = 6) {
        const now = new Date();
        const monthBuckets = [];
        for (let i = monthCount - 1; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            monthBuckets.push({
                key: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
                date: d,
                label: d.toLocaleString('id-ID', { month: 'short', year: '2-digit' })
            });
        }

        const counts = Object.fromEntries(monthBuckets.map(b => [b.key, 0]));
        const rangeStart = monthBuckets[0]?.date || new Date(now.getFullYear(), now.getMonth(), 1);
        let baselineTotal = 0;

        students.forEach(student => {
            if (!student.created_at) return;
            const createdAt = new Date(student.created_at);
            if (Number.isNaN(createdAt.getTime())) return;

            if (createdAt < rangeStart) {
                baselineTotal++;
                return;
            }

            const key = `${createdAt.getFullYear()}-${String(createdAt.getMonth() + 1).padStart(2, '0')}`;
            if (key in counts) counts[key]++;
        });

        const labels = monthBuckets.map(b => b.label);
        const monthlyCounts = monthBuckets.map(b => counts[b.key] || 0);
        const cumulativeCounts = [];
        let runningTotal = baselineTotal;
        monthlyCounts.forEach(value => {
            runningTotal += value;
            cumulativeCounts.push(runningTotal);
        });

        return { labels, monthlyCounts, cumulativeCounts };
    },

    renderCharts(students, materials) {
        if (typeof Chart === 'undefined') return;
        
        // 1. Registration Growth Chart
        const ctxReg = document.getElementById('adminRegistrationChart');
        if (ctxReg) {
            if (this.charts.registration) this.charts.registration.destroy();
            const trend = this.buildRegistrationTrend(students, 6);
            this.charts.registration = new Chart(ctxReg, {
                type: 'bar',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Siswa Baru',
                            data: trend.monthlyCounts,
                            backgroundColor: 'rgba(37, 99, 167, 0.75)',
                            borderRadius: 8,
                            maxBarThickness: 28
                        },
                        {
                            type: 'line',
                            label: 'Total Siswa',
                            data: trend.cumulativeCounts,
                            borderColor: '#1f8a70',
                            backgroundColor: '#1f8a70',
                            pointRadius: 3,
                            pointHoverRadius: 4,
                            tension: 0.28
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            align: 'start',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 12,
                                font: { size: 11 }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            }
                        }
                    }
                },
            });
        }

        // 2. Category Distribution
        const ctxCat = document.getElementById('adminCategoryChart');
        if (ctxCat) {
            if (this.charts.category) this.charts.category.destroy();
            const cats = {};
            materials.forEach(m => { cats[m.category || 'Umum'] = (cats[m.category || 'Umum'] || 0) + 1; });
            if (Object.keys(cats).length === 0) {
                cats['Belum ada materi'] = 1;
            }
            this.charts.category = new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(cats),
                    datasets: [{
                        data: Object.values(cats),
                        backgroundColor: ['#1f8a70', '#2563a7', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    cutout: '62%',
                    layout: {
                        padding: {
                            top: 6
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            align: 'start',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    }
};

window.Admin = Admin;
window.switchAdminTab = (id) => Admin.switchTab(id);
window.toggleAdminForm = (show) => Admin.toggleMaterialForm(show);
window.handleSaveMaterial = (e) => Admin.handleSaveMaterial(e);
window.handleDeleteMaterial = (id) => Admin.handleDeleteMaterial(id);
window.handleEditMaterial = (id) => Admin.handleEditMaterial(id);
window.openSubMaterialView = (id, title) => Admin.openSubMaterialView(id, title);
window.handleSaveSubMaterial = (e) => Admin.handleSaveSubMaterial(e);
window.toggleSubMaterialForm = (show) => document.getElementById('admin-submaterial-form-container').classList.toggle('d-none', !show);
window.toggleSubMaterialView = (show) => {
    document.getElementById('admin-submaterial-view').classList.toggle('d-none', !show);
    document.getElementById('admin-list-view').classList.toggle('d-none', show);
};
window.openQuizView = (id, title) => Admin.openQuizView(id, title);
window.toggleQuizView = (show) => {
    document.getElementById('admin-quiz-view').classList.toggle('d-none', !show);
    document.getElementById('admin-list-view').classList.toggle('d-none', show);
};
window.handleCreateQuiz = (e) => Admin.handleCreateQuiz(e);
window.showCreateQuizForm = () => Admin.showCreateQuizForm();
window.hideQuizForm = () => Admin.hideQuizForm();
window.manageQuizQuestions = (id, title) => Admin.manageQuizQuestions(id, title);
window.editQuizConfig = (id) => Admin.editQuizConfig(id);
window.backToQuizList = () => Admin.backToQuizList();
window.toggleQuestionForm = (show) => document.getElementById('admin-question-form-container').classList.toggle('d-none', !show);
window.handleSaveQuestion = (e) => Admin.handleSaveQuestion(e);
window.handleDeleteQuiz = () => Admin.handleDeleteQuiz();
window.generateCourseWithAI = () => Admin.generateCourseWithAI();
