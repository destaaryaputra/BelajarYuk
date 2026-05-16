<?php
/**
 * Belajaryuk - Single Page Application (SPA)
 * Entry point untuk semua routes
 * Frontend: Pure HTML/CSS/JavaScript (SPA)
 * Backend: API Router (/api)
 */

$env_file = __DIR__ . '/../src/Config/lingkungan.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

session_start();

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <script>
        // Suppress harmless deprecated warnings from external scripts (Vercel/Sentry/Zustand)
        (function() {
            const originalWarn = console.warn;
            console.warn = function(...args) {
                const msg = args[0] && typeof args[0] === 'string' ? args[0] : '';
                if (msg.includes('Default export is deprecated') || msg.includes('zustand')) return;
                originalWarn.apply(console, args);
            };
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Belajaryuk - Platform Pembelajaran Interaktif</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/gaya.css">
    <link rel="stylesheet" href="/assets/css/responsif.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- External Libraries -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script>
        // Suppress harmless deprecated warnings from external scripts
        const originalWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('Default export is deprecated')) return;
            originalWarn.apply(console, args);
        };
    </script>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen" class="splash-screen">
        <div class="splash-logo">
            <div class="css-art-logo"></div>
            <h1>Belajaryuk</h1>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading-overlay d-none"></div>

    <!-- Notifications -->
    <div id="notification-toast" class="notification-toast"></div>

    <!-- Global Navigation (Private) -->
    <nav id="global-nav" class="global-nav d-none">
        <div class="nav-top-bar"></div>
        <div class="nav-bottom-bar">
            <button type="button" data-page="dashboard-page"><i data-lucide="layout-dashboard"></i> Dashboard</button>
            <button type="button" data-page="materials-page"><i data-lucide="book-open"></i> Materi</button>
            <button type="button" data-page="progress-page"><i data-lucide="line-chart"></i> Progress</button>
            <button type="button" data-page="profile-page"><i data-lucide="user"></i> Profil</button>
        </div>
    </nav>

    <!-- Main App Container -->
    <div id="app">
        <section id="landing-page" class="page">
            <div class="lp-bg-decor">
                <div class="lp-blob blob-1"></div>
                <div class="lp-blob blob-2"></div>
            </div>
            
            <header class="public-header">
                <div class="lp-container">
                    <div class="header-inner">
                        <div class="nav-brand-logo">
                            <div class="nav-logo-wrapper">
                                <div class="css-art-logo"></div>
                            </div>
                            <span>Belajaryuk</span>
                        </div>
                        <div class="header-actions">
                            <button type="button" class="btn-ghost" data-page="login-page">Masuk</button>
                            <button type="button" class="btn-p btn-small" data-page="register-page">Daftar</button>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                <!-- Hero Section -->
                <div class="hero-wrapper">
                    <div class="lp-container">
                        <div class="hero-grid reveal-on-scroll">
                            <div class="hero-text">
                                <div class="hero-pre-header">
                                    <div class="hero-badge">
                                        <span class="badge-dot"></span>
                                        Smart Learning Solution
                                    </div>
                                </div>
                                <h1>Belajar Cerdas,<br><span class="text-gradient">Hasil Maksimal.</span></h1>
                                <p>Tingkatkan skill digital kamu melalui kurikulum yang terstruktur dan bantuan AI yang siap membantu setiap kesulitanmu.</p>
                                <div class="hero-cta-group">
                                    <button type="button" class="btn-p" data-page="register-page">
                                        Mulai Belajar Sekarang
                                        <i data-lucide="arrow-right" class="btn-icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="hero-preview">
                                <div class="preview-card">
                                    <div class="preview-nav">
                                        <div class="p-dot" style="background: #ff5f56;"></div>
                                        <div class="p-dot" style="background: #ffbd2e;"></div>
                                        <div class="p-dot" style="background: #27c93f;"></div>
                                    </div>
                                    <div class="preview-content">
                                        <div class="preview-image-wrapper">
                                            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop" alt="Learning Dashboard" class="hero-main-img">
                                            <div class="image-overlay-glow"></div>
                                            <div class="play-btn-pulse">
                                                <i data-lucide="play-circle" style="width: 64px; height: 64px; color: white;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div id="features" class="features-wrapper">
                    <div class="lp-container">
                        <div class="section-header reveal-on-scroll">
                            <span class="section-subtitle">Keunggulan Kami</span>
                            <h2>Mengapa Belajaryuk Berbeda?</h2>
                            <p>Kami menggabungkan kurikulum industri dengan teknologi AI tercanggih untuk mempercepat proses belajarmu secara signifikan.</p>
                        </div>
                        
                        <div class="features-grid-modern">
                            <div class="f-card reveal-on-scroll">
                                <div class="f-icon"><i data-lucide="zap"></i></div>
                                <h3>Belajar 2x Lebih Cepat</h3>
                                <p>Metode pembelajaran adaptif yang menyesuaikan dengan kecepatan belajarmu masing-masing.</p>
                            </div>
                            <div class="f-card reveal-on-scroll">
                                <div class="f-icon"><i data-lucide="bot"></i></div>
                                <h3>Asisten AI Personal</h3>
                                <p>Bantuan instan 24/7 untuk menjawab setiap pertanyaan teknis yang kamu temukan di dalam materi.</p>
                            </div>
                            <div class="f-card reveal-on-scroll">
                                <div class="f-icon"><i data-lucide="target"></i></div>
                                <h3>Kurikulum Relevan</h3>
                                <p>Materi yang selalu diperbarui mengikuti tren industri teknologi terbaru saat ini.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats-wrapper reveal-on-scroll">
                    <div class="lp-container">
                        <div class="stats-grid-inner">
                            <div class="stat-item">
                                <div class="stat-value">50+</div>
                                <div class="stat-label">Materi Terstruktur</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">12</div>
                                <div class="stat-label">Kategori Pembelajaran</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">24/7</div>
                                <div class="stat-label">Akses Belajar</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="final-cta-wrapper reveal-on-scroll">
                    <div class="lp-container">
                        <div class="cta-card">
                            <div class="cta-content">
                                <h2>Siap untuk Memulai Perjalananmu?</h2>
                                <p>Bergabunglah bersama ribuan pelajar lainnya dan bangun karir impianmu di dunia digital mulai hari ini.</p>
                                <button type="button" class="btn-p" data-page="register-page">
                                    Daftar Sekarang Secara Gratis
                                    <i data-lucide="arrow-right" class="btn-icon"></i>
                                </button>
                            </div>
                            <div class="cta-visual">
                                <div class="cta-blob"></div>
                                <i data-lucide="rocket" class="cta-rocket-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simple Footer (Just Legal & Name) -->
                <footer class="minimal-footer">
                    <div class="lp-container">
                        <div class="mf-inner">
                            <div class="mf-copy">&copy; 2026 Belajaryuk. All rights reserved.</div>
                        </div>
                    </div>
                </footer>
            </main>
        </section>

        <section id="login-page" class="page">
                <div class="auth-overlay">
                    <div class="auth-card reveal-on-scroll">
                        <div class="auth-header">
                            <h2>Selamat Datang</h2>
                            <p>Lanjutkan progres belajar di <strong>Belajaryuk</strong>.</p>
                        </div>
                    <form id="login-form" class="modern-form" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label for="login-username"><i data-lucide="user"></i> Username</label>
                            <input type="text" id="login-username" placeholder="Masukkan username kamu" autocomplete="username" required>
                        </div>
                        <div class="form-group">
                            <label for="login-password"><i data-lucide="lock"></i> Kata Sandi</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="login-password" placeholder="••••••••" autocomplete="current-password" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-p btn-full">Masuk Sekarang</button>
                            <div class="auth-divider"><span>atau</span></div>
                            <button type="button" class="btn-outline btn-full" data-page="register-page">Belum punya akun? Daftar</button>
                        </div>
                    </form>
                    <button type="button" class="btn-back-home" data-page="landing-page">
                        <i data-lucide="arrow-left"></i> Kembali ke Beranda
                    </button>
                </div>
            </div>
        </section>

        <section id="register-page" class="page">
                <div class="auth-overlay">
                    <div class="auth-card reveal-on-scroll">
                        <div class="auth-header">
                            <h2>Daftar Akun Baru</h2>
                            <p>Mulai kembangkan skill digital bersama <strong>Belajaryuk</strong>.</p>
                        </div>
                    <form id="register-form" class="modern-form" onsubmit="handleRegister(event)">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="register-fullname"><i data-lucide="user-plus"></i> Nama Lengkap</label>
                                <input type="text" id="register-fullname" placeholder="Nama Lengkap" autocomplete="name" required>
                            </div>
                            <div class="form-group">
                                <label for="register-username"><i data-lucide="at-sign"></i> Username</label>
                                <input type="text" id="register-username" placeholder="username_kamu" autocomplete="username" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="register-email"><i data-lucide="mail"></i> Email</label>
                            <input type="email" id="register-email" placeholder="email@contoh.com" autocomplete="email" required>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="register-password"><i data-lucide="lock"></i> Kata Sandi</label>
                                <input type="password" id="register-password" placeholder="••••••••" autocomplete="new-password" required>
                            </div>
                            <div class="form-group">
                                <label for="register-password-confirm"><i data-lucide="shield-check"></i> Konfirmasi</label>
                                <input type="password" id="register-password-confirm" placeholder="••••••••" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-p btn-full">Buat Akun Sekarang</button>
                            <div class="auth-divider"><span>atau</span></div>
                            <button type="button" class="btn-outline btn-full" data-page="login-page">Sudah punya akun? Masuk</button>
                        </div>
                    </form>
                    <button type="button" class="btn-back-home" data-page="landing-page">
                        <i data-lucide="arrow-left"></i> Kembali ke Beranda
                    </button>
                </div>
            </div>
        </section>

        <section id="dashboard-page" class="page">
            <div id="dynamic-dashboard-content"></div>
        </section>

        <section id="materials-page" class="page">
            <main>
                <div class="header-section">
                    <h1>Materi Pembelajaran</h1>
                    <p>Pilih kelas yang ingin kamu pelajari.</p>
                </div>

                <div id="categoryTabs" class="category-tabs-scroll"></div>
                
                <div id="materials-search-bar" class="search-bar">
                    <div class="search-input-wrapper">
                        <i data-lucide="search" class="search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Cari judul atau deskripsi materi...">
                    </div>
                    <div class="custom-dropdown" id="categoryDropdown">
                        <button type="button" id="categoryDropdownTrigger" class="custom-dropdown-trigger">
                            <span id="current-category-label">Semua Kategori</span>
                            <i data-lucide="chevron-down" class="chevron-icon"></i>
                        </button>
                        <div class="custom-dropdown-menu" id="categoryDropdownMenu">
                            <div class="custom-dropdown-item active" data-value="all">Semua Kategori</div>
                        </div>
                        <input type="hidden" id="categoryFilter" value="all">
                    </div>
                </div>

                <div id="materials-list"></div>
            </main>
        </section>

        <section id="material-detail-page" class="page">
            <main class="learning-detail-page-shell">
                <div class="material-detail-toolbar">
                    <button type="button" class="btn-back-material" data-page="materials-page">
                        <i data-lucide="arrow-left"></i>
                        Kembali ke Materi
                    </button>
                    <div class="syllabus-dropdown" id="syllabusDropdown">
                        <button type="button" id="syllabusDropdownTrigger" class="syllabus-dropdown-trigger">
                            <i data-lucide="list" class="syllabus-icon"></i>
                            <span>Episode: <strong id="current-episode-title">Pilih Episode</strong></span>
                            <i data-lucide="chevron-down" class="chevron-icon"></i>
                        </button>
                        <div class="syllabus-dropdown-menu" id="syllabus-dropdown-items">
                            <!-- Items akan dimuat secara dinamis -->
                        </div>
                    </div>
                </div>
                <div class="learning-detail-layout">
                    <aside id="course-syllabus" class="content-card"></aside>
                    <div id="material-detail"></div>
                </div>
            </main>
        </section>

        <section id="progress-page" class="page">
            <main>
                <div class="header-section">
                    <h1>Progress Belajar</h1>
                    <p>Ringkasan aktivitas dan hasil kuis kamu.</p>
                </div>
                <div id="progress-summary" class="mb-32"></div>
                <div class="content-card">
                    <h2>Progress per Kategori</h2>
                    <div id="category-progress"></div>
                </div>
                <div class="content-card">
                    <h2>Papan Peringkat (Leaderboard)</h2>
                    <div id="leaderboard-list"></div>
                </div>
                <div class="content-card">
                    <h2>Riwayat Kuis</h2>
                    <div id="quiz-performance"></div>
                </div>
            </main>
        </section>

        <section id="profile-page" class="page">
            <main class="profile-container">
                <h1>Profil Saya</h1>
                <div id="profile-content" class="content-card"></div>
            </main>
        </section>

        <section id="admin-page" class="page">
            <div class="admin-layout">
                <aside class="admin-sidebar">
                    <div class="admin-brand">
                        <div class="nav-logo-wrapper">
                            <div class="css-art-logo"></div>
                        </div>
                        <span>Belajaryuk</span>
                    </div>
                    <div class="admin-sidebar-nav">
                        <button id="btn-tab-dashboard" type="button" class="active" onclick="switchAdminTab('dashboard')">
                            <i data-lucide="layout-dashboard"></i> Dashboard
                        </button>
                        <button id="btn-tab-materi" type="button" onclick="switchAdminTab('materi')">
                            <i data-lucide="book-open"></i> Materi
                        </button>
                        <button id="btn-tab-pengguna" type="button" onclick="switchAdminTab('pengguna')">
                            <i data-lucide="users"></i> Siswa
                        </button>
                        <button id="btn-tab-laporan" type="button" onclick="switchAdminTab('laporan')">
                            <i data-lucide="file-bar-chart"></i> Laporan
                        </button>
                        <button id="btn-tab-diskusi" type="button" onclick="switchAdminTab('diskusi')">
                            <i data-lucide="message-square"></i> Diskusi
                        </button>
                        <button id="btn-tab-pengaturan" type="button" onclick="switchAdminTab('pengaturan')">
                            <i data-lucide="settings"></i> Pengaturan
                        </button>
                    </div>
                    <div class="admin-sidebar-footer">
                        <button type="button" class="btn-logout-admin" data-action="logout">
                            <i data-lucide="log-out"></i> 
                            <span>Keluar Panel</span>
                        </button>
                    </div>
                </aside>

                <main class="admin-main">
                    <header class="admin-topbar">
                        <h2 id="admin-page-title">Panel Admin</h2>
                        <div class="admin-topbar-actions">
                            <span id="admin-user-name">Admin</span>
                        </div>
                    </header>
                    <div class="admin-content">
                        <div id="admin-tab-dashboard" class="admin-tab-content">
                            <div class="admin-dashboard-hero">
                                <div>
                                    <p class="admin-eyebrow">Kontrol Operasional</p>
                                    <h3>Ringkasan Belajaryuk</h3>
                                    <p>Pantau siswa, materi, nilai, dan diskusi dari satu tempat.</p>
                                </div>
                                <div id="admin-quick-actions" class="admin-quick-actions"></div>
                            </div>
                            <div class="stats-grid admin-primary-metrics">
                                <div class="stat-card stat-card-info"><h3>Total Siswa</h3><div class="value" id="admin-stat-users">--</div></div>
                                <div class="stat-card stat-card-primary"><h3>Total Kursus</h3><div class="value" id="admin-stat-materials">--</div></div>
                                <div class="stat-card stat-card-accent"><h3>Siswa Baru Bulan Ini</h3><div class="value" id="admin-stat-recent">--</div></div>
                            </div>
                            <div class="admin-priority-grid">
                                <div class="content-card chart-card chart-card-main">
                                    <h3>Pertumbuhan Siswa</h3>
                                    <canvas id="adminRegistrationChart"></canvas>
                                </div>
                                <div class="content-card chart-card chart-card-main">
                                    <h3>Kategori Materi</h3>
                                    <canvas id="adminCategoryChart"></canvas>
                                </div>
                            </div>
                            <div id="admin-dashboard-insights" class="stats-grid"></div>
                            <div class="admin-dashboard-grid admin-secondary-grid">
                                <div class="content-card">
                                    <h3>Siswa Terbaru</h3>
                                    <div id="admin-recent-users"></div>
                                </div>
                                <div class="content-card">
                                    <h3>Materi Terbaru</h3>
                                    <div id="admin-recent-materials"></div>
                                </div>
                            </div>
                            <div class="content-card">
                                <div class="d-flex justify-between align-center mb-16">
                                    <h3>Diskusi Terbaru</h3>
                                    <button type="button" class="btn-outline btn-small" onclick="switchAdminTab('diskusi')">Moderasi</button>
                                </div>
                                <div id="admin-recent-comments"></div>
                            </div>
                        </div>

                        <div id="admin-tab-materi" class="admin-tab-content" style="display: none;">
                            <div id="admin-list-view">
                                <div class="d-flex justify-between align-center mb-16">
                                    <h3>Daftar Materi</h3>
                                    <button type="button" onclick="toggleAdminForm(true)">Tambah Materi</button>
                                </div>
                                <div id="admin-materials-table"></div>
                            </div>

                            <div id="admin-form-view" style="display: none;">
                                <div class="content-card">
                                    <h3 id="admin-form-title">Tambah Materi Baru</h3>
                                    <form id="create-material-form" onsubmit="handleSaveMaterial(event)">
                                        <input type="hidden" id="mat-id">
                                        <div><label for="mat-title">Judul</label><input type="text" id="mat-title" required></div>
                                        <div><label for="mat-category">Kategori</label><input type="text" id="mat-category" required></div>
                                        <div><label for="mat-difficulty">Level</label><select id="mat-difficulty"><option value="beginner">Pemula</option><option value="intermediate">Menengah</option><option value="advanced">Mahir</option></select></div>
                                        <div><label for="mat-duration">Durasi Menit</label><input type="number" id="mat-duration" min="0" value="0"></div>
                                        <div><label for="mat-video">Video URL</label><input type="url" id="mat-video"></div>
                                        <div><label for="mat-desc">Deskripsi</label><textarea id="mat-desc" required></textarea></div>
                                        <div><label>Konten</label><div id="mat-content-editor"></div><textarea id="mat-content" style="display:none;"></textarea></div>
                                        <div><label for="mat-thumbnail">Thumbnail</label><input type="file" id="mat-thumbnail" accept="image/*"></div>
                                        <div class="d-flex gap-12">
                                            <button type="submit">Simpan</button>
                                            <button type="button" class="btn-outline" onclick="toggleAdminForm(false)">Batal</button>
                                            <button type="button" class="btn-outline" onclick="generateCourseWithAI()">Generate AI</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div id="admin-submaterial-view" style="display: none;">
                                <div class="content-card">
                                    <div class="d-flex justify-between align-center mb-16">
                                        <h3 id="admin-submat-title">Kelola Episode</h3>
                                        <div class="d-flex gap-12">
                                            <button type="button" onclick="toggleSubMaterialForm(true)">Tambah Episode</button>
                                            <button type="button" class="btn-outline" onclick="toggleSubMaterialView(false)">Kembali</button>
                                        </div>
                                    </div>
                                    <div id="admin-submaterials-table"></div>
                                    <div id="admin-submaterial-form-container" style="display: none; margin-top: 24px;">
                                        <h3 id="admin-submat-form-title">Form Episode Baru</h3>
                                        <form id="create-submaterial-form" onsubmit="handleSaveSubMaterial(event)">
                                            <input type="hidden" id="submat-id">
                                            <input type="hidden" id="submat-material-id">
                                            <div><label for="submat-title">Judul Episode</label><input type="text" id="submat-title" required></div>
                                            <div><label for="submat-video">Video URL</label><input type="url" id="submat-video"></div>
                                            <div><label>Konten Episode</label><div id="submat-content-editor"></div><textarea id="submat-content" style="display:none;"></textarea></div>
                                            <div><label for="submat-pdf">PDF</label><input type="file" id="submat-pdf" accept="application/pdf"></div>
                                            <button type="submit">Simpan Episode</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div id="admin-quiz-view" style="display: none;">
                                <div class="content-card">
                                    <div class="d-flex justify-between align-center mb-16">
                                        <h3 id="admin-quiz-title">Kuis</h3>
                                        <button type="button" class="btn-outline" onclick="toggleQuizView(false)">Kembali</button>
                                    </div>
                                    <input type="hidden" id="quiz-material-id">
                                    <div id="admin-quiz-setup">
                                        <form id="create-quiz-form" onsubmit="handleCreateQuiz(event)">
                                            <div><label for="quiz-title">Judul Kuis</label><input type="text" id="quiz-title" required></div>
                                            <div><label for="quiz-desc">Deskripsi</label><textarea id="quiz-desc"></textarea></div>
                                            <div><label for="quiz-passing">Passing Score</label><input type="number" id="quiz-passing" value="60" min="0" max="100"></div>
                                            <div><label for="quiz-time">Batas Waktu Menit</label><input type="number" id="quiz-time" value="0" min="0"></div>
                                            <button type="submit">Buat Kuis</button>
                                        </form>
                                    </div>
                                    <div id="admin-quiz-manage" style="display: none;">
                                        <h3 id="active-quiz-title"></h3>
                                        <p id="active-quiz-info"></p>
                                        <input type="hidden" id="q-quiz-id">
                                        <div class="d-flex gap-12 mb-16">
                                            <button type="button" onclick="toggleQuestionForm(true)">Tambah Soal</button>
                                            <button type="button" class="btn-outline btn-danger" onclick="handleDeleteQuiz()">Hapus Kuis</button>
                                        </div>
                                        <div id="admin-questions-table"></div>
                                        <div id="admin-question-form-container" style="display: none; margin-top: 24px;">
                                            <form id="create-question-form" onsubmit="handleSaveQuestion(event)">
                                                <div><label for="q-text">Pertanyaan</label><textarea id="q-text" required></textarea></div>
                                                <div><label for="q-opt-a">Opsi A</label><input type="text" id="q-opt-a" required></div>
                                                <div><label for="q-opt-b">Opsi B</label><input type="text" id="q-opt-b" required></div>
                                                <div><label for="q-opt-c">Opsi C</label><input type="text" id="q-opt-c" required></div>
                                                <div><label for="q-opt-d">Opsi D</label><input type="text" id="q-opt-d" required></div>
                                                <div><label for="q-correct">Jawaban Benar</label><select id="q-correct"><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select></div>
                                                <button type="submit">Simpan Soal</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="admin-tab-pengguna" class="admin-tab-content" style="display: none;">
                            <div class="content-card">
                                <h3>Data Siswa</h3>
                                <div id="admin-users-table"></div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </section>
    </div>

    <!-- AI Chat Widget -->
    <div id="ai-chat-widget" class="ai-chat-widget" style="display: none;">
        <div id="ai-chat-window" class="ai-chat-window">
            <div class="ai-chat-header">
                <div class="ai-header-info">
                    <span>Asisten AI</span>
                </div>
                <div style="display: flex; gap: 4px; align-items: center;">
                    <button onclick="clearAIChat()" class="ai-close-btn" title="Hapus Riwayat Obrolan">
                        <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
                    </button>
                    <button onclick="toggleAIChat()" class="ai-close-btn" title="Tutup Chat">
                        <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>
            <div id="ai-chat-messages" class="ai-chat-messages">
                <div class="chat-message ai-message">
                    Halo! Saya Asisten AI Belajaryuk. Ada yang membuatmu bingung saat belajar? Tanyakan saja padaku!
                </div>
            </div>
            <div class="ai-chat-input-area">
                <input type="text" id="ai-chat-input" placeholder="Ketik pertanyaanmu..." onkeypress="handleAIChatInput(event)">
                <button onclick="sendAIChatMessage()">
                    Kirim
                </button>
            </div>
        </div>
        <button id="ai-chat-toggle" class="ai-chat-toggle" onclick="toggleAIChat()" title="Tanya AI">
            <i data-lucide="sparkles"></i>
        </button>
    </div>

    <!-- External Libraries (JavaScript) -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    
    <!-- Application Scripts -->
    <script src="/js/services/api.js" defer></script>
    <script src="/js/autentikasi.js" defer></script>
    <script src="/js/dasbor.js" defer></script>
    <script src="/js/profil.js" defer></script>
    <script src="/js/progres.js" defer></script>
    <script src="/js/admin/dasbor.js" defer></script>
    <script src="/js/admin/daftar-materi.js" defer></script>
    <script src="/js/admin/navigasi.js" defer></script>
    <script src="/js/admin/pengguna.js" defer></script>
    <script src="/js/admin/editor.js" defer></script>
    <script src="/js/admin/form-materi.js" defer></script>
    <script src="/js/admin/episode.js" defer></script>
    <script src="/js/admin/kuis.js" defer></script>
    <script src="/js/admin/aksi-materi.js" defer></script>
    <script src="/js/admin/laporan.js" defer></script>
    <script src="/js/admin/diskusi.js" defer></script>
    <script src="/js/admin/pengaturan.js" defer></script>
    <script src="/js/learning/daftar-materi.js" defer></script>
    <script src="/js/learning/pelajaran.js" defer></script>
    <script src="/js/learning/diskusi.js" defer></script>
    <script src="/js/learning/pengerjaan-kuis.js" defer></script>
    <script src="/js/chat-ai.js" defer></script>
    <script src="/js/aplikasi.js" defer></script>
</body>
</html>
