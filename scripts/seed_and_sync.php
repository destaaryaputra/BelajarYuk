<?php
require 'vendor/autoload.php';
require 'src/Config/bootstrap_web.php';

use App\Config\Database;
use App\Models\Quiz;

try {
    $db = Database::getInstance();
    $quizModel = new Quiz();
    
    echo "--- SEEDING QUIZ QUESTIONS ---" . PHP_EOL;

    // 1. Get Quiz IDs
    $quizzes = $db->query("SELECT id, title FROM kuis")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($quizzes as $quiz) {
        $quizId = $quiz['id'];
        $title = $quiz['title'];

        echo "Syncing curated questions for '$title' (ID: $quizId)..." . PHP_EOL;
        $quizModel->syncCuratedQuestionsForQuiz((int) $quizId);
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
