/** Material detail and lesson playback logic. */
var currentCourseData = null;

async function viewMaterial(id) {
    console.log('Viewing material:', id);
    const detailPage = document.getElementById('material-detail-page');
    const detailContainer = document.getElementById('material-detail');
    const syllabusContainer = document.getElementById('course-syllabus');

    if (detailPage) showPage('material-detail-page');

    // 1. RENDER SKELETON DETAIL
    if (detailContainer) {
        detailContainer.innerHTML = `
            <div class="content-card">
                <div class="skeleton-box" style="height: 14px; width: 30%; margin-bottom: 20px;"></div>
                <div class="skeleton-box" style="height: 32px; width: 70%; margin-bottom: 16px;"></div>
                <div class="skeleton-box" style="height: 300px; width: 100%; border-radius: 12px; margin-bottom: 24px;"></div>
                <div class="skeleton-box" style="height: 100px; width: 100%; border-radius: 12px;"></div>
            </div>
        `;
    }

    if (syllabusContainer) {
        syllabusContainer.innerHTML = Array(4).fill(0).map(() => `
            <div class="syllabus-item" style="pointer-events: none; opacity: 0.7;">
                <div class="syllabus-item-icon skeleton-box" style="border:none;"></div>
                <div class="syllabus-item-title skeleton-box" style="height: 16px; width: 80%;"></div>
            </div>
        `).join('');
    }

    try {
        const response = await API.getMaterialDetail(id);
        let material = response.data.material || response.data;
        
        if (Array.isArray(material)) material = material[0];
        if (!material) {
            showNotification('Maaf, materi tidak ditemukan.', 'error');
            return;
        }

        currentCourseData = material;

        // Track activity
        API.trackActivity(id).catch(err => console.warn('Gagal mencatat aktivitas:', err));

        // Unlock logic
        let lastCompletedIndex = -1;
        if (response.data.user_progress && response.data.user_progress.completed_at) {
            lastCompletedIndex = (currentCourseData.sub_materials || []).length;
        } else {
            lastCompletedIndex = (currentCourseData.sub_materials || []).length;
        }

        currentCourseData.unlockedIndex = lastCompletedIndex >= 0 ? lastCompletedIndex : 0;
        if (!currentCourseData.sub_materials) currentCourseData.sub_materials = [];

        renderSyllabus();
        
        if (currentCourseData.sub_materials.length > 0) {
            const dropdown = document.getElementById('syllabusDropdown');
            if (dropdown) dropdown.style.display = 'block';
            playLesson(currentCourseData.sub_materials[0].id);
        } else {
            const dropdown = document.getElementById('syllabusDropdown');
            if (dropdown) dropdown.style.display = 'none';
            
            const actionButtons = `
                <div class="action-buttons mt-32">
                    <button onclick="markAsCompleted(${currentCourseData.id})" class="flex-1">
                        Tandai Kursus Selesai
                    </button>
                    <button onclick="startFinalQuiz(${currentCourseData.id})" class="btn-outline flex-1">
                        Kerjakan Kuis Akhir
                    </button>
                </div>
            `;

            const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };
            const difficulty = diffMap[currentCourseData.difficulty] || 'Pemula';
            const duration = currentCourseData.duration_minutes ? ` • ${currentCourseData.duration_minutes} Menit` : '';

            const defaultThumb = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80';
            const getThumb = (thumb) => thumb ? (thumb.startsWith('http') || thumb.startsWith('/') ? thumb : '/public/uploads/thumbnails/' + thumb) : defaultThumb;

            detailContainer.innerHTML = `
                <div class="content-card">
                    <span class="category-tag category-tag-detail">${App.Utils.escapeHtml(currentCourseData.category || 'Umum')} • ${difficulty}${duration}</span>
                    <h1 class="mb-16">${App.Utils.escapeHtml(currentCourseData.title)}</h1>
                    <div class="material-media">
                        <img src="${getThumb(currentCourseData.thumbnail)}" alt="Thumbnail">
                    </div>
                    <div class="material-content">
                        ${currentCourseData.content || currentCourseData.description || '<p>Belum ada episode pembelajaran untuk materi ini.</p>'}
                    </div>
                    ${actionButtons}
                </div>
            `;
            if (typeof renderDiscussionBoard === 'function') renderDiscussionBoard();
        }
    } catch (error) {
        console.error('View Material Error:', error);
        handleAPIError(error);
        if (detailContainer) detailContainer.innerHTML = `<div class="content-card"><p class="text-danger">Gagal memuat detail materi. Silakan coba lagi.</p></div>`;
    }
}

