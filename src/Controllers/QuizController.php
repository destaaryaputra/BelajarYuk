<?php
namespace App\Controllers;

use App\Models\Quiz;
use App\Utils\Response;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CSRFMiddleware;
use App\Utils\Security;
use Exception;

/**
 * Quiz Controller
 * Handle quiz operations
 */

class QuizController {
    private ?Quiz $quizModel;

    public function __construct() {
        $this->quizModel = new Quiz();
    }

    public function listQuizzesAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Unauthorized', null, 403);
        }

        try {
            $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;
            if (!$material_id) Response::error('Material ID required', null, 400);

            $db = \App\Config\Database::getInstance();
            $stmt = $db->prepare("SELECT q.*, sm.title as sub_material_title 
                                 FROM kuis q 
                                 LEFT JOIN sub_materi sm ON q.sub_material_id = sm.id 
                                 WHERE q.material_id = ? 
                                 ORDER BY q.quiz_type DESC, q.created_at ASC");
            $stmt->execute([$material_id]);
            $quizzes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::success('Daftar kuis berhasil dimuat', $quizzes);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get quiz untuk material atau sub-materi tertentu
     */
    public function getQuiz(): void {
        try {
            $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;
            $sub_material_id = isset($_GET['sub_material_id']) ? intval($_GET['sub_material_id']) : null;

            if (!$material_id && !$sub_material_id) {
                Response::error('Pilih materi atau episode pembelajarannya dulu ya.', null, 400);
            }

            if ($sub_material_id) {
                $quiz = $this->quizModel->getQuizBySubMaterialId($sub_material_id);
            } else {
                $quiz = $this->quizModel->getQuizByMaterialId($material_id);
            }

            if (!$quiz) {
                Response::success('Belum ada kuis untuk bagian ini.', null);
                return;
            }

            Response::success('Kuis siap dikerjakan', $quiz);
        } catch (Exception $e) {
            error_log("Get quiz error: " . $e->getMessage());
            Response::error('Waduh, sistem gagal memuat kuis. Coba muat ulang ya.', null, 500);
        }
    }

    /**
     * Get questions untuk quiz
     */
    public function getQuestions(): void {
        try {
            if (!isset($_GET['quiz_id'])) {
                Response::error('Pilih kuis yang ingin dikerjakan terlebih dahulu.', null, 400);
            }

            $quiz_id = intval($_GET['quiz_id']);
            $questions = $this->quizModel->getQuestionsByQuizId($quiz_id);

            Response::success('Daftar pertanyaan berhasil dimuat', $questions);
        } catch (Exception $e) {
            error_log("Get questions error: " . $e->getMessage());
            Response::error('Waduh, pertanyaan kuisnya gagal dimuat. Coba lagi ya.', null, 500);
        }
    }

    /**
     * Submit quiz answers (protected)
     */
    public function submitQuiz(): void {
        AuthMiddleware::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);
        }

        CSRFMiddleware::verify();

        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!isset($data['quiz_id']) || !isset($data['answers'])) {
                Response::error('Pastikan kuis dan jawaban sudah lengkap ya.', null, 400);
            }

            $user = AuthMiddleware::getAuthUser();
            $quiz_id = intval($data['quiz_id']);
            $answers = $data['answers'];

            $result = $this->quizModel->submitQuizAnswers($user['id'], $quiz_id, $answers);

            if ($result['success']) {
                Response::success($result['message'], [
                    'score' => $result['score'],
                    'total_points' => $result['total_points'],
                    'percentage' => $result['percentage']
                ]);
            } else {
                Response::error($result['message'], null, 400);
            }
        } catch (Exception $e) {
            error_log("Submit quiz error: " . $e->getMessage());
            Response::error('Ups, jawaban kuis gagal dikirim. Coba kumpulkan lagi.', null, 500);
        }
    }

    /**
     * Get user's quiz results (protected)
     */
    public function getUserResults(): void {
        AuthMiddleware::requireAuth();

        try {
        $user = AuthMiddleware::getAuthUser();
        if (!$user) {
            // Should not happen if AuthMiddleware works, but guard against null
            Response::error('Unauthorized. Please login.', null, 401);
            return;
        }
        $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : null;

        $results = $this->quizModel->getUserQuizResults($user['id'], $quiz_id);

        Response::success('Hasil kuis berhasil dimuat', $results);
        } catch (Exception $e) {
            error_log("Get user results error: " . $e->getMessage());
            Response::error('Ups, riwayat kuismu gagal ditampilkan.', null, 500);
        }
    }

    public function getAdminReport(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, hanya Admin yang bisa melihat laporan.', null, 403);
            return;
        }

        try {
            Response::success('Laporan kuis berhasil dimuat', $this->quizModel->getAdminReport());
        } catch (Exception $e) {
            error_log("Admin report error: " . $e->getMessage());
            Response::error('Gagal memuat laporan kuis.', null, 500);
        }
    }

    public function createQuizAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, fitur ini khusus untuk admin ya.', null, 403);
            return;
        }
        
        CSRFMiddleware::verify();
        $data = json_decode(file_get_contents("php://input"), true);

        $materialId = intval($data['material_id'] ?? 0);
        $subMaterialId = isset($data['sub_material_id']) ? intval($data['sub_material_id']) : null;
        $quizType = Security::sanitize($data['quiz_type'] ?? 'final');
        $title = Security::sanitize($data['title'] ?? '');
        $description = Security::sanitize($data['description'] ?? '');
        $passingScore = intval($data['passing_score'] ?? 70);
        $timeLimit = intval($data['time_limit_minutes'] ?? 0);

        if (!$materialId || $title === '') {
            Response::error('Materi dan judul kuis wajib diisi.', null, 422);
        }

        if ($passingScore < 0 || $passingScore > 100) {
            Response::error('Passing score harus berada di rentang 0 sampai 100.', null, 422);
        }
        
        $result = $this->quizModel->createQuiz([
            'material_id' => $materialId,
            'sub_material_id' => $subMaterialId,
            'quiz_type' => $quizType,
            'title' => $title,
            'description' => $description,
            'passing_score' => $passingScore,
            'time_limit_minutes' => max(0, $timeLimit)
        ]);

        if ($result['success']) Response::success($result['message'], $result);
        else Response::error($result['message']);
    }

    public function addQuestionAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, fitur ini khusus untuk admin ya.', null, 403);
            return;
        }

        CSRFMiddleware::verify();
        $data = json_decode(file_get_contents("php://input"), true);

        $quizId = intval($data['quiz_id'] ?? 0);
        $questionText = Security::sanitize($data['question_text'] ?? '');
        $options = [
            Security::sanitize($data['opt_a'] ?? ''),
            Security::sanitize($data['opt_b'] ?? ''),
            Security::sanitize($data['opt_c'] ?? ''),
            Security::sanitize($data['opt_d'] ?? '')
        ];
        $map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        $correct_idx = $map[strtoupper($data['correct_opt'] ?? 'A')] ?? 0;
        $correct_answer = $options[$correct_idx];

        if (!$quizId || $questionText === '' || in_array('', $options, true)) {
            Response::error('Soal dan semua pilihan jawaban wajib diisi.', null, 422);
        }

        $result = $this->quizModel->addQuestion([
            'quiz_id' => $quizId,
            'question_text' => $questionText,
            'options' => $options,
            'correct_answer' => $correct_answer
        ]);

        if ($result['success']) Response::success($result['message']);
        else Response::error($result['message']);
    }

    public function deleteQuestionAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, kamu tidak punya izin untuk aksi ini.', null, 403);
            return;
        }

        CSRFMiddleware::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);

        $data = json_decode(file_get_contents("php://input"), true);
        $this->quizModel->deleteQuestion(intval($data['id'] ?? 0));
        Response::success('Soal berhasil dihapus.');
    }

    public function deleteQuizAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, kamu tidak punya izin untuk aksi ini.', null, 403);
            return;
        }
        
        CSRFMiddleware::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);

        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->quizModel->deleteQuiz(intval($data['id'] ?? 0));
        if ($result['success']) Response::success($result['message']);
        else Response::error($result['message'], null, 500);
    }
}
