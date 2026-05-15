/** Admin user management. */
async function loadAdminUsers() {
    showLoading(true);
    try {
        const response = await API.getAllUsers();
        // Handle paginated response: data.users contains the array
        const users = response.data.users || response.data || [];
        const students = users.filter(user => user.role === 'student');
        
        let html = `
            <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Informasi Siswa</th>
                        <th>Email</th>
                        <th>Bergabung</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        if (students.length === 0) {
            html += `<tr><td colspan="5" style="text-align: center;">Belum ada data siswa yang terdaftar.</td></tr>`;
        } else {
            students.forEach(u => {
                const date = new Date(u.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
                const encodedName = encodeURIComponent(u.full_name || '');
                
                html += `
                    <tr>
                        <td class="text-muted">#${u.id}</td>
                        <td>
                            <div class="font-medium">${escapeHtml(u.full_name)}</div>
                            <div class="text-sm text-muted">@${escapeHtml(u.username)}</div>
                        </td>
                        <td>${escapeHtml(u.email)}</td>
                        <td class="text-muted">${date}</td>
                        <td style="text-align: right;">
                            <button class="btn-outline btn-text-danger" onclick="handleDeleteUser(${u.id}, decodeURIComponent('${encodedName}'))">Hapus</button>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += '</tbody></table></div>';
        document.getElementById('admin-users-table').innerHTML = html;
        
    } catch (error) {
        handleAPIError(error);
        document.getElementById('admin-users-table').innerHTML = '<p class="text-danger">Gagal memuat data siswa.</p>';
    } finally {
        showLoading(false);
    }
}

async function handleUpdateUserRole(userId, newRole) {
    if(!confirm(`Yakin ingin mengubah peran (role) pengguna ini menjadi ${newRole.toUpperCase()}?`)) {
        loadAdminUsers();
        return;
    }
    showLoading(true);
    try { await API.updateUserRole(userId, newRole); showNotification('Role berhasil diperbarui!', 'success'); loadAdminUsers(); }
    catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

async function handleDeleteUser(userId, userName) {
    if(!confirm(`Peringatan! Yakin ingin menghapus akun '${userName}'? Semua data pembelajarannya akan hilang permanen.`)) return;
    showLoading(true);
    try { await API.deleteUser(userId); showNotification('Pengguna berhasil dihapus!', 'success'); loadAdminUsers(); }
    catch(err) { handleAPIError(err); } finally { showLoading(false); }
}

