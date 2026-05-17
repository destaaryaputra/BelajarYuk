/**
 * Belajaryuk - Quiz Module
 * Handles Student Quiz Experience
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Quiz = {
    state: {
        quiz: null,
        questions: [],
        answers: {}, // map of questionId -> answerText
        materialId: null,
        subMaterialId: null
    },

    async load() {
        const content = document.getElementById('quiz-content');
        const loading = document.getElementById('quiz-loading');
        const result = document.getElementById('quiz-result');
        if (!content) return;

        // Reset state
        this.state.answers = {};
        content.classList.add('d-none');
        result.classList.add('d-none');
        loading.classList.remove('d-none');

        // Check if we have sub_material_id (Mini Quiz) or material_id (Final Quiz)
        this.state.materialId = localStorage.getItem('active_material_id');
        this.state.subMaterialId = localStorage.getItem('active_sub_material_id');

        try {
            // 1. Fetch Quiz Data
            const res = await API.getQuiz(this.state.materialId, this.state.subMaterialId);
            const quiz = res.data;

            if (!quiz) {
                loading.innerHTML = `
                    <div class="empty-state p-48">
                        <i data-lucide="info" class="icon-xl mb-16"></i>
                        <h3>Belum ada kuis untuk bagian ini</h3>
                        <p class="text-muted mb-24">Kamu bisa lanjut ke materi berikutnya.</p>
                        <button class="btn-primary" onclick="window.location.hash='material-detail-page'">Kembali ke Materi</button>
                    </div>
                `;
                if (window.lucide) window.lucide.createIcons();
                return;
            }

            this.state.quiz = quiz;

            // 2. Fetch Questions
            const qRes = await API.getQuizQuestions(quiz.id);
            this.state.questions = qRes.data || [];

            if (this.state.questions.length === 0) {
                throw new Error('Kuis ini belum memiliki pertanyaan.');
            }

            // 3. Render
            this.renderQuiz();
            this.setupListeners();

            loading.classList.add('d-none');
            content.classList.remove('d-none');
            if (window.lucide) window.lucide.createIcons();

        } catch (error) {
            console.error('Quiz load error:', error);
            loading.innerHTML = `<p class="text-danger p-48">${error.message || 'Gagal memuat kuis.'}</p>`;
        }
    },

    renderQuiz() {
        const titleEl = document.getElementById('quiz-title-display');
        const descEl = document.getElementById('quiz-desc-display');
        const typeEl = document.getElementById('quiz-type-label');
        const container = document.getElementById('questions-container');

        titleEl.textContent = this.state.quiz.title;
        descEl.textContent = this.state.quiz.description || '';
        typeEl.textContent = this.state.quiz.quiz_type === 'mini' ? 'Mini Kuis (Episode)' : 'Kuis Final Modul';

        let html = '';
        this.state.questions.forEach((q, idx) => {
            html += `
                <div class="question-card" data-q-id="${q.id}">
                    <div class="question-text">${idx + 1}. ${UI.escapeHtml(q.question_text)}</div>
                    <div class="options-grid">
                        ${['A', 'B', 'C', 'D'].map(optKey => {
                            const optVal = q[`opt_${optKey.toLowerCase()}`];
                            if (!optVal) return '';
                            return `
                                <div class="option-item" data-opt="${UI.escapeHtml(optVal)}">
                                    <div class="option-badge">${optKey}</div>
                                    <div class="option-label">${UI.escapeHtml(optVal)}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    },

    setupListeners() {
        const options = document.querySelectorAll('.option-item');
        options.forEach(opt => {
            opt.onclick = () => {
                const card = opt.closest('.question-card');
                const qId = card.getAttribute('data-q-id');
                const val = opt.getAttribute('data-opt');

                // Select visual
                card.querySelectorAll('.option-item').forEach(i => i.classList.remove('selected'));
                opt.classList.add('selected');

                // Save answer
                this.state.answers[qId] = val;
            };
        });

        document.getElementById('btn-submit-quiz').onclick = () => this.handleSubmit();
        document.getElementById('btn-cancel-quiz').onclick = () => window.location.hash = 'material-detail-page';
        document.getElementById('btn-retry-quiz').onclick = () => this.load();
        document.getElementById('btn-finish-quiz').onclick = () => window.location.hash = 'material-detail-page';
    },

    async handleSubmit() {
        const answeredCount = Object.keys(this.state.answers).length;
        if (answeredCount < this.state.questions.length) {
            const confirmed = await UI.confirm('Kamu belum menjawab semua soal. Tetap kumpulkan?', 'Jawaban Belum Lengkap');
            if (!confirmed) return;
        }

        UI.showLoading();
        try {
            const res = await API.submitQuiz({
                quiz_id: this.state.quiz.id,
                answers: this.state.answers
            });

            this.renderResult(res.data);

        } catch (error) {
            UI.showNotification(error.message, 'error');
        } finally {
            UI.hideLoading();
        }
    },

    renderResult(data) {
        document.getElementById('quiz-content').classList.add('d-none');
        const resultView = document.getElementById('quiz-result');
        resultView.classList.remove('d-none');

        const percentage = Math.round(data.percentage || 0);
        const isPassed = percentage >= (this.state.quiz.passing_score || 60);

        document.getElementById('result-percentage').textContent = `${percentage}%`;
        document.getElementById('result-percentage').className = `score-value ${isPassed ? 'text-success' : 'text-danger'}`;
        document.getElementById('result-title').textContent = isPassed ? 'Selamat! Kamu Lulus' : 'Yah, Belum Lulus';
        document.getElementById('result-icon-box').className = `result-icon-box mb-24 ${isPassed ? '' : 'fail'}`;
        document.getElementById('result-icon-box').innerHTML = `<i data-lucide="${isPassed ? 'award' : 'alert-circle'}" class="icon-xl"></i>`;
        
        document.getElementById('result-points').textContent = `+${data.total_points || 0}`;
        document.getElementById('btn-finish-quiz').textContent = isPassed ? 'Lanjut Belajar' : 'Kembali ke Materi';

        if (window.lucide) window.lucide.createIcons();
        
        if (isPassed && typeof confetti === 'function') {
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, zIndex: 10007 });
        }
    }
};
