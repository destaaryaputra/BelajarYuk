<?php

namespace App\Models;

use App\Config\Database;
use App\Services\QuizQuestionBank;
use PDO;
use Exception;

/**
 * Quiz Model
 * Handle semua operasi untuk kuis
 */

class Quiz {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get quiz by material ID (Final Quiz)
     */
    public function getQuizByMaterialId(int $material_id): ?array {
        try {
            error_log("Fetching final quiz for material_id: $material_id");
            $query = "SELECT id, title, description, material_id, passing_score, total_questions, created_at, quiz_type, sub_material_id 
                     FROM kuis 
                     WHERE material_id = ? AND quiz_type = 'final'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$material_id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Quiz result: " . ($result ? json_encode($result) : "NULL"));
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get quiz error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get mini quiz by sub material ID
     */
    public function getQuizBySubMaterialId(int $sub_material_id): ?array {
        try {
            error_log("Fetching mini quiz for sub_material_id: $sub_material_id");
            $query = "SELECT id, title, description, material_id, sub_material_id, quiz_type, passing_score, total_questions, created_at 
                     FROM kuis 
                     WHERE sub_material_id = ? AND quiz_type = 'mini'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sub_material_id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Mini quiz result: " . ($result ? json_encode($result) : "NULL"));
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get mini quiz error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get questions untuk quiz
     */
    public function getQuestionsByQuizId(int $quiz_id): array {
        try {
            $this->syncCuratedQuestions($quiz_id);

            $query = "SELECT id, quiz_id, question_text, question_type, options, correct_answer, COALESCE(points,0) AS points 
                     FROM pertanyaan 
                     WHERE quiz_id = ? AND status = 'active' 
                     ORDER BY order_number ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$quiz_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Transform JSON options for frontend compatibility
            return array_map(function(array $q) {
                $opts = is_string($q['options']) ? json_decode($q['options'], true) : $q['options'];
                if (is_array($opts)) {
                    $q['opt_a'] = $opts[0] ?? '';
                    $q['opt_b'] = $opts[1] ?? '';
                    $q['opt_c'] = $opts[2] ?? '';
                    $q['opt_d'] = $opts[3] ?? '';
                }
                return $q;
            }, $rows);
        } catch (Exception $e) {
            error_log("Get questions error: " . $e->getMessage());
            return [];
        }
    }

    public function syncCuratedQuestionsForQuiz(int $quiz_id): void {
        $this->syncCuratedQuestions($quiz_id);
    }

    private function syncCuratedQuestions(int $quiz_id): void {
        try {
            $quizStmt = $this->db->prepare("
                SELECT
                    q.id,
                    q.quiz_type,
                    q.title,
                    q.material_id,
                    q.sub_material_id,
                    m.title AS material_title,
                    m.content AS material_content,
                    sm.title AS sub_material_title,
                    sm.content AS sub_material_content
                FROM kuis q
                LEFT JOIN materi m ON m.id = q.material_id
                LEFT JOIN sub_materi sm ON sm.id = q.sub_material_id
                WHERE q.id = ?
            ");
            $quizStmt->execute([$quiz_id]);
            $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);
            if (!$quiz) {
                return;
            }

            $curatedQuestions = QuizQuestionBank::questionsFor($quiz);
            if (empty($curatedQuestions)) {
                return;
            }

            $existingStmt = $this->db->prepare("
                SELECT id, question_text, options, correct_answer, COALESCE(points, 0) AS points, status
                FROM pertanyaan
                WHERE quiz_id = ?
                ORDER BY order_number ASC, id ASC
            ");
            $existingStmt->execute([$quiz_id]);
            $existingQuestions = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($this->questionsAlreadySynced($existingQuestions, $curatedQuestions)) {
                return;
            }

            $updateStmt = $this->db->prepare("
                UPDATE pertanyaan
                SET question_text = ?,
                    question_type = 'multiple_choice',
                    options = ?,
                    correct_answer = ?,
                    points = ?,
                    order_number = ?,
                    status = 'active'
                WHERE id = ?
            ");
            $insertStmt = $this->db->prepare("
                INSERT INTO pertanyaan (quiz_id, question_text, question_type, options, correct_answer, points, order_number, status)
                VALUES (?, ?, 'multiple_choice', ?, ?, ?, ?, 'active')
            ");

            foreach ($curatedQuestions as $index => $question) {
                $optionsJson = json_encode($question['options'], JSON_UNESCAPED_UNICODE);
                $points = intval($question['points'] ?? 10);
                $order = $index + 1;

                if (isset($existingQuestions[$index])) {
                    $updateStmt->execute([
                        $question['question_text'],
                        $optionsJson,
                        $question['correct_answer'],
                        $points,
                        $order,
                        $existingQuestions[$index]['id']
                    ]);
                } else {
                    $insertStmt->execute([
                        $quiz_id,
                        $question['question_text'],
                        $optionsJson,
                        $question['correct_answer'],
                        $points,
                        $order
                    ]);
                }
            }

            if (count($existingQuestions) > count($curatedQuestions)) {
                $extraIds = array_slice(array_column($existingQuestions, 'id'), count($curatedQuestions));
                $placeholders = implode(',', array_fill(0, count($extraIds), '?'));
                $this->db->prepare("UPDATE pertanyaan SET status = 'deleted' WHERE id IN ($placeholders)")->execute($extraIds);
            }

            $this->db->prepare("UPDATE kuis SET total_questions = ? WHERE id = ?")->execute([count($curatedQuestions), $quiz_id]);
        } catch (Exception $e) {
            error_log('Sync curated quiz questions error: ' . $e->getMessage());
        }
    }

    private function questionsAlreadySynced(array $existingQuestions, array $curatedQuestions): bool {
        $activeExisting = array_values(array_filter($existingQuestions, function(array $question) {
            return ($question['status'] ?? 'active') === 'active';
        }));

        if (count($activeExisting) !== count($curatedQuestions)) {
            return false;
        }

        foreach ($curatedQuestions as $index => $curated) {
            $existing = $activeExisting[$index] ?? null;
            if (!$existing) {
                return false;
            }

            $existingOptions = is_string($existing['options']) ? json_decode($existing['options'], true) : $existing['options'];
            if (
                ($existing['question_text'] ?? '') !== $curated['question_text'] ||
                ($existing['correct_answer'] ?? '') !== $curated['correct_answer'] ||
                array_values($existingOptions ?: []) !== array_values($curated['options'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Submit quiz answers
     */
    public function submitQuizAnswers(int $user_id, int $quiz_id, array $answers): array {
        try {
            // Get all questions
            $questions = $this->getQuestionsByQuizId($quiz_id);
            $score = 0;
            $total_points = 0;
            $correct_answers = 0;

            foreach ($questions as $question) {
                $total_points += $question['points'];

                if (isset($answers[$question['id']])) {
                    $user_answer = $answers[$question['id']];
                    $correct_answer = $question['correct_answer'];

                    if ($user_answer === $correct_answer) {
                        $score += $question['points'];
                        $correct_answers++;
                    }

                    // Save user's answer
                    $this->saveUserAnswer($user_id, $question['id'], $user_answer);
                }
            }

            // Calculate percentage
            $percentage = ($total_points > 0) ? ($score / $total_points) * 100 : 0;

            // Save quiz result
            $query = "INSERT INTO hasil_kuis (user_id, quiz_id, score, total_points, percentage, submitted_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $quiz_id, $score, $total_points, $percentage]);

            \App\Models\Progress::clearUserProgressCache($user_id);

            return [
                'success' => true,
                'message' => 'Kuis berhasil disubmit.',
                'score' => $score,
                'total_points' => $total_points,
                'earned_points' => $score,
                'max_points' => $total_points,
                'correct_answers' => $correct_answers,
                'percentage' => round($percentage, 2)
            ];
        } catch (Exception $e) {
            error_log("Submit quiz error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan hasil kuis.'];
        }
    }

    /**
     * Save individual answer
     */
    private function saveUserAnswer(int $user_id, int $question_id, string $answer): bool {
        try {
            $query = "INSERT INTO jawaban_pengguna (user_id, question_id, answer, answered_at) 
                     VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $question_id, $answer]);

            return true;
        } catch (Exception $e) {
            error_log("Save user answer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's quiz results
     */
    public function getUserQuizResults(int $user_id, ?int $quiz_id = null): array|null|false {
        try {
            $query = "SELECT q.id, q.title, hk.score, hk.total_points, hk.percentage, hk.submitted_at 
                         FROM hasil_kuis hk
                     JOIN kuis q ON hk.quiz_id = q.id
                     WHERE hk.user_id = ?";
            
            $params = [$user_id];
            
            if ($quiz_id) {
                $query .= " AND hk.quiz_id = ?";
                $params[] = $quiz_id;
            }
            
            $query .= " ORDER BY hk.submitted_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $quiz_id ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user quiz results error: " . $e->getMessage());
            return null;
        }
    }

    public function getAdminReport(): array {
        try {
            $summaryStmt = $this->db->query("
                SELECT
                    COUNT(hk.id) AS total_attempts,
                    COALESCE(ROUND(AVG(hk.percentage), 2), 0) AS avg_score,
                    COALESCE(MAX(hk.percentage), 0) AS highest_score,
                    COALESCE(MIN(hk.percentage), 0) AS lowest_score
                FROM hasil_kuis hk
            ");
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $perQuizStmt = $this->db->query("
                SELECT
                    q.id,
                    q.title AS quiz_title,
                    m.title AS material_title,
                    q.passing_score,
                    COUNT(hk.id) AS attempts,
                    COALESCE(ROUND(AVG(hk.percentage), 2), 0) AS avg_score,
                    SUM(CASE WHEN hk.percentage >= q.passing_score THEN 1 ELSE 0 END) AS passed_count
                FROM kuis q
                LEFT JOIN materi m ON q.material_id = m.id
                LEFT JOIN hasil_kuis hk ON hk.quiz_id = q.id
                WHERE q.status = 'active'
                GROUP BY q.id, q.title, m.title, q.passing_score
                ORDER BY q.created_at DESC
            ");

            $recentStmt = $this->db->query("
                SELECT
                    hk.id,
                    hk.percentage,
                    hk.score,
                    hk.total_points,
                    hk.submitted_at,
                    q.title AS quiz_title,
                    m.title AS material_title,
                    u.full_name,
                    u.username
                FROM hasil_kuis hk
                JOIN kuis q ON hk.quiz_id = q.id
                LEFT JOIN materi m ON q.material_id = m.id
                JOIN pengguna u ON hk.user_id = u.id
                ORDER BY hk.submitted_at DESC
                LIMIT 20
            ");

            return [
                'summary' => [
                    'total_attempts' => (int) ($summary['total_attempts'] ?? 0),
                    'avg_score' => (float) ($summary['avg_score'] ?? 0),
                    'highest_score' => (float) ($summary['highest_score'] ?? 0),
                    'lowest_score' => (float) ($summary['lowest_score'] ?? 0),
                ],
                'per_quiz' => $perQuizStmt->fetchAll(PDO::FETCH_ASSOC),
                'recent_results' => $recentStmt->fetchAll(PDO::FETCH_ASSOC),
            ];
        } catch (Exception $e) {
            error_log('Admin quiz report error: ' . $e->getMessage());
            return [
                'summary' => ['total_attempts' => 0, 'avg_score' => 0, 'highest_score' => 0, 'lowest_score' => 0],
                'per_quiz' => [],
                'recent_results' => [],
            ];
        }
    }

    /**
     * Create quiz (admin only)
     */
    public function createQuiz(array $data): array {
        try {
            $query = "INSERT INTO kuis (title, description, material_id, sub_material_id, quiz_type, passing_score, total_questions, time_limit_minutes, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) RETURNING id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['material_id'],
                $data['sub_material_id'] ?? null,
                $data['quiz_type'] ?? 'final',
                $data['passing_score'] ?? 60,
                $data['total_questions'] ?? 0,
                $data['time_limit_minutes'] ?? null
            ]);

            return ['success' => true, 'message' => 'Kuis berhasil dibuat.', 'quiz_id' => $stmt->fetchColumn()];
        } catch (Exception $e) {
            error_log("Create quiz error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal membuat kuis.'];
        }
    }

    /**
     * Tambah pertanyaan ke kuis
     */
    public function addQuestion(array $data): array {
        try {
            // Cari urutan terakhir
            $stmt = $this->db->prepare("SELECT COALESCE(MAX(order_number), 0) + 1 FROM pertanyaan WHERE quiz_id = ?");
            $stmt->execute([$data['quiz_id']]);
            $order_number = $stmt->fetchColumn();

            $query = "INSERT INTO pertanyaan (quiz_id, question_text, question_type, options, correct_answer, points, order_number) 
                     VALUES (?, ?, 'multiple_choice', ?, ?, 10, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['quiz_id'], $data['question_text'], json_encode($data['options']), 
                $data['correct_answer'], $order_number
            ]);

            // Update total soal di tabel kuis
            $this->db->prepare("UPDATE kuis SET total_questions = total_questions + 1 WHERE id = ?")->execute([$data['quiz_id']]);

            return ['success' => true, 'message' => 'Soal berhasil ditambahkan.'];
        } catch (Exception $e) {
            error_log("Add question error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambahkan soal.'];
        }
    }

    public function deleteQuestion(int $id): array {
        try {
            $stmt = $this->db->prepare("SELECT quiz_id FROM pertanyaan WHERE id = ?");
            $stmt->execute([$id]);
            $quiz_id = $stmt->fetchColumn();

            $this->db->prepare("DELETE FROM pertanyaan WHERE id = ?")->execute([$id]);
            if ($quiz_id) $this->db->prepare("UPDATE kuis SET total_questions = total_questions - 1 WHERE id = ?")->execute([$quiz_id]);
            
            return ['success' => true, 'message' => 'Soal berhasil dihapus.'];
        } catch (Exception $e) { 
            error_log("Delete question error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus soal.']; 
        }
    }

    public function deleteQuiz(int $id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM kuis WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Kuis beserta soalnya berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete quiz error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus kuis.'];
        }
    }
}
