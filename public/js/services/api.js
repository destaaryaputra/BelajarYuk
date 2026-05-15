/**
 * Belajaryuk - API Service Layer
 * Standard: Senior Frontend Engineer (Namespaced & Error-handled)
 */

window.App = window.App || {};

App.Config = {
    API_BASE_URL: '/belajaryuk/api',
    STORAGE_KEYS: {
        AUTH_TOKEN: 'belajaryuk_auth_token',
        USER_DATA: 'belajaryuk_user_data',
        THEME: 'belajaryuk_theme'
    },
    REQUEST_TIMEOUT: 30000
};

class APIService {
    constructor(baseURL) {
        this.baseURL = baseURL;
        this.token = localStorage.getItem(App.Config.STORAGE_KEYS.AUTH_TOKEN);
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = { ...options.headers };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const method = options.method || 'GET';
        if (method !== 'GET' && this.csrfToken) {
            headers['X-CSRF-Token'] = this.csrfToken;
        }

        // Handle Body & Content-Type
        let body = options.body;
        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }
        // Note: If body is FormData, browser will set Content-Type with boundary automatically.

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), App.Config.REQUEST_TIMEOUT);

        try {
            const response = await fetch(url, {
                method,
                headers,
                credentials: 'same-origin',
                body: body,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            const text = await response.text();
            let data = {};
            
            try {
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error("RESPONS SERVER BUKAN JSON! Berikut isi aslinya:\n\n", text);
                throw new Error("Server memunculkan pesan error tersembunyi. Silakan periksa Console untuk detailnya.");
            }

            if (!response.ok) {
                if (response.status === 401 && !endpoint.includes('/auth/login')) {
                    this.clearAuth();
                    if (App.Router && typeof App.Router.showPage === 'function') {
                        App.Router.showPage('login-page');
                    }
                    if (App.Utils && typeof App.Utils.showNotification === 'function') {
                        App.Utils.showNotification('Sesi berakhir. Silakan masuk kembali.', 'warning');
                    }
                }

                if (data.errors) {
                    let errorDetails = '';
                    if (typeof data.errors === 'object' && !Array.isArray(data.errors)) {
                        errorDetails = Object.values(data.errors).flat().join(' ');
                    } else if (Array.isArray(data.errors)) {
                        errorDetails = data.errors.join(' ');
                    }
                    throw new Error(`${data.message || 'Validasi Gagal'}: ${errorDetails}`);
                }

                throw new Error(data.message || 'API Request failed');
            }

            return data;
        } catch (error) {
            let finalError = error instanceof Error ? error : new Error(String(error));
            if (finalError.name === 'AbortError') {
                finalError = new Error('Koneksi terputus. Waktu permintaan habis (Timeout).');
            }
            throw finalError;
        }
    }

    async get(endpoint, options = {}) { return this.request(endpoint, { ...options, method: 'GET' }); }
    async post(endpoint, body, options = {}) { return this.request(endpoint, { ...options, method: 'POST', body }); }
    async put(endpoint, body, options = {}) { return this.request(endpoint, { ...options, method: 'PUT', body }); }
    async delete(endpoint, options = {}) { return this.request(endpoint, { ...options, method: 'DELETE' }); }

    setToken(token) {
        this.token = token;
        localStorage.setItem(App.Config.STORAGE_KEYS.AUTH_TOKEN, token);
    }

    clearAuth() {
        this.token = null;
        localStorage.removeItem(App.Config.STORAGE_KEYS.AUTH_TOKEN);
        localStorage.removeItem(App.Config.STORAGE_KEYS.USER_DATA);
    }

    // --- Auth API ---
    register(userData) { return this.post('/auth/register', userData); }
    login(username, password) { return this.post('/auth/login', { username, password }); }
    logout() { return this.post('/auth/logout', {}); }
    getCurrentUser() { return this.get('/auth/current-user'); }
    updateProfile(userData) { return this.put('/auth/profile', userData); }
    getAllUsers(page = 1, limit = 50) { return this.get(`/auth/users?page=${page}&limit=${limit}`); }
    updateUserRole(userId, role) { return this.post('/auth/users/update-role', { user_id: userId, role }); }
    deleteUser(userId) { return this.post('/auth/users/delete', { user_id: userId }); }
    
    // --- Materials API ---
    getMaterials(page = 1, limit = 10, category = null) {
        let endpoint = `/materials?page=${page}&limit=${limit}`;
        if (category) endpoint += `&category=${encodeURIComponent(category)}`;
        return this.get(endpoint);
    }
    getMaterialDetail(id) { return this.get(`/materials/detail?id=${id}`); }
    getCategories() { return this.get('/materials/categories'); }
    markMaterialAsCompleted(materialId) { return this.post('/materials/mark-completed', { material_id: materialId }); }
    createMaterial(data) { return this.post('/materials/create', data); }
    updateMaterial(id, data) { return this.post(`/materials/update?id=${id}`, data); }
    deleteMaterial(id) { return this.post('/materials/delete', { material_id: id }); }
    
    // --- Sub-Materials (Episodes) API ---
    getSubMaterialsAdmin(materialId) { return this.get(`/materials/sub?material_id=${materialId}`); }
    createSubMaterial(data) { return this.post('/materials/sub/create', data); }
    updateSubMaterial(id, data) { 
        // Senior Architect Fix: Ensure ID is passed in FormData if possible or in query
        if (data instanceof FormData) data.append('id', id);
        return this.post(`/materials/sub/update?id=${id}`, data); 
    }
    deleteSubMaterial(id) { return this.post('/materials/sub/delete', { id }); }

    // --- Comments & Discussion API ---
    getComments(materialId) { return this.get(`/materials/comments?material_id=${materialId}`); }
    addComment(materialId, commentText) { return this.post('/materials/comments/add', { material_id: materialId, comment_text: commentText }); }
    getAllCommentsAdmin(limit = 100) { return this.get(`/materials/comments/admin?limit=${limit}`); }
    deleteCommentAdmin(id) { return this.post('/materials/comments/delete', { id }); }

    // --- Quiz API ---
    getQuiz(materialId) { return this.get(`/quiz?material_id=${materialId}`); }
    getQuizQuestions(quizId) { return this.get(`/quiz/questions?quiz_id=${quizId}`); }
    submitQuiz(quizId, answers) { return this.post('/quiz/submit', { quiz_id: quizId, answers }); }
    getUserResults(quizId = null) { 
        let endpoint = '/quiz/results';
        if (quizId) endpoint += `?quiz_id=${quizId}`;
        return this.get(endpoint);
    }
    getAdminQuizReport() { return this.get('/quiz/admin-report'); }
    createQuizAdmin(data) { return this.post('/quiz/create', data); }
    addQuestionAdmin(data) { return this.post('/quiz/questions/add', data); }
    deleteQuestionAdmin(id) { return this.post('/quiz/questions/delete', { id }); }
    deleteQuizAdmin(id) { return this.post('/quiz/delete', { id }); }

    // --- Progress & Gamification API ---
    getDashboardData() { return this.get('/progress/dashboard'); }
    getProgressSummary() { return this.get('/progress/summary'); }
    getProgressByCategory() { return this.get('/progress/categories'); }
    getQuizPerformance(limit = 10) { return this.get(`/progress/quiz-performance?limit=${limit}`); }
    getLearningStreak() { return this.get('/progress/streak'); }
    getAchievements() { return this.get('/progress/achievements'); }
    getLeaderboard(limit = 10) { return this.get(`/progress/leaderboard?limit=${limit}`); }
    trackActivity(materialId) { return this.post('/progress/track', { material_id: materialId }); }

    // --- AI API ---
    chatAI(messages, materialId = null) { return this.post('/ai/chat', { messages, material_id: materialId }); }
    getAIHistory(limit = 20) { return this.get(`/ai/history?limit=${limit}`); }
    clearAIHistory() { return this.post('/ai/clear-history', {}); }
    generateCourseAI(topic) { return this.post('/ai/generate-course', { topic }); }
}

