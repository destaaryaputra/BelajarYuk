/** Admin tab navigation. */
function switchAdminTab(tabId) {
    const allTabs = ['dashboard', 'materi', 'pengguna', 'laporan', 'diskusi', 'pengaturan'];
    
    allTabs.forEach(t => {
        const btn = document.getElementById(`btn-tab-${t}`);
        const view = document.getElementById(`admin-tab-${t}`);
        if (btn) btn.classList.remove('active');
        if (view) view.style.display = 'none';
    });
    
    const activeBtn = document.getElementById(`btn-tab-${tabId}`);
    if (activeBtn) activeBtn.classList.add('active');
    
    let activeView = document.getElementById(`admin-tab-${tabId}`);
    if (!activeView) {
        activeView = document.createElement('div');
        activeView.id = `admin-tab-${tabId}`;
        activeView.className = 'admin-tab-content';
        document.querySelector('.admin-content').appendChild(activeView);
    }
    activeView.style.display = 'block';
    
    const titles = {
        'dashboard': 'Ringkasan & Analitik',
        'materi': 'Kelola Materi',
        'pengguna': 'Data Siswa',
        'laporan': 'Laporan & Analitik Nilai',
        'diskusi': 'Moderasi Forum Siswa',
        'pengaturan': 'Pengaturan Platform'
    };
    document.getElementById('admin-page-title').innerText = titles[tabId] || 'Panel Admin';
    
    if (tabId === 'dashboard') {
        loadAdminDashboard();
    } else if (tabId === 'materi') {
        if (document.getElementById('admin-list-view')) document.getElementById('admin-list-view').style.display = 'block';
        if (document.getElementById('admin-form-view')) document.getElementById('admin-form-view').style.display = 'none';
        if (document.getElementById('admin-submaterial-view')) document.getElementById('admin-submaterial-view').style.display = 'none';
        if (document.getElementById('admin-quiz-view')) document.getElementById('admin-quiz-view').style.display = 'none';
        loadAdminMaterials();
    } else if (tabId === 'pengguna') {
        loadAdminUsers();
    } else if (tabId === 'laporan') {
        loadAdminReports();
    } else if (tabId === 'diskusi') {
        loadAdminDiscussions();
    } else if (tabId === 'pengaturan') {
        loadAdminSettings();
    } else {
        activeView.innerHTML = `
            <div class="empty-state bg-white" style="margin-top: 24px; padding: 64px 20px; border-radius: var(--radius-xl);">
                <div class="css-art-empty-box" style="transform: scale(1.5); margin-bottom: 30px;"></div>
                <h3 style="margin-top: 16px;">Tab Tidak Dikenal</h3>
                <p>Panel ini tidak terdaftar dalam konfigurasi admin.</p>
            </div>
        `;
    }
}

