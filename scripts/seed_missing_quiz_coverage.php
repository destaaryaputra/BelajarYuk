<?php
require 'vendor/autoload.php';
require 'src/Config/bootstrap_web.php';

use App\Config\Database;

/**
 * Seed missing quiz coverage:
 * 1) Ensure each material has a final quiz
 * 2) Ensure each episode (sub_materi) has a mini quiz
 * 3) Ensure each seeded quiz has default questions
 */

function normalizeTitle(string $title): string
{
    return trim(preg_replace('/^\d+[\.\s\-]+/', '', $title));
}

function ensureQuizColumns(PDO $db): void
{
    $db->exec("
        ALTER TABLE kuis
        ADD COLUMN IF NOT EXISTS sub_material_id INT REFERENCES sub_materi(id) ON DELETE CASCADE
    ");
    $db->exec("
        ALTER TABLE kuis
        ADD COLUMN IF NOT EXISTS quiz_type TEXT CHECK (quiz_type IN ('mini', 'final')) DEFAULT 'final'
    ");
}

function insertQuizQuestion(PDO $db, int $quizId, int $order, string $question, array $options, string $correct): void
{
    $stmt = $db->prepare("
        INSERT INTO pertanyaan (quiz_id, question_text, question_type, options, correct_answer, points, order_number, status)
        VALUES (?, ?, 'multiple_choice', ?, ?, 10, ?, 'active')
    ");
    $stmt->execute([$quizId, $question, json_encode($options, JSON_UNESCAPED_UNICODE), $correct, $order]);
}

function seedQuestionsForQuiz(PDO $db, int $quizId, string $materialTitle, ?string $episodeTitle, string $quizType): int
{
    $countStmt = $db->prepare("SELECT COUNT(*) FROM pertanyaan WHERE quiz_id = ?");
    $countStmt->execute([$quizId]);
    $existing = (int) $countStmt->fetchColumn();
    if ($existing > 0) {
        return $existing;
    }

    $cleanMaterial = normalizeTitle($materialTitle);
    $cleanEpisode = $episodeTitle ? normalizeTitle($episodeTitle) : null;

    if ($quizType === 'mini' && $cleanEpisode) {
        $questions = [
            [
                "Topik utama yang dibahas pada episode \"{$cleanEpisode}\" adalah...",
                [
                    $cleanEpisode,
                    "Ringkasan akhir seluruh modul",
                    "Evaluasi performa sistem",
                    "Topik di luar pembelajaran"
                ],
                $cleanEpisode
            ],
            [
                "Langkah terbaik setelah mempelajari \"{$cleanEpisode}\" adalah...",
                [
                    "Mencoba praktik langsung dari materi tersebut",
                    "Langsung melewati semua latihan",
                    "Mengabaikan rangkuman dan catatan",
                    "Berhenti belajar total"
                ],
                "Mencoba praktik langsung dari materi tersebut"
            ],
            [
                "Tujuan mini kuis setelah episode adalah...",
                [
                    "Mengecek pemahaman sebelum lanjut ke episode berikutnya",
                    "Mengganti seluruh materi teori",
                    "Menentukan nilai akhir modul",
                    "Menghapus progres belajar"
                ],
                "Mengecek pemahaman sebelum lanjut ke episode berikutnya"
            ]
        ];
    } else {
        $questions = [
            [
                "Kuis final pada modul \"{$cleanMaterial}\" digunakan untuk...",
                [
                    "Mengukur pemahaman keseluruhan modul",
                    "Mengganti semua episode pembelajaran",
                    "Menonaktifkan progres user",
                    "Menghapus riwayat kuis"
                ],
                "Mengukur pemahaman keseluruhan modul"
            ],
            [
                "Strategi terbaik saat mengerjakan kuis final adalah...",
                [
                    "Membaca soal dengan teliti dan pilih jawaban paling tepat",
                    "Menjawab acak tanpa membaca",
                    "Melewati semua soal yang mudah",
                    "Menutup kuis sebelum selesai"
                ],
                "Membaca soal dengan teliti dan pilih jawaban paling tepat"
            ],
            [
                "Jika hasil kuis final belum maksimal, langkah yang disarankan adalah...",
                [
                    "Ulangi materi yang belum dikuasai lalu coba lagi",
                    "Hapus akun dan mulai dari nol",
                    "Lewati semua materi berikutnya",
                    "Jangan pernah latihan lagi"
                ],
                "Ulangi materi yang belum dikuasai lalu coba lagi"
            ],
            [
                "Poin kuis pada modul \"{$cleanMaterial}\" menggambarkan...",
                [
                    "Akumulasi jawaban benar sesuai bobot soal",
                    "Jumlah halaman materi",
                    "Jumlah tombol yang diklik",
                    "Lama waktu login"
                ],
                "Akumulasi jawaban benar sesuai bobot soal"
            ],
            [
                "Agar progres belajar konsisten setelah kuis final, sebaiknya...",
                [
                    "Review hasil dan lanjutkan materi berikutnya",
                    "Mengabaikan hasil kuis sepenuhnya",
                    "Mengulang soal yang sama tanpa evaluasi",
                    "Menonaktifkan semua notifikasi belajar"
                ],
                "Review hasil dan lanjutkan materi berikutnya"
            ]
        ];
    }

    $order = 1;
    foreach ($questions as $q) {
        insertQuizQuestion($db, $quizId, $order, $q[0], $q[1], $q[2]);
        $order++;
    }

    $total = count($questions);
    $update = $db->prepare("UPDATE kuis SET total_questions = ? WHERE id = ?");
    $update->execute([$total, $quizId]);

    return $total;
}

try {
    $db = Database::getInstance();
    ensureQuizColumns($db);

    echo "--- QUIZ COVERAGE SEED START ---" . PHP_EOL;

    $materials = $db->query("SELECT id, title FROM materi WHERE status = 'active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $subs = $db->query("
        SELECT sm.id, sm.material_id, sm.title
        FROM sub_materi sm
        JOIN materi m ON m.id = sm.material_id
        WHERE m.status = 'active'
        ORDER BY sm.material_id, sm.order_number
    ")->fetchAll(PDO::FETCH_ASSOC);

    $createdFinal = 0;
    $createdMini = 0;
    $seededQuestionSets = 0;
    $totalQuestionsInserted = 0;

    // 1) Final quiz per material
    foreach ($materials as $m) {
        $materialId = (int) $m['id'];
        $materialTitle = (string) $m['title'];

        $findFinal = $db->prepare("SELECT id FROM kuis WHERE material_id = ? AND quiz_type = 'final' LIMIT 1");
        $findFinal->execute([$materialId]);
        $quizId = $findFinal->fetchColumn();

        if (!$quizId) {
            $insert = $db->prepare("
                INSERT INTO kuis (material_id, sub_material_id, quiz_type, title, description, passing_score, total_questions, time_limit_minutes, status, created_at, updated_at)
                VALUES (?, NULL, 'final', ?, ?, 70, 0, 20, 'active', NOW(), NOW())
                RETURNING id
            ");
            $insert->execute([
                $materialId,
                "Kuis Final: " . normalizeTitle($materialTitle),
                "Evaluasi akhir untuk modul " . normalizeTitle($materialTitle) . "."
            ]);
            $quizId = (int) $insert->fetchColumn();
            $createdFinal++;
            echo "Created final quiz for material #{$materialId}" . PHP_EOL;
        }

        $questionCount = seedQuestionsForQuiz($db, (int) $quizId, $materialTitle, null, 'final');
        if ($questionCount > 0) {
            $seededQuestionSets++;
            $totalQuestionsInserted += $questionCount;
        }
    }

    // 2) Mini quiz per episode
    foreach ($subs as $s) {
        $subId = (int) $s['id'];
        $materialId = (int) $s['material_id'];
        $episodeTitle = (string) $s['title'];

        $matStmt = $db->prepare("SELECT title FROM materi WHERE id = ?");
        $matStmt->execute([$materialId]);
        $materialTitle = (string) ($matStmt->fetchColumn() ?: "Materi {$materialId}");

        $findMini = $db->prepare("SELECT id FROM kuis WHERE sub_material_id = ? AND quiz_type = 'mini' LIMIT 1");
        $findMini->execute([$subId]);
        $quizId = $findMini->fetchColumn();

        if (!$quizId) {
            $insert = $db->prepare("
                INSERT INTO kuis (material_id, sub_material_id, quiz_type, title, description, passing_score, total_questions, time_limit_minutes, status, created_at, updated_at)
                VALUES (?, ?, 'mini', ?, ?, 60, 0, 8, 'active', NOW(), NOW())
                RETURNING id
            ");
            $insert->execute([
                $materialId,
                $subId,
                "Mini Kuis: " . normalizeTitle($episodeTitle),
                "Cek pemahaman singkat untuk episode " . normalizeTitle($episodeTitle) . "."
            ]);
            $quizId = (int) $insert->fetchColumn();
            $createdMini++;
            echo "Created mini quiz for sub-material #{$subId}" . PHP_EOL;
        }

        $questionCount = seedQuestionsForQuiz($db, (int) $quizId, $materialTitle, $episodeTitle, 'mini');
        if ($questionCount > 0) {
            $seededQuestionSets++;
            $totalQuestionsInserted += $questionCount;
        }
    }

    $summary = $db->query("
        SELECT quiz_type, COUNT(*) AS total
        FROM kuis
        GROUP BY quiz_type
        ORDER BY quiz_type
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo PHP_EOL . "--- SUMMARY ---" . PHP_EOL;
    echo "Created final quizzes: {$createdFinal}" . PHP_EOL;
    echo "Created mini quizzes: {$createdMini}" . PHP_EOL;
    echo "Quiz question sets ensured: {$seededQuestionSets}" . PHP_EOL;
    echo "Total questions ensured/inserted: {$totalQuestionsInserted}" . PHP_EOL;
    foreach ($summary as $row) {
        echo $row['quiz_type'] . ": " . $row['total'] . PHP_EOL;
    }

    echo "--- QUIZ COVERAGE SEED DONE ---" . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
