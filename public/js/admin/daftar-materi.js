/** Admin material listing. */
async function loadAdminMaterials() {
    showLoading(true);
    try {
        const response = await API.getMaterials(1, 50);
        const materials = response.data.materials || response.data || [];
        
        let tableHtml = `
            <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul Kursus</th>
                        <th>Kategori</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        if (materials.length === 0) {
            tableHtml += `<tr><td colspan="4" style="text-align: center;">Belum ada modul materi yang ditambahkan.</td></tr>`;
        } else {
            materials.forEach(m => {
                const encodedTitle = encodeURIComponent(m.title || '');
                
                tableHtml += `
                    <tr>
                        <td class="text-muted">#${m.id}</td>
                        <td class="font-medium">${escapeHtml(m.title)}</td>
                        <td><span class="badge badge-student">${escapeHtml(m.category || 'Umum')}</span></td>
                        <td style="text-align: right;">
                            <div class="d-flex justify-end gap-8">
                                <button class="btn-outline btn-small btn-outline-warning" onclick="openQuizView(${m.id}, decodeURIComponent('${encodedTitle}'))">Kuis</button>
                                <button class="btn-outline btn-small btn-outline-info" onclick="openSubMaterialView(${m.id}, decodeURIComponent('${encodedTitle}'))">Episode</button>
                                <button class="btn-outline btn-small" onclick="handleEditMaterial(${m.id})">Edit</button>
                                <button class="btn-outline btn-text-danger" onclick="handleDeleteMaterial(${m.id})">Hapus</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        tableHtml += '</tbody></table></div>';
        document.getElementById('admin-materials-table').innerHTML = tableHtml;
        
    } catch (error) {
        handleAPIError(error);
        document.getElementById('admin-materials-table').innerHTML = '<p class="text-danger">Gagal memuat data admin.</p>';
    } finally {
        showLoading(false);
    }
}

