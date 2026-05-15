/** 
 * Material catalogue logic. 
 * Mengelola pemuatan, pencarian, dan penyaringan materi.
 */
var allMaterialsData = [];
var activeCategory = 'all';

/**
 * Memuat data materi dan kategori dari API
 */
async function loadMaterials() {
    const listContainer = document.getElementById('materials-list');
    if (!listContainer) return;

    // 1. TAMPILKAN SKELETON (Efek Loading)
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
        // 2. FETCH DATA DARI API
        const [matsRes, catsRes] = await Promise.all([
            API.getMaterials(1, 200).catch(() => ({ data: { materials: [] } })),
            API.getCategories().catch(() => ({ data: [] }))
        ]);

        const rawMaterials = matsRes.data?.materials || matsRes.data || [];
        allMaterialsData = Array.isArray(rawMaterials) ? rawMaterials : [];
        
        const dbCategories = Array.isArray(catsRes.data) ? catsRes.data : (Array.isArray(catsRes) ? catsRes : []);

        // 3. RENDER OPSI KATEGORI
        renderCategoryOptions(dbCategories);

        // 4. INISIALISASI EVENT LISTENERS
        setupMaterialFilters();

        // 5. JIKA DATA KOSONG
        if (allMaterialsData.length === 0) {
            listContainer.innerHTML = `
                <div class="empty-state bg-white" style="grid-column: 1 / -1; width: 100%;">
                    <div class="css-art-empty-box" style="transform: scale(1.2);"></div>
                    <h3>Wah, Kelas Masih Kosong!</h3>
                    <p>Belum ada materi yang tersedia saat ini. Silakan kembali lagi nanti.</p>
                </div>`;
            return;
        }

        // 6. JALANKAN FILTER AWAL
        filterMaterials();

        // 7. INISIALISASI ANIMASI & IKON
        setTimeout(() => {
            if (typeof initScrollAnimations === 'function') initScrollAnimations();
            if (typeof renderIcons === 'function') {
                try { renderIcons(); } catch(e) {}
            }
        }, 150);

    } catch (error) {
        listContainer.innerHTML = `<p class="text-danger">Gagal memuat daftar materi. Silakan coba lagi.</p>`;
    }
}

/**
 * Menyiapkan event listeners untuk pencarian dan filter
 */
function setupMaterialFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput && !searchInput.dataset.initialized) {
        searchInput.addEventListener('input', () => {
            filterMaterials();
        });
        searchInput.dataset.initialized = "true";
    }

    const trigger = document.getElementById('categoryDropdownTrigger');
    if (trigger) {
        trigger.onclick = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            toggleCategoryDropdown();
        };
    }
}

/**
 * Merender daftar kategori ke dalam Dropdown Menu dan Tabs
 */
function renderCategoryOptions(categories) {
    const dropdownMenu = document.getElementById('categoryDropdownMenu');
    const catTabs = document.getElementById('categoryTabs');
    if (!dropdownMenu) return;

    const items = ['all'];
    if (Array.isArray(categories)) {
        categories.forEach(c => {
            const val = typeof c === 'string' ? c : (c.category || c.name);
            if (val && !items.includes(val)) items.push(val);
        });
    }

    dropdownMenu.innerHTML = items.map(val => {
        const label = val === 'all' ? 'Semua Kategori' : val;
        const isActive = activeCategory === val;
        return `
            <div class="custom-dropdown-item ${isActive ? 'active' : ''}" 
                 data-value="${escapeHtml(val)}" 
                 onclick="event.stopPropagation(); selectCategory('${escapeHtml(val)}')">
                ${escapeHtml(label)}
            </div>`;
    }).join('');

    if (catTabs) {
        catTabs.innerHTML = items.map(val => {
            const label = val === 'all' ? 'Semua' : val;
            const isActive = activeCategory === val;
            return `
                <div class="category-tab ${isActive ? 'active' : ''}" 
                     data-category="${escapeHtml(val)}" 
                     onclick="setCategoryFilter('${escapeHtml(val)}')">
                    ${escapeHtml(label)}
                </div>`;
        }).join('');
    }
}

/**
 * Toggle (Buka/Tutup) Dropdown Kategori
 */
function toggleCategoryDropdown() {
    const dropdown = document.getElementById('categoryDropdown');
    if (dropdown) {
        const syllabus = document.getElementById('syllabusDropdown');
        if (syllabus) syllabus.classList.remove('show');
        
        const isCurrentlyShowing = dropdown.classList.contains('show');
        if (isCurrentlyShowing) {
            dropdown.classList.remove('show');
        } else {
            dropdown.classList.add('show');
            if (typeof renderIcons === 'function') renderIcons();
        }
    }
}

// Global click listener
document.addEventListener('click', function(event) {
    const catDropdown = document.getElementById('categoryDropdown');
    if (catDropdown && catDropdown.classList.contains('show')) {
        if (!catDropdown.contains(event.target)) {
            catDropdown.classList.remove('show');
        }
    }
});

