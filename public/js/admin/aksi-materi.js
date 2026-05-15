/** Admin material edit/delete actions. */
async function handleEditMaterial(id) {
    showLoading(true);
    try {
        const response = await API.getMaterialDetail(id);
        let material = response.data.material || response.data;
        if (Array.isArray(material)) material = material[0];
        
        document.getElementById('mat-id').value = material.id;
        document.getElementById('mat-title').value = material.title || '';
        document.getElementById('mat-category').value = material.category || '';
        document.getElementById('mat-difficulty').value = material.difficulty || 'beginner';
        document.getElementById('mat-duration').value = material.duration_minutes || 0;
        document.getElementById('mat-video').value = material.video_url || '';
        document.getElementById('mat-desc').value = material.description || '';
        
        initQuillEditors();
        if (matQuill) {
            matQuill.clipboard.dangerouslyPasteHTML(material.content || '');
        } else {
            document.getElementById('mat-content').value = material.content || '';
        }
        
        document.getElementById('admin-form-title').innerText = 'Edit Materi: ' + material.title;
        toggleAdminForm(true);
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

async function handleDeleteMaterial(id) {
    if (!confirm('Yakin ingin menghapus modul materi ini? Ingat, semua kuis dan progres belajar siswa di modul ini akan ikut terhapus!')) return;
    
    showLoading(true);
    try {
        const response = await API.deleteMaterial(id);
        showNotification(response.message || 'Materi berhasil dihapus.', 'success');
        loadAdminMaterials();
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}
