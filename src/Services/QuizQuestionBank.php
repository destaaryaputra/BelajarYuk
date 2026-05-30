<?php

namespace App\Services;

class QuizQuestionBank {
    public static function questionsFor(array $quiz): array {
        $quizType = strtolower((string)($quiz['quiz_type'] ?? 'final'));
        $materialTitle = self::cleanText((string)($quiz['material_title'] ?? ''));

        if ($quizType === 'mini') {
            return self::miniQuestions($quiz);
        }

        return self::finalQuestions($materialTitle);
    }

    private static function miniQuestions(array $quiz): array {
        $materialTitle = self::cleanText((string)($quiz['material_title'] ?? 'materi ini'));
        $episodeTitle = self::cleanEpisodeTitle((string)($quiz['sub_material_title'] ?? 'episode ini'));
        $episodeContent = self::cleanText((string)($quiz['sub_material_content'] ?? ''));

        if ($episodeContent === '') {
            return [];
        }

        $outcome = self::miniPracticeOutcome($materialTitle, $episodeTitle);
        $importance = self::miniImportance($materialTitle, $episodeTitle);

        return [
            self::q(
                "Setelah mempelajari episode \"{$episodeTitle}\", pemahaman utama apa yang harus kamu miliki?",
                $episodeContent,
                [
                    'Menghafal judul episode tanpa memahami inti praktiknya.',
                    'Melewati konsep dasar dan langsung menyalin hasil akhir.',
                    'Fokus pada tampilan akhir tanpa memahami alasan setiap langkah.'
                ]
            ),
            self::q(
                "Cara paling tepat membuktikan bahwa kamu memahami episode \"{$episodeTitle}\" adalah...",
                $outcome,
                [
                    'Mengulang video tanpa mencoba menerapkan konsepnya sendiri.',
                    'Menebak jawaban dari istilah yang terdengar paling teknis.',
                    'Mengabaikan kesalahan kecil karena yang penting hasilnya terlihat mirip.'
                ]
            ),
            self::q(
                "Mengapa episode \"{$episodeTitle}\" penting dalam alur {$materialTitle}?",
                $importance,
                [
                    'Karena episode ini hanya pelengkap dan tidak berpengaruh ke materi berikutnya.',
                    'Karena cukup dihafal sekali tanpa perlu latihan ulang.',
                    'Karena semua detailnya bisa dilewati jika sudah melihat contoh hasil akhir.'
                ]
            )
        ];
    }

    private static function finalQuestions(string $materialTitle): array {
        $title = strtolower($materialTitle);

        if (strpos($title, 'html') !== false) {
            return self::htmlFinalQuestions();
        }

        if (strpos($title, 'css') !== false) {
            return self::cssFinalQuestions();
        }

        if (strpos($title, 'javascript') !== false) {
            return self::javascriptFinalQuestions();
        }

        if (strpos($title, 'efektif') !== false) {
            return self::learningFinalQuestions();
        }

        if (strpos($title, 'canva') !== false) {
            return self::canvaFinalQuestions();
        }

        return [];
    }

