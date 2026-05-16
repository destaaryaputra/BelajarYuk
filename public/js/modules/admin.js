/**
 * Belajaryuk - Admin Module
 * Consolidated and refactored for ES Modules
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Admin = {
    charts: {
        registration: null,
        category: null
    },
    editors: {
        matQuill: null,
        submatQuill: null
    },
    currentSubMaterials: [],

    async load() {
        const userData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        if (userData) {
            const user = JSON.parse(userData);
            const adminNameEl = document.getElementById('admin-user-name');
            if (adminNameEl) adminNameEl.textContent = user.full_name || 'Admin';
        }

        this.setupFormListeners();
        this.switchTab('dashboard');
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
        const allTabs = ['dashboard', 'materi', 'pengguna', 'laporan', 'diskusi', 'pengaturan'];
        allTabs.forEach(t => {
            document.getElementById(`btn-tab-${t}`)?.classList.remove('active');
            document.getElementById(`admin-tab-${t}`)?.classList.add('d-none');
        });
        
        document.getElementById(`btn-tab-${tabId}`)?.classList.add('active');
        document.getElementById(`admin-tab-${tabId}`)?.classList.remove('d-none');
        
        const titles = {
            'dashboard': 'Ringkasan & Analitik',
            'materi': 'Kelola Materi',
            'pengguna': 'Data Siswa',
            'laporan': 'Laporan & Analitik Nilai',
            'diskusi': 'Moderasi Forum',
            'pengaturan': 'Pengaturan Platform'
        };
        const titleEl = document.getElementById('admin-page-title');
        if (titleEl) titleEl.innerText = titles[tabId] || 'Panel Admin';
        
        if (tabId === 'dashboard') this.loadDashboard();
        else if (tabId === 'materi') this.loadMaterials();
        else if (tabId === 'pengguna') this.loadUsers();
        else if (tabId === 'laporan') this.loadReports();
        else if (tabId === 'diskusi') this.loadDiscussions();
        else if (tabId === 'pengaturan') this.loadSettings();
        
        if (window.lucide) window.lucide.createIcons();
    },

    // --- DASHBOARD ---
    async loadDashboard() {
        const quickActions = document.getElementById('admin-quick-actions');
        if (quickActions) {
            quickActions.innerHTML = `
                <button type="button" class="btn-p" onclick="Admin.switchTab('materi'); Admin.toggleMaterialForm(true)">
                    <i data-lucide="plus"></i>
                    <div><span>Tambah</span>Materi</div>
                </button>
                <button type="button" class="btn-outline" onclick="Admin.switchTab('pengguna')">
                    <i data-lucide="users"></i>
                    <div><span>Kelola</span>Siswa</div>
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

            this.animateNumber('admin-stat-users', 0, students.length, 800);
            this.animateNumber('admin-stat-materials', 0, materials.length, 800);
            
            // Populate Mini Lists
            this.renderRecentUsers(students.slice(0, 5));
            this.renderRecentMaterials(materials.slice(0, 5));
            
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
            html += `
                <div class="admin-mini-item">
                    <div class="admin-avatar">${(u.full_name || u.username || '?')[0].toUpperCase()}</div>
                    <div class="admin-mini-main">
                        <strong>${UI.escapeHtml(u.full_name || u.username)}</strong>
                        <span>${UI.escapeHtml(u.email)}</span>
                    </div>
                </div>`;
        });
        container.innerHTML = html + '</div>';
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
                    <div class="admin-course-mark">M</div>
                    <div class="admin-mini-main">
                        <strong>${UI.escapeHtml(m.title)}</strong>
                        <span>${UI.escapeHtml(m.category || 'Umum')}</span>
                    </div>
                </div>`;
        });
        container.innerHTML = html + '</div>';
    },

    // --- MATERIALS ---
    async loadMaterials() {
        UI.showLoading();
        try {
            const res = await API.getMaterials(1, 100);
            const materials = res.data?.materials || res.data || [];
            const container = document.getElementById('admin-materials-table');
            if (!container) return;

            let html = '<div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>ID</th><th>Judul</th><th>Kategori</th><th class="text-right">Aksi</th></tr></thead><tbody>';
            if (materials.length === 0) html += '<tr><td colspan="4" class="text-center">Kosong.</td></tr>';
            else {
                materials.forEach(m => {
                    html += `
                        <tr>
                            <td class="text-muted">#${m.id}</td>
                            <td class="font-medium">${UI.escapeHtml(m.title)}</td>
                            <td><span class="badge badge-student">${UI.escapeHtml(m.category || 'Umum')}</span></td>
                            <td class="text-right">
                                <div class="d-flex justify-end gap-8">
                                    <button class="btn-outline btn-small" onclick="Admin.openQuizView(${m.id}, '${UI.escapeHtml(m.title)}')">Kuis</button>
                                    <button class="btn-outline btn-small" onclick="Admin.openSubMaterialView(${m.id}, '${UI.escapeHtml(m.title)}')">Episode</button>
                                    <button class="btn-outline btn-small" onclick="Admin.handleEditMaterial(${m.id})">Edit</button>
                                    <button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteMaterial(${m.id})">Hapus</button>
                                </div>
                            </td>
                        </tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div>';
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
        if (!confirm('Hapus materi?')) return;
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
            let html = '<table class="admin-table"><thead><tr><th>Part</th><th>Judul</th><th>Aksi</th></tr></thead><tbody>';
            if (this.currentSubMaterials.length === 0) html += '<tr><td colspan="3" class="text-center">Kosong.</td></tr>';
            else {
                this.currentSubMaterials.forEach((s, i) => {
                    html += `<tr><td>${i+1}</td><td>${UI.escapeHtml(s.title)}</td><td>
                        <button class="btn-outline btn-small" onclick="Admin.handleEditSubMat(${s.id})">Edit</button>
                        <button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteSubMat(${s.id}, ${materialId})">Hapus</button>
                    </td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table>';
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
        if (!confirm('Hapus episode?')) return;
        UI.showLoading();
        try { await API.deleteSubMaterial(id); this.loadSubMaterials(matId); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    // --- QUIZ ---
    async openQuizView(materialId, title) {
        document.getElementById('admin-list-view').classList.add('d-none');
        document.getElementById('admin-quiz-view').classList.remove('d-none');
        document.getElementById('admin-quiz-title').innerText = `Kuis: ${title}`;
        document.getElementById('quiz-material-id').value = materialId;
        UI.showLoading();
        try {
            const res = await API.getQuiz(materialId);
            const q = res.data;
            if (q && q.id) {
                document.getElementById('admin-quiz-setup').classList.add('d-none');
                document.getElementById('admin-quiz-manage').classList.remove('d-none');
                document.getElementById('active-quiz-title').innerText = q.title;
                document.getElementById('q-quiz-id').value = q.id;
                this.loadQuestions(q.id);
            } else {
                document.getElementById('admin-quiz-setup').classList.remove('d-none');
                document.getElementById('admin-quiz-manage').classList.add('d-none');
            }
        } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async handleCreateQuiz(e) {
        e.preventDefault();
        const matId = document.getElementById('quiz-material-id').value;
        const data = {
            material_id: matId,
            title: document.getElementById('quiz-title').value,
            description: document.getElementById('quiz-desc').value,
            passing_score: document.getElementById('quiz-passing').value,
            time_limit_minutes: document.getElementById('quiz-time').value
        };
        UI.showLoading();
        try { await API.post('/quiz/create', data); this.openQuizView(matId, 'Kuis Baru'); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async loadQuestions(quizId) {
        try {
            const res = await API.getQuizQuestions(quizId);
            const qs = res.data || [];
            let html = '<table class="admin-table"><thead><tr><th>No</th><th>Soal</th><th>Aksi</th></tr></thead><tbody>';
            qs.forEach((q, i) => {
                html += `<tr><td>${i+1}</td><td>${UI.escapeHtml(q.question_text)}</td><td><button class="btn-outline btn-text-danger btn-small" onclick="Admin.deleteQuestion(${q.id}, ${quizId})">Hapus</button></td></tr>`;
            });
            document.getElementById('admin-questions-table').innerHTML = html + '</tbody></table>';
        } catch (error) { console.error(error); }
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
        try { await API.post('/quiz/questions/add', data); document.getElementById('admin-question-form-container').classList.add('d-none'); this.loadQuestions(quizId); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async deleteQuestion(id, qid) {
        if (!confirm('Hapus soal?')) return;
        UI.showLoading();
        try { await API.post('/quiz/questions/delete', { id }); this.loadQuestions(qid); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async handleDeleteQuiz() {
        if (!confirm('Hapus seluruh kuis?')) return;
        const qid = document.getElementById('q-quiz-id').value;
        const mid = document.getElementById('quiz-material-id').value;
        UI.showLoading();
        try { await API.post('/quiz/delete', { id: qid }); this.openQuizView(mid, 'Kuis'); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    // --- USERS ---
    async loadUsers() {
        UI.showLoading();
        try {
            const res = await API.getAllUsers();
            const users = (res.data?.users || []).filter(u => u.role === 'student');
            const container = document.getElementById('admin-users-table');
            let html = '<table class="admin-table"><thead><tr><th>Siswa</th><th>Email</th><th>Aksi</th></tr></thead><tbody>';
            if (users.length === 0) html += '<tr><td colspan="3" class="text-center">Belum ada siswa.</td></tr>';
            else {
                users.forEach(u => {
                    html += `<tr><td><strong>${UI.escapeHtml(u.full_name)}</strong><br>@${UI.escapeHtml(u.username)}</td><td>${UI.escapeHtml(u.email)}</td>
                    <td><button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteUser(${u.id})">Hapus</button></td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table>';
        } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async handleDeleteUser(id) {
        if (!confirm('Hapus akun siswa permanen?')) return;
        UI.showLoading();
        try { await API.post('/auth/users/delete', { user_id: id }); this.loadUsers(); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    // --- OTHERS ---
    async loadDiscussions() {
        try {
            const res = await API.getAllCommentsAdmin(50);
            const comments = res.data || [];
            const container = document.getElementById('admin-tab-diskusi');
            let html = '<div class="content-card"><h3>Moderasi Forum</h3><table class="admin-table"><thead><tr><th>Siswa</th><th>Komentar</th><th>Aksi</th></tr></thead><tbody>';
            if (comments.length === 0) html += '<tr><td colspan="3" class="text-center">Kosong.</td></tr>';
            else {
                comments.forEach(c => {
                    html += `<tr><td>${UI.escapeHtml(c.username)}</td><td>${UI.escapeHtml(c.comment_text)}</td>
                    <td><button class="btn-outline btn-text-danger btn-small" onclick="Admin.handleDeleteComment(${c.id})">Hapus</button></td></tr>`;
                });
            }
            container.innerHTML = html + '</tbody></table></div>';
        } catch (error) { console.error(error); }
    },

    async handleDeleteComment(id) {
        if (!confirm('Hapus komentar?')) return;
        UI.showLoading();
        try { await API.post('/materials/comments/delete', { id }); this.loadDiscussions(); } catch (error) { console.error(error); } finally { UI.hideLoading(); }
    },

    async loadSettings() {
        const container = document.getElementById('admin-tab-pengaturan');
        if (container) container.innerHTML = '<div class="content-card"><h3>Pengaturan Platform</h3><p>Konfigurasi API: ' + Config.API_BASE_URL + '</p></div>';
    },

    async generateCourseWithAI() {
        const topic = prompt("Topik materi AI?");
        if (!topic) return;
        UI.showLoading();
        UI.showNotification('AI sedang menyusun materi...', 'info', 10000);
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
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
    },

    renderCharts(students, materials) {
        if (typeof Chart === 'undefined') return;
        
        // 1. Registration Growth Chart
        const ctxReg = document.getElementById('adminRegistrationChart');
        if (ctxReg) {
            if (this.charts.registration) this.charts.registration.destroy();
            const data = [students.length, students.length + 2, students.length + 5, students.length + 3];
            this.charts.registration = new Chart(ctxReg, {
                type: 'line',
                data: {
                    labels: ['Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: [{
                        label: 'Siswa',
                        data: data,
                        borderColor: '#1f8a70',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(31, 138, 112, 0.1)'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // 2. Category Distribution
        const ctxCat = document.getElementById('adminCategoryChart');
        if (ctxCat) {
            if (this.charts.category) this.charts.category.destroy();
            const cats = {};
            materials.forEach(m => { cats[m.category || 'Umum'] = (cats[m.category || 'Umum'] || 0) + 1; });
            this.charts.category = new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(cats),
                    datasets: [{
                        data: Object.values(cats),
                        backgroundColor: ['#1f8a70', '#2563a7', '#f59e0b', '#ef4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
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
window.toggleQuestionForm = (show) => document.getElementById('admin-question-form-container').classList.toggle('d-none', !show);
window.handleSaveQuestion = (e) => Admin.handleSaveQuestion(e);
window.handleDeleteQuiz = () => Admin.handleDeleteQuiz();
window.generateCourseWithAI = () => Admin.generateCourseWithAI();
