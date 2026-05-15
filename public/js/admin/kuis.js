/** Admin quiz management. */
function toggleQuizView(show) {
    document.getElementById('admin-quiz-view').style.display = show ? 'block' : 'none';
    document.getElementById('admin-list-view').style.display = show ? 'none' : 'block';
}

async function openQuizView(materialId, materialTitle) {
    document.getElementById('admin-list-view').style.display = 'none';
    document.getElementById('admin-form-view').style.display = 'none';
    document.getElementById('admin-submaterial-view').style.display = 'none';
    document.getElementById('admin-quiz-view').style.display = 'block';
    
    document.getElementById('admin-quiz-title').innerText = `Kuis: ${materialTitle}`;
    document.getElementById('quiz-material-id').value = materialId;
    
    showLoading(true);
    try {
        const res = await API.getQuiz(materialId);
        const quiz = res.data;
        
        if (quiz && quiz.id) {
            document.getElementById('admin-quiz-setup').style.display = 'none';
            document.getElementById('admin-quiz-manage').style.display = 'block';
            
            document.getElementById('active-quiz-title').innerText = quiz.title;
            document.getElementById('active-quiz-info').innerText = `Passing Score: ${quiz.passing_score} | Soal: ${quiz.total_questions} Butir`;
            document.getElementById('q-quiz-id').value = quiz.id;
            
            loadQuestionsAdmin(quiz.id);
        } else {
            document.getElementById('admin-quiz-setup').style.display = 'block';
            document.getElementById('admin-quiz-manage').style.display = 'none';
        }
    } catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

async function handleCreateQuiz(e) {
    e.preventDefault();
    showLoading(true);
    try {
        const matId = document.getElementById('quiz-material-id').value;
        await API.createQuizAdmin({
            material_id: matId,
            title: document.getElementById('quiz-title').value,
            description: document.getElementById('quiz-desc').value,
            passing_score: document.getElementById('quiz-passing').value,
            time_limit_minutes: document.getElementById('quiz-time').value
        });
        showNotification('Kuis berhasil dibuat!', 'success');
        openQuizView(matId, document.getElementById('admin-quiz-title').innerText.replace('Kuis: ', ''));
    } catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

function toggleQuestionForm(show) {
    document.getElementById('admin-question-form-container').style.display = show ? 'block' : 'none';
    if(!show) {
        const quizId = document.getElementById('q-quiz-id').value;
        document.getElementById('create-question-form').reset();
        document.getElementById('q-quiz-id').value = quizId;
    }
}

async function loadQuestionsAdmin(quizId) {
    try {
        const res = await API.getQuizQuestions(quizId);
        const questions = res.data || [];
        let html = '<div style="overflow-x: auto; padding-bottom: 16px;"><table class="admin-table"><thead><tr><th>No</th><th>Pertanyaan</th><th style="text-align:right;">Aksi</th></tr></thead><tbody>';
        if (questions.length === 0) html += '<tr><td colspan="3" style="text-align:center;">Belum ada soal.</td></tr>';
        else {
            questions.forEach((q, i) => {
                html += `<tr><td>${i+1}</td><td>${q.question_text}</td>
                <td class="text-right"><button class="btn-outline btn-text-danger" onclick="deleteQuestion(${q.id}, ${quizId})">Hapus</button></td></tr>`;
            });
        }
        document.getElementById('admin-questions-table').innerHTML = html + '</tbody></table></div>';
    } catch(err) { handleAPIError(err); }
}

async function handleSaveQuestion(e) {
    e.preventDefault();
    showLoading(true);
    try {
        const qId = document.getElementById('q-quiz-id').value;
        await API.addQuestionAdmin({
            quiz_id: qId, question_text: document.getElementById('q-text').value,
            opt_a: document.getElementById('q-opt-a').value, opt_b: document.getElementById('q-opt-b').value,
            opt_c: document.getElementById('q-opt-c').value, opt_d: document.getElementById('q-opt-d').value,
            correct_opt: document.getElementById('q-correct').value
        });
        toggleQuestionForm(false); loadQuestionsAdmin(qId);
        showNotification('Soal ditambahkan!', 'success');
    } catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

async function deleteQuestion(id, quizId) {
    if(!confirm('Yakin ingin menghapus pertanyaan ini?')) return;
    showLoading(true);
    try { await API.deleteQuestionAdmin(id); loadQuestionsAdmin(quizId); } catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

async function handleDeleteQuiz() {
    const quizId = document.getElementById('q-quiz-id').value;
    const matId = document.getElementById('quiz-material-id').value;
    
    if(!confirm('Yakin ingin menghapus kuis ini? Semua soal di dalamnya juga akan ikut terhapus lho.')) return;
    
    showLoading(true);
    try {
        await API.deleteQuizAdmin(quizId);
        showNotification('Kuis berhasil dihapus!', 'success');
        openQuizView(matId, document.getElementById('admin-quiz-title').innerText.replace('Kuis: ', ''));
    } catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

