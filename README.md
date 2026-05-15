# Belajaryuk - Platform Pembelajaran Interaktif

Platform pembelajaran online untuk anak sekolah dengan design yang menarik dan fitur gamification, dibangun dengan PHP 8.2, JavaScript SPA, dan Supabase PostgreSQL.

## Quick Start (XAMPP + Supabase)

### 1. Jalankan XAMPP
- Buka XAMPP Control Panel
- **Start** Apache

### 2. Konfigurasi Environment
```bash
# Copy template environment
cp .env.example .env

# Edit .env dengan Supabase credentials
# DB_HOST, DB_USER, DB_PASS, DB_PORT
```

### 3. Buka Aplikasi
```
http://localhost/belajaryuk
```

---

## Struktur Folder

```
belajaryuk/
├── index.php               ← Root router (fallback jika .htaccess fail)
├── .htaccess               ← Apache rewrite rules
├── .env                    ← Environment variables (Supabase)
├── .env.example            ← Template environment
│
├── public/                 ← Web root (SPA frontend)
│   ├── index.php           ← SPA entry point
│   ├── .htaccess           ← Routing rules untuk public
│   ├── js/
│   │   ├── aplikasi.js     ← Logika inti SPA
│   │   ├── autentikasi.js  ← Alur autentikasi
│   │   ├── dasbor.js       ← Halaman dasbor siswa
│   │   ├── profil.js       ← Halaman profil
│   │   ├── progres.js      ← Halaman progres belajar
│   │   ├── chat-ai.js      ← Widget chat AI
│   │   ├── admin/          ← Fitur panel admin
│   │   ├── learning/       ← Fitur pembelajaran siswa
│   │   └── services/
│   │       └── api.js      ← API service layer & konfigurasi frontend
│   └── assets/
│       └── css/
│           ├── gaya.css    ← Manifest import CSS
│           ├── dasar.css
│           ├── halaman.css
│           ├── komponen.css
│           ├── admin.css
│           ├── visual.css
│           └── responsif.css
│
├── api/
│   ├── router.php          ← API router (handles /api/*)
│   └── routes.php          ← Daftar endpoint API
│
├── src/
│   ├── config/
│   │   ├── lingkungan.php   ← Config & security settings
│   │   └── basis-data.php   ← PDO Supabase connection
│   ├── controllers/
│   │   ├── KontrolerAuth.php
│   │   ├── KontrolerMateri.php
│   │   ├── KontrolerKuis.php
│   │   ├── KontrolerProgres.php
│   │   └── KontrolerAI.php
│   ├── models/
│   │   ├── Pengguna.php
│   │   ├── Materi.php
│   │   ├── Kuis.php
│   │   └── Progres.php
│   ├── middlewares/
│   │   ├── MiddlewareAutentikasi.php
│   │   └── MiddlewareCSRF.php
│   ├── services/
│   │   └── LayananUpload.php
│   └── utils/
│       ├── Keamanan.php
│       └── Respons.php
│
├── scripts/
│   └── sinkron_materi_video.php  ← Sinkron materi dari playlist YouTube Indonesia
│
├── skema_supabase.sql     ← Database schema reference
└── README.md               ← This file
```

---

## Konfigurasi

### Environment (.env)
```env
APP_ENV=development
APP_URL=http://localhost/belajaryuk
JWT_SECRET=dev-secret-key-change-me-in-production

# Supabase (Connection Pooler)
DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
DB_NAME=postgres
DB_USER=postgres.xxxxx
DB_PASS=xxxxx
DB_PORT=6543

GROQ_API_KEY=  # Optional: untuk fitur AI chat
```

### API Router
- Semua request `/api/*` ditangani oleh `api/router.php`
- Controller merespons dengan JSON format standar:
```json
{
  "success": true/false,
  "message": "Description",
  "data": {}
}
```

---

## Fitur Utama

- **Autentikasi & Registrasi** - Login, register, session management
- **Materi Pembelajaran** - Multi-course, sub-materials, HTML content
- **Kuis Interaktif** - Multiple choice dengan scoring
- **Progress Tracking** - Timeline pembelajaran, achievements
- **Responsive Design** - Mobile, tablet, desktop support
- **CSRF Protection** - Secure API requests
- **Gamification** - Streaks, badges, leaderboard
- **AI Assistant** - Chat dengan Groq AI (optional)