    private static function htmlFinalQuestions(): array {
        return [
            self::q('Mengapa struktur `<!DOCTYPE html>`, `<html>`, `<head>`, dan `<body>` penting dalam dokumen HTML?', 'Agar browser memahami standar dokumen, metadata halaman, dan konten yang harus ditampilkan.', ['Agar semua teks otomatis menjadi tebal.', 'Agar CSS tidak perlu ditulis lagi.', 'Agar file HTML bisa berjalan tanpa browser.']),
            self::q('Kapan heading `<h1>` sampai `<h6>` sebaiknya digunakan?', 'Saat membuat hierarki judul dan subjudul agar struktur konten mudah dipahami.', ['Untuk memperbesar semua teks tanpa memikirkan struktur.', 'Untuk mengganti fungsi paragraf pada seluruh halaman.', 'Untuk menyimpan metadata yang tidak terlihat pengguna.']),
            self::q('Apa alasan penggunaan atribut `alt` pada elemen gambar?', 'Memberi deskripsi gambar untuk aksesibilitas dan cadangan saat gambar gagal dimuat.', ['Agar ukuran gambar otomatis mengecil.', 'Agar gambar berubah menjadi hyperlink.', 'Agar browser menghapus background gambar.']),
            self::q('Dalam pembuatan hyperlink, fungsi utama atribut `href` adalah...', 'Menentukan tujuan tautan yang akan dibuka saat link diklik.', ['Menentukan warna teks link.', 'Menentukan ukuran font link.', 'Menentukan jenis file gambar.']),
            self::q('Jika ingin membuat tabel dengan sel yang melebar ke beberapa kolom, atribut yang tepat adalah...', '`colspan`, karena atribut ini menggabungkan beberapa kolom dalam satu sel.', ['`rowspan`, karena atribut ini menggabungkan beberapa kolom.', '`cellpadding`, karena atribut ini menambah jumlah kolom.', '`border`, karena atribut ini menyatukan isi tabel.']),
            self::q('Mengapa elemen `<label>` sebaiknya dipasangkan dengan input form?', 'Agar input lebih jelas, mudah diakses, dan dapat dipilih melalui teks label.', ['Agar form otomatis terkirim tanpa tombol.', 'Agar semua input menjadi wajib diisi.', 'Agar password selalu disembunyikan.']),
            self::q('Apa perbedaan utama ordered list dan unordered list?', 'Ordered list menunjukkan urutan bernomor, sedangkan unordered list menunjukkan daftar tanpa urutan angka.', ['Ordered list hanya untuk gambar, unordered list hanya untuk link.', 'Ordered list dipakai di CSS, unordered list dipakai di JavaScript.', 'Keduanya selalu menghasilkan tampilan tabel.']),
            self::q('Pilihan markup yang paling semantik untuk menandai teks yang benar-benar penting adalah...', '`<strong>`, karena memberi makna penekanan penting, bukan sekadar tampilan.', ['`<br>`, karena membuat teks lebih penting.', '`<img>`, karena menampilkan teks sebagai gambar.', '`<table>`, karena menyusun teks penting dalam kolom.'])
        ];
    }

    private static function cssFinalQuestions(): array {
        return [
            self::q('Dalam aturan CSS `p { color: red; }`, bagian `p`, `color`, dan `red` masing-masing berperan sebagai...', 'Selector, property, dan value.', ['Property, selector, dan value.', 'Value, selector, dan property.', 'Selector, value, dan property.']),
            self::q('Kapan external CSS lebih tepat digunakan dibanding inline CSS?', 'Saat banyak halaman perlu memakai gaya yang konsisten dan mudah dipelihara.', ['Saat hanya ingin memberi style satu elemen secara darurat.', 'Saat CSS tidak boleh dipakai di file lain.', 'Saat ingin menghapus kebutuhan selector.']),
            self::q('Apa perbedaan fokus antara font styling dan text styling?', 'Font styling mengatur bentuk huruf, sedangkan text styling mengatur perilaku teks seperti warna, jarak, dan perataan.', ['Font styling hanya untuk background, text styling hanya untuk gambar.', 'Font styling mengatur link, text styling mengatur tabel.', 'Keduanya selalu memiliki property yang sama.']),
            self::q('Mengapa selector class sering lebih fleksibel daripada selector id?', 'Class dapat dipakai pada banyak elemen, sedangkan id sebaiknya unik untuk satu elemen.', ['Class selalu lebih kuat specificity-nya daripada id.', 'Class hanya bisa dipakai pada elemen paragraf.', 'Id tidak bisa dipakai untuk styling CSS.']),
            self::q('Apa manfaat pseudo-class seperti `:hover`?', 'Memberi style berdasarkan kondisi atau interaksi elemen.', ['Mengubah HTML menjadi JavaScript.', 'Menghapus semua style bawaan browser.', 'Menjadikan semua selector bernilai sama.']),
            self::q('Konsep inheritance dalam CSS berarti...', 'Sebagian property dapat diwariskan dari elemen induk ke elemen anak.', ['Semua property selalu menimpa style browser.', 'Setiap elemen wajib memiliki id.', 'CSS hanya bisa ditulis di tag `<style>`.']),
            self::q('Jika dua aturan CSS menargetkan elemen yang sama, specificity membantu menentukan...', 'Aturan mana yang lebih kuat ketika terjadi konflik style.', ['Ukuran file CSS setelah dikompresi.', 'Jumlah gambar yang boleh dipakai.', 'Kecepatan internet pengguna.']),
            self::q('Mengapa memahami background position dan repeat penting?', 'Agar gambar latar dapat ditempatkan dan diulang sesuai kebutuhan desain.', ['Agar teks otomatis menjadi responsif.', 'Agar selector CSS tidak perlu ditulis.', 'Agar semua gambar berubah menjadi ikon.'])
        ];
    }

