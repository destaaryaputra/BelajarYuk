/** Admin material form and AI generation. */
function toggleAdminForm(show) {
    const listView = document.getElementById('admin-list-view');
    const formView = document.getElementById('admin-form-view');
    const submatView = document.getElementById('admin-submaterial-view');
    const quizView = document.getElementById('admin-quiz-view');
    
    if (show) {
        listView.style.display = 'none';
        formView.style.display = 'block';
        submatView.style.display = 'none';
        if (quizView) quizView.style.display = 'none';
        initQuillEditors();
    } else {
        listView.style.display = 'block';
        formView.style.display = 'none';
        submatView.style.display = 'none';
        if (quizView) quizView.style.display = 'none';
        document.getElementById('create-material-form').reset();
        if (matQuill) matQuill.setContents([]);
        document.getElementById('mat-id').value = '';
        document.getElementById('admin-form-title').innerText = 'Tambah Materi Baru';
    }
}

async function handleSaveMaterial(event) {
    event.preventDefault();
    
    const matId = document.getElementById('mat-id').value;
    const formData = new FormData();
    formData.append('title', document.getElementById('mat-title').value.trim());
    formData.append('category', document.getElementById('mat-category').value.trim());
    formData.append('difficulty', document.getElementById('mat-difficulty').value);
    formData.append('duration_minutes', document.getElementById('mat-duration').value || 0);
    formData.append('video_url', document.getElementById('mat-video').value.trim());
    formData.append('description', document.getElementById('mat-desc').value.trim());
    
    let content = matQuill ? matQuill.root.innerHTML : '';
    if (content === '<p><br></p>') content = '';
    formData.append('content', content.trim());
    
    const fileInput = document.getElementById('mat-thumbnail');
    if (fileInput.files.length > 0) {
        formData.append('thumbnail', fileInput.files[0]);
    }
    
    showLoading(true);
    try {
        let response;
        if (matId) {
            response = await API.updateMaterial(matId, formData);
            showNotification(response.message || 'Materi berhasil diperbarui!', 'success');
        } else {
            response = await API.createMaterial(formData);
            showNotification(response.message || 'Materi berhasil ditambahkan!', 'success');
        }
        toggleAdminForm(false);
        loadAdminMaterials();
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

async function generateCourseWithAI() {
    const topic = prompt("Topik apa yang ingin dibuatkan materinya oleh AI? \n(Contoh: 'Dasar Pemrograman Web' atau 'Tips Belajar Bahasa Inggris')");
    if (!topic) return;

    showLoading(true);
    showNotification('AI sedang merangkum materi untukmu. Tunggu sebentar ya, sekitar 10-15 detik...', 'info', 8000);

    try {
        const response = await API.generateCourseAI(topic);
        const courseData = response?.data?.course || response?.data;
        if (!courseData) throw new Error('Format AI tidak valid');

        document.getElementById('mat-title').value = courseData.title;
        document.getElementById('mat-category').value = courseData.category;
        document.getElementById('mat-desc').value = courseData.description;
        
        initQuillEditors();
        if (matQuill) matQuill.clipboard.dangerouslyPasteHTML(courseData.content || '');

        showNotification('Materi berhasil dirangkum oleh AI! Jangan lupa direview dulu ya sebelum disimpan.', 'success', 5000);
    } catch (error) {
        console.error("AI Gen Error:", error);
        handleAPIError(error);
        showNotification("Ups, AI gagal menyusun materi. Coba gunakan judul yang lebih jelas ya.", "error");
    } finally { showLoading(false); }
}

