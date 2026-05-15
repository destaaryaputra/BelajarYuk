/** Admin platform settings overview backed by live API data. */
async function loadAdminSettings() {
    const container = document.getElementById('admin-tab-pengaturan');
    if (!container) return;

    container.innerHTML = '<div class="content-card"><p class="text-center p-20">Memuat pengaturan platform...</p></div>';

    try {
        const [usersRes, materialsRes, categoriesRes] = await Promise.all([
            API.getAllUsers(),
            API.getMaterials(1, 100),
            API.getCategories()
        ]);

        const users = usersRes.data?.users || usersRes.data || [];
        const materialsData = materialsRes.data || {};
        const materials = materialsData.materials || materialsData || [];
        const categories = categoriesRes.data || [];
        const admins = users.filter(user => user.role === 'admin');
        const students = users.filter(user => user.role === 'student');
        const currentAdmin = JSON.parse(localStorage.getItem(CONFIG.STORAGE_KEYS.USER_DATA) || '{}');

        container.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><h3>Total Siswa</h3><div class="value">${students.length}</div></div>
                <div class="stat-card"><h3>Admin</h3><div class="value">${admins.length}</div></div>
                <div class="stat-card"><h3>Kategori Materi</h3><div class="value">${categories.length}</div></div>
            </div>
            <div class="content-card">
                <h3>Profil Admin</h3>
                <div class="profile-field"><label>Nama</label><span>${escapeAdminHtml(currentAdmin.full_name || 'Admin')}</span></div>
                <div class="profile-field"><label>Username</label><span>@${escapeAdminHtml(currentAdmin.username || 'admin')}</span></div>
                <div class="profile-field"><label>Email</label><span>${escapeAdminHtml(currentAdmin.email || '-')}</span></div>
                <div class="profile-field"><label>Peran</label><span>Admin</span></div>
            </div>
            <div class="content-card">
                <h3>Konfigurasi Aplikasi</h3>
                <div class="profile-field"><label>Nama Aplikasi</label><span>Belajaryuk</span></div>
                <div class="profile-field"><label>API Base URL</label><span>${escapeAdminHtml(CONFIG.API_BASE_URL)}</span></div>
                <div class="profile-field"><label>Request Timeout</label><span>${Number(CONFIG.REQUEST_TIMEOUT || 0) / 1000} detik</span></div>
                <div class="profile-field"><label>Mode Data</label><span>Sinkron langsung dari database melalui API</span></div>
            </div>
            <div class="content-card">
                <h3>Ringkasan Data Database</h3>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr><th>Data</th><th>Jumlah</th><th>Sumber</th></tr></thead>
                        <tbody>
                            <tr><td>Siswa</td><td>${students.length}</td><td>tabel pengguna</td></tr>
                            <tr><td>Admin</td><td>${admins.length}</td><td>tabel pengguna</td></tr>
                            <tr><td>Materi Aktif</td><td>${materialsData.total || materials.length}</td><td>tabel materi</td></tr>
                            <tr><td>Kategori</td><td>${categories.length}</td><td>tabel materi</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (error) {
        handleAPIError(error);
        container.innerHTML = '<div class="content-card"><p class="text-danger">Gagal memuat pengaturan dan ringkasan database.</p></div>';
    }
}

function escapeAdminHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
}
