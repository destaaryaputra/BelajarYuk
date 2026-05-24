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
        THEME: 'belajaryuk_theme',
        API_CACHE: 'belajaryuk_api_cache'
    },
    REQUEST_TIMEOUT: 30000,
    CACHE_TTL: 1000 * 60 * 60 // 1 hour
};

class APIService {
    constructor(baseURL) {
        this.baseURL = baseURL;
        this.token = localStorage.getItem(Config.STORAGE_KEYS.AUTH_TOKEN);
        this.cache = JSON.parse(localStorage.getItem(Config.STORAGE_KEYS.API_CACHE) || '{}');
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    saveCache() {
        localStorage.setItem(Config.STORAGE_KEYS.API_CACHE, JSON.stringify(this.cache));
    }

    getCache(key) {
        const cached = this.cache[key];
        if (cached && (Date.now() - cached.timestamp < Config.CACHE_TTL)) {
            return cached.data;
        }
        return null;
    }

    setCache(key, data) {
        this.cache[key] = {
            data,
            timestamp: Date.now()
        };
        this.saveCache();
    }

    clearCache() {
        this.cache = {};
        localStorage.removeItem(Config.STORAGE_KEYS.API_CACHE);
    }

    async request(endpoint, options = {}) {
        const useCache = options.useCache && options.method === 'GET';
        const cacheKey = `GET:${endpoint}`;

        if (useCache) {
            const cachedData = this.getCache(cacheKey);
            if (cachedData) {
                // Return cached data immediately but trigger background refresh
                this.request(endpoint, { ...options, useCache: false })
                    .then(freshData => {
                        this.setCache(cacheKey, freshData);
                        // Optional: Dispatch event if data changed significantly
                        window.dispatchEvent(new CustomEvent('api-data-refreshed', { 
                            detail: { endpoint, data: freshData } 
                        }));
                    })
                    .catch(err => console.warn('Background refresh failed:', err));
                
                return cachedData;
            }
        }

        const url = `${this.baseURL}${endpoint}`;
        const headers = { ...options.headers };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const method = options.method || 'GET';
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
                const isAiEndpoint = endpoint.includes('/ai/');
                if (response.status === 401 && !endpoint.includes('/auth/login') && !isAiEndpoint) {
                    this.clearAuth();
                    this.clearCache();
                    window.location.hash = 'login-page';
                }
                throw new Error(data.message || 'API Request failed');
            }

            if (method === 'GET') {
                this.setCache(cacheKey, data);
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
    async post(endpoint, body, options = {}) { 
        // Invalidate cache on mutations
        this.clearCache(); 
        return this.request(endpoint, { ...options, method: 'POST', body }); 
    }
    async put(endpoint, body, options = {}) { 
        this.clearCache();
        return this.request(endpoint, { ...options, method: 'PUT', body }); 
    }
    async delete(endpoint, options = {}) { 
        this.clearCache();
        return this.request(endpoint, { ...options, method: 'DELETE' }); 
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem(Config.STORAGE_KEYS.AUTH_TOKEN, token);
    }

    clearAuth() {
        this.token = null;
        localStorage.removeItem(Config.STORAGE_KEYS.AUTH_TOKEN);
        localStorage.removeItem(Config.STORAGE_KEYS.USER_DATA);
        this.clearCache();
    }

    // --- Auth API ---
    register(userData) { return this.post('/auth/register', userData); }
    login(username, password) { return this.post('/auth/login', { username, password }); }
    logout() { return this.post('/auth/logout', {}); }
    getCurrentUser() { return this.get('/auth/current-user', { useCache: true }); }
    getAllUsers() { return this.get('/auth/users'); }
    
    // --- Progress & Materials ---
    getDashboardData() { return this.get('/progress/dashboard', { useCache: true }); }
    getProgressSummary() { return this.get('/progress/summary', { useCache: true }); }
    getProgressByCategories() { return this.get('/progress/categories', { useCache: true }); }
    getLeaderboard(limit = 10) { return this.get(`/progress/leaderboard?limit=${limit}`, { useCache: true }); }
    getQuizPerformance() { return this.get('/progress/quiz-performance', { useCache: true }); }
    trackProgress(materialId) { return this.post('/progress/track', { material_id: materialId }); }
    
    getMaterials(page = 1, limit = 10, category = null) {
        let endpoint = `/materials?page=${page}&limit=${limit}`;
        if (category) endpoint += `&category=${encodeURIComponent(category)}`;
        return this.get(endpoint, { useCache: true });
    }
    getMaterialDetail(id) { return this.get(`/materials/detail?id=${id}`, { useCache: true }); }
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
