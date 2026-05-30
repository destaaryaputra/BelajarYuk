/**
 * Belajaryuk - API Service Module
 */

const getApiBaseUrl = () => {
    let path = window.location.pathname;
    path = path.replace(/\/(index|api)\.(php|html?)$/i, '');
    const base = path.split('/api')[0].replace(/\/$/, '');
    return (base === '' ? '' : base) + '/api';
};

export const Config = {
    API_BASE_URL: getApiBaseUrl(),
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
        this.token = localStorage.getItem(Config.STORAGE_KEYS.AUTH_TOKEN);
        this.cache = new Map();
        this.pendingGets = new Map();
        this.cacheTtls = [
            ['/progress/dashboard', 45000],
            ['/progress/summary', 45000],
            ['/progress/categories', 45000],
            ['/progress/quiz-performance', 30000],
            ['/progress/leaderboard', 30000],
            ['/materials/detail', 45000],
            ['/materials/categories', 120000],
            ['/materials', 60000],
            ['/auth/current-user', 45000],
            ['/quiz/results', 15000]
        ];
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    getCacheTtl(endpoint, override) {
        if (override === false || override === 0) return 0;
        if (typeof override === 'number') return Math.max(0, override);

        const path = endpoint.split('?')[0];
        const rule = this.cacheTtls.find(([prefix]) => path === prefix || path.startsWith(`${prefix}/`));
        return rule ? rule[1] : 0;
    }

    getCacheKey(endpoint) {
        return `${this.token || 'guest'}:${endpoint}`;
    }

    clearCache() {
        this.cache.clear();
        this.pendingGets.clear();
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = { ...options.headers };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const method = options.method || 'GET';
        const cacheTtl = method === 'GET' ? this.getCacheTtl(endpoint, options.cacheTtl) : 0;
        const cacheKey = cacheTtl > 0 ? this.getCacheKey(endpoint) : null;

        if (cacheKey) {
            const cached = this.cache.get(cacheKey);
            if (cached && cached.expiresAt > Date.now()) {
                return cached.data;
            }
            this.cache.delete(cacheKey);

            if (this.pendingGets.has(cacheKey)) {
                return this.pendingGets.get(cacheKey);
            }
        }

        const csrfToken = this.getCsrfToken();
        if (method !== 'GET' && csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        let body = options.body;
        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), Config.REQUEST_TIMEOUT);

        const requestPromise = (async () => {
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
                throw new Error("Server error (Bukan JSON).");
            }

            if (!response.ok) {
                // Jangan logout jika error 401 berasal dari AI (karena itu masalah API Key AI, bukan sesi user)
                const isAiEndpoint = endpoint.includes('/ai/');
                if (response.status === 401 && !endpoint.includes('/auth/login') && !isAiEndpoint) {
                    this.clearAuth();
                    window.location.hash = 'login-page';
                }
                throw new Error(data.message || 'API Request failed');
            }

            if (cacheKey) {
                this.cache.set(cacheKey, {
                    data,
                    expiresAt: Date.now() + cacheTtl
                });
            } else if (method !== 'GET') {
                this.clearCache();
            }

            return data;
        } catch (error) {
            let finalError = error instanceof Error ? error : new Error(String(error));
            if (finalError.name === 'AbortError') {
                finalError = new Error('Koneksi terputus. Waktu permintaan habis (Timeout).');
            }
            throw finalError;
        }
        })();

        if (cacheKey) {
            this.pendingGets.set(cacheKey, requestPromise);
            return requestPromise.finally(() => {
                this.pendingGets.delete(cacheKey);
            });
        }

        return requestPromise;
    }

    async get(endpoint, options = {}) { return this.request(endpoint, { ...options, method: 'GET' }); }
    async post(endpoint, body, options = {}) { return this.request(endpoint, { ...options, method: 'POST', body }); }
    async put(endpoint, body, options = {}) { return this.request(endpoint, { ...options, method: 'PUT', body }); }
    async delete(endpoint, options = {}) { return this.request(endpoint, { ...options, method: 'DELETE' }); }

    setToken(token) {
        this.clearCache();
        this.token = token;
        localStorage.setItem(Config.STORAGE_KEYS.AUTH_TOKEN, token);
    }

    clearAuth() {
        this.clearCache();
        this.token = null;
        localStorage.removeItem(Config.STORAGE_KEYS.AUTH_TOKEN);
        localStorage.removeItem(Config.STORAGE_KEYS.USER_DATA);
    }

    // --- Auth API ---
    register(userData) { return this.post('/auth/register', userData); }
    login(username, password) { return this.post('/auth/login', { username, password }); }
    logout() { return this.post('/auth/logout', {}); }
    getCurrentUser() { return this.get('/auth/current-user'); }
    getAllUsers() { return this.get('/auth/users'); }
    
    // --- Progress & Materials ---
    getDashboardData() { return this.get('/progress/dashboard'); }
    getProgressSummary() { return this.get('/progress/summary'); }
    getProgressByCategories() { return this.get('/progress/categories'); }
    getLeaderboard(limit = 10) { return this.get(`/progress/leaderboard?limit=${limit}`); }
    getQuizPerformance() { return this.get('/progress/quiz-performance'); }
    trackProgress(materialId) { return this.post('/progress/track', { material_id: materialId }); }
    
    getMaterials(page = 1, limit = 10, category = null) {
        let endpoint = `/materials?page=${page}&limit=${limit}`;
        if (category) endpoint += `&category=${encodeURIComponent(category)}`;
        return this.get(endpoint);
    }
    getMaterialDetail(id) { return this.get(`/materials/detail?id=${id}`); }
    getSubMaterialsAdmin(materialId) { return this.get(`/materials/sub?material_id=${materialId}`); }
    deleteMaterial(materialId) { return this.post('/materials/delete', { material_id: materialId }); }
    deleteSubMaterial(id) { return this.post('/materials/sub/delete', { id }); }
    markMaterialCompleted(materialId, subMaterialId = null) { 
        return this.post('/materials/mark-completed', { material_id: materialId, sub_material_id: subMaterialId }); 
    }
    getComments(materialId) { return this.get(`/materials/comments?material_id=${materialId}`); }
    addComment(payload) { return this.post('/materials/comments/add', payload); }
    getAllCommentsAdmin(limit = 100) { return this.get(`/materials/comments/admin?limit=${limit}`); }
    
    // --- Quiz API ---
    getQuiz(materialId, subMaterialId = null) { 
        let endpoint = `/quiz?material_id=${materialId}`;
        if (subMaterialId) endpoint += `&sub_material_id=${subMaterialId}`;
        return this.get(endpoint); 
    }
    getQuizzesAdmin(materialId) { return this.get(`/quiz/list-admin?material_id=${materialId}`); }
    getQuizQuestions(quizId) { return this.get(`/quiz/questions?quiz_id=${quizId}`); }
    submitQuiz(data) { return this.post('/quiz/submit', data); }
    getUserQuizResults(quizId = null) {
        const endpoint = quizId ? `/quiz/results?quiz_id=${quizId}` : '/quiz/results';
        return this.get(endpoint);
    }
    getAdminQuizReport() { return this.get('/quiz/admin-report'); }

    // --- AI Chat API ---
    getAIHistory(limit = 12) { return this.get(`/ai/history?limit=${limit}`); }
    clearAIHistory() { return this.post('/ai/clear-history', {}); }
    chatAI(messages, materialId = null) { 
        return this.post('/ai/chat', { messages, material_id: materialId }); 
    }
}

export const API = new APIService(Config.API_BASE_URL);