    private static function javascriptFinalQuestions(): array {
        return [
            self::q('Dalam konteks pemrograman, program paling tepat dipahami sebagai...', 'Serangkaian instruksi logis yang diberikan kepada komputer untuk menyelesaikan tugas.', ['Kumpulan warna dan font untuk halaman web.', 'File gambar yang dijalankan browser.', 'Daftar video yang harus ditonton berurutan.']),
            self::q('Perbedaan utama compiler dan interpreter adalah...', 'Compiler menerjemahkan program sebelum dijalankan, interpreter menjalankan dan menerjemahkan instruksi secara bertahap.', ['Compiler hanya untuk HTML, interpreter hanya untuk CSS.', 'Compiler berjalan di browser, interpreter berjalan di database.', 'Keduanya selalu menghasilkan file gambar.']),
            self::q('Mengapa JavaScript penting untuk web modern?', 'Karena JavaScript membuat halaman web dapat merespons interaksi dan menjalankan logika di browser.', ['Karena JavaScript menggantikan semua tag HTML.', 'Karena JavaScript hanya dipakai untuk menggambar logo.', 'Karena JavaScript membuat internet tidak perlu server.']),
            self::q('Mengapa memahami tipe data penting sebelum menulis logika JavaScript?', 'Karena operasi dan hasil program bergantung pada jenis nilai yang sedang diproses.', ['Karena semua tipe data memiliki perilaku yang sama.', 'Karena tipe data hanya dibutuhkan untuk komentar.', 'Karena JavaScript tidak mengenal nilai teks.']),
            self::q('Operator perbandingan paling tepat digunakan untuk...', 'Membandingkan dua nilai dan menghasilkan nilai boolean.', ['Menggabungkan file HTML dan CSS.', 'Membuat variabel baru tanpa nilai.', 'Menghapus error dari console.']),
            self::q('Operator logika seperti AND dan OR berguna ketika...', 'Program harus mengambil keputusan berdasarkan lebih dari satu kondisi.', ['Program hanya menampilkan gambar statis.', 'Variabel tidak boleh berubah.', 'String harus selalu diubah menjadi angka.']),
            self::q('Apa fungsi utama variabel dalam JavaScript?', 'Menyimpan nilai agar dapat digunakan kembali dan diolah oleh program.', ['Mengatur warna teks secara otomatis.', 'Mengganti fungsi browser.', 'Membuat file HTML baru tanpa kode.']),
            self::q('Kapan `typeof` membantu saat belajar JavaScript dasar?', 'Saat mengecek jenis nilai agar operasi yang dipilih sesuai.', ['Saat mengirim email dari browser.', 'Saat mengubah ukuran video YouTube.', 'Saat memilih warna background di CSS.'])
        ];
    }

