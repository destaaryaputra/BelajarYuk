<?php

namespace App\Services;

use App\Models\User;
use App\Utils\Security;
use Exception;

class AuthService {
    private User $userModel;

    public function __construct(User $userModel = null) {
        $this->userModel = $userModel ?? new User();
    }

    /**
     * Handle user registration logic
     */
    public function register(array $data): array {
        $username = Security::sanitize($data['username'] ?? '');
        $email = strtolower(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $data['password'] ?? '';
        $full_name = Security::sanitize($data['full_name'] ?? '');

        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            throw new Exception('Pastikan semua kolom formulir sudah diisi ya.', 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            throw new Exception('Username harus 3-30 karakter dan hanya boleh berisi huruf, angka, atau underscore.', 422);
        }

        if (!Security::validateEmail($email)) {
            throw new Exception('Format email belum valid.', 422);
        }

        if (strlen($password) < 8) {
            throw new Exception('Kata sandi minimal 8 karakter agar akun lebih aman.', 422);
        }

        $result = $this->userModel->register($username, $email, $password, $full_name);
        if (!$result['success']) {
            throw new Exception($result['message'], 400);
        }

        return $result;
    }

    /**
     * Handle user login logic
     */
    public function login(string $username, string $password): array {
        if (empty($username) || empty($password)) {
            throw new Exception('Username dan kata sandi tidak boleh kosong.', 400);
        }

        $result = $this->userModel->login($username, $password);
        
        if (!$result['success']) {
            // Secure generic error message to prevent user enumeration
            throw new Exception('Username atau kata sandi yang kamu masukkan kurang tepat. Silakan cek kembali.', 401);
        }

        return $result;
    }
}