/**
 * Menangani pilihan kategori dari dropdown
 */
function selectCategory(category) {
    const labelEl = document.getElementById('current-category-label');
    const filterInput = document.getElementById('categoryFilter');
    
    if (labelEl) labelEl.innerText = (category === 'all') ? 'Semua Kategori' : category;
    if (filterInput) filterInput.value = category;
    
    const dropdown = document.getElementById('categoryDropdown');
    if (dropdown) dropdown.classList.remove('show');

    document.querySelectorAll('.custom-dropdown-item').forEach(item => {
        item.classList.toggle('active', item.getAttribute('data-value') === category);
    });

    setCategoryFilter(category);
}

/**
 * Mengatur filter kategori dan mengupdate UI tab
 */
function setCategoryFilter(category) {
    activeCategory = category;
    const labelEl = document.getElementById('current-category-label');
    if (labelEl) labelEl.innerText = (category === 'all') ? 'Semua Kategori' : category;

    const filterInput = document.getElementById('categoryFilter');
    if (filterInput) filterInput.value = category;

    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.toggle('active', tab.getAttribute('data-category') === category);
    });

    filterMaterials();
}

/**
 * Inti Logika Penyaringan Materi
 */
function filterMaterials() {
    const searchInput = document.getElementById('searchInput');
    const filterInput = document.getElementById('categoryFilter');
    
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const category = (filterInput ? filterInput.value : activeCategory) || 'all';

    if (!Array.isArray(allMaterialsData)) return;

    const filtered = allMaterialsData.filter(m => {
        if (!m) return false;
        const title = String(m.title || '').toLowerCase();
        const desc = String(m.description || '').toLowerCase();
        const matCat = String(m.category || 'Umum').toLowerCase();

        const matchQuery = !query || title.includes(query) || desc.includes(query);
        const matchCat = (category === 'all') || (matCat === category.toLowerCase());
        return matchQuery && matchCat;
    });

    renderMaterialsList(filtered);
}

/**
 * Menampilkan daftar materi ke layar
 */
function renderMaterialsList(materials) {
    const materialsList = document.getElementById('materials-list');
    if (!materialsList) return;

    if (materials.length === 0) {
        materialsList.innerHTML = `
            <div class="empty-state bg-white" style="grid-column: 1 / -1; width: 100%; padding: 48px 0;">
                <div class="css-art-empty-box" style="transform: scale(1.2);"></div>
                <h3>Oops, Tidak Ditemukan</h3>
                <p>Tidak ada materi yang cocok dengan pencarian atau kategori ini.</p>
                <button class="btn-outline mt-16" onclick="resetMaterialsFilter()">Tampilkan Semua Materi</button>
            </div>`;
        return;
    }

    const getThumbnailUrl = (thumb) => {
        if (!thumb) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80';
        if (thumb.startsWith('http') || thumb.startsWith('/')) return thumb;
        return '/belajaryuk/public/uploads/thumbnails/' + thumb;
    };

    const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };

    materialsList.innerHTML = materials.map((material, index) => {
        const difficulty = diffMap[material.difficulty] || 'Pemula';
        const duration = material.duration_minutes ? ` • ${material.duration_minutes} Menit` : '';
        const title = escapeHtml(material.title);
        const category = material.category || 'Umum';
        const description = escapeHtml(material.description ? material.description.substring(0, 80) + '...' : 'Deskripsi belum tersedia.');
        
        return `
            <div class="material-card reveal-on-scroll is-visible" style="transition-delay: ${index * 0.05}s">
                <div class="img-wrapper" onclick="viewMaterial(${material.id})">
                    <img src="${getThumbnailUrl(material.thumbnail)}" alt="${title}" loading="lazy">
                </div>
                
                <div class="material-card-content">
                    <span class="category-tag clickable-tag" onclick="setCategoryFilter('${escapeHtml(category)}')">${escapeHtml(category)}</span>
                    <span class="meta-tag">• ${difficulty}${duration}</span>
                    <h3 onclick="viewMaterial(${material.id})">${title}</h3>
                    <p>${description}</p>
                </div>
                <div class="material-card-footer">
                    <button onclick="viewMaterial(${material.id})" class="btn-full">Mulai Belajar</button>
                </div>
            </div>
        `;
    }).join('');

    if (typeof initScrollAnimations === 'function') {
        setTimeout(initScrollAnimations, 50);
    }
}

function resetMaterialsFilter() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    setCategoryFilter('all');
}

// Expose fungsi ke global
window.toggleCategoryDropdown = toggleCategoryDropdown;
window.selectCategory = selectCategory;
window.setCategoryFilter = setCategoryFilter;
window.filterMaterials = filterMaterials;
window.resetMaterialsFilter = resetMaterialsFilter;
