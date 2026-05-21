<?php

namespace App\Controllers;

use App\Services\MaterialService;
use App\Utils\Response;
use App\Utils\Security;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CSRFMiddleware;
use Exception;

/**
 * Material Controller
 * Refactored to use MaterialService (Service Layer Pattern)
 */

class MaterialController {
    private MaterialService $materialService;

    public function __construct(MaterialService $materialService = null) {
        $this->materialService = $materialService ?? new MaterialService();
    }

    public function getAllMaterials(): void {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
            $category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : null;

            $result = $this->materialService->getPaginatedMaterials($page, $limit, $category);
            Response::success('Materi berhasil diambil', $result);
        } catch (Exception $e) {
            error_log("Get materials error: " . $e->getMessage());
            Response::error('Gagal mengambil materi', null, 500);
        }
    }

    public function getMaterial(): void {
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if (!$id) Response::error('Pilih materi pembelajarannya dulu ya.', null, 400);

            $userId = null;
            try {
                if (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_SESSION['user'])) {
                    $user = AuthMiddleware::getAuthUser();
                    $userId = $user['id'] ?? null;
                }
            } catch (Exception $e) {}

            $result = $this->materialService->getMaterialDetail($id, $userId);
            Response::success('Detail materi berhasil diambil', $result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function getCategories(): void {
        try {
            Response::success('Kategori berhasil diambil', $this->materialService->getCategories());
        } catch (Exception $e) {
            Response::error('Gagal mengambil kategori', null, 500);
        }
    }

    public function markAsCompleted(): void {
        AuthMiddleware::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Aksi tidak valid.', null, 405);
        CSRFMiddleware::verify();

        try {
            $rawInput = file_get_contents("php://input");
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Payload JSON tidak valid.', null, 400);
                return;
            }
            $materialId = intval($data['material_id'] ?? 0);
            $subMaterialId = isset($data['sub_material_id']) ? intval($data['sub_material_id']) : null;
            
            if (!$materialId) {
                Response::error('Pilih materi dulu ya.', null, 400);
                return;
            }

            $user = AuthMiddleware::getAuthUser();
            $result = $this->materialService->markAsCompleted($user['id'], $materialId, $subMaterialId);
            Response::success($result['message']);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function createMaterial(): void {
        AuthMiddleware::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Aksi tidak valid.', null, 405);
        CSRFMiddleware::verify();

        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Maaf, hanya Admin yang boleh menambahkan materi.', null, 403);
            return;
        }

        try {
            $result = $this->materialService->createMaterial($_POST, $_FILES);
            Response::success($result['message'], ['material_id' => $result['material_id']], 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function updateMaterial(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Aksi tidak valid.', null, 405);

        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if (!$id) Response::error('ID materi diperlukan.', null, 400);

            $result = $this->materialService->updateMaterial($id, $_POST, $_FILES);
            Response::success($result['message']);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function deleteMaterial(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Aksi tidak valid.', null, 405);

        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = intval($data['material_id'] ?? 0);
            if (!$id) Response::error('ID materi diperlukan.', null, 400);

            $result = $this->materialService->deleteMaterial($id);
            Response::success($result['message']);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function getSubMaterialsAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $materialId = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
            if (!$materialId) Response::error('ID materi diperlukan.', null, 400);

            $result = $this->materialService->getSubMaterialsAdmin($materialId);
            Response::success('Daftar episode berhasil dimuat', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function createSubMaterial(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $result = $this->materialService->createSubMaterial($_POST, $_FILES);
            Response::success($result['message']);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function updateSubMaterial(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
            if (!$id) Response::error('ID episode diperlukan.', null, 400);

            $result = $this->materialService->updateSubMaterial($id, $_POST, $_FILES);
            Response::success($result['message']);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 500);
        }
    }

    public function deleteSubMaterial(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = intval($data['id'] ?? 0);
            if (!$id) Response::error('ID episode diperlukan.', null, 400);

            $result = $this->materialService->deleteSubMaterial($id);
            Response::success($result['message']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function getComments(): void {
        try {
            $materialId = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
            if (!$materialId) Response::error('ID materi diperlukan.', null, 400);

            $result = $this->materialService->getComments($materialId);
            Response::success('Komentar berhasil diambil', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function addComment(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $materialId = intval($data['material_id'] ?? 0);
            $text = $data['comment_text'] ?? '';

            if (!$materialId || empty($text)) {
                Response::error('Data komentar tidak lengkap.', null, 400);
            }

            $result = $this->materialService->addComment($materialId, $user['id'], $text);
            Response::success($result['message']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function getAllCommentsAdmin(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            $result = $this->materialService->getAllCommentsAdmin($limit);
            Response::success('Seluruh komentar berhasil dimuat', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function deleteCommentAdmin(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        if (($user['role'] ?? '') !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = intval($data['id'] ?? 0);
            if (!$id) Response::error('ID komentar diperlukan.', null, 400);

            $result = $this->materialService->deleteCommentAdmin($id);
            Response::success($result['message']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }
}
