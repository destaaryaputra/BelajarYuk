<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

/**
 * Material Model
 * Handle semua operasi untuk materi pembelajaran
 */

class Material {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Menghitung total seluruh materi di database secara akurat
     */
    public function getTotalMaterialsCount($category = null) {
        try {
            $query = "SELECT COUNT(*) FROM materi WHERE status = 'active'";
            $params = [];
            if ($category) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (Exception $e) { return 0; }
    }

    /**
     * Get semua materi (dengan pagination)
     */
    public function getAllMaterials($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, thumbnail, created_at 
                     FROM materi 
                     WHERE status = 'active' 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get materials error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get material by ID dengan detail lengkap
     */
    public function getMaterialById($id) {
        try {
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, content, thumbnail, video_url, created_at 
                     FROM materi 
                     WHERE id = ? AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get material detail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get materi by kategori
     */
    public function getMaterialsByCategory($category, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT id, title, description, category, difficulty, duration_minutes, thumbnail, created_at 
                     FROM materi 
                     WHERE category = :category AND status = 'active' 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get materials by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get semua kategori
     */
    public function getCategories() {
        try {
            $query = "SELECT DISTINCT category FROM materi WHERE status = 'active' ORDER BY category";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get categories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create material (admin only)
     */
    public function createMaterial($data) {
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
    public function updateMaterial($id, $data) {
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
    public function deleteMaterial($id) {
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
    public function getUserProgress($user_id, $material_id) {
        try {
            $query = "SELECT * FROM progres_materi WHERE user_id = ? AND material_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $material_id]);

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user progress error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark material as completed
     */
    public function markAsCompleted($user_id, $material_id) {
        try {
            $query = "INSERT INTO progres_materi (user_id, material_id, completed_at) 
                     VALUES (?, ?, NOW())
                     ON CONFLICT (user_id, material_id) DO UPDATE SET completed_at = NOW()";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $material_id]);

            return ['success' => true, 'message' => 'Materi ditandai selesai.'];
        } catch (Exception $e) {
            error_log("Mark as completed error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menandai materi.'];
        }
    }

    /**
     * Get sub materials (episode) by material ID
     */
    public function getSubMaterials($material_id) {
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
    public function createSubMaterial($data) {
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

    public function updateSubMaterial($id, $title, $video_url, $content, $document_url = null) {
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
            return ['success' => false, 'message' => 'Gagal memperbarui episode.'];
        }
    }

    public function deleteSubMaterial($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM sub_materi WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Episode berhasil dihapus.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal menghapus episode.'];
        }
    }

    public function getComments($material_id) {
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
            if (strpos($e->getMessage(), 'komentar_materi') !== false) {
                $this->createCommentTable();
            }
            return []; 
        }
    }

    public function getAllComments($limit = 100) {
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
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'komentar_materi') !== false) {
                $this->createCommentTable();
            }
            error_log("Get all comments error: " . $e->getMessage());
            return [];
        }
    }

    public function deleteComment($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM komentar_materi WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Komentar berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete comment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus komentar.'];
        }
    }

    public function addComment($material_id, $user_id, $comment_text) {
        try {
            $query = "INSERT INTO komentar_materi (material_id, user_id, comment_text) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$material_id, $user_id, $comment_text]);
            return ['success' => true, 'message' => 'Komentar terkirim.'];
        } catch (Exception $e) { 
            // Jika tabel hilang, buat otomatis lalu coba simpan lagi!
            if (strpos($e->getMessage(), 'komentar_materi') !== false) {
                $this->createCommentTable();
                $stmt = $this->db->prepare($query);
                $stmt->execute([$material_id, $user_id, $comment_text]);
                return ['success' => true, 'message' => 'Komentar terkirim.'];
            }
            return ['success' => false, 'message' => 'Gagal mengirim komentar.']; 
        }
    }

    private function createCommentTable() {
        $query = "CREATE TABLE IF NOT EXISTS komentar_materi (
            id SERIAL PRIMARY KEY,
            material_id INT REFERENCES materi(id) ON DELETE CASCADE,
            user_id INT REFERENCES pengguna(id) ON DELETE CASCADE,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
        );";
        $this->db->exec($query);
    }
}
