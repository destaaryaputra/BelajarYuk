<?php
/**
 * Sinkronisasi materi dari playlist YouTube Indonesia.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$env_file = __DIR__ . '/../src/config/lingkungan.php';
if (file_exists($env_file)) require_once $env_file;

use App\Config\Database;

$db = Database::getInstance();

function episode(string $title, string $content, string $youtubeId): array
{
    return [
        'title' => $title,
        'content' => $content,
        'video_url' => "https://www.youtube.com/embed/{$youtubeId}",
    ];
}

$materi = [
    1 => [
        'title' => 'Belajar HTML Dasar',
        'description' => 'Belajar HTML dari nol mengikuti playlist Indonesia Web Programming UNPAS: struktur dokumen, tag dasar, teks, link, gambar, tabel, dan form.',
        'category' => 'Programming',
        'difficulty' => 'beginner',
        'duration_minutes' => 130,
        'thumbnail' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80',
        'content' => 'Materi ini disusun mengikuti playlist HTML Dasar dari Web Programming UNPAS agar siswa belajar secara runtut dari pengenalan sampai form.',
        'episodes' => [
            episode('1. Pendahuluan HTML', 'Memahami fungsi HTML, sejarah singkat web, dan posisi HTML dalam pembuatan halaman website.', 'NBZ9Ro6UKV8'),
            episode('2. Hello World', 'Membuat file HTML pertama dan memahami struktur minimal halaman HTML.', '1NicaVOCXHA'),
            episode('3. Code Editor', 'Menyiapkan editor kode dan membiasakan workflow menulis file HTML.', '3sLSi9L5nWE'),
            episode('4. Tag HTML', 'Mengenal konsep tag, elemen, atribut, dan cara browser membaca markup HTML.', 'cUWBYzA6M-8'),
            episode('5. Paragraf', 'Mengatur konten teks menggunakan paragraf, pemisah baris, dan struktur teks sederhana.', 'Dl_bIYBc9gM'),
            episode('6. Heading', 'Menggunakan heading H1 sampai H6 untuk membuat hierarki konten yang jelas.', 'SMetRBdIh-8'),
            episode('7. List', 'Membuat ordered list, unordered list, dan nested list untuk konten berurutan.', 'gLHEoeupIZs'),
            episode('8. Hyperlink', 'Membuat tautan internal dan eksternal menggunakan anchor serta memahami atribut link.', 'QIlBOI-hTuA'),
            episode('9. Image', 'Menampilkan gambar, mengatur sumber file, teks alternatif, ukuran, dan link pada gambar.', 'yb_emYhY3Pc'),
            episode('10. Table', 'Membuat tabel dasar dengan baris, kolom, header, dan struktur table HTML.', '7-QNafrXigs'),
            episode('11. Table Merging', 'Menggabungkan sel tabel menggunakan colspan dan rowspan.', 'qs8G2XWf7Yk'),
            episode('12. Form', 'Mengenal elemen form, input, label, textarea, select, dan button.', 'LQf_Jj7jbCI'),
            episode('13. Form Lanjutan', 'Melengkapi form HTML dengan variasi input dan struktur yang lebih rapi.', '_CNkLKU-LoE'),
        ],
    ],
    2 => [
        'title' => 'Belajar CSS Dasar',
        'description' => 'Belajar CSS dasar berbahasa Indonesia dari Web Programming UNPAS: anatomi CSS, penempatan, font, teks, background, selector, pseudo class, inheritance, dan specificity.',
        'category' => 'Programming',
        'difficulty' => 'beginner',
        'duration_minutes' => 100,
        'thumbnail' => 'https://images.unsplash.com/photo-1507721999472-8ed4421c4af2?w=800&q=80',
        'content' => 'Materi ini mengikuti playlist CSS Dasar dari Web Programming UNPAS sebagai lanjutan alami setelah HTML Dasar.',
        'episodes' => [
            episode('1. Pendahuluan CSS', 'Mengenal fungsi CSS dan bagaimana CSS memisahkan struktur konten dari tampilan.', 'CleFk3BZB3g'),
            episode('2. Anatomi CSS', 'Memahami selector, property, value, deklarasi, dan aturan dasar penulisan CSS.', '8lXDi2Mxp9c'),
            episode('3. Penempatan CSS', 'Membedakan inline CSS, internal CSS, dan external CSS serta kapan masing-masing dipakai.', 'bnnislprJro'),
            episode('4. Font Styling', 'Mengatur jenis font, ukuran, ketebalan, style, dan varian teks.', 'nPHed3_oPvY'),
            episode('5. Text Styling', 'Mengatur warna teks, alignment, dekorasi, indentasi, transformasi, dan spacing.', 'xih8giA7S3Q'),
            episode('6. Background', 'Mengatur warna latar, gambar latar, posisi, repeat, dan attachment.', 'zm-HPYS_ELU'),
            episode('7. Selector', 'Mengenal selector tag, class, id, dan kombinasi selector untuk memilih elemen.', '0KLwWyQyMQo'),
            episode('8. Pseudo Class', 'Menggunakan pseudo class seperti hover, active, visited, dan child selector.', 'G0gYWdIHOug'),
            episode('9. Inheritance', 'Memahami pewarisan style dari parent ke child agar CSS lebih efisien.', 'kY2FEA3y43E'),
            episode('10. Specificity', 'Memahami bobot selector agar konflik style dapat dibaca dan diselesaikan.', 'yu74Y1ndd5w'),
        ],
    ],
    3 => [
        'title' => 'Belajar JavaScript Dasar',
        'description' => 'Belajar dasar pemrograman dengan JavaScript dari playlist Indonesia Web Programming UNPAS: konsep programming, tipe data, operator, variabel, dan cara menulis JavaScript.',
        'category' => 'Programming',
        'difficulty' => 'beginner',
        'duration_minutes' => 210,
        'thumbnail' => 'https://images.unsplash.com/photo-1579468118864-1b9ea3c0db4a?w=800&q=80',
        'content' => 'Materi ini mengikuti urutan playlist Dasar Pemrograman dengan JavaScript dari Web Programming UNPAS.',
        'episodes' => [
            episode('1. Intro Dasar Pemrograman dengan JavaScript', 'Pengantar jalur belajar JavaScript dan alasan memulai dari konsep pemrograman dasar.', 'RUTV_5m4VeI'),
            episode('2. Apa Itu Pemrograman', 'Memahami pemrograman sebagai proses memberi instruksi terstruktur kepada komputer.', 'Ncrlg9kTC6U'),
            episode('3. Bahasa Pemrograman', 'Mengenal peran bahasa pemrograman dan bagaimana manusia berkomunikasi dengan komputer.', 'dugL0oYx0w0'),
            episode('4. Compiler vs Interpreter', 'Membedakan cara compiler dan interpreter menjalankan instruksi program.', 'gCBysZKiU3Y'),
            episode('5. Kenapa Belajar JavaScript', 'Mengenal alasan JavaScript penting untuk web modern dan ekosistem pengembangannya.', '6UhT1lmV9DE'),
            episode('6. Sejarah JavaScript', 'Mengenal asal-usul JavaScript dan perkembangannya di dunia web.', 'G4LEU-NtQUg'),
            episode('7. Lingkungan Pengembangan JavaScript', 'Menyiapkan browser, console, editor, dan workflow latihan JavaScript.', 'h7zwbfS5CVU'),
            episode('8. Nilai dan Tipe Data', 'Memahami konsep nilai dan tipe data sebagai fondasi logika program.', '1FAnrYu7LCM'),
            episode('9. Tipe Data Angka', 'Menggunakan number, operasi matematika, dan perilaku angka di JavaScript.', 'oPlEq7fewIg'),
            episode('10. Operator Aritmatika, Penugasan, dan Perbandingan', 'Menggunakan operator dasar untuk menghitung, menyimpan, dan membandingkan nilai.', 'EnXClrVdpTM'),
            episode('11. Operator Logika, String, Typeof, dan Kondisional', 'Menggunakan operator lanjutan untuk membuat ekspresi logika dan keputusan sederhana.', '_XSeF00qNWE'),
            episode('12. Tipe Data String', 'Mengolah teks, menggabungkan string, dan memahami karakter dalam JavaScript.', 'ud322_5-M3s'),
            episode('13. Tipe Data Boolean', 'Menggunakan nilai true/false untuk logika program.', 'y1X-JoJMXXA'),
            episode('14. Variable', 'Menyimpan nilai ke variabel dan memahami cara data dipakai ulang dalam program.', 'X1q_cK0Qv6o'),
            episode('15. Menulis JavaScript', 'Mempraktikkan cara menulis JavaScript dengan struktur yang benar.', 'a7Kheeu59JQ'),
        ],
    ],
    4 => [
        'title' => 'Belajar Lebih Efektif',
        'description' => 'Materi produktivitas belajar dari playlist Indonesia Solve Media: Pomodoro, passion, neuron, tidur, metafora, flash card, memori, dan strategi menghadapi ujian.',
        'category' => 'Personal Development',
        'difficulty' => 'beginner',
        'duration_minutes' => 150,
        'thumbnail' => 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=800&q=80',
        'content' => 'Materi ini mengikuti playlist Belajar Cara Belajar dari Solve Media untuk membantu siswa belajar lebih efektif dan konsisten.',
        'episodes' => [
            episode('1. Teknik Pomodoro untuk Menunda-nunda', 'Menggunakan sesi fokus dan jeda agar belajar tidak terasa berat dan lebih konsisten.', 'SkupexXpd5E'),
            episode('2. Menghadapi Mata Pelajaran yang Susah', 'Membangun cara pandang yang lebih sehat saat bertemu materi sulit.', 'pUvkaRtRwYI'),
            episode('3. Memahami Cara Kerja Neuron', 'Mengenal bagaimana otak membentuk koneksi saat belajar hal baru.', '3d7DDIalu9U'),
            episode('4. Memaksimalkan Kinerja Otak dengan Tidur', 'Memahami peran tidur dalam konsolidasi memori dan pemulihan fokus.', 'LiuKdEecB6M'),
            episode('5. Menggunakan Metafora untuk Belajar', 'Memakai analogi dan metafora agar konsep sulit lebih mudah dipahami.', '820qsd2oB9Y'),
            episode('6. Menggunakan Flash Card', 'Melatih ingatan dengan flash card dan pengulangan aktif.', 'tnT5uhFDzMI'),
            episode('7. Melatih Kemampuan Mengingat', 'Memahami strategi memperkuat memori agar materi lebih lama tersimpan.', 'NMeSvkCK6KM'),
            episode('8. Tips Menghafal Pelajaran Secara Efektif', 'Menggunakan jembatan keledai dan teknik asosiasi untuk membantu hafalan.', '8T-fyrOK_fc'),
            episode('9. Trik Memori Auto-Pintar', 'Membangun teknik memori praktis untuk mengingat konsep penting.', 'TuIn0YG6iDk'),
            episode('10. Membangun Rantai Otak', 'Menyambungkan ide dan konsep agar pemahaman menjadi lebih utuh.', 'kumskTJcu_8'),
            episode('11. Terlalu Banyak Informasi', 'Mengelola informasi berlebihan agar belajar tetap fokus dan tidak kewalahan.', '4BxQ7bI117E'),
            episode('12. Makanan untuk Otak', 'Mengenal kebiasaan yang mendukung kesehatan otak dan kualitas belajar.', 'JiWQzoyeRcA'),
            episode('13. Kekurangan Menjadi Kelebihan', 'Mengubah hambatan belajar menjadi strategi adaptif.', 'FW7JnxZPIK4'),
            episode('14. Pintar Menghadapi Ujian', 'Mempersiapkan ujian dengan strategi belajar dan mental yang lebih terarah.', 'GxWNksGgBoI'),
            episode('15. Praktik Belajar Cara Belajar', 'Merangkum dan mempraktikkan teknik belajar yang sudah dipelajari.', 'SUokzN_dCCI'),
        ],
    ],
    5 => [
        'title' => 'Belajar Canva Dasar',
        'description' => 'Belajar Canva dari playlist Indonesia Kampus Tutorial: jenis akun, elemen, teks, dokumen, download, shape, chart, frame, gambar, dan fitur praktis lainnya.',
        'category' => 'Design',
        'difficulty' => 'beginner',
        'duration_minutes' => 150,
        'thumbnail' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&q=80',
        'content' => 'Materi ini mengikuti playlist Tutorial Canva Untuk Pemula dari Kampus Tutorial agar siswa belajar Canva secara runtut dari dasar sampai fitur praktis.',
        'episodes' => [
            episode('1. Mengenal Canva dan Area Kerja', 'Mengenal Canva, area kerja, dan cara menyimpan file desain.', 'WhJjFBVB_dY'),
            episode('2. Jenis Akun Canva', 'Memahami perbedaan jenis akun Canva dan fitur yang tersedia untuk pengguna.', 'CdCKN8SzHQE'),
            episode('3. Mengenal Element Canva', 'Mencari, memilih, dan mengatur elemen visual di dalam desain.', 'fbWRIOwu0pE'),
            episode('4. Mengganti Nama dan Ukuran Dokumen', 'Mengatur nama dokumen dan ukuran desain sesuai kebutuhan proyek.', 'MPNdkk5V1MA'),
            episode('5. Download File Canva', 'Mengekspor desain ke format JPG, PNG, video, dan PDF.', 'Rz8SU9AVFYI'),
            episode('6. Menggambar Shape di Canva', 'Membuat dan mengatur shape atau bangun datar sebagai elemen dasar desain.', '5D3Yha9W2hs'),
            episode('7. Mengedit Teks di Canva', 'Mengubah isi, ukuran, warna, posisi, dan gaya teks agar desain mudah dibaca.', 'cbjSfqYDahQ'),
            episode('8. Menambahkan Efek Teks', 'Menggunakan efek teks untuk membuat judul atau highlight visual lebih menarik.', '4c2aRCYnsQc'),
            episode('9. Membuat Animasi Teks', 'Menerapkan animasi teks sederhana untuk konten bergerak atau presentasi.', 'EVSlalNj1LE'),
            episode('10. Menambahkan Bagan, Chart, dan Diagram', 'Membuat visual data dengan chart, diagram, dan bagan di Canva.', 'OHr_o9bPxgU'),
            episode('11. Membuat Frame atau Bingkai', 'Menggunakan frame untuk membingkai foto dan membuat komposisi visual lebih rapi.', 'nny_MeQhwaQ'),
            episode('12. Mengedit Gambar dan Grafis', 'Mengatur gambar dan grafis agar cocok dengan tema desain.', 'RE2Zs919TSk'),
            episode('13. Efek Powerclip Masking pada Teks', 'Membuat efek masking teks agar desain terlihat lebih kreatif.', 'IS7x4fLzl5Q'),
            episode('14. Menghapus Background Foto', 'Menghapus latar belakang foto untuk membuat desain lebih bersih dan fleksibel.', 'XcxXIylEvi8'),
        ],
    ],
];

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    $updateMateri = $db->prepare(
        "UPDATE materi
         SET title = :title,
             description = :description,
             category = :category,
             difficulty = :difficulty,
             duration_minutes = :duration_minutes,
             thumbnail = :thumbnail,
             content = :content,
             video_url = NULL,
             status = 'active'
         WHERE id = :id"
    );

    $insertMateri = $db->prepare(
        "INSERT INTO materi (id, title, description, category, difficulty, duration_minutes, thumbnail, content, video_url, status, created_at)
         VALUES (:id, :title, :description, :category, :difficulty, :duration_minutes, :thumbnail, :content, NULL, 'active', NOW())"
    );

    $deleteEpisode = $db->prepare("DELETE FROM sub_materi WHERE material_id = ?");
    $insertEpisode = $db->prepare(
        "INSERT INTO sub_materi (material_id, title, content, order_number, video_url)
         VALUES (:material_id, :title, :content, :order_number, :video_url)"
    );

    foreach ($materi as $id => $data) {
        $payload = [
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':difficulty' => $data['difficulty'],
            ':duration_minutes' => $data['duration_minutes'],
            ':thumbnail' => $data['thumbnail'],
            ':content' => $data['content'],
        ];

        $updateMateri->execute($payload);
        if ($updateMateri->rowCount() === 0) {
            $insertMateri->execute($payload);
        }

        $deleteEpisode->execute([$id]);

        foreach ($data['episodes'] as $index => $episode) {
            $insertEpisode->execute([
                ':material_id' => $id,
                ':title' => $episode['title'],
                ':content' => $episode['content'],
                ':order_number' => $index + 1,
                ':video_url' => $episode['video_url'],
            ]);
        }

        echo "OK materi #{$id}: {$data['title']} (" . count($data['episodes']) . " episode)\n";
    }

    $db->exec("SELECT setval(pg_get_serial_sequence('materi', 'id'), GREATEST((SELECT MAX(id) FROM materi), 1), true)");
    $db->commit();

    echo "Selesai. Materi dari playlist Indonesia sudah disinkronkan.\n";
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "Gagal sinkron materi: " . $e->getMessage() . "\n");
    exit(1);
}