function renderSyllabus() {
    const syllabusContainer = document.getElementById('course-syllabus');
    const dropdownContainer = document.getElementById('syllabus-dropdown-items');
    if (!currentCourseData) return;

    setupSyllabusDropdown();

    const syllabusHtml = currentCourseData.sub_materials.map((lesson, index) => {
        const isLocked = index > currentCourseData.unlockedIndex;
        const icon = isLocked ? '<i data-lucide="lock" style="width:14px;"></i>' : (index + 1);
        const opacityStyle = isLocked ? 'opacity: 0.5; cursor: not-allowed;' : '';
        const clickHandler = isLocked ? `showNotification('Yuk, selesaikan episode sebelumnya dulu ya.', 'warning')` : `event.stopPropagation(); playLesson(${lesson.id})`;
        
        return `
            <div class="syllabus-item" id="lesson-btn-${lesson.id}" onclick="${clickHandler}" style="${opacityStyle}">
                <div class="syllabus-item-icon">${icon}</div>
                <div class="syllabus-item-title">${App.Utils.escapeHtml(lesson.title)}</div>
            </div>
        `;
    }).join('');

    if (syllabusContainer) syllabusContainer.innerHTML = syllabusHtml;
    if (dropdownContainer) dropdownContainer.innerHTML = syllabusHtml;
    
    if (typeof renderIcons === 'function') renderIcons();
}

function setupSyllabusDropdown() {
    const trigger = document.getElementById('syllabusDropdownTrigger');
    if (trigger && !trigger.dataset.initialized) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSyllabusDropdown();
        });
        trigger.dataset.initialized = "true";
    }
}

function toggleSyllabusDropdown() {
    const dropdown = document.getElementById('syllabusDropdown');
    if (dropdown) {
        const isShowing = dropdown.classList.contains('show');
        dropdown.classList.toggle('show', !isShowing);
    }
}

document.addEventListener('click', function(event) {
    const sylDropdown = document.getElementById('syllabusDropdown');
    if (sylDropdown && sylDropdown.classList.contains('show')) {
        if (!sylDropdown.contains(event.target)) {
            sylDropdown.classList.remove('show');
        }
    }
});

