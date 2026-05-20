/**
 * Belajaryuk - Materials Module
 */

import { API } from './api.js';
import { UI } from './ui.js';

export const Materials = {
    allMaterials: [],
    activeCategory: 'all',

    async load() {
        const listContainer = document.getElementById('materials-list');
        if (!listContainer) return;

        // 1. Show Skeletons
        listContainer.innerHTML = Array(6).fill(0).map(() => `
            <div class="material-card" style="pointer-events: none; opacity: 0.7;">
                <div class="skeleton-box" style="height: 180px; width: 100%; border-radius: 12px 12px 0 0;"></div>
                <div class="material-card-content">
                    <div class="skeleton-box" style="height: 14px; width: 40%; margin-bottom: 12px;"></div>
                    <div class="skeleton-box" style="height: 20px; width: 90%; margin-bottom: 8px;"></div>
                    <div class="skeleton-box" style="height: 20px; width: 60%;"></div>
                </div>
            </div>
        `).join('');

        try {
            // Senior UX: Fetch materials and user progress in parallel
            const [matsRes, catsRes, progressRes] = await Promise.all([
                API.getMaterials(1, 200).catch(() => ({ data: { materials: [] } })),
                API.get('/materials/categories').catch(() => ({ data: [] })),
                API.getProgressByCategories().catch(() => ({ data: { materials: [] } }))
            ]);

            const rawMaterials = matsRes.data?.materials || matsRes.data || [];
            this.allMaterials = Array.isArray(rawMaterials) ? rawMaterials : [];
            
            // Map progress to materials
            const userDetailedProgress = progressRes.data?.materials || [];
            this.allMaterials = this.allMaterials.map(m => {
                const prog = userDetailedProgress.find(p => p.id == m.id);
                return { ...m, progress_percentage: prog ? prog.percentage : 0 };
            });

            const dbCategories = Array.isArray(catsRes.data) ? catsRes.data : (Array.isArray(catsRes) ? catsRes : []);

            this.renderCategoryOptions(dbCategories);
            this.setupListeners();

            if (this.allMaterials.length === 0) {
                listContainer.innerHTML = `
                    <div class="empty-state bg-white" style="grid-column: 1 / -1; width: 100%;">
                        <div class="css-art-empty-box" style="transform: scale(1.2);"></div>
                        <h3>Wah, Kelas Masih Kosong!</h3>
                        <p>Belum ada materi yang tersedia saat ini.</p>
                    </div>`;
                return;
            }

            // Check if there's a pending deep link from dashboard
            const pendingId = localStorage.getItem('pending_material_id');
            if (pendingId) {
                localStorage.removeItem('pending_material_id');
                this.viewMaterial(pendingId);
            } else {
                this.filter();
            }

        } catch (error) {
            console.error('Materials load error:', error);
            listContainer.innerHTML = `<p class="text-danger">Gagal memuat materi.</p>`;
        }
    },

    setupListeners() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.oninput = () => this.filter();
        }

        const trigger = document.getElementById('categoryDropdownTrigger');
        if (trigger) {
            trigger.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('categoryDropdown')?.classList.toggle('show');
            };
        }

        // Global click to close dropdown
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('categoryDropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    },

    renderCategoryOptions(categories) {
        const dropdownMenu = document.getElementById('categoryDropdownMenu');
        const catTabs = document.getElementById('categoryTabs');
        if (!dropdownMenu) return;

        const items = ['all'];
        categories.forEach(c => {
            const val = typeof c === 'string' ? c : (c.category || c.name);
            if (val && !items.includes(val)) items.push(val);
        });

        dropdownMenu.innerHTML = items.map(val => {
            const label = val === 'all' ? 'Semua Kategori' : val;
            return `<div class="custom-dropdown-item ${this.activeCategory === val ? 'active' : ''}" data-value="${val}">${UI.escapeHtml(label)}</div>`;
        }).join('');

        dropdownMenu.querySelectorAll('.custom-dropdown-item').forEach(item => {
            item.onclick = () => {
                this.setCategory(item.getAttribute('data-value'));
                dropdownMenu.parentElement.classList.remove('show');
            };
        });

        if (catTabs) {
            catTabs.innerHTML = items.map(val => {
                const label = val === 'all' ? 'Semua' : val;
                return `<div class="category-tab ${this.activeCategory === val ? 'active' : ''}" data-category="${val}">${UI.escapeHtml(label)}</div>`;
            }).join('');

            catTabs.querySelectorAll('.category-tab').forEach(tab => {
                tab.onclick = () => this.setCategory(tab.getAttribute('data-category'));
            });
        }
    },

    setCategory(category) {
        this.activeCategory = category;
        const labelEl = document.getElementById('current-category-label');
        if (labelEl) labelEl.innerText = (category === 'all') ? 'Semua Kategori' : category;

        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.toggle('active', tab.getAttribute('data-category') === category);
        });

        this.filter();
    },

    filter() {
        const query = document.getElementById('searchInput')?.value.toLowerCase().trim() || '';
        
        const filtered = this.allMaterials.filter(m => {
            const title = String(m.title || '').toLowerCase();
            const desc = String(m.description || '').toLowerCase();
            const matCat = String(m.category || 'Umum').toLowerCase();

            const matchQuery = !query || title.includes(query) || desc.includes(query);
            const matchCat = (this.activeCategory === 'all') || (matCat === this.activeCategory.toLowerCase());
            return matchQuery && matchCat;
        });

        this.renderList(filtered);
    },

    renderList(materials) {
        const container = document.getElementById('materials-list');
        if (!container) return;

        if (materials.length === 0) {
            container.innerHTML = `<div class="empty-state bg-white" style="grid-column: 1 / -1; width: 100%;"><h3>Tidak ditemukan.</h3></div>`;
            return;
        }

        const getThumb = (thumb) => {
            if (!thumb) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80';
            if (thumb.startsWith('http') || thumb.startsWith('/')) return thumb;
            return '/public/uploads/thumbnails/' + thumb;
        };

        const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };

        container.innerHTML = materials.map((m, index) => {
            const difficulty = diffMap[m.difficulty] || 'Pemula';
            return `
                <div class="material-card reveal-on-scroll is-visible" style="transition-delay: ${index * 0.05}s">
                    <div class="img-wrapper" data-id="${m.id}">
                        <img src="${getThumb(m.thumbnail)}" alt="${UI.escapeHtml(m.title)}" loading="lazy">
                    </div>
                    <div class="material-card-content">
                        <span class="category-tag">${UI.escapeHtml(m.category || 'Umum')}</span>
                        <span class="meta-tag">• ${difficulty}</span>
                        <h3 data-id="${m.id}">${UI.escapeHtml(m.title)}</h3>
                        <p>${UI.escapeHtml(m.description ? m.description.substring(0, 80) + '...' : '...')}</p>
                    </div>
                    <div class="material-card-footer">
                        <button type="button" class="btn-full" data-id="${m.id}">Mulai Belajar</button>
                    </div>
                </div>
            `;
        }).join('');

        container.querySelectorAll('[data-id]').forEach(el => {
            el.onclick = () => this.viewMaterial(el.getAttribute('data-id'));
        });
        
        if (window.lucide) window.lucide.createIcons();
    },

    async viewMaterial(id) {
        // Logic for viewing material detail
        window.location.hash = 'material-detail-page';
        localStorage.setItem('active_material_id', id);
    }
};
