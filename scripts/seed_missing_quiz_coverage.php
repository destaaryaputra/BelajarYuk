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

function normalizeText(?string $text): string
{
    $plain = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = preg_replace('/\s+/u', ' ', $plain);
    return trim((string) $plain);
}

function buildSnippet(?string $content, int $maxLen = 90): string
{
    $text = normalizeText($content);
    if ($text === '') {
        return 'Penjelasan inti pada video episode ini.';
    }
    if (mb_strlen($text) <= $maxLen) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $maxLen - 1)) . '…';
}

function uniqueOptions(string $correct, array $candidates, int $target = 4): array
{
    $options = [$correct];
    foreach ($candidates as $c) {
        $c = trim((string) $c);
        if ($c === '' || in_array($c, $options, true)) {
            continue;
        }
        $options[] = $c;
        if (count($options) >= $target) break;
    }

    $fallback = [
        'Ringkasan umum di luar topik episode',
        'Pembahasan yang tidak muncul pada video ini',
        'Topik dari modul lain yang berbeda',
        'Materi pengantar yang tidak dibahas di sini'
    ];
    foreach ($fallback as $f) {
        if (count($options) >= $target) break;
        if (!in_array($f, $options, true)) $options[] = $f;
    }

    shuffle($options);
    return array_slice($options, 0, $target);
}

function buildOrderOptions(int $order, int $total): array
{
    $pool = [$order];
    for ($i = 1; $i <= 3; $i++) {
        $up = $order + $i;
        $down = $order - $i;
        if ($up <= $total) $pool[] = $up;
        if ($down >= 1) $pool[] = $down;
    }
    $pool = array_values(array_unique($pool));

    while (count($pool) < 4) {
        $pool[] = min($total, max(1, count($pool) + 1));
        $pool = array_values(array_unique($pool));
    }

    $opts = array_slice($pool, 0, 4);
    shuffle($opts);
    return $opts;
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

function seedQuestionsForQuiz(
    PDO $db,
    int $quizId,
    string $materialTitle,
    ?string $episodeTitle,
    string $quizType,
    array $context = [],
    bool $forceReseed = false
): int
{
    $countStmt = $db->prepare("SELECT COUNT(*) FROM pertanyaan WHERE quiz_id = ?");
    $countStmt->execute([$quizId]);
    $existing = (int) $countStmt->fetchColumn();
    if ($existing > 0 && !$forceReseed) {
        return $existing;
    }
    if ($existing > 0 && $forceReseed) {
        $del = $db->prepare("DELETE FROM pertanyaan WHERE quiz_id = ?");
        $del->execute([$quizId]);
    }

    $cleanMaterial = normalizeTitle($materialTitle);
    $cleanEpisode = $episodeTitle ? normalizeTitle($episodeTitle) : null;

    if ($quizType === 'mini' && $cleanEpisode) {
        $episodeSnippet = (string) ($context['episode_snippet'] ?? 'Penjelasan inti pada video episode ini.');
        $titleDistractors = (array) ($context['title_distractors'] ?? []);
        $snippetDistractors = (array) ($context['snippet_distractors'] ?? []);
        $episodeOrder = (int) ($context['episode_order'] ?? 1);
        $totalEpisodes = max(1, (int) ($context['total_episodes'] ?? 1));

        $q1Options = uniqueOptions($cleanEpisode, $titleDistractors);
        $q2Options = uniqueOptions($episodeSnippet, $snippetDistractors);
        $q3OptionsNum = buildOrderOptions($episodeOrder, $totalEpisodes);
        $q3Options = array_map(static fn($n) => "Episode ke-{$n}", $q3OptionsNum);
        $q3Correct = "Episode ke-{$episodeOrder}";

        $questions = [
            [
                "Pada video episode ini, topik yang dibahas adalah...",
                $q1Options,
                $cleanEpisode
            ],
            [
                "Cuplikan isi yang paling sesuai dengan episode \"{$cleanEpisode}\" adalah...",
                $q2Options,
                $episodeSnippet
            ],
            [
                "Dalam urutan modul, episode \"{$cleanEpisode}\" berada di...",
                $q3Options,
                $q3Correct
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
        SELECT sm.id, sm.material_id, sm.title, sm.content, sm.order_number
        FROM sub_materi sm
        JOIN materi m ON m.id = sm.material_id
        WHERE m.status = 'active'
        ORDER BY sm.material_id, sm.order_number
    ")->fetchAll(PDO::FETCH_ASSOC);

    $episodeMap = [];
    foreach ($subs as $s) {
        $mId = (int) $s['material_id'];
        if (!isset($episodeMap[$mId])) $episodeMap[$mId] = [];
        $episodeMap[$mId][] = [
            'id' => (int) $s['id'],
            'title' => normalizeTitle((string) $s['title']),
            'snippet' => buildSnippet($s['content'] ?? null),
            'order' => (int) ($s['order_number'] ?? 1)
        ];
    }

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

        $episodeList = $episodeMap[$materialId] ?? [];
        $thisEpisode = null;
        foreach ($episodeList as $ep) {
            if ((int) $ep['id'] === $subId) {
                $thisEpisode = $ep;
                break;
            }
        }

        $titleDistractors = [];
        $snippetDistractors = [];
        foreach ($episodeList as $ep) {
            if ((int) $ep['id'] === $subId) continue;
            $titleDistractors[] = $ep['title'];
            $snippetDistractors[] = $ep['snippet'];
        }

        $context = [
            'episode_snippet' => $thisEpisode['snippet'] ?? 'Penjelasan inti pada video episode ini.',
            'title_distractors' => $titleDistractors,
            'snippet_distractors' => $snippetDistractors,
            'episode_order' => (int) ($thisEpisode['order'] ?? 1),
            'total_episodes' => max(1, count($episodeList))
        ];

        // Refresh mini quiz questions so they always match the latest episode context
        $questionCount = seedQuestionsForQuiz($db, (int) $quizId, $materialTitle, $episodeTitle, 'mini', $context, true);
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
