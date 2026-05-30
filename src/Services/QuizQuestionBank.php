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

        $profile = self::miniConceptProfile($materialTitle, $episodeTitle, $episodeContent);
        $templates = self::miniQuestionTemplates($episodeTitle, $profile['concept']);

        return [
            self::q(
                $templates[0],
                $episodeContent,
                [
                    'Membahas topik lain yang tidak menjadi fokus video ini.',
                    'Hanya mengulang judul video tanpa memahami isi materinya.',
                    'Melewati konsep utama dan langsung menebak hasil akhirnya.'
                ]
            ),
            self::q(
                $templates[1],
                $profile['definition'],
                [
                    $profile['definition_distractors'][0],
                    $profile['definition_distractors'][1],
                    $profile['definition_distractors'][2]
                ]
            ),
            self::q(
                $templates[2],
                $profile['application'],
                [
                    $profile['application_distractors'][0],
                    $profile['application_distractors'][1],
                    $profile['application_distractors'][2]
                ]
            )
        ];
    }

    private static function miniQuestionTemplates(string $episodeTitle, string $concept): array {
        $sets = [
            [
                "Video \"{$episodeTitle}\" terutama membahas tentang apa?",
                "Apa yang dimaksud dengan {$concept} dalam episode ini?",
                "Dalam praktik, kapan konsep {$concept} paling tepat digunakan?"
            ],
            [
                "Pokok materi yang dijelaskan pada episode \"{$episodeTitle}\" adalah...",
                "Pernyataan yang paling tepat tentang {$concept} adalah...",
                "Jika kamu mengikuti materi video ini, penerapan {$concept} yang benar adalah..."
            ],
            [
                "Setelah menonton video \"{$episodeTitle}\", hal utama yang perlu dipahami adalah...",
                "{$concept} sebaiknya dipahami sebagai...",
                "Contoh penggunaan {$concept} yang sesuai dengan materi adalah..."
            ]
        ];

        return $sets[abs(crc32($episodeTitle)) % count($sets)];
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

    private static function miniConceptProfile(string $materialTitle, string $episodeTitle, string $episodeContent): array {
        $title = strtolower($materialTitle);
        $episode = strtolower($episodeTitle);

        if (strpos($title, 'html') !== false) {
            return self::findMiniProfile($episode, self::htmlMiniProfiles(), self::defaultMiniProfile($episodeTitle, $episodeContent, 'HTML'));
        }

        if (strpos($title, 'css') !== false) {
            return self::findMiniProfile($episode, self::cssMiniProfiles(), self::defaultMiniProfile($episodeTitle, $episodeContent, 'CSS'));
        }

        if (strpos($title, 'javascript') !== false) {
            return self::findMiniProfile($episode, self::javascriptMiniProfiles(), self::defaultMiniProfile($episodeTitle, $episodeContent, 'JavaScript'));
        }

        if (strpos($title, 'efektif') !== false) {
            return self::findMiniProfile($episode, self::learningMiniProfiles(), self::defaultMiniProfile($episodeTitle, $episodeContent, 'strategi belajar'));
        }

        if (strpos($title, 'canva') !== false) {
            return self::findMiniProfile($episode, self::canvaMiniProfiles(), self::defaultMiniProfile($episodeTitle, $episodeContent, 'Canva'));
        }

        return self::defaultMiniProfile($episodeTitle, $episodeContent, 'materi ini');
    }

    private static function findMiniProfile(string $episodeTitle, array $profiles, array $default): array {
        foreach ($profiles as $profile) {
            foreach ($profile['keys'] as $key) {
                if (strpos($episodeTitle, $key) !== false) {
                    return self::normalizeMiniProfile($profile);
                }
            }
        }

        return $default;
    }

    private static function normalizeMiniProfile(array $profile): array {
        $profile['definition_distractors'] = $profile['definition_distractors'] ?? [
            'Istilah yang hanya dipakai sebagai hiasan dan tidak memengaruhi hasil belajar.',
            'Fitur yang otomatis bekerja tanpa perlu dipahami cara penggunaannya.',
            'Bagian yang bisa dilewati karena tidak berhubungan dengan materi video.'
        ];

        $profile['application_distractors'] = $profile['application_distractors'] ?? [
            'Menyalin contoh tanpa mencoba memahami fungsi langkah yang dilakukan.',
            'Mengabaikan konsep dasar dan langsung memilih jawaban yang terlihat paling rumit.',
            'Memakai fitur secara acak tanpa menyesuaikan tujuan materi.'
        ];

        return $profile;
    }

    private static function defaultMiniProfile(string $episodeTitle, string $episodeContent, string $domain): array {
        return self::normalizeMiniProfile([
            'keys' => [],
            'concept' => $episodeTitle,
            'definition' => $episodeContent,
            'application' => "Menerapkan konsep {$episodeTitle} pada latihan {$domain} dan mengecek apakah hasilnya sesuai tujuan materi."
        ]);
    }

    private static function htmlMiniProfiles(): array {
        return [
            ['keys' => ['pendahuluan'], 'concept' => 'HTML', 'definition' => 'HTML adalah bahasa markup untuk menyusun struktur konten halaman web.', 'application' => 'Memakai HTML untuk membuat kerangka halaman sebelum menambahkan tampilan CSS atau interaksi JavaScript.'],
            ['keys' => ['hello world'], 'concept' => 'struktur minimal HTML', 'definition' => 'Struktur minimal HTML adalah susunan dasar dokumen agar browser bisa membaca dan menampilkan halaman.', 'application' => 'Membuat file HTML sederhana lalu membukanya di browser untuk memastikan struktur dokumen sudah benar.'],
            ['keys' => ['code editor'], 'concept' => 'code editor', 'definition' => 'Code editor adalah aplikasi untuk menulis, menyimpan, dan merapikan kode HTML.', 'application' => 'Menggunakan code editor saat membuat file HTML agar penulisan tag dan struktur dokumen lebih mudah dikontrol.'],
            ['keys' => ['tag html', 'tag'], 'concept' => 'tag HTML', 'definition' => 'Tag HTML adalah penanda yang memberi tahu browser jenis dan fungsi suatu bagian konten.', 'application' => 'Memilih tag yang sesuai, misalnya heading untuk judul, paragraf untuk teks, dan anchor untuk tautan.'],
            ['keys' => ['paragraf'], 'concept' => 'paragraf HTML', 'definition' => 'Paragraf HTML digunakan untuk menulis blok teks utama agar konten lebih terstruktur.', 'application' => 'Menaruh isi bacaan dalam tag paragraf dan memakai pemisah baris hanya saat benar-benar diperlukan.'],
            ['keys' => ['heading'], 'concept' => 'heading HTML', 'definition' => 'Heading HTML adalah judul bertingkat dari H1 sampai H6 untuk membentuk hierarki konten.', 'application' => 'Memakai H1 untuk judul utama dan heading berikutnya untuk subbagian agar halaman mudah dipahami.'],
            ['keys' => ['list'], 'concept' => 'list HTML', 'definition' => 'List HTML adalah elemen untuk menyusun item dalam daftar berurutan atau tidak berurutan.', 'application' => 'Memakai ordered list untuk langkah berurutan dan unordered list untuk kumpulan poin tanpa urutan khusus.'],
            ['keys' => ['hyperlink'], 'concept' => 'hyperlink', 'definition' => 'Hyperlink adalah tautan yang menghubungkan halaman ke alamat atau bagian lain melalui atribut href.', 'application' => 'Memakai anchor saat ingin membawa pengguna ke halaman, file, email, atau bagian tertentu.'],
            ['keys' => ['image'], 'concept' => 'elemen gambar HTML', 'definition' => 'Elemen gambar HTML menampilkan gambar dengan sumber file dan teks alternatif.', 'application' => 'Mengisi src dengan lokasi gambar dan alt dengan deskripsi agar gambar tetap informatif saat tidak tampil.'],
            ['keys' => ['table merging'], 'concept' => 'colspan dan rowspan', 'definition' => 'Colspan dan rowspan adalah atribut untuk menggabungkan sel tabel secara horizontal atau vertikal.', 'application' => 'Menggunakan colspan saat satu sel perlu melebar beberapa kolom dan rowspan saat melebar beberapa baris.'],
            ['keys' => ['table'], 'concept' => 'tabel HTML', 'definition' => 'Tabel HTML menyusun data dalam baris dan kolom agar mudah dibandingkan.', 'application' => 'Memakai table, tr, th, dan td untuk menampilkan data terstruktur, bukan untuk mengatur layout halaman.'],
            ['keys' => ['form lanjutan'], 'concept' => 'input form lanjutan', 'definition' => 'Input form lanjutan adalah variasi isian yang membantu pengguna memasukkan data sesuai kebutuhan.', 'application' => 'Memilih jenis input yang tepat, seperti select, textarea, checkbox, atau radio sesuai bentuk data yang diminta.'],
            ['keys' => ['form'], 'concept' => 'form HTML', 'definition' => 'Form HTML adalah kumpulan input yang dipakai untuk menerima data dari pengguna.', 'application' => 'Memakai label, input, textarea, select, dan button agar data pengguna bisa diisi dengan jelas.']
        ];
    }

    private static function cssMiniProfiles(): array {
        return [
            ['keys' => ['pendahuluan'], 'concept' => 'CSS', 'definition' => 'CSS adalah bahasa stylesheet untuk mengatur tampilan halaman web.', 'application' => 'Memakai CSS untuk mengubah warna, ukuran, jarak, layout, dan gaya visual tanpa mengubah struktur HTML.'],
            ['keys' => ['anatomi'], 'concept' => 'anatomi CSS', 'definition' => 'Anatomi CSS terdiri dari selector, property, value, dan deklarasi.', 'application' => 'Menulis aturan seperti selector lalu memberi property dan value agar elemen tertentu berubah tampilannya.'],
            ['keys' => ['penempatan'], 'concept' => 'penempatan CSS', 'definition' => 'Penempatan CSS adalah cara menaruh style secara inline, internal, atau external.', 'application' => 'Memakai external CSS saat banyak halaman perlu style yang konsisten dan mudah dipelihara.'],
            ['keys' => ['font styling'], 'concept' => 'font styling', 'definition' => 'Font styling mengatur bentuk huruf seperti jenis font, ukuran, tebal, dan gaya.', 'application' => 'Mengatur font-family, font-size, font-weight, atau font-style agar teks sesuai kebutuhan desain.'],
            ['keys' => ['text styling'], 'concept' => 'text styling', 'definition' => 'Text styling mengatur tampilan teks seperti warna, perataan, dekorasi, indentasi, dan spasi.', 'application' => 'Memakai color, text-align, text-decoration, text-transform, atau letter-spacing sesuai tujuan bacaan.'],
            ['keys' => ['background'], 'concept' => 'background CSS', 'definition' => 'Background CSS mengatur warna atau gambar latar belakang elemen.', 'application' => 'Mengatur background-color, background-image, repeat, position, dan attachment agar latar terlihat sesuai desain.'],
            ['keys' => ['selector'], 'concept' => 'selector CSS', 'definition' => 'Selector CSS menentukan elemen HTML mana yang akan diberi style.', 'application' => 'Memilih selector tag, class, id, atau kombinasi selector sesuai target elemen yang ingin diubah.'],
            ['keys' => ['pseudo class'], 'concept' => 'pseudo-class', 'definition' => 'Pseudo-class memberi style pada elemen berdasarkan kondisi tertentu seperti hover atau active.', 'application' => 'Memakai :hover saat ingin tombol atau link berubah tampilan ketika disentuh kursor.'],
            ['keys' => ['inheritance'], 'concept' => 'inheritance', 'definition' => 'Inheritance adalah pewarisan sebagian property CSS dari elemen induk ke elemen anak.', 'application' => 'Mengatur property teks di parent agar elemen anak mengikuti style dasar yang sama.'],
            ['keys' => ['specificity'], 'concept' => 'specificity', 'definition' => 'Specificity adalah bobot selector yang menentukan aturan CSS mana yang menang saat terjadi konflik.', 'application' => 'Mengecek bobot selector ketika style tidak muncul karena kalah oleh aturan lain yang lebih spesifik.']
        ];
    }

    private static function javascriptMiniProfiles(): array {
        return [
            ['keys' => ['intro'], 'concept' => 'dasar pemrograman dengan JavaScript', 'definition' => 'Dasar JavaScript adalah fondasi untuk memahami logika, data, dan instruksi dalam program web.', 'application' => 'Memulai dari konsep pemrograman sebelum membuat interaksi web yang lebih kompleks.'],
            ['keys' => ['apa itu pemrograman'], 'concept' => 'pemrograman', 'definition' => 'Pemrograman adalah proses memberi instruksi terstruktur kepada komputer untuk menyelesaikan tugas.', 'application' => 'Menyusun langkah logis agar komputer dapat menjalankan perintah sesuai tujuan.'],
            ['keys' => ['bahasa pemrograman'], 'concept' => 'bahasa pemrograman', 'definition' => 'Bahasa pemrograman adalah alat komunikasi antara manusia dan komputer melalui aturan sintaks tertentu.', 'application' => 'Memakai bahasa seperti JavaScript untuk menulis instruksi yang bisa dijalankan mesin.'],
            ['keys' => ['compiler', 'interpreter'], 'concept' => 'compiler dan interpreter', 'definition' => 'Compiler menerjemahkan program sebelum dijalankan, sedangkan interpreter menjalankannya secara bertahap.', 'application' => 'Membedakan cara kode diproses agar paham mengapa JavaScript sering dijalankan langsung oleh browser.'],
            ['keys' => ['kenapa belajar javascript'], 'concept' => 'peran JavaScript', 'definition' => 'JavaScript membuat halaman web dapat memiliki logika, respons, dan interaksi.', 'application' => 'Memakai JavaScript saat halaman perlu merespons klik, input, validasi, atau perubahan tampilan.'],
            ['keys' => ['sejarah'], 'concept' => 'sejarah JavaScript', 'definition' => 'Sejarah JavaScript menjelaskan bagaimana bahasa ini berkembang menjadi teknologi penting di web modern.', 'application' => 'Memahami konteks perkembangan JavaScript agar tidak bingung dengan ekosistem dan standar yang berubah.'],
            ['keys' => ['lingkungan'], 'concept' => 'lingkungan pengembangan JavaScript', 'definition' => 'Lingkungan pengembangan JavaScript mencakup browser, console, editor, dan file untuk latihan kode.', 'application' => 'Menulis kode di editor lalu mengujinya melalui browser console atau file JavaScript.'],
            ['keys' => ['nilai dan tipe data'], 'concept' => 'nilai dan tipe data', 'definition' => 'Nilai adalah data yang diproses program, sedangkan tipe data menjelaskan jenis nilai tersebut.', 'application' => 'Mengenali apakah data berupa angka, string, boolean, atau tipe lain sebelum melakukan operasi.'],
            ['keys' => ['angka'], 'concept' => 'tipe data angka', 'definition' => 'Tipe data angka digunakan untuk menyimpan dan menghitung nilai numerik.', 'application' => 'Menggunakan number saat program perlu melakukan operasi matematika atau perhitungan.'],
            ['keys' => ['aritmatika', 'penugasan', 'perbandingan'], 'concept' => 'operator JavaScript', 'definition' => 'Operator JavaScript digunakan untuk menghitung, menyimpan, dan membandingkan nilai.', 'application' => 'Memakai operator aritmatika untuk hitung, penugasan untuk menyimpan, dan perbandingan untuk mengecek kondisi.'],
            ['keys' => ['logika', 'kondisional'], 'concept' => 'operator logika dan kondisional', 'definition' => 'Operator logika dan kondisional membantu program membuat keputusan berdasarkan kondisi.', 'application' => 'Memakai AND, OR, dan struktur kondisi ketika hasil program bergantung pada beberapa syarat.'],
            ['keys' => ['string'], 'concept' => 'string', 'definition' => 'String adalah tipe data untuk menyimpan teks atau kumpulan karakter.', 'application' => 'Memakai string untuk nama, pesan, label, atau teks yang perlu digabung dan ditampilkan.'],
            ['keys' => ['boolean'], 'concept' => 'boolean', 'definition' => 'Boolean adalah tipe data yang hanya bernilai true atau false.', 'application' => 'Memakai boolean untuk menyatakan status seperti sudah login, valid, aktif, atau selesai.'],
            ['keys' => ['variable', 'variabel'], 'concept' => 'variabel', 'definition' => 'Variabel adalah tempat menyimpan nilai agar dapat digunakan kembali dalam program.', 'application' => 'Menyimpan data seperti nama pengguna, skor, atau hasil perhitungan ke dalam variabel.'],
            ['keys' => ['menulis javascript'], 'concept' => 'penulisan JavaScript', 'definition' => 'Penulisan JavaScript mengikuti sintaks agar instruksi dapat dibaca dan dijalankan dengan benar.', 'application' => 'Menulis kode secara rapi, menguji hasilnya, dan membaca error jika instruksi belum benar.']
        ];
    }

    private static function learningMiniProfiles(): array {
        return [
            ['keys' => ['pomodoro'], 'concept' => 'teknik Pomodoro', 'definition' => 'Teknik Pomodoro membagi belajar menjadi sesi fokus dan jeda pendek.', 'application' => 'Belajar dalam blok waktu terukur lalu mengambil jeda agar fokus tidak cepat habis.'],
            ['keys' => ['susah'], 'concept' => 'strategi menghadapi materi sulit', 'definition' => 'Strategi menghadapi materi sulit adalah memecah topik dan melatihnya bertahap.', 'application' => 'Mengubah pelajaran sulit menjadi bagian kecil yang bisa dipahami satu per satu.'],
            ['keys' => ['neuron'], 'concept' => 'neuron dalam belajar', 'definition' => 'Neuron membentuk koneksi saat otak memproses dan mengulang informasi baru.', 'application' => 'Melatih materi berulang agar koneksi pemahaman makin kuat.'],
            ['keys' => ['tidur'], 'concept' => 'tidur untuk belajar', 'definition' => 'Tidur membantu pemulihan fokus dan penguatan memori setelah belajar.', 'application' => 'Mengatur waktu tidur sebelum ujian atau sesi belajar penting agar otak bekerja lebih baik.'],
            ['keys' => ['metafora'], 'concept' => 'metafora belajar', 'definition' => 'Metafora menghubungkan konsep sulit dengan gambaran yang lebih familiar.', 'application' => 'Membuat analogi saat konsep terasa abstrak agar lebih mudah dipahami.'],
            ['keys' => ['flash card'], 'concept' => 'flash card', 'definition' => 'Flash card adalah kartu tanya-jawab untuk melatih ingatan aktif.', 'application' => 'Memakai satu sisi untuk pertanyaan dan sisi lain untuk jawaban lalu mengulangnya berkala.'],
            ['keys' => ['mengingat'], 'concept' => 'latihan mengingat', 'definition' => 'Latihan mengingat adalah usaha mengambil kembali informasi tanpa langsung melihat catatan.', 'application' => 'Menutup materi lalu mencoba menjelaskan ulang poin penting dari ingatan sendiri.'],
            ['keys' => ['menghafal'], 'concept' => 'teknik menghafal efektif', 'definition' => 'Teknik menghafal efektif menghubungkan informasi dengan asosiasi, pola, atau jembatan ingatan.', 'application' => 'Membuat asosiasi atau singkatan agar fakta penting lebih mudah diingat.'],
            ['keys' => ['auto-pintar', 'memori'], 'concept' => 'trik memori', 'definition' => 'Trik memori adalah cara praktis untuk membuat informasi lebih mudah disimpan dan dipanggil kembali.', 'application' => 'Menghubungkan informasi baru dengan gambar, cerita, tempat, atau pola tertentu.'],
            ['keys' => ['rantai otak'], 'concept' => 'rantai otak', 'definition' => 'Rantai otak adalah menghubungkan beberapa ide agar pemahaman menjadi utuh.', 'application' => 'Menyusun konsep dari yang paling dasar sampai lanjutan agar tidak belajar secara terpisah-pisah.'],
            ['keys' => ['terlalu banyak informasi'], 'concept' => 'pengelolaan informasi', 'definition' => 'Pengelolaan informasi adalah memilih, mengelompokkan, dan memproses materi agar tidak kewalahan.', 'application' => 'Membuat prioritas dan rangkuman saat sumber belajar terlalu banyak.'],
            ['keys' => ['makanan'], 'concept' => 'kebiasaan pendukung otak', 'definition' => 'Kebiasaan pendukung otak mencakup asupan, istirahat, dan pola hidup yang membantu fokus.', 'application' => 'Menjaga makan, minum, tidur, dan jeda agar belajar tidak hanya bergantung pada motivasi.'],
            ['keys' => ['kekurangan'], 'concept' => 'mengubah hambatan belajar', 'definition' => 'Mengubah hambatan belajar berarti mencari strategi dari kelemahan yang dimiliki.', 'application' => 'Mengenali kekurangan lalu membuat cara belajar yang sesuai dengan kondisi diri.'],
            ['keys' => ['ujian'], 'concept' => 'strategi menghadapi ujian', 'definition' => 'Strategi menghadapi ujian mencakup latihan soal, review bertahap, dan pengelolaan tekanan.', 'application' => 'Mempersiapkan ujian jauh hari dengan latihan, tidur cukup, dan evaluasi kesalahan.'],
            ['keys' => ['praktik belajar'], 'concept' => 'praktik belajar cara belajar', 'definition' => 'Praktik belajar cara belajar adalah menerapkan teknik yang sudah dipelajari pada rutinitas nyata.', 'application' => 'Memilih beberapa teknik lalu menguji mana yang paling membantu proses belajar sendiri.']
        ];
    }

    private static function canvaMiniProfiles(): array {
        return [
            ['keys' => ['mengenal canva', 'area kerja'], 'concept' => 'area kerja Canva', 'definition' => 'Area kerja Canva adalah ruang untuk membuat, mengatur, menyimpan, dan mengedit desain.', 'application' => 'Memahami posisi kanvas, panel elemen, toolbar, halaman, dan menu agar proses desain lebih terarah.'],
            ['keys' => ['jenis akun'], 'concept' => 'jenis akun Canva', 'definition' => 'Jenis akun Canva menentukan fitur, aset, dan kemampuan kolaborasi yang tersedia.', 'application' => 'Membedakan fitur akun gratis dan berbayar sebelum memilih aset atau template desain.'],
            ['keys' => ['element', 'elemen'], 'concept' => 'elemen Canva', 'definition' => 'Elemen Canva adalah objek visual seperti ikon, shape, garis, foto, ilustrasi, dan dekorasi.', 'application' => 'Mencari dan mengatur elemen agar desain punya visual pendukung yang sesuai pesan.'],
            ['keys' => ['nama dan ukuran', 'ukuran dokumen'], 'concept' => 'ukuran dokumen Canva', 'definition' => 'Ukuran dokumen Canva adalah dimensi desain yang disesuaikan dengan media tujuan.', 'application' => 'Memilih ukuran poster, presentasi, feed, atau dokumen sebelum mulai menyusun desain.'],
            ['keys' => ['download'], 'concept' => 'download file Canva', 'definition' => 'Download file Canva adalah proses mengekspor desain ke format seperti JPG, PNG, PDF, atau video.', 'application' => 'Memilih format ekspor sesuai kebutuhan, misalnya PNG untuk gambar dan PDF untuk dokumen.'],
            ['keys' => ['shape'], 'concept' => 'shape Canva', 'definition' => 'Shape Canva adalah bentuk dasar yang dapat dipakai sebagai elemen layout, penekanan, atau dekorasi.', 'application' => 'Menggunakan kotak, lingkaran, atau bentuk lain untuk membangun komposisi dan highlight desain.'],
            ['keys' => ['teks di canva', 'mengedit teks'], 'concept' => 'pengeditan teks Canva', 'definition' => 'Pengeditan teks Canva mengubah isi, font, ukuran, warna, posisi, dan gaya teks.', 'application' => 'Mengatur teks agar judul, subjudul, dan isi mudah dibaca serta sesuai tema desain.'],
            ['keys' => ['efek teks'], 'concept' => 'efek teks Canva', 'definition' => 'Efek teks Canva adalah gaya visual tambahan pada tulisan seperti bayangan, outline, atau bentuk efek lain.', 'application' => 'Memakai efek teks untuk menonjolkan judul tanpa mengurangi keterbacaan.'],
            ['keys' => ['animasi teks'], 'concept' => 'animasi teks Canva', 'definition' => 'Animasi teks Canva membuat tulisan bergerak untuk presentasi, video, atau konten dinamis.', 'application' => 'Memilih animasi yang mendukung pesan dan tidak mengganggu pembaca.'],
            ['keys' => ['bagan', 'chart', 'diagram'], 'concept' => 'chart dan diagram Canva', 'definition' => 'Chart dan diagram Canva membantu menyajikan data atau hubungan informasi secara visual.', 'application' => 'Mengubah data menjadi grafik agar perbandingan dan pola lebih mudah dipahami.'],
            ['keys' => ['frame', 'bingkai'], 'concept' => 'frame Canva', 'definition' => 'Frame Canva adalah wadah berbentuk tertentu untuk memasukkan dan memangkas foto secara rapi.', 'application' => 'Menaruh gambar ke dalam frame agar komposisi foto mengikuti bentuk yang diinginkan.'],
            ['keys' => ['gambar dan grafis'], 'concept' => 'pengeditan gambar Canva', 'definition' => 'Pengeditan gambar Canva mengatur foto atau grafis agar cocok dengan komposisi desain.', 'application' => 'Menyesuaikan posisi, ukuran, crop, filter, atau elemen grafis agar desain terlihat menyatu.'],
            ['keys' => ['powerclip', 'masking'], 'concept' => 'masking teks', 'definition' => 'Masking teks adalah efek memasukkan gambar atau tekstur ke dalam bentuk huruf.', 'application' => 'Memakai masking pada judul kreatif ketika ingin teks terlihat lebih visual namun tetap terbaca.'],
            ['keys' => ['background'], 'concept' => 'hapus background', 'definition' => 'Hapus background adalah teknik menghilangkan latar foto agar objek utama lebih mudah dipakai dalam desain.', 'application' => 'Menghapus latar saat objek foto perlu ditempatkan di layout baru tanpa gangguan background lama.']
        ];
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
