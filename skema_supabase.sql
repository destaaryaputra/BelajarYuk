-- ========================================================
-- SKEMA DATABASE BELAJARYUK (OPTIMIZED FOR SUPABASE)
-- ========================================================

-- 1. Tabel Pengguna (Data Akun & Profil)
CREATE TABLE IF NOT EXISTS pengguna (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    role TEXT CHECK (role IN ('student', 'admin')) DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Materi (Data Kursus/Materi Utama)
CREATE TABLE IF NOT EXISTS materi (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    content TEXT NULL,
    thumbnail VARCHAR(255) NULL,
    video_url VARCHAR(500) NULL,
    difficulty TEXT CHECK (difficulty IN ('beginner', 'intermediate', 'advanced')) DEFAULT 'beginner',
    duration_minutes INT DEFAULT 0,
    status TEXT CHECK (status IN ('active', 'draft', 'deleted')) DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel Sub Materi (Episode/Pelajaran di dalam Materi)
CREATE TABLE IF NOT EXISTS sub_materi (
    id SERIAL PRIMARY KEY,
    material_id INT REFERENCES materi(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(500) NULL,
    document_url VARCHAR(255) NULL,
    content TEXT NULL,
    order_number INT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabel Komentar Materi (Forum Diskusi)
CREATE TABLE IF NOT EXISTS komentar_materi (
    id SERIAL PRIMARY KEY,
    material_id INT REFERENCES materi(id) ON DELETE CASCADE,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 5. Tabel Progres Materi (Status Selesai Materi)
CREATE TABLE IF NOT EXISTS progres_materi (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    material_id INT REFERENCES materi(id) ON DELETE CASCADE,
    completed_at TIMESTAMPTZ NULL,
    progress_percentage INT DEFAULT 0,
    last_accessed_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, material_id)
);

-- 6. Tabel Kuis (Data Kuis per Materi)
CREATE TABLE IF NOT EXISTS kuis (
    id SERIAL PRIMARY KEY,
    material_id INT REFERENCES materi(id) ON DELETE CASCADE,
    sub_material_id INT REFERENCES sub_materi(id) ON DELETE CASCADE,
    quiz_type TEXT CHECK (quiz_type IN ('mini', 'final')) DEFAULT 'final',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    passing_score INT DEFAULT 60,
    total_questions INT DEFAULT 0,
    time_limit_minutes INT NULL,
    status TEXT CHECK (status IN ('active', 'draft', 'deleted')) DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 7. Tabel Pertanyaan (Soal Kuis)
CREATE TABLE IF NOT EXISTS pertanyaan (
    id SERIAL PRIMARY KEY,
    quiz_id INT REFERENCES kuis(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type TEXT CHECK (question_type IN ('multiple_choice', 'short_answer', 'true_false')) DEFAULT 'multiple_choice',
    options JSONB NULL, -- Menyimpan pilihan jawaban dalam format JSON
    correct_answer VARCHAR(255) NOT NULL,
    explanation TEXT NULL,
    points INT DEFAULT 10,
    order_number INT NOT NULL,
    status TEXT CHECK (status IN ('active', 'draft', 'deleted')) DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 8. Tabel Hasil Kuis (Nilai Akhir Kuis Siswa)
CREATE TABLE IF NOT EXISTS hasil_kuis (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    quiz_id INT REFERENCES kuis(id) ON DELETE CASCADE,
    score INT NOT NULL,
    total_points INT NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    submitted_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 9. Tabel Jawaban Pengguna (Detail Jawaban per Soal)
CREATE TABLE IF NOT EXISTS jawaban_pengguna (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    question_id INT REFERENCES pertanyaan(id) ON DELETE CASCADE,
    answer VARCHAR(500) NOT NULL,
    answered_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 10. Tabel Pencapaian (Gamifikasi)
CREATE TABLE IF NOT EXISTS pencapaian (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255) NULL,
    earned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 11. Tabel Riwayat Chat AI
CREATE TABLE IF NOT EXISTS percakapan_ai (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
    material_id INT REFERENCES materi(id) ON DELETE SET NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- ========================================================
-- INDEX UNTUK PERFORMA
-- ========================================================
-- Optimasi Filter & Status
CREATE INDEX IF NOT EXISTS idx_materi_status ON materi(status);
CREATE INDEX IF NOT EXISTS idx_materi_category ON materi(category);

-- Optimasi Relasi Parent-Child
CREATE INDEX IF NOT EXISTS idx_sub_materi_material ON sub_materi(material_id);
CREATE INDEX IF NOT EXISTS idx_kuis_material ON kuis(material_id);
CREATE INDEX IF NOT EXISTS idx_kuis_sub_material ON kuis(sub_material_id);
CREATE INDEX IF NOT EXISTS idx_pertanyaan_quiz ON pertanyaan(quiz_id);

-- Optimasi Data User (Sering digunakan di Dashboard & Profile)
CREATE INDEX IF NOT EXISTS idx_progres_user ON progres_materi(user_id);
CREATE INDEX IF NOT EXISTS idx_progres_material ON progres_materi(material_id);
CREATE INDEX IF NOT EXISTS idx_hasil_kuis_user ON hasil_kuis(user_id);
CREATE INDEX IF NOT EXISTS idx_hasil_kuis_quiz ON hasil_kuis(quiz_id);
CREATE INDEX IF NOT EXISTS idx_komentar_material ON komentar_materi(material_id);
CREATE INDEX IF NOT EXISTS idx_percakapan_ai_user ON percakapan_ai(user_id);

-- ========================================================
-- DATA AWAL (ADMIN DEFAULT)
-- ========================================================
-- Username: admin, Password: password123 (Hashed)
INSERT INTO pengguna (username, email, password, full_name, role) 
VALUES ('admin', 'admin@belajaryuk.local', '$2y$12$uqDnxv1pLVkVLtPjVdE0Pu3S.F5o5K2D8Lb7XD7Y5D7Y5D7Y5D7Y5', 'Administrator Belajaryuk', 'admin')
ON CONFLICT (username) DO NOTHING;
