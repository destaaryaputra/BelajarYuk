# 🎓 Belajaryuk - Platform Pembelajaran Interaktif

[![Version](https://img.shields.io/badge/version-2.0.0-emerald.svg)](https://github.com/destaaryaputra/BelajarYuk)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Framework](https://img.shields.io/badge/backend-PHP%208.x-777bb4.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/database-Supabase%20(PostgreSQL)-3ecf8e.svg)](https://supabase.com/)

**Belajaryuk** adalah platform *Learning Management System* (LMS) modern yang dirancang untuk memberikan pengalaman belajar yang interaktif, terukur, dan menyenangkan melalui gamifikasi serta bantuan AI.

---

## ✨ Fitur Unggulan

### 🧑‍🎓 Student Side
- **Dashboard Interaktif:** Ringkasan progres belajar, poin akumulasi, dan statistik belajar yang divisualisasikan dengan apik.
- **Smart Learning Flow:** Materi video terintegrasi dengan kuis mini di setiap episode untuk memastikan pemahaman maksimal.
- **Gamifikasi & Leaderboard:** Dapatkan poin dari setiap kuis dan bersaing di papan peringkat global dengan medali (Emas, Perak, Perunggu).
- **Yuki AI Assistant:** Asisten belajar berbasis AI (Groq/LLM) yang siap menjawab pertanyaan siswa seputar materi secara *real-time*.
- **Progress Tracking:** Pantau persentase kelulusan modul dan riwayat kuis secara mendetail.
- **Modern UI:** Antarmuka responsif dengan efek *glassmorphism*, mode gelap, dan transisi yang halus.

### 👨‍💼 Admin Panel
- **Manajemen Materi:** Kontrol penuh atas pembuatan materi, sub-materi (episode), dan pengunggahan konten.
- **Kuis & Soal:** Alat pembuat kuis (Mini & Final) dengan editor soal yang intuitif.
- **Laporan & Analytics:** Visualisasi tren nilai kuis dan total percobaan melalui grafik *dual-axis* yang informatif.
- **Manajemen Pengguna:** Pantau aktivitas siswa, poin, dan progres mereka secara individual.
- **AI Course Generator:** (Experimental) Membantu admin merancang kerangka materi menggunakan AI.

---

## 🏗️ Arsitektur Sistem

Proyek ini dibangun dengan prinsip **Clean Architecture** dan **Layered Architecture** untuk memastikan kode mudah dipelihara dan dikembangkan:

- **Controller Layer:** Menangani *request* masuk dan memvalidasi input.
- **Service Layer:** Berisi logika bisnis utama aplikasi.
- **Model Layer:** Berinteraksi langsung dengan database menggunakan PDO.
- **Frontend Modular:** Menggunakan vanilla JavaScript dengan sistem ES Modules untuk performa optimal tanpa *overhead* framework berat.

---

## 🚀 Teknologi yang Digunakan

| Komponen | Teknologi |
| --- | --- |
| **Backend** | PHP 8.x (Native with OOP) |
| **Database** | PostgreSQL (Supabase) |
| **Frontend** | Vanilla JS (ES6+), CSS3 (Custom Design System), HTML5 |
| **AI Engine** | Groq Cloud API (LLM Integration) |
| **Auth** | JWT (JSON Web Token) |
| **Icons** | Lucide Icons |
| **Charts** | Chart.js |

---

## ⚙️ Instalasi Lokal

### Prasyarat
- PHP >= 8.0
- Composer
- Web Server (Apache/Nginx)
- Database PostgreSQL (atau akun Supabase)

### Langkah-langkah
1. **Clone Repositori**
   ```bash
   git clone https://github.com/destaaryaputra/BelajarYuk.git
   cd BelajarYuk
   ```

2. **Instal Dependensi**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**
   Salin file `.env.example` menjadi `.env` dan lengkapi kredensialnya:
   ```env
   DB_HOST=your_host
   DB_NAME=your_db_name
   DB_USER=your_user
   DB_PASS=your_password
   GROQ_API_KEY=your_groq_key
   JWT_SECRET=your_random_secret
   ```

4. **Setup Database**
   Impor file `skema_supabase.sql` ke dalam database PostgreSQL Anda.

5. **Jalankan Aplikasi**
   Jika menggunakan XAMPP, letakkan di folder `htdocs` dan akses melalui `http://localhost/belajaryuk`.

---

## 📸 Tampilan Cuplikan
*(Segera tambahkan screenshot di sini untuk daya tarik visual)*

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah [MIT License](LICENSE).

---

**Dibuat dengan ❤️ oleh [Desta Arya Putra](https://github.com/destaaryaputra)**
