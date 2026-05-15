<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class PercakapanAI {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function saveConversation(int $userId, ?int $materialId, string $question, string $answer): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO percakapan_ai (user_id, material_id, question, answer, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            return $stmt->execute([$userId, $materialId ?: null, $question, $answer]);
        } catch (Exception $e) {
            error_log('Save AI conversation error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserHistory(int $userId, int $limit = 20): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.id, p.material_id, p.question, p.answer, p.created_at, m.title AS material_title
                 FROM percakapan_ai p
                 LEFT JOIN materi m ON p.material_id = m.id
                 WHERE p.user_id = :user_id
                 ORDER BY p.created_at DESC
                 LIMIT :limit"
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log('Get AI conversation history error: ' . $e->getMessage());
            return [];
        }
    }

    public function clearUserHistory(int $userId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM percakapan_ai WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log('Clear AI conversation history error: ' . $e->getMessage());
            return false;
        }
    }
}
