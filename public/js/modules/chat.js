/**
 * Belajaryuk - AI Chat Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const AIChat = {
    history: [],
    loaded: false,
    systemPrompt: "Kamu adalah Asisten AI Belajaryuk. Jawab dalam bahasa Indonesia yang jelas, ramah, ringkas, dan membantu siswa belajar.",

    init() {
        this.updateVisibility();
        window.addEventListener('hashchange', () => this.updateVisibility());
        
        // Inject component if needed
        const widget = document.getElementById('ai-chat-widget');
        if (widget && widget.innerHTML === '') {
            this.loadComponent();
        }
    },

    async loadComponent() {
        try {
            const base = window.location.pathname.replace(/\/(index|api)\.(php|html?)$/i, '').replace(/\/$/, '');
            const response = await fetch(`${base}/components/ai-chat.html`);
            const html = await response.text();
            const widget = document.getElementById('ai-chat-widget');
            if (widget) {
                widget.innerHTML = html;
                if (window.lucide) window.lucide.createIcons();
                this.setupListeners();
            }
        } catch (error) {
            console.error('Failed to load AI Chat component:', error);
        }
    },

    setupListeners() {
        const input = document.getElementById('ai-chat-input');
        if (input) {
            input.onkeypress = (e) => {
                if (e.key === 'Enter') this.sendMessage();
            };
        }
        
        const toggleBtn = document.getElementById('ai-chat-toggle');
        if (toggleBtn) {
            toggleBtn.onclick = () => this.toggle();
        }

        const closeBtns = document.querySelectorAll('.ai-close-btn');
        closeBtns.forEach(btn => {
            if (btn.title === 'Tutup Chat') btn.onclick = () => this.toggle();
            if (btn.title === 'Hapus Riwayat Obrolan') btn.onclick = () => this.clear();
        });
        
        const sendBtn = document.querySelector('.ai-chat-input-area button');
        if (sendBtn) {
            sendBtn.onclick = () => this.sendMessage();
        }
    },

    updateVisibility() {
        const widget = document.getElementById('ai-chat-widget');
        if (!widget) return;

        const hash = window.location.hash.replace('#', '') || 'landing-page';
        const isPublicPage = ['landing-page', 'login-page', 'register-page'].includes(hash);
        const isAdminPage = hash === 'admin-page';
        const isLoggedIn = !!localStorage.getItem(Config.STORAGE_KEYS.AUTH_TOKEN);

        const shouldShow = isLoggedIn && !isPublicPage && !isAdminPage;
        widget.classList.toggle('d-none', !shouldShow);
    },

    toggle() {
        const windowEl = document.getElementById('ai-chat-window');
        if (!windowEl) return;
        
        windowEl.classList.toggle('open');
        if (windowEl.classList.contains('open')) {
            this.loadHistory();
            document.getElementById('ai-chat-input')?.focus();
        }
    },

    async loadHistory() {
        if (this.loaded) return;
        this.loaded = true;

        try {
            const response = await API.getAIHistory(12);
            const history = response.data || [];
            if (!Array.isArray(history) || history.length === 0) return;

            const container = document.getElementById('ai-chat-messages');
            if (container) container.innerHTML = '';
            
            this.history = [{ role: 'system', content: this.systemPrompt }];

            history.forEach(item => {
                this.appendMessage(item.question || '', 'user');
                this.appendMessage(item.answer || '', 'ai');
                this.history.push({ role: 'user', content: item.question || '' });
                this.history.push({ role: 'assistant', content: item.answer || '' });
            });

            this.trimHistory();
        } catch (error) {
            this.loaded = false;
            console.warn('Gagal memuat riwayat AI:', error);
        }
    },

    async clear() {
        if (!confirm("Hapus riwayat obrolan?")) return;
        
        try {
            await API.clearAIHistory();
            this.history = [{ role: 'system', content: this.systemPrompt }];
            const container = document.getElementById('ai-chat-messages');
            if (container) container.innerHTML = '<div class="chat-message ai-message">Riwayat dibersihkan.</div>';
        } catch (error) {
            UI.showNotification(error.message, 'error');
        }
    },

    async sendMessage() {
        const input = document.getElementById('ai-chat-input');
        const text = input?.value.trim();
        if (!text) return;

        this.appendMessage(text, 'user');
        input.value = '';

        const container = document.getElementById('ai-chat-messages');
        const typingId = 'typing-' + Date.now();
        if (container) {
            container.innerHTML += `<div id="${typingId}" class="ai-typing-indicator"><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div></div>`;
            container.scrollTop = container.scrollHeight;
        }

        this.history.push({ role: 'user', content: text });
        this.trimHistory();

        try {
            const response = await API.chatAI(this.history);
            document.getElementById(typingId)?.remove();

            const aiReply = response?.data?.reply || 'AI sedang sibuk.';
            this.history.push({ role: 'assistant', content: aiReply });
            this.trimHistory();
            this.appendMessage(aiReply, 'ai');
        } catch (error) {
            document.getElementById(typingId)?.remove();
            this.appendMessage('Gagal menghubungi AI.', 'ai');
        }
    },

    appendMessage(text, sender) {
        const container = document.getElementById('ai-chat-messages');
        if (!container) return;

        const msgClass = sender === 'user' ? 'user-message' : 'ai-message';
        let content = '';
        
        if (sender === 'ai' && window.marked) {
            content = window.marked.parse(String(text));
        } else {
            content = UI.escapeHtml(String(text)).replace(/\n/g, '<br>');
        }

        container.innerHTML += `<div class="chat-message ${msgClass}">${content}</div>`;
        container.scrollTop = container.scrollHeight;
    },

    trimHistory() {
        if (this.history.length > 13) {
            this.history = [this.history[0], ...this.history.slice(-12)];
        }
    }
};
