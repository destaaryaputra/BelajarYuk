<?php

namespace App\Controllers;

use App\Models\PercakapanAI;
use App\Models\Material;
use App\Utils\Response;
use App\Utils\Security;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CSRFMiddleware;
use Exception;

/**
 * AI Controller
 * Handle AI requests via server-side proxy
 */

class AIController {
    private ?PercakapanAI $conversationModel = null;
    private ?Material $materialModel = null;

    public function __construct() {
        $this->conversationModel = new PercakapanAI();
        $this->materialModel = new Material();
    }

    private function requireApiKey(): void {
        if (!defined('GROQ_API_KEY') || GROQ_API_KEY === '') {
            error_log('AI API key missing.');
            Response::error('Sistem AI belum siap, nih. Hubungi admin ya.', null, 503);
        }
    }

    private function checkRateLimit(string $key, int $limit, int $windowSeconds): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = AuthMiddleware::getAuthUser();
        if ($user && isset($user['id'])) {
            $key .= '_' . (int) $user['id'];
        }

        $now = time();
        if (!isset($_SESSION[$key]) || $now > $_SESSION[$key]['reset']) {
            $_SESSION[$key] = ['count' => 0, 'reset' => $now + $windowSeconds];
        }

        if ($_SESSION[$key]['count'] >= $limit) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }

    private function sanitizeMessages(array $messages, int $maxCount): array {
        $clean = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role = $message['role'] ?? '';
            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $content = trim((string)($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            if (strlen($content) > 2000) {
                $content = substr($content, 0, 2000);
            }

            $clean[] = ['role' => $role, 'content' => $content];
        }

        if (count($clean) > $maxCount) {
            $clean = array_slice($clean, -$maxCount);
        }

        return $clean;
    }

    private function buildSystemPrompt(?array $material): string {
        $prompt = "Kamu adalah 'Yuki', asisten belajar pintar dan teman diskusi yang asyik di platform Belajaryuk. " .
            "Gaya bicaramu harus ramah, suportif, manusiawi, dan tidak kaku (gunakan sapaan seperti 'Halo!', 'Semangat ya!', atau 'Wah, pertanyaan bagus nih!'). " .
            "Gunakan bahasa Indonesia yang luwes namun tetap sopan. Jangan menjawab seperti robot yang membosankan.\n\n" .
            "Tugas utamanya:\n" .
            "1. Bantu siswa memahami konsep pemrograman, desain, dan teknologi dengan penjelasan yang sederhana tapi mendalam.\n" .
            "2. Gunakan analogi dunia nyata agar materi sulit jadi mudah dimengerti.\n" .
            "3. Selalu berikan motivasi agar siswa semangat belajar.\n" .
            "4. Gunakan format Markdown (bold, list, code blocks) agar jawabanmu rapi dan enak dibaca.\n" .
            "5. Jika pertanyaan di luar konteks belajar/edukasi, arahkan kembali ke topik belajar dengan cara yang halus dan lucu.";

        if ($material) {
            $content = trim(strip_tags((string)($material['content'] ?? $material['description'] ?? '')));
            if (strlen($content) > 1500) {
                $content = substr($content, 0, 1500);
            }

            $prompt .= "\n\nSaat ini siswa sedang membaca materi ini, jadi hubungkan jawabanmu dengan konteks ini jika relevan:\n" .
                "--- KONTEKS MATERI ---\n" .
                "Judul: " . ($material['title'] ?? '-') . "\n" .
                "Kategori: " . ($material['category'] ?? '-') . "\n" .
                "Isi/Ringkasan: " . ($content ?: '-') . "\n" .
                "--- AKHIR KONTEKS ---";
        }

        return $prompt;
    }

    private function callGroq(array $payload): array {
        // Gunakan model yang lebih cerdas (70B) untuk respon yang lebih berkualitas
        if (!isset($payload['model'])) {
            $payload['model'] = 'llama-3.3-70b-versatile';
        }
        
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'status' => 500, 'message' => $error ?: 'Gagal menghubungi AI'];
        }

        curl_close($ch);
        $data = json_decode($result, true);

        if (!is_array($data)) {
            return ['success' => false, 'status' => 502, 'message' => 'Ups, jawaban dari AI kurang jelas. Coba tanyakan lagi ya.'];
        }

        if ($status >= 400) {
            $message = $data['error']['message'] ?? 'AI sedang sibuk, coba tanya lagi nanti ya.';
            // Jika Groq return 401 (API Key salah), kita kirim 400 atau 500 ke frontend
            // agar tidak memicu auto-logout di api.js
            $finalStatus = ($status === 401) ? 400 : $status;
            return ['success' => false, 'status' => $finalStatus, 'message' => "AI Error: " . $message];
        }

        return ['success' => true, 'status' => $status, 'data' => $data];
    }

    public function chat(): void {
        AuthMiddleware::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);
        }

        CSRFMiddleware::verify();
        $this->requireApiKey();

        if (!$this->checkRateLimit('ai_chat_rate', 20, 60)) {
            Response::error('Wah, kamu bertanya terlalu cepat. Tunggu sebentar ya sebelum mencoba lagi.', null, 429);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $messages = $data['messages'] ?? [];
        $materialId = isset($data['material_id']) ? intval($data['material_id']) : null;

        if (!is_array($messages) || count($messages) === 0) {
            Response::error('Pesan yang kamu kirim sepertinya kosong. Yuk, ketik sesuatu!', null, 400);
        }

        $cleanMessages = $this->sanitizeMessages($messages, 12);
        if (count($cleanMessages) === 0) {
            Response::error('Pesan yang kamu kirim belum sesuai format.', null, 400);
        }

        $material = $materialId ? $this->materialModel->getMaterialById($materialId) : null;
        $serverMessages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($material ?: null)]],
            $cleanMessages
        );

        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => $serverMessages,
            'temperature' => 0.7
        ];

        $result = $this->callGroq($payload);
        if (!$result['success']) {
            Response::error($result['message'], null, $result['status']);
        }

        $reply = $result['data']['choices'][0]['message']['content'] ?? '';
        $lastUserMessage = '';
        for ($i = count($cleanMessages) - 1; $i >= 0; $i--) {
            if ($cleanMessages[$i]['role'] === 'user') {
                $lastUserMessage = $cleanMessages[$i]['content'];
                break;
            }
        }

        $user = AuthMiddleware::getAuthUser();
        if ($reply !== '' && $lastUserMessage !== '' && isset($user['id'])) {
            $this->conversationModel->saveConversation((int) $user['id'], $materialId, $lastUserMessage, $reply);
        }

        Response::success('OK', ['reply' => $reply]);
    }

    public function history(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
            $history = $this->conversationModel->getUserHistory((int) $user['id'], $limit);
            Response::success('Riwayat AI berhasil dimuat', $history);
        } catch (Exception $e) {
            Response::error('Gagal memuat riwayat AI.', null, 500);
        }
    }

    public function clearHistory(): void {
        AuthMiddleware::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);
        }

        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        if ($this->conversationModel->clearUserHistory((int) $user['id'])) {
            Response::success('Riwayat AI berhasil dihapus.');
            return;
        }

        Response::error('Gagal menghapus riwayat AI.', null, 500);
    }

    public function generateCourse(): void {
        AuthMiddleware::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Ups, aksi ini tidak dikenali oleh sistem.', null, 405);
        }

        CSRFMiddleware::verify();
        $this->requireApiKey();

        $user = AuthMiddleware::getAuthUser();
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            Response::error('Maaf, fitur pembuatan materi AI ini khusus untuk admin.', null, 403);
        }

        if (!$this->checkRateLimit('ai_course_rate', 10, 300)) {
            Response::error('Tunggu sebentar ya, AI butuh istirahat sebelum membuat materi baru.', null, 429);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $topic = trim((string)($data['topic'] ?? ''));

        if ($topic === '' || strlen($topic) > 120) {
            Response::error('Topik yang kamu masukkan tidak valid atau terlalu panjang.', null, 400);
        }

        $systemPrompt = "Kamu adalah ahli pembuat kurikulum edukasi. Buat 1 materi kursus komprehensif tentang: \"{$topic}\".\n" .
            "Kembalikan jawabanmu HANYA dalam format JSON murni (tanpa teks pengantar atau penutup apapun), dengan struktur persis seperti ini:\n" .
            "{\n" .
            "  \"title\": \"Judul Menarik\",\n" .
            "  \"category\": \"Kategori Umum\",\n" .
            "  \"description\": \"Deskripsi singkat 2 kalimat\",\n" .
            "  \"content\": \"Artikel lengkap dan sangat panjang format HTML menggunakan tag <h2>, <p>, <ul>, <strong>, dll.\"\n" .
            "}";

        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'system', 'content' => $systemPrompt]],
            'temperature' => 0.7
        ];

        $result = $this->callGroq($payload);
        if (!$result['success']) {
            Response::error($result['message'], null, $result['status']);
        }

        $reply = trim($result['data']['choices'][0]['message']['content'] ?? '');
        $start = strpos($reply, '{');
        $end = strrpos($reply, '}');
        if ($start === false || $end === false || $end <= $start) {
            Response::error('Waduh, format dari AI kurang rapi nih. Coba tanyakan topik lain ya.', null, 502);
        }

        $jsonText = substr($reply, $start, $end - $start + 1);
        $courseData = json_decode($jsonText, true);
        if (!is_array($courseData)) {
            Response::error('Ups, teks dari AI rusak. Mari kita ulangi sekali lagi!', null, 502);
        }

        $course = [
            'title' => Security::sanitize($courseData['title'] ?? ''),
            'category' => Security::sanitize($courseData['category'] ?? ''),
            'description' => Security::sanitize($courseData['description'] ?? ''),
            'content' => Security::sanitizeHtml($courseData['content'] ?? '')
        ];

        if ($course['title'] === '' || $course['category'] === '' || $course['description'] === '') {
            Response::error('Yah, rangkuman AI terpotong dan tidak lengkap. Coba buat ulang ya.', null, 502);
        }

        // Kirimkan response sukses kembali ke pengguna
        Response::success('Materi AI berhasil di-generate', $course);
    }
}
