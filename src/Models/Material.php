<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

/**
 * Material Model
 * Handle semua operasi untuk materi pembelajaran
 * 
 * Mengikuti prinsip SOLID dan Layered Architecture.
 */

class Material {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Menghitung total seluruh materi di database secara akurat
     */
    public function getTotalMaterialsCount(?string $category = null): int {
        try {
            $query = "SELECT COUNT(*) FROM materi WHERE status = 'active'";
            $params = [];
            if ($category) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) { 
            error_log("Total materials count error: " . $e->getMessage());
            return 0; 
        }
    }

    /**
     * Get semua materi (dengan pagination)
     */
    public function getAllMaterials(int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, thumbnail, created_at 
                     FROM materi 
                     WHERE status = 'active' 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get materials error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get material by ID dengan detail lengkap
     */
    public function getMaterialById(int $id): ?array {
        try {
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, content, thumbnail, video_url, created_at 
                     FROM materi 
                     WHERE id = ? AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get material detail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get materi by kategori
     */
    public function getMaterialsByCategory(string $category, int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, thumbnail, created_at 
                     FROM materi 
                     WHERE category = :category AND status = 'active' 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get materials by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get semua kategori
     */
    public function getCategories(): array {
        try {
            $query = "SELECT DISTINCT category FROM materi WHERE status = 'active' ORDER BY category";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get categories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create material (admin only)
     */
    public function createMaterial(array $data): array {
        try {
            $query = "INSERT INTO materi (title, description, category, difficulty, duration_minutes, content, thumbnail, video_url, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) RETURNING id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['category'],
                $data['difficulty'] ?? 'beginner',
                $data['duration_minutes'] ?? 0,
                $data['content'] ?? null,
                $data['thumbnail'] ?? null,
                $data['video_url'] ?? null
            ]);

            return ['success' => true, 'message' => 'Materi berhasil dibuat.', 'material_id' => $stmt->fetchColumn()];
        } catch (Exception $e) {
            error_log("Create material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal membuat materi.'];
        }
    }

    /**
     * Update material (admin only)
     */
    public function updateMaterial(int $id, array $data): array {
        try {
            $query = "UPDATE materi SET title = ?, description = ?, category = ?, difficulty = ?, duration_minutes = ?, content = ?, thumbnail = ?, video_url = ? WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['category'],
                $data['difficulty'] ?? 'beginner',
                $data['duration_minutes'] ?? 0,
                $data['content'] ?? null,
                $data['thumbnail'] ?? null,
                $data['video_url'] ?? null,
                $id
            ]);

            return ['success' => true, 'message' => 'Materi berhasil diperbarui.'];
        } catch (Exception $e) {
            error_log("Update material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui materi.'];
        }
    }

    /**
     * Delete material (admin only)
     */
    public function deleteMaterial(int $id): array {
        try {
            // Hard delete untuk memicu ON DELETE CASCADE pada tabel Kuis, Episode, dan Progres Siswa
            $query = "DELETE FROM materi WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Materi berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus materi.'];
        }
    }

    /**
     * Get user progress untuk materi
     */
    public function getUserProgress(int $user_id, int $material_id): ?array {
        try {
            $query = "SELECT * FROM progres_materi WHERE user_id = ? AND material_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $material_id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get user progress error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark material as completed
     */
    public function markAsCompleted(int $user_id, int $material_id): array {
        try {
            $query = "INSERT INTO progres_materi (user_id, material_id, completed_at) 
                     VALUES (?, ?, NOW())
                     ON CONFLICT (user_id, material_id) DO UPDATE SET completed_at = NOW()";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $material_id]);

            // Sync after marking main material as completed
            $progressModel = new \App\Models\Progress();
            $progressModel->syncMaterialProgress($user_id, $material_id);

            return ['success' => true, 'message' => 'Materi ditandai selesai.'];
        } catch (Exception $e) {
            error_log("Mark as completed error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menandai materi.'];
        }
    }

    /**
     * Mark sub-material (episode) as completed
     */
    public function markSubMaterialCompleted(int $user_id, int $sub_material_id): array {
        try {
            // Get material_id first for sync
            $stmtM = $this->db->prepare("SELECT material_id FROM sub_materi WHERE id = ?");
            $stmtM->execute([$sub_material_id]);
            $material_id = $stmtM->fetchColumn();

            $query = "INSERT INTO progres_sub_materi (user_id, sub_material_id, completed_at) 
                     VALUES (?, ?, NOW())
                     ON CONFLICT (user_id, sub_material_id) DO UPDATE SET completed_at = NOW()";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $sub_material_id]);

            // Auto Sync Progress Percentage to progres_materi table
            if ($material_id) {
                $progressModel = new \App\Models\Progress();
                $progressModel->syncMaterialProgress($user_id, (int)$material_id);
            }

            return ['success' => true, 'message' => 'Episode ditandai selesai.'];
        } catch (Exception $e) {
            error_log("Mark sub-material completed error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menandai episode.'];
        }
    }

    /**
     * Get list of completed sub-material IDs for a user in a specific material
     */
    public function getCompletedSubMaterials(int $user_id, int $material_id): array {
        try {
            $query = "SELECT sub_material_id FROM progres_sub_materi psm
                     JOIN sub_materi sm ON psm.sub_material_id = sm.id
                     WHERE psm.user_id = ? AND sm.material_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $material_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Get completed sub-materials error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get sub materials (episode) by material ID
     */
    public function getSubMaterials(int $material_id): array {
        try {
            $query = "SELECT * FROM sub_materi WHERE material_id = ? ORDER BY order_number ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$material_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get sub materials error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new sub material (Episode)
     */
    public function createSubMaterial(array $data): array {
        try {
            // Hitung otomatis urutan episode selanjutnya agar tidak bertumpuk di 999
            $stmtOrder = $this->db->prepare("SELECT COALESCE(MAX(order_number), 0) + 1 FROM sub_materi WHERE material_id = ?");
            $stmtOrder->execute([$data['material_id']]);
            $nextOrder = $stmtOrder->fetchColumn();

            $query = "INSERT INTO sub_materi (material_id, title, video_url, document_url, content, order_number) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['material_id'], $data['title'], $data['video_url'], 
                $data['document_url'], $data['content'], $nextOrder
            ]);
            return ['success' => true, 'message' => 'Episode berhasil disimpan.'];
        } catch (Exception $e) {
            error_log("Create sub material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan episode.'];
        }
    }

    public function updateSubMaterial(int $id, string $title, string $video_url, string $content, ?string $document_url = null): array {
        try {
            $query = "UPDATE sub_materi SET title = ?, video_url = ?, content = ?";
            $params = [$title, $video_url, $content];

            if ($document_url !== null) {
                $query .= ", document_url = ?";
                $params[] = $document_url;
            }

            $query .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return ['success' => true, 'message' => 'Episode berhasil diperbarui.'];
        } catch (Exception $e) {
            error_log("Update sub-material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui episode.'];
        }
    }

    public function deleteSubMaterial(int $id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM sub_materi WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Episode berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete sub-material error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus episode.'];
        }
    }

    public function getComments(int $material_id): array {
        try {
            $query = "SELECT c.id, c.comment_text, c.created_at, u.full_name, u.username, u.role 
                     FROM komentar_materi c
                     JOIN pengguna u ON c.user_id = u.id
                     WHERE c.material_id = ?
                     ORDER BY c.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$material_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { 
            error_log("Get comments error: " . $e->getMessage());
            return []; 
        }
    }

    public function getAllComments(int $limit = 100): array {
        try {
            $query = "SELECT c.id, c.material_id, c.comment_text, c.created_at,
                            m.title AS material_title,
                            u.full_name, u.username, u.role
                     FROM komentar_materi c
                     JOIN materi m ON c.material_id = m.id
                     JOIN pengguna u ON c.user_id = u.id
                     ORDER BY c.created_at DESC
                     LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get all comments error: " . $e->getMessage());
            return [];
        }
    }

    public function deleteComment(int $id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM komentar_materi WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Komentar berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete comment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus komentar.'];
        }
    }

    public function addComment(int $material_id, int $user_id, string $comment_text): array {
        $query = "INSERT INTO komentar_materi (material_id, user_id, comment_text) VALUES (?, ?, ?)";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$material_id, $user_id, $comment_text]);
            return ['success' => true, 'message' => 'Komentar terkirim.'];
        } catch (Exception $e) { 
            error_log("Add comment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengirim komentar.']; 
        }
    }
}
