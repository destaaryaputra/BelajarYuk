/** AI chat widget logic. */
const AI_SYSTEM_PROMPT = "Kamu adalah Asisten AI Belajaryuk. Jawab dalam bahasa Indonesia yang jelas, ramah, ringkas, dan membantu siswa belajar.";
let aiHistoryLoaded = false;

document.addEventListener('DOMContentLoaded', () => {
    updateAIWidgetVisibility();
    window.addEventListener('page:changed', updateAIWidgetVisibility);
    window.addEventListener('storage', updateAIWidgetVisibility);
});

function updateAIWidgetVisibility() {
    const widget = document.getElementById('ai-chat-widget');
    if (!widget) return;

    const activePage = document.querySelector('.page.active');
    const isPublicPage = !activePage || ['landing-page', 'login-page', 'register-page'].includes(activePage.id);
    const isAdmin = document.body.classList.contains('admin-mode');
    const isLoggedIn = !!localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);

    widget.style.display = (!isLoggedIn || isPublicPage || isAdmin) ? 'none' : 'flex';
}

function toggleAIChat() {
    const windowEl = document.getElementById('ai-chat-window');
    windowEl.classList.toggle('open');
    if (windowEl.classList.contains('open')) {
        loadAIChatHistory();
        document.getElementById('ai-chat-input').focus();
    }
}

function handleAIChatInput(event) {
    if (event.key === 'Enter') {
        sendAIChatMessage();
    }
}

let aiChatHistory = [
    { role: 'system', content: AI_SYSTEM_PROMPT }
];

async function loadAIChatHistory() {
    if (aiHistoryLoaded) return;
    aiHistoryLoaded = true;

    try {
        const response = await App.Service.API.getAIHistory(12);
        const history = response.data || [];
        if (!Array.isArray(history) || history.length === 0) return;

        const messagesContainer = document.getElementById('ai-chat-messages');
        messagesContainer.innerHTML = '';
        aiChatHistory = [{ role: 'system', content: AI_SYSTEM_PROMPT }];

        history.forEach(item => {
            appendChatMessage(item.question || '', 'user');
            appendChatMessage(item.answer || '', 'ai');
            aiChatHistory.push({ role: 'user', content: item.question || '' });
            aiChatHistory.push({ role: 'assistant', content: item.answer || '' });
        });

        trimAIChatHistory();
    } catch (error) {
        aiHistoryLoaded = false;
        console.warn('Gagal memuat riwayat AI:', error);
    }
}

async function clearAIChat() {
    if (!confirm("Yakin ingin menghapus seluruh riwayat obrolan dengan AI?")) return;
    
    try {
        await App.Service.API.clearAIHistory();
    } catch (error) {
        App.Utils.showNotification(error.message, 'error');
        return;
    }

    aiChatHistory = [{ role: 'system', content: AI_SYSTEM_PROMPT }];
    aiHistoryLoaded = true;
    document.getElementById('ai-chat-messages').innerHTML = '<div class="chat-message ai-message">Riwayat obrolan sudah dibersihkan. Ada yang bisa saya bantu?</div>';
}

async function sendAIChatMessage() {
    const inputEl = document.getElementById('ai-chat-input');
    const text = inputEl.value.trim();
    if (!text) return;

    appendChatMessage(text, 'user');
    inputEl.value = '';

    const messagesContainer = document.getElementById('ai-chat-messages');
    const typingId = 'typing-' + Date.now();
    messagesContainer.innerHTML += `
        <div id="${typingId}" class="ai-typing-indicator">
            <div class="ai-typing-dot"></div>
            <div class="ai-typing-dot"></div>
            <div class="ai-typing-dot"></div>
        </div>
    `;
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    aiChatHistory.push({ role: 'user', content: text });
    trimAIChatHistory();

    try {
        const response = await App.Service.API.chatAI(aiChatHistory, getCurrentMaterialIdForAI());
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();

        const aiReply = response?.data?.reply || '';
        if (!aiReply) {
            appendChatMessage('Ups, AI sedang sibuk. Coba tanya lagi dalam beberapa saat ya.', 'ai');
            return;
        }

        aiChatHistory.push({ role: 'assistant', content: aiReply });
        trimAIChatHistory();
        appendChatMessage(aiReply, 'ai');
    } catch (error) {
        console.error("AI Error:", error);
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();
        const errorMsg = error instanceof Error ? error.message : 'Koneksi terputus. Pastikan internetmu stabil dan coba lagi.';
        appendChatMessage(errorMsg, 'ai');
    }
}

function trimAIChatHistory() {
    if (aiChatHistory.length > 13) {
        aiChatHistory = [aiChatHistory[0], ...aiChatHistory.slice(-12)];
    }
}

function getCurrentMaterialIdForAI() {
    // Coba ambil dari currentMaterialId (biasanya diatur di pelajaran.js)
    if (typeof currentMaterialId !== 'undefined' && currentMaterialId) {
        return currentMaterialId;
    }
    // Fallback ke objek course data
    if (typeof currentCourseData === 'object' && currentCourseData && currentCourseData.id) {
        return currentCourseData.id;
    }
    return null;
}

function appendChatMessage(text, sender) {
    const messagesContainer = document.getElementById('ai-chat-messages');
    const msgClass = sender === 'user' ? 'user-message' : 'ai-message';

    let contentHtml = '';
    if (sender === 'ai') {
        // Gunakan marked jika tersedia untuk merender Markdown dari AI
        if (typeof marked !== 'undefined') {
            contentHtml = marked.parse(String(text));
        } else {
            contentHtml = App.Utils.escapeHtml(String(text)).replace(/\n/g, '<br>');
        }
    } else {
        // Pesan user tetap di-escape demi keamanan
        contentHtml = App.Utils.escapeHtml(String(text)).replace(/\n/g, '<br>');
    }

    messagesContainer.innerHTML += `<div class="chat-message ${msgClass}">${contentHtml}</div>`;
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Render ulang ikon jika ada di dalam pesan AI
    if (typeof App.UI.renderIcons === 'function') App.UI.renderIcons();
}

