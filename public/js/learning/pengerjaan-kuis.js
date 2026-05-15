/** Student quiz player logic. */
var currentQuizData = null;

async function startFinalQuiz(materialId) {
    console.log('Starting Final Quiz for material:', materialId);
    showLoading(true);
    
    try {
        if (!materialId) {
            console.error('Final Quiz Error: materialId is undefined');
            showNotification('Materi tidak valid. Silakan muat ulang halaman.', 'error');
            return;
        }

        const quizRes = await API.getQuiz(materialId);
        const quizData = quizRes.data;
        
        if (!quizData) {
            console.warn('Final Quiz: No quiz found for material', materialId);
            showNotification('Kuis untuk materi ini belum dibuat oleh admin.', 'warning');
            return;
        }
        
        const activeQuiz = Array.isArray(quizData) ? quizData[0] : quizData;
        if (!activeQuiz || !activeQuiz.id) {
            showNotification('Format kuis tidak valid.', 'error');
            return;
        }
        
        console.log('Loading questions for quiz:', activeQuiz.id);
        const qRes = await API.getQuizQuestions(activeQuiz.id);
        activeQuiz.questions = qRes.data || [];
        
        if (activeQuiz.questions.length === 0) {
            showNotification('Kuis ini belum memiliki pertanyaan.', 'warning');
            return;
        }

        currentQuizData = activeQuiz;
        renderQuizUI();
    } catch (error) {
        console.error('Final Quiz Load Error:', error);
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

function renderQuizUI() {
    const detailContainer = document.getElementById('material-detail');
    if (!detailContainer) {
        console.error('Final Quiz Error: #material-detail container not found');
        return;
    }
    
    let questionsHtml = currentQuizData.questions.map((q, index) => {
        let options = [];
        try { 
            options = typeof q.options === 'string' ? JSON.parse(q.options) : q.options; 
        } catch(e) { 
            console.error('Failed to parse options for question', q.id, e);
            options = []; 
        }
        
        if (!Array.isArray(options)) options = [];

        const optsHtml = options.map((opt, i) => `
            <label class="quiz-option-label">
                <input type="radio" name="q_${q.id}" value="${App.Utils.escapeHtml(String(opt))}" class="quiz-option-input">
                <span class="quiz-option-text">${App.Utils.escapeHtml(String(opt))}</span>
            </label>
        `).join('');

        return `
            <div class="mb-32 quiz-question-item">
                <h4 class="mb-12">${index + 1}. ${App.Utils.escapeHtml(q.question_text)}</h4>
                <div class="quiz-options-grid">
                    ${optsHtml}
                </div>
            </div>
        `;
    }).join('');

    detailContainer.innerHTML = `
        <div class="content-card">
            <span class="category-tag category-tag-detail">Kuis Akhir</span>
            <h2 class="mb-12">${App.Utils.escapeHtml(currentQuizData.title)}</h2>
            <p class="text-muted mb-32">${App.Utils.escapeHtml(currentQuizData.description || '')}</p>
            
            <form id="quiz-form" onsubmit="submitFinalQuiz(event)">
                ${questionsHtml}
                <div class="mt-32">
                    <button type="submit" class="btn-full">Kirim Jawaban & Lihat Hasil</button>
                    <button type="button" class="btn-text btn-full mt-8" onclick="viewMaterial(${currentQuizData.material_id})">Batal</button>
                </div>
            </form>
        </div>
    `;
    window.scrollTo(0, 0);
}

async function submitFinalQuiz(event) {
    event.preventDefault();
    
    if (!currentQuizData) return;

    let answers = {};
    let allAnswered = true;
    
    currentQuizData.questions.forEach(q => {
        const selected = document.querySelector(`input[name="q_${q.id}"]:checked`);
        if (selected) {
            answers[q.id] = selected.value;
        } else {
            allAnswered = false;
        }
    });

    if (!allAnswered) {
        showNotification('Pastikan semua pertanyaan sudah dijawab ya sebelum dikirim.', 'warning');
        return;
    }

    showLoading(true);
    try {
        const resultRes = await API.submitQuiz(currentQuizData.id, answers);
        const result = resultRes.data;
        
        // Refresh auth data to sync points/streak
        if (typeof checkAuth === 'function') {
            await checkAuth();
        }

        const detailContainer = document.getElementById('material-detail');
        const isPassed = result.percentage >= (currentQuizData.passing_score || 60);
        
        if (isPassed && typeof triggerConfetti === 'function') {
            triggerConfetti();
        }

        const resultIcon = isPassed ? '<div class="css-art-trophy"></div>' : '<div class="css-art-fail"></div>';

        detailContainer.innerHTML = `
            <div class="content-card quiz-result-card text-center">
                <div class="mb-24 d-flex justify-center">${resultIcon}</div>
                <h2 class="mb-12">${isPassed ? 'Yeay! Selamat, kamu lulus kuis ini.' : 'Jangan menyerah! Yuk, ulangi kuisnya.'}</h2>
                <p class="text-muted mb-32">Kamu berhasil mengumpulkan skor <strong>${result.percentage}%</strong></p>
                
                <div class="d-flex flex-column gap-12">
                    ${!isPassed ? `<button class="btn-full" onclick="startFinalQuiz(${currentQuizData.material_id})">Ulangi Kuis</button>` : ''}
                    <button class="btn-outline btn-full" onclick="viewMaterial(${currentQuizData.material_id})">Kembali ke Materi</button>
                    <button class="btn-text btn-full" onclick="showPage('progress-page')">Lihat Progress Saya</button>
                </div>
            </div>
        `;
        window.scrollTo(0, 0);
    } catch (error) {
        console.error('Submit Quiz Error:', error);
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

// Explicitly expose functions to window
window.startFinalQuiz = startFinalQuiz;
window.submitFinalQuiz = submitFinalQuiz;
window.renderQuizUI = renderQuizUI;