function playLesson(lessonId) {
    const lesson = currentCourseData.sub_materials.find(l => l.id === lessonId);
    if (!lesson) return;

    document.querySelectorAll('.syllabus-item').forEach(el => el.classList.remove('active'));
    const activeBtns = document.querySelectorAll(`[id="lesson-btn-${lesson.id}"]`);
    activeBtns.forEach(btn => btn.classList.add('active'));

    const currentTitleEl = document.getElementById('current-episode-title');
    if (currentTitleEl) currentTitleEl.innerText = lesson.title;

    const dropdown = document.getElementById('syllabusDropdown');
    if (dropdown) dropdown.classList.remove('show');

    const detailContainer = document.getElementById('material-detail');
    
    let mediaHtml = '';
    if (lesson.video_url) {
        let videoId = null;
        const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
        const ytMatch = lesson.video_url.match(youtubeRegex);
        
        if (ytMatch && ytMatch[1]) {
            videoId = ytMatch[1];
        } else if (lesson.video_url.includes('youtube.com/embed/')) {
            const parts = lesson.video_url.split('embed/');
            if (parts.length > 1) videoId = parts[1].split(/[?&]/)[0];
        }

        if (videoId) {
            mediaHtml = `
                <div class="material-media youtube-wrapper">
                    <iframe id="lesson-youtube-player" src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&enablejsapi=1" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
                </div>`;
        } else {
            const defaultThumb = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80';
            const posterUrl = currentCourseData.thumbnail ? (currentCourseData.thumbnail.startsWith('http') || currentCourseData.thumbnail.startsWith('/') ? currentCourseData.thumbnail : '/public/uploads/thumbnails/' + currentCourseData.thumbnail) : defaultThumb;
            mediaHtml = `
                <div class="material-media native-video-wrapper">
                    <video id="lesson-native-player" controls autoplay poster="${posterUrl}">
                        <source src="${lesson.video_url}" type="video/mp4">
                    </video>
                </div>`;
        }
    }

    let pdfHtml = '';
    if (lesson.document_url) {
        pdfHtml = `
            <div class="pdf-container mt-24">
                <div class="pdf-header">
                    <span class="pdf-title">Modul Pendamping (PDF)</span>
                    <a href="/public/assets/documents/${lesson.document_url}" download class="btn-outline btn-small">Unduh PDF</a>
                </div>
                <iframe src="/public/assets/documents/${lesson.document_url}" class="pdf-iframe"></iframe>
            </div>
        `;
    }

    const currentIndex = currentCourseData.sub_materials.findIndex(l => l.id === lessonId);
    const hasNext = currentIndex < currentCourseData.sub_materials.length - 1;
    
    let actionButtons = '';
    if (hasNext) {
        const nextLesson = currentCourseData.sub_materials[currentIndex + 1];
        actionButtons = `<button onclick="unlockAndPlayNext(${currentIndex}, ${nextLesson.id})" class="flex-1">Selesai & Lanjut</button>`;
    } else {
        actionButtons = `
            <button onclick="markAsCompleted(${currentCourseData.id})" class="flex-1">Tandai Kursus Selesai</button>
            <button onclick="startFinalQuiz(${currentCourseData.id})" class="btn-outline flex-1">Kerjakan Kuis Akhir</button>
        `;
    }

    const diffMap = { 'beginner': 'Pemula', 'intermediate': 'Menengah', 'advanced': 'Mahir' };
    const difficulty = diffMap[currentCourseData.difficulty] || 'Pemula';
    const duration = currentCourseData.duration_minutes ? ` • ${currentCourseData.duration_minutes} Menit` : '';

    detailContainer.innerHTML = `
        <div class="content-card">
            <span class="category-tag category-tag-detail">${App.Utils.escapeHtml(currentCourseData.category || 'Umum')} • ${difficulty}${duration}</span>
            <h1 class="mb-16">${App.Utils.escapeHtml(lesson.title)}</h1>
            ${mediaHtml}
            <div class="material-content mt-24">
                ${lesson.content || '<p>Deskripsi materi sedang disiapkan oleh admin.</p>'}
            </div>
            ${pdfHtml}
            <div class="action-buttons mt-32">${actionButtons}</div>
        </div>
    `;
    
    if (typeof renderDiscussionBoard === 'function') renderDiscussionBoard();
    window.scrollTo(0, 0);
}

function triggerConfetti() {
    if (typeof confetti !== 'function') return;
    var count = 200;
    var defaults = { origin: { y: 0.6 }, zIndex: 10000 };
    function fire(particleRatio, opts) {
        confetti(Object.assign({}, defaults, opts, { particleCount: Math.floor(count * particleRatio) }));
    }
    fire(0.25, { spread: 26, startVelocity: 55 });
    fire(0.2, { spread: 60 });
    fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
    fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
    fire(0.1, { spread: 120, startVelocity: 45 });
}

function unlockAndPlayNext(currentIndex, nextLessonId) {
    currentCourseData.unlockedIndex = Math.max(currentCourseData.unlockedIndex, currentIndex + 1);
    renderSyllabus();
    playLesson(nextLessonId);
}

async function markAsCompleted(id) {
    showLoading(true);
    try {
        await API.markMaterialAsCompleted(id);
        if (typeof checkAuth === 'function') await checkAuth();
        triggerConfetti();
        showNotification('Selamat! Modul ini telah selesai dipelajari.', 'success');
        setTimeout(() => { showPage('materials-page'); }, 1500);
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

function stopAllPlayback() {
    const nativeVideo = document.getElementById('lesson-native-player');
    if (nativeVideo) nativeVideo.pause();
    const ytIframe = document.getElementById('lesson-youtube-player');
    if (ytIframe && ytIframe.contentWindow) {
        ytIframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
    }
}

// Explicitly expose to window
window.toggleSyllabusDropdown = toggleSyllabusDropdown;
window.playLesson = playLesson;
window.unlockAndPlayNext = unlockAndPlayNext;
window.markAsCompleted = markAsCompleted;
window.viewMaterial = viewMaterial;
window.stopAllPlayback = stopAllPlayback;
window.triggerConfetti = triggerConfetti;
