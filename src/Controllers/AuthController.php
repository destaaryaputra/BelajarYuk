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
            $data = json_decode(file_get_contents("php://input"), true);
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
            $data = json_decode(file_get_contents("php://input"), true);
            $username = Security::sanitize($data['username'] ?? '');
            $password = $data['password'] ?? '';

            $result = $this->authService->login($username, $password);
            
            // Simpan ke session (Session sudah di-start oleh router/index)
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
        Response::success('Data pengguna berhasil dimuat', $user);
    }

    public function updateProfile(): void {
        AuthMiddleware::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Metode permintaan tidak valid.', null, 405);
        }
        CSRFMiddleware::verify();

        try {
            $user = AuthMiddleware::getAuthUser();
            $data = json_decode(file_get_contents("php://input"), true);
            
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

        $data = json_decode(file_get_contents("php://input"), true);
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

        $data = json_decode(file_get_contents("php://input"), true);
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
