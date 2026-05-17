<?php
require 'vendor/autoload.php';
require 'src/Config/bootstrap_web.php';

use App\Config\Database;

try {
    $db = Database::getInstance();
    
    echo "--- SEEDING QUIZ QUESTIONS ---" . PHP_EOL;

    // 1. Get Quiz IDs
    $quizzes = $db->query("SELECT id, title FROM kuis")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($quizzes as $quiz) {
        $quizId = $quiz['id'];
        $title = $quiz['title'];
        
        // Check if questions already exist
        $existing = $db->prepare("SELECT count(*) FROM pertanyaan WHERE quiz_id = ?");
        $existing->execute([$quizId]);
        if ($existing->fetchColumn() > 0) {
            echo "Skipping '$title' (ID: $quizId) - already has questions." . PHP_EOL;
            continue;
        }

        echo "Seeding questions for '$title'..." . PHP_EOL;

        if (strpos(strtolower($title), 'html') !== false) {
            $questions = [
                ['Apa kepanjangan dari HTML?', 'Hyper Text Markup Language', ['Hyper Text Markup Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language', 'Highly Textual Modern Language']],
                ['Tag mana yang digunakan untuk membuat judul terbesar?', '<h1>', ['<h1>', '<h6>', '<head>', '<title>']],
                ['Karakter mana yang digunakan untuk menandai tag akhir?', '/', ['/', '<', '*', '^']],
                ['Tag HTML mana yang digunakan untuk mendefinisikan teks penting?', '<strong>', ['<strong>', '<i>', '<important>', '<b>']]
            ];
        } else if (strpos(strtolower($title), 'javascript') !== false) {
            $questions = [
                ['Apa cara yang benar untuk menulis array di JavaScript?', 'var colors = ["red", "green", "blue"]', ['var colors = ["red", "green", "blue"]', 'var colors = 1 = ("red"), 2 = ("green")', 'var colors = (1:"red", 2:"green")', 'var colors = "red", "green", "blue"']],
                ['Bagaimana cara menulis "Hello World" dalam kotak alert?', 'alert("Hello World");', ['alert("Hello World");', 'msg("Hello World");', 'msgBox("Hello World");', 'alertBox("Hello World");']],
                ['Bagaimana cara memanggil fungsi bernama "myFunction"?', 'myFunction()', ['myFunction()', 'call myFunction()', 'call function myFunction()', 'run myFunction()']]
            ];
        } else {
            $questions = [
                ['Pertanyaan Contoh 1?', 'Jawaban Benar', ['Jawaban Benar', 'Salah A', 'Salah B', 'Salah C']]
            ];
        }

        foreach ($questions as $index => $q) {
            $stmt = $db->prepare("INSERT INTO pertanyaan (quiz_id, question_text, question_type, options, correct_answer, points, order_number) VALUES (?, ?, 'multiple_choice', ?, ?, 10, ?)");
            $stmt->execute([
                $quizId, 
                $q[0], 
                json_encode($q[2]), 
                $q[1], 
                $index + 1
            ]);
        }
        
        // Update total questions count
        $db->prepare("UPDATE kuis SET total_questions = ? WHERE id = ?")->execute([count($questions), $quizId]);
        echo "Done seeding '$title'." . PHP_EOL;
    }

    echo "--- CRUD SYNC AUDIT ---" . PHP_EOL;
    // Check if sequences are aligned (common issue in PostgreSQL/Supabase)
    $tables = ['materi', 'sub_materi', 'kuis', 'pertanyaan', 'pengguna'];
    foreach ($tables as $table) {
        try {
            $res = $db->query("SELECT setval(pg_get_serial_sequence('$table', 'id'), coalesce(max(id), 1)) FROM $table");
            echo "Reset sequence for table: $table" . PHP_EOL;
        } catch (Exception $seqEx) {
            // Might not be a serial sequence or table empty
            echo "Note: Could not reset sequence for $table (likely no primary key sequence or table empty)" . PHP_EOL;
        }
    }

    echo "CRUD Synchronization Complete." . PHP_EOL;

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