---

## API Endpoints

### Authentication
```
POST   /api/auth/register          - Register user baru
POST   /api/auth/login             - Login
POST   /api/auth/logout            - Logout
GET    /api/auth/current-user      - Current user info
PUT    /api/auth/profile           - Update profil
```

### Materials
```
GET    /api/materials              - Semua materi (paginated)
GET    /api/materials/detail       - Material detail + sub-materials
GET    /api/materials/categories   - Semua kategori
POST   /api/materials/mark-completed - Mark material selesai
```

### Quiz
```
GET    /api/quiz                   - Get quiz for material
GET    /api/quiz/questions         - Get questions
POST   /api/quiz/submit            - Submit answers
GET    /api/quiz/results           - User results
```

### Progress
```
GET    /api/progress/summary       - Learning summary
GET    /api/progress/categories    - Progress by category
GET    /api/progress/streak        - Learning streak
GET    /api/progress/achievements  - Achievements
```

---

## 🔐 Security

### Built-in Protections
- **CSRF Token** - X-CSRF-Token header validation
- **SQL Injection** - Prepared statements (PDO)
- **Password Security** - bcrypt hashing
- **Session Hardening** - HttpOnly cookies, SameSite=Lax
- **Input Validation** - Security::sanitize() on all user input
- **API Authentication** - JWT tokens via Authorization header

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Generate strong `JWT_SECRET`
- [ ] Enable HTTPS (set in nginx/Apache)
- [ ] Restrict database user permissions
- [ ] Enable rate limiting
- [ ] Setup monitoring/logging
- [ ] Regular backups

---

## Troubleshooting

| Masalah | Solusi |
|--------|--------|
| 404 Not Found | Pastikan Apache mod_rewrite enabled, .htaccess readable |
| Database Error | Cek .env credentials, koneksi Supabase status |
| Assets tidak loading | Clear browser cache (Ctrl+Shift+Delete) |
| CSRF Token Mismatch | Pastikan X-CSRF-Token dikirim dalam headers |
| API timeout | Cek koneksi internet, database response time |

---

## Responsive Breakpoints

- **Desktop**: 1200px+
- **Tablet**: 769px - 1024px  
- **Mobile**: 480px - 768px
- **Small Mobile**: < 480px

---

## Color Scheme

- Primary: `#6366f1` (Indigo)
- Success: `#10b981` (Green)
- Warning: `#f59e0b` (Amber)
- Danger: `#ef4444` (Red)
- Info: `#0ea5e9` (Sky Blue)

---

## Database Schema

Menggunakan 9 tabel utama:
- `pengguna` - User accounts
- `materi` - Learning materials
- `sub_materi` - Lessons/episodes
- `kuis` - Quiz definitions
- `pertanyaan` - Quiz questions
- `jawaban_user` - Quiz responses
- `progres_user` - Progress tracking
- `pencapaian` - User achievements
- `percakapan_ai` - AI chat history

---

## AI Integration

Fitur "Tanya AI" menggunakan Groq API:
- Set `GROQ_API_KEY` di .env untuk mengaktifkan
- Endpoint: `POST /api/ai/chat`
- Disimpan dalam table `percakapan_ai`

---

## Performance

Optimizations implemented:
- Minified CSS & JavaScript
- Lazy loading untuk images
- Query optimization (indexed keys)
- Response compression headers
- Caching strategy via headers
- CDN-ready asset paths

---

## Deployment

### Traditional Hosting
1. Upload semua file ke hosting
2. Create database via hosting control panel
3. Import `skema_supabase.sql`
4. Update `.env` dengan credentials
5. Set folder permissions: `755` untuk directories, `644` untuk files
6. Set correct Apache DocumentRoot

### Docker
```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_pgsql
COPY . /var/www/html/
RUN a2enmod rewrite
WORKDIR /var/www/html
```

---

## Development Tips

- Frontend: Plain HTML/CSS/JavaScript (no framework)
- Backend: PHP 8.2 with MVC pattern
- Database: Supabase PostgreSQL via PDO
- API: RESTful with JSON responses
- Error logs: Check PHP error_log for debugging

---

## License

Open source. Silakan gunakan dan modifikasi.

---

**Happy Learning!**

Made for students everywhere.

# BelajarYuk
