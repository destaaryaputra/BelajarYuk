/**
 * Belajaryuk - Admin Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Admin = {
    charts: {
        registration: null,
        category: null
    },

    async load() {
        const userData = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        if (userData) {
            const user = JSON.parse(userData);
            const adminNameEl = document.getElementById('admin-user-name');
            if (adminNameEl) adminNameEl.textContent = user.full_name || 'Admin';
        }

        this.switchTab('dashboard');
    },

    switchTab(tabId) {
        const allTabs = ['dashboard', 'materi', 'pengguna', 'laporan', 'diskusi', 'pengaturan'];
        
        allTabs.forEach(t => {
            document.getElementById(`btn-tab-${t}`)?.classList.remove('active');
            document.getElementById(`admin-tab-${t}`)?.classList.add('d-none');
        });
        
        document.getElementById(`btn-tab-${tabId}`)?.classList.add('active');
        document.getElementById(`admin-tab-${tabId}`)?.classList.remove('d-none');
        
        const titles = {
            'dashboard': 'Ringkasan & Analitik',
            'materi': 'Kelola Materi',
            'pengguna': 'Data Siswa',
            'laporan': 'Laporan & Analitik Nilai',
            'diskusi': 'Moderasi Forum',
            'pengaturan': 'Pengaturan Platform'
        };
        const titleEl = document.getElementById('admin-page-title');
        if (titleEl) titleEl.innerText = titles[tabId] || 'Panel Admin';
        
        if (tabId === 'dashboard') this.loadDashboard();
        
        if (window.lucide) window.lucide.createIcons();
    },

    async loadDashboard() {
        try {
            const [usersRes, materialsRes] = await Promise.all([
                API.get('/auth/users'),
                API.getMaterials(1, 100)
            ]);

            const students = (usersRes.data?.users || []).filter(u => u.role === 'student');
            const materials = materialsRes.data?.materials || [];

            document.getElementById('admin-stat-users').textContent = students.length;
            document.getElementById('admin-stat-materials').textContent = materials.length;

            this.renderCharts(students, materials);
        } catch (error) {
            console.error('Admin dashboard error:', error);
        }
    },

    renderCharts(students, materials) {
        if (typeof Chart === 'undefined') return;

        // Simple Registration Chart
        const ctxReg = document.getElementById('adminRegistrationChart');
        if (ctxReg) {
            if (this.charts.registration) this.charts.registration.destroy();
            this.charts.registration = new Chart(ctxReg, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Siswa Baru',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#0f766e',
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // Simple Category Chart
        const ctxCat = document.getElementById('adminCategoryChart');
        if (ctxCat) {
            if (this.charts.category) this.charts.category.destroy();
            this.charts.category = new Chart(ctxCat, {
                type: 'bar',
                data: {
                    labels: ['Design', 'Dev', 'AI'],
                    datasets: [{
                        label: 'Materi',
                        data: [5, 10, 4],
                        backgroundColor: '#0ea5e9'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }
};