    private static function learningFinalQuestions(): array {
        return [
            self::q('Inti teknik Pomodoro dalam belajar adalah...', 'Membagi waktu belajar menjadi sesi fokus dan jeda agar konsentrasi lebih terjaga.', ['Belajar terus tanpa istirahat sampai selesai.', 'Mengerjakan semua materi secara acak.', 'Menghafal hanya saat menjelang ujian.']),
            self::q('Saat menghadapi pelajaran yang sulit, sikap belajar yang paling sehat adalah...', 'Memecah materi menjadi bagian kecil dan memberi ruang untuk latihan bertahap.', ['Menghindari materi tersebut sepenuhnya.', 'Menganggap sulit berarti tidak berbakat.', 'Menghafal jawaban tanpa memahami alasan.']),
            self::q('Mengapa tidur berpengaruh pada kualitas belajar?', 'Tidur membantu otak memulihkan fokus dan menguatkan ingatan.', ['Tidur menggantikan kebutuhan latihan.', 'Tidur membuat semua materi otomatis dikuasai.', 'Tidur hanya bermanfaat untuk pelajaran olahraga.']),
            self::q('Metafora membantu belajar karena...', 'Konsep baru dapat dihubungkan dengan pengalaman yang lebih familiar.', ['Metafora membuat fakta tidak perlu dicek.', 'Metafora selalu lebih penting daripada latihan.', 'Metafora menggantikan seluruh penjelasan guru.']),
            self::q('Flash card paling efektif ketika digunakan untuk...', 'Active recall dan pengulangan berkala, bukan sekadar membaca ulang.', ['Menyimpan catatan panjang tanpa latihan.', 'Menggambar dekorasi buku catatan.', 'Menghindari evaluasi pemahaman.']),
            self::q('Strategi menghadapi terlalu banyak informasi adalah...', 'Memilih prioritas, membuat struktur, dan memproses materi secara bertahap.', ['Membuka semua sumber sekaligus.', 'Menghafal semua kalimat tanpa seleksi.', 'Berpindah topik setiap beberapa menit.']),
            self::q('Mengapa latihan mengingat lebih kuat daripada hanya membaca ulang?', 'Karena otak dilatih mengambil kembali informasi, sehingga jejak memori lebih kuat.', ['Karena membaca ulang selalu buruk.', 'Karena latihan mengingat tidak butuh pemahaman.', 'Karena semua materi harus dihafal kata per kata.']),
            self::q('Persiapan ujian yang matang sebaiknya mencakup...', 'Review bertahap, latihan soal, tidur cukup, dan strategi mengelola tekanan.', ['Begadang penuh pada malam terakhir.', 'Hanya membaca rangkuman sekali.', 'Menunggu motivasi muncul sebelum belajar.'])
        ];
    }

    private static function canvaFinalQuestions(): array {
        return [
            self::q('Mengapa memahami area kerja Canva penting sebelum mulai desain?', 'Agar pengguna tahu tempat memilih elemen, mengatur halaman, menyimpan, dan mengedit desain.', ['Agar semua desain otomatis viral.', 'Agar tidak perlu menentukan ukuran dokumen.', 'Agar Canva berubah menjadi editor kode.']),
            self::q('Saat memilih ukuran dokumen di Canva, pertimbangan utama adalah...', 'Media tujuan desain, misalnya presentasi, poster, feed, atau dokumen cetak.', ['Warna favorit pembuat desain saja.', 'Jumlah akun Canva yang dimiliki.', 'Nama file yang paling panjang.']),
            self::q('Fungsi elemen Canva dalam desain adalah...', 'Menambahkan komponen visual seperti ikon, shape, ilustrasi, garis, dan dekorasi.', ['Menghapus semua teks dari desain.', 'Mengubah akun gratis menjadi pro.', 'Membuat koneksi internet lebih cepat.']),
            self::q('Mengapa hierarchy teks penting dalam desain?', 'Agar pembaca tahu mana informasi utama, pendukung, dan detail.', ['Agar semua teks berukuran sama.', 'Agar teks tidak perlu diberi warna.', 'Agar desain hanya berisi judul.']),
            self::q('Kapan format PNG lebih tepat dipilih saat download desain?', 'Saat membutuhkan kualitas gambar baik atau latar transparan jika fitur tersedia.', ['Saat ingin mengedit rumus spreadsheet.', 'Saat desain harus menjadi file audio.', 'Saat semua gambar harus dikompresi maksimal.']),
            self::q('Frame di Canva paling tepat digunakan untuk...', 'Menempatkan foto ke bentuk atau komposisi tertentu secara rapi.', ['Menghapus seluruh halaman desain.', 'Mengubah teks menjadi kode HTML.', 'Mengganti fungsi chart.']),
            self::q('Chart atau diagram di Canva sebaiknya dipakai ketika...', 'Data perlu dijelaskan secara visual agar lebih mudah dibandingkan.', ['Desain tidak memiliki data apa pun.', 'Semua teks harus disembunyikan.', 'Hanya ingin menambah hiasan tanpa makna.']),
            self::q('Efek teks dan masking sebaiknya digunakan dengan prinsip...', 'Mendukung pesan desain tanpa mengorbankan keterbacaan.', ['Memakai efek sebanyak mungkin di semua teks.', 'Membuat judul sulit dibaca agar terlihat ramai.', 'Menghapus kontras antara teks dan latar.'])
        ];
    }

