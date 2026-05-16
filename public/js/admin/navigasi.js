/** Admin tab navigation. */
function switchAdminTab(tabId) {
    const allTabs = ['dashboard', 'materi', 'pengguna', 'laporan', 'diskusi', 'pengaturan'];
    
    allTabs.forEach(t => {
        const btn = document.getElementById(`btn-tab-${t}`);
        const view = document.getElementById(`admin-tab-${t}`);
        if (btn) btn.classList.remove('active');
        if (view) view.classList.add('d-none');
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
    activeView.classList.remove('d-none');
    
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
        const listView = document.getElementById('admin-list-view');
        const formView = document.getElementById('admin-form-view');
        const subView = document.getElementById('admin-submaterial-view');
        const quizView = document.getElementById('admin-quiz-view');

        if (listView) listView.classList.remove('d-none');
        if (formView) formView.classList.add('d-none');
        if (subView) subView.classList.add('d-none');
        if (quizView) quizView.classList.add('d-none');
        
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
            <div class="empty-state bg-white mt-24" style="padding: 64px 20px; border-radius: var(--radius-xl);">
                <div class="css-art-empty-box" style="transform: scale(1.5); margin-bottom: 30px;"></div>
                <h3 class="mt-24">Tab Tidak Dikenal</h3>
                <p>Panel ini tidak terdaftar dalam konfigurasi admin.</p>
            </div>
        `;
    }
}

