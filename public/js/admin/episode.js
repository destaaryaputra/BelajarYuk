/** Admin submaterial listing and edit state. */
let currentSubMaterialsList = [];

function openSubMaterialView(materialId, materialTitle) {
    document.getElementById('admin-list-view').style.display = 'none';
    document.getElementById('admin-form-view').style.display = 'none';
    document.getElementById('admin-submaterial-view').style.display = 'block';
    document.getElementById('admin-quiz-view').style.display = 'none';
    
    document.getElementById('admin-submat-title').innerText = `Kelola Episode: ${materialTitle}`;
    document.getElementById('submat-material-id').value = materialId;
    
    loadSubMaterials(materialId);
}

function toggleSubMaterialView(show) {
    if (!show) {
        document.getElementById('admin-submaterial-view').style.display = 'none';
        document.getElementById('admin-list-view').style.display = 'block';
    }
}

async function loadSubMaterials(materialId) {
    showLoading(true);
    try {
        const response = await API.getSubMaterialsAdmin(materialId);
        const subs = response.data || [];
        currentSubMaterialsList = subs;
        
        let html = `
            <div style="overflow-x: auto; border-radius: var(--radius-lg); padding-bottom: 16px;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Part</th><th>Judul Episode</th><th>Media Terlampir</th><th style="text-align: right;">Aksi</th>
                    </tr>
                </thead><tbody>
        `;
        if (subs.length === 0) html += `<tr><td colspan="4" style="text-align: center;">Belum ada episode.</td></tr>`;
        else {
            subs.forEach((s, index) => {
                html += `
                    <tr>
                        <td class="text-bold-muted">${index + 1}</td>
                        <td class="font-medium">${s.title}</td>
                        <td>
                            ${s.video_url ? '<span class="badge badge-video">Video</span>' : ''}
                            ${s.document_url ? '<span class="badge badge-pdf">PDF</span>' : ''}
                        </td>
                        <td class="text-right">
                            <button class="btn-outline btn-text-info mr-8" onclick="handleEditSubMat(${s.id})">Edit</button>
                            <button class="btn-outline btn-text-danger" onclick="handleDeleteSubMat(${s.id}, ${materialId})">Hapus</button>
                        </td>
                    </tr>`;
            });
        }
        html += '</tbody></table></div>';
        document.getElementById('admin-submaterials-table').innerHTML = html;
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

function handleEditSubMat(id) {
    const submat = currentSubMaterialsList.find(s => s.id === id);
    if (!submat) return;

    document.getElementById('admin-submaterial-form-container').style.display = 'block';
    document.getElementById('submat-id').value = submat.id;
    document.getElementById('submat-material-id').value = submat.material_id;
    document.getElementById('submat-title').value = submat.title || '';
    document.getElementById('submat-video').value = submat.video_url || '';
    
    initQuillEditors();
    if (submatQuill) submatQuill.clipboard.dangerouslyPasteHTML(submat.content || '');
    else document.getElementById('submat-content').value = submat.content || '';

    document.getElementById('admin-submat-form-title').innerText = 'Edit Episode: ' + submat.title;
    window.scrollTo(0, document.getElementById('admin-submaterial-form-container').offsetTop);
}

function toggleSubMaterialForm(show) {
    const formContainer = document.getElementById('admin-submaterial-form-container');
    formContainer.style.display = show ? 'block' : 'none';
    
    const matId = document.getElementById('submat-material-id').value;
    document.getElementById('create-submaterial-form').reset();
    document.getElementById('submat-material-id').value = matId;
    document.getElementById('submat-id').value = '';
    document.getElementById('admin-submat-form-title').innerText = 'Form Episode Baru';
    
    initQuillEditors();
    if (submatQuill) submatQuill.setContents([]);
}

async function handleSaveSubMaterial(event) {
    event.preventDefault();
    const matId = document.getElementById('submat-material-id').value;
    const submatId = document.getElementById('submat-id').value;
    
    const formData = new FormData();
    formData.append('material_id', matId);
    formData.append('title', document.getElementById('submat-title').value.trim());
    formData.append('video_url', document.getElementById('submat-video').value.trim());
    
    let content = submatQuill ? submatQuill.root.innerHTML : '';
    if (content === '<p><br></p>') content = '';
    formData.append('content', content.trim());
    
    const fileInput = document.getElementById('submat-pdf');
    if (fileInput.files.length > 0) formData.append('pdf', fileInput.files[0]);
    
    showLoading(true);
    try {
        if (submatId) {
            await API.updateSubMaterial(submatId, formData);
            showNotification('Episode berhasil diperbarui!', 'success');
        } else {
            await API.createSubMaterial(formData);
            showNotification('Episode berhasil ditambahkan!', 'success');
        }
        toggleSubMaterialForm(false);
        loadSubMaterials(matId);
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

async function handleDeleteSubMat(id, materialId) {
    if (!confirm('Yakin ingin menghapus episode ini?')) return;
    showLoading(true);
    try {
        await API.deleteSubMaterial(id);
        showNotification('Episode dihapus!', 'success');
        loadSubMaterials(materialId);
    } catch (error) { handleAPIError(error); } 
    finally { showLoading(false); }
}

