<?php

namespace App\Services;

use App\Models\Material;
use App\Utils\Security;
use App\Services\UploadService;
use Exception;

class MaterialService {
    private Material $materialModel;

    public function __construct(Material $materialModel = null) {
        $this->materialModel = $materialModel ?? new Material();
    }

    public function getPaginatedMaterials(int $page, int $limit, ?string $category = null): array {
        if ($category) {
            $materials = $this->materialModel->getMaterialsByCategory($category, $page, $limit);
        } else {
            $materials = $this->materialModel->getAllMaterials($page, $limit);
        }
        
        $total = $this->materialModel->getTotalMaterialsCount($category);

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($total),
            'materials' => $materials 
        ];
    }

    public function getMaterialDetail(int $id, ?int $userId = null): array {
        $material = $this->materialModel->getMaterialById($id);

        if (!$material) {
            throw new Exception('Maaf, materi tidak ditemukan.', 404);
        }

        $user_progress = null;
        $completed_episodes = [];
        if ($userId) {
            $user_progress = $this->materialModel->getUserProgress($userId, $id);
            $completed_episodes = $this->materialModel->getCompletedSubMaterials($userId, $id);
        }

        if (method_exists($this->materialModel, 'getSubMaterials')) {
            $material['sub_materials'] = $this->materialModel->getSubMaterials($id);
        }

        return [
            'material' => $material,
            'user_progress' => $user_progress,
            'completed_episodes' => $completed_episodes
        ];
    }

    public function createMaterial(array $postData, array $files): array {
        $data = [
            'title' => Security::sanitize($postData['title'] ?? ''),
            'description' => Security::sanitize($postData['description'] ?? ''),
            'category' => Security::sanitize($postData['category'] ?? ''),
            'difficulty' => Security::sanitize($postData['difficulty'] ?? 'beginner'),
            'duration_minutes' => intval($postData['duration_minutes'] ?? 0),
            'content' => isset($postData['content']) ? Security::sanitizeHtml($postData['content']) : null,
            'video_url' => Security::sanitize($postData['video_url'] ?? ''),
            'thumbnail' => null
        ];

        if (empty($data['title'])) {
            throw new Exception('Judul materi tidak boleh kosong.', 422);
        }

        if (isset($files['thumbnail']) && $files['thumbnail']['size'] > 0) {
            $upload_result = UploadService::uploadThumbnail($files['thumbnail']);
            if (!$upload_result['success']) {
                throw new Exception($upload_result['message'], 400);
            }
            $data['thumbnail'] = $upload_result['filename'];
        }

        $result = $this->materialModel->createMaterial($data);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }

        return $result;
    }

    public function updateMaterial(int $id, array $postData, array $files): array {
        $existing = $this->materialModel->getMaterialById($id);
        if (!$existing) {
            throw new Exception('Materi tidak ditemukan.', 404);
        }

        $data = [
            'title' => Security::sanitize($postData['title'] ?? ''),
            'description' => Security::sanitize($postData['description'] ?? ''),
            'category' => Security::sanitize($postData['category'] ?? ''),
            'difficulty' => Security::sanitize($postData['difficulty'] ?? 'beginner'),
            'duration_minutes' => intval($postData['duration_minutes'] ?? 0),
            'content' => isset($postData['content']) ? Security::sanitizeHtml($postData['content']) : null,
            'video_url' => Security::sanitize($postData['video_url'] ?? ''),
            'thumbnail' => $existing['thumbnail']
        ];

        if (empty($data['title'])) {
            throw new Exception('Judul materi tidak boleh kosong.', 422);
        }

        if (isset($files['thumbnail']) && $files['thumbnail']['size'] > 0) {
            $upload_result = UploadService::uploadThumbnail($files['thumbnail']);
            if (!$upload_result['success']) {
                throw new Exception($upload_result['message'], 400);
            }
            $data['thumbnail'] = $upload_result['filename'];
        }

        $result = $this->materialModel->updateMaterial($id, $data);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }

        return $result;
    }

    public function deleteMaterial(int $id): array {
        $result = $this->materialModel->deleteMaterial($id);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }
        return $result;
    }

    public function getSubMaterialsAdmin(int $materialId): array {
        return $this->materialModel->getSubMaterials($materialId);
    }

    public function createSubMaterial(array $postData, array $files): array {
        $data = [
            'material_id' => intval($postData['material_id'] ?? 0),
            'title' => Security::sanitize($postData['title'] ?? ''),
            'video_url' => Security::sanitize($postData['video_url'] ?? ''),
            'content' => isset($postData['content']) ? Security::sanitizeHtml($postData['content']) : null,
            'document_url' => null
        ];

        if (!$data['material_id'] || empty($data['title'])) {
            throw new Exception('Materi ID dan Judul episode wajib diisi.', 422);
        }

        if (isset($files['pdf']) && $files['pdf']['size'] > 0) {
            $upload_result = UploadService::uploadPdf($files['pdf']);
            if (!$upload_result['success']) {
                throw new Exception($upload_result['message'], 400);
            }
            $data['document_url'] = $upload_result['filename'];
        }

        $result = $this->materialModel->createSubMaterial($data);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }

        return $result;
    }

    public function updateSubMaterial(int $id, array $postData, array $files): array {
        $title = Security::sanitize($postData['title'] ?? '');
        $video_url = Security::sanitize($postData['video_url'] ?? '');
        $content = isset($postData['content']) ? Security::sanitizeHtml($postData['content']) : null;
        $document_url = null;

        if (empty($title)) {
            throw new Exception('Judul episode tidak boleh kosong.', 422);
        }

        if (isset($files['pdf']) && $files['pdf']['size'] > 0) {
            $upload_result = UploadService::uploadPdf($files['pdf']);
            if (!$upload_result['success']) {
                throw new Exception($upload_result['message'], 400);
            }
            $document_url = $upload_result['filename'];
        }

        $result = $this->materialModel->updateSubMaterial($id, $title, $video_url, $content, $document_url);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }

        return $result;
    }

    public function deleteSubMaterial(int $id): array {
        $result = $this->materialModel->deleteSubMaterial($id);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }
        return $result;
    }

    public function getCategories(): array {
        return $this->materialModel->getCategories();
    }

    public function markAsCompleted(int $userId, int $materialId, ?int $subMaterialId = null): array {
        if ($subMaterialId) {
            $result = $this->materialModel->markSubMaterialCompleted($userId, $subMaterialId);
        } else {
            $result = $this->materialModel->markAsCompleted($userId, $materialId);
        }
        
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }
        return $result;
    }

    public function getComments(int $materialId): array {
        return $this->materialModel->getComments($materialId);
    }

    public function addComment(int $materialId, int $userId, string $text): array {
        $text = Security::sanitize($text);
        if (empty($text)) {
            throw new Exception('Komentar tidak boleh kosong.', 422);
        }
        $result = $this->materialModel->addComment($materialId, $userId, $text);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }
        return $result;
    }

    public function getAllCommentsAdmin(int $limit): array {
        return $this->materialModel->getAllComments($limit);
    }

    public function deleteCommentAdmin(int $id): array {
        $result = $this->materialModel->deleteComment($id);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }
        return $result;
    }
}
