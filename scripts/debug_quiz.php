<?php
require 'vendor/autoload.php';
require 'src/Config/bootstrap_web.php';

use App\Config\Database;

try {
    $db = Database::getInstance();
    
    // Check tables existence first
    $tables = $db->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . implode(', ', $tables) . PHP_EOL;

    $qCount = $db->query("SELECT count(*) FROM kuis")->fetchColumn();
    $pCount = $db->query("SELECT count(*) FROM pertanyaan")->fetchColumn();
    
    echo "--- QUIZ STATS ---" . PHP_EOL;
    echo "Total Kuis: $qCount" . PHP_EOL;
    echo "Total Pertanyaan: $pCount" . PHP_EOL;
    
    echo "--- QUIZ LIST ---" . PHP_EOL;
    $quizzes = $db->query("SELECT id, title, material_id, quiz_type, status FROM kuis")->fetchAll(PDO::FETCH_ASSOC);
    print_r($quizzes);

    echo "--- QUESTIONS PER QUIZ ---" . PHP_EOL;
    $qPerQuiz = $db->query("SELECT quiz_id, count(*) as count FROM pertanyaan GROUP BY quiz_id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($qPerQuiz);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
