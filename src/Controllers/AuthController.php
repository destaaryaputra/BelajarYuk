<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Utils\Response;
use App\Utils\Security;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CSRFMiddleware;
use Exception;

/**
 * Auth Controller
 * Refactored to use AuthService (Service Layer Pattern)
 */

class AuthController {
    private ?User $userModel;
    private AuthService $authService;

    public function __construct(AuthService $authService = null) {
        $this->userModel = new User();
        $this->authService = $authService ?? new AuthService($this->userModel);
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Metode permintaan tidak valid.', null, 405);
        }

        CSRFMiddleware::verify();

        try {
            $rawInput = file_get_contents("php://input");
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Payload JSON tidak valid.', null, 400);
                return;
            }
            $result = $this->authService->register($data);
            Response::success($result['message'], ['user_id' => $result['user_id']], 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 400);
        }
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Metode permintaan tidak valid.', null, 405);
        }

        CSRFMiddleware::verify();

        try {
            $rawInput = file_get_contents("php://input");
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Payload JSON tidak valid.', null, 400);
                return;
            }
            $username = Security::sanitize($data['username'] ?? '');
            $password = $data['password'] ?? '';

            $result = $this->authService->login($username, $password);
            
            // Simpan ke session (pastikan session sudah dimulai)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['auth_token'] = $result['token'];
            $_SESSION['user'] = $result['user'];
            
            Response::success($result['message'], [
                'token' => $result['token'], 
                'user' => $result['user']
            ], 200);
        } catch (Exception $e) {
            $code = $e->getCode();
            Response::error($e->getMessage(), null, is_numeric($code) && $code >= 400 ? $code : 401);
        }
    }

    public function logout(): void {
        CSRFMiddleware::verify();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        Response::success('Kamu berhasil keluar.');
    }

    public function getCurrentUser(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        
        // Update Streak on every heartbeat
        $this->userModel->updateStreak($user['id']);
        
        // Refetch to get updated streak_count
        $freshUser = $this->userModel->getUserById($user['id']);
        $freshUser['role'] = $user['role'];
        
        Response::success('Data pengguna berhasil dimuat', $freshUser);
    }

    public function updateAvatar(): void {
        AuthMiddleware::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Metode permintaan tidak valid.', null, 405);
        }
        CSRFMiddleware::verify();

        try {
            $user = AuthMiddleware::getAuthUser();
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['size'] === 0) {
                Response::error('Pilih file foto profil dulu ya.', null, 400);
            }

            $upload_result = \App\Services\UploadService::uploadAvatar($_FILES['avatar']);
            if (!$upload_result['success']) {
                Response::error($upload_result['message'], null, 400);
            }

            $result = $this->userModel->updateAvatar($user['id'], $upload_result['filename']);
            if ($result['success']) {
                Response::success($result['message'], ['avatar' => $upload_result['filename']]);
            } else {
                Response::error($result['message'], null, 500);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    public function updateProfile(): void {
        AuthMiddleware::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Metode permintaan tidak valid.', null, 405);
        }
        CSRFMiddleware::verify();

        try {
            $user = AuthMiddleware::getAuthUser();
$rawInput = file_get_contents("php://input");
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Payload JSON tidak valid.', null, 400);
                return;
            }
            
            $updateData = [
                'full_name' => Security::sanitize($data['full_name'] ?? ''),
                'username' => Security::sanitize($data['username'] ?? ''),
                'email' => Security::sanitize($data['email'] ?? ''),
                'bio' => Security::sanitize($data['bio'] ?? ''),
                'password' => $data['password'] ?? null
            ];

            if (empty($updateData['full_name'])) throw new Exception('Nama lengkap tidak boleh kosong.', 400);

            $result = $this->userModel->updateProfile($user['id'], $updateData);

            if ($result['success']) {
                $updatedUser = $this->userModel->getUserById($user['id']);
                $updatedUser['role'] = $user['role'];
                
                // Update Session
                $_SESSION['user'] = $updatedUser;
                
                Response::success($result['message'], ['user' => $updatedUser]);
            } else {
                Response::error($result['message'], null, 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), null, 400);
        }
    }

    public function getAllUsers(): void {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getAuthUser();
        if ($user['role'] !== 'admin') {
            Response::error('Maaf, hanya Admin yang bisa mengakses fitur ini.', null, 403);
            return;
        }

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        // Batasi nilai limit agar tidak mengakibatkan beban memori berlebih
        $limit = min($limit, 100);

        $users = $this->userModel->getAllUsers($page, $limit);
        $total = $this->userModel->getTotalUsersCount();

        Response::success('Daftar pengguna berhasil dimuat', [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    public function updateUserRole(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        
        if ($user['role'] !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Payload JSON tidak valid.', null, 400);
            return;
        }
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : null;
        $role = Security::sanitize($data['role'] ?? '');

        if (!$user_id || !in_array($role, ['student', 'admin'])) {
            Response::error('Data tidak valid.', null, 400);
            return;
        }
        
        if ($user_id === $user['id']) {
            Response::error('Tidak bisa mengubah role sendiri.', null, 403);
            return;
        }

        $result = $this->userModel->updateUserRole($user_id, $role);
        if ($result['success']) Response::success($result['message']);
        else Response::error($result['message'], null, 500);
    }

    public function deleteUser(): void {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verify();
        $user = AuthMiddleware::getAuthUser();
        
        if ($user['role'] !== 'admin') {
            Response::error('Akses ditolak.', null, 403);
            return;
        }

        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Payload JSON tidak valid.', null, 400);
            return;
        }
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : null;

        if (!$user_id) {
            Response::error('Pilih pengguna dulu.', null, 400);
            return;
        }

        if ($user_id === $user['id']) {
            Response::error('Tidak bisa menghapus diri sendiri.', null, 403);
            return;
        }

        $result = $this->userModel->deleteUser($user_id);
        if ($result['success']) Response::success($result['message']);
        else Response::error($result['message'], null, 500);
    }
}
