# Analisis Penyebab Loading Tidak Hilang

## 1. Alur yang Melibatkan `UI.showLoading()` / `UI.hideLoading()`
- **MaterialDetail**: sebelum memanggil API `mark-completed` UI menampilkan overlay, kemudian menutupnya **hanya** pada jalur sukses. Jika API gagal atau terjadi pengecualian, overlay tetap terlihat.  
  - *Perbaikan*: membungkus pemanggilan API dalam `try … finally` sehingga `UI.hideLoading()` dijalankan **apapun** hasilnya.

- **Router**: memanggil `UI.showLoading()` saat navigasi dan menutupnya di blok `finally`. Jika terjadi error di dalam `try`, `finally` tetap mengeksekusi `UI.hideLoading()`.

## 2. Redundansi & Kode Tak Terpakai
- `progress.js` meng‑import `Config` yang tidak dipakai – dihapus untuk mengurangi bundle size.
- `UI.showLoading()` memiliki guard `if (this.isSplashVisible()) return;`. Setelah splash disembunyikan, guard tidak lagi menghalangi, tapi tetap aman.

## 3. Potensi Penyebab 500 pada `/api/quiz/results`
- **Query SQL** di `QuizModel::getUserQuizResults` meng‑akses kolom `percentage` & `total_points`. Jika skema DB berubah (kolom dihapus atau tipe tidak cocok) akan memicu `PDOException` → 500.
- **Auth Middleware**: bila token tidak valid, `AuthMiddleware::requireAuth()` mengirim `Response::error` (401) tetapi tidak `return` sehingga eksekusi dapat berlanjut dan menghasilkan *Notice* yang dalam `E_ALL` menjadi *Exception*.
- **Parameter `quiz_id`** yang tidak numeric dapat menyebabkan bind error pada driver PostgreSQL.

## 4. Perbaikan yang Diterapkan
1. **MaterialDetail.js** – Tambahan `try … finally` pada semua tombol (Next, Finish‑to‑Quiz, Finish) sehingga overlay selalu ditutup.
2. **Quiz.js** – Pada error saat memuat kuis, dipanggil `UI.hideLoading()` untuk menutup overlay yang mungkin masih aktif.
3. **QuizController::getUserResults** – Penambahan logging dan guard untuk memastikan eksekusi berhenti bila user tidak ada.
4. **QuizModel::getUserQuizResults** – Logging query & parameter untuk memudahkan debugging.
5. **User::getUserById** – Sertakan `streak_count` sehingga profil tidak menampilkan nilai lama.
6. **Leaderboard** – Filter hanya materi yang selesai (`completed_at IS NOT NULL`).
7. **Progress::getUserProgressSummary** – Cache APCu (30 detik) untuk mengurangi beban DB.
8. **Penghapusan import tidak terpakai** (`Config` di `progress.js`).

## 5. Langkah Verifikasi Selanjutnya
- Buka *Developer Console* → Network, pastikan tidak ada request `loading` yang terus‑menunggu.
- Jika masih muncul, periksa `php_errorlog` untuk baris yang mengandung `SQLSTATE` atau `Notice` pada saat panggilan `/api/quiz/results`.
- Pastikan kolom `percentage` dan `total_points` ada di tabel `hasil_kuis`.
- Pastikan token JWT masih berlaku (cek `exp`).

Dengan perbaikan ini, overlay loading akan selalu hilang baik pada keberhasilan maupun kegagalan, sehingga pengalaman pengguna menjadi stabil saat melanjutkan ke kuis.