    private static function miniPracticeOutcome(string $materialTitle, string $episodeTitle): string {
        $title = strtolower($materialTitle);

        if (strpos($title, 'html') !== false) {
            return "Mampu menulis contoh markup yang sesuai dengan fungsi episode \"{$episodeTitle}\" dan menjelaskan alasan penggunaan elemennya.";
        }

        if (strpos($title, 'css') !== false) {
            return "Mampu memilih selector atau property yang relevan dengan episode \"{$episodeTitle}\" lalu melihat dampaknya pada tampilan halaman.";
        }

        if (strpos($title, 'javascript') !== false) {
            return "Mampu membuat contoh kecil di console atau file JavaScript yang menunjukkan konsep episode \"{$episodeTitle}\" berjalan.";
        }

        if (strpos($title, 'efektif') !== false) {
            return "Mampu menerapkan strategi dari episode \"{$episodeTitle}\" pada sesi belajar nyata dan mengevaluasi hasilnya.";
        }

        if (strpos($title, 'canva') !== false) {
            return "Mampu menggunakan fitur pada episode \"{$episodeTitle}\" untuk memperbaiki desain secara terarah, bukan sekadar mencoba acak.";
        }

        return "Mampu menerapkan konsep episode \"{$episodeTitle}\" pada contoh praktik yang nyata.";
    }

    private static function miniImportance(string $materialTitle, string $episodeTitle): string {
        $title = strtolower($materialTitle);

        if (strpos($title, 'html') !== false) {
            return 'Karena HTML menjadi struktur dasar konten web, sehingga setiap episode membantu membangun markup yang rapi dan bermakna.';
        }

        if (strpos($title, 'css') !== false) {
            return 'Karena CSS mengatur presentasi visual, sehingga konsep episode ini membantu membuat tampilan lebih konsisten dan mudah dikendalikan.';
        }

        if (strpos($title, 'javascript') !== false) {
            return 'Karena JavaScript membangun logika dan interaksi, sehingga konsep episode ini menjadi dasar untuk menulis program yang masuk akal.';
        }

        if (strpos($title, 'efektif') !== false) {
            return 'Karena strategi belajar perlu dipraktikkan bertahap agar kebiasaan belajar menjadi lebih sadar, konsisten, dan tahan lama.';
        }

        if (strpos($title, 'canva') !== false) {
            return 'Karena setiap fitur Canva perlu dipakai dengan tujuan desain yang jelas agar hasil visual lebih rapi dan komunikatif.';
        }

        return "Karena episode \"{$episodeTitle}\" menjadi bagian dari pemahaman bertahap dalam materi ini.";
    }

    private static function q(string $text, string $correct, array $distractors): array {
        $options = array_slice(array_merge([$correct], $distractors), 0, 4);
        $offset = count($options) > 0 ? abs(crc32($text)) % count($options) : 0;
        $options = array_merge(array_slice($options, $offset), array_slice($options, 0, $offset));

        return [
            'question_text' => $text,
            'correct_answer' => $correct,
            'options' => $options,
            'points' => 10
        ];
    }

    private static function cleanEpisodeTitle(string $title): string {
        $title = preg_replace('/^\s*\d+\.\s*/', '', $title) ?: $title;
        return self::cleanText($title);
    }

    private static function cleanText(string $value): string {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;
        return trim($value);
    }
}