// Instantiate Service
App.Service = App.Service || {};
App.Service.API = new APIService(App.Config.API_BASE_URL);

// UI Utils
App.Utils = {
    showLoading(show = true) {
        const loader = document.getElementById('loading');
        if (loader) loader.classList.toggle('d-none', !show);
    },

    showNotification(message, type = 'success', duration = 3000) {
        const toast = document.getElementById('notification-toast');
        if (!toast) return;

        const titleMap = { success: 'Berhasil', error: 'Gagal', warning: 'Perhatian', info: 'Info' };
        const iconMap = { success: '✓', error: '!', warning: '!', info: 'i' };

        if (toast.hideTimeout) clearTimeout(toast.hideTimeout);
        toast.className = 'notification-toast ' + type;
        
        if (duration > 0) {
            toast.classList.add('with-timer');
            toast.style.setProperty('--toast-duration', `${duration}ms`);
        }

        toast.innerHTML = `
            <span class="toast-icon">${iconMap[type]}</span>
            <div class="toast-content">
                <p class="toast-title">${titleMap[type]}</p>
                <p class="toast-message">${this.escapeHtml(message)}</p>
            </div>
            <button type="button" class="toast-close">✕</button>
            <span class="toast-progress"></span>
        `;

        toast.querySelector('.toast-close').onclick = () => toast.classList.remove('show');
        requestAnimationFrame(() => toast.classList.add('show'));

        if (duration > 0) {
            toast.hideTimeout = setTimeout(() => toast.classList.remove('show'), duration);
        }
    },

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
};
