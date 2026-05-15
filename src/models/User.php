<?php

namespace App\Models;

use App\Config\Database;
use App\Utils\Security;
use PDO;
use Exception;

/**
 * User Model
 * Handle semua operasi yang berhubungan dengan user
 */

class User {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create user baru (registrasi)
     */
    public function register($username, $email, $password, $full_name) {
        try {
            // Check if user already exists
            $query = "SELECT id FROM pengguna WHERE email = ? OR username = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email, $username]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email atau username sudah terdaftar.'];
            }

            // Hash password
            $hashed_password = Security::hashPassword($password);

            // Insert user
            $query = "INSERT INTO pengguna (username, email, password, full_name, created_at) 
                     VALUES (?, ?, ?, ?, NOW()) RETURNING id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username, $email, $hashed_password, $full_name]);

            return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.', 'user_id' => $stmt->fetchColumn()];
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan saat registrasi.'];
        }
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            error_log("Attempting login for username: " . $username);
            $query = "SELECT id, username, email, full_name, password, role FROM pengguna WHERE username = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username]);

            if ($stmt->rowCount() === 0) {
                error_log("Login failed: Username not found: " . $username);
                return ['success' => false, 'message' => 'Username atau password salah.'];
            }

            $user = $stmt->fetch();

            if (!Security::verifyPassword($password, $user['password'])) {
                error_log("Login failed: Password mismatch for user: " . $username);
                return ['success' => false, 'message' => 'Username atau password salah.'];
            }
            error_log("Login success for user: " . $username);

            // Generate JWT token
            $token = Security::generateJWT([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]);

            return [
                'success' => true,
                'message' => 'Login berhasil!',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
        } catch (Exception $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan saat login.'];
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        try {
            $query = "SELECT id, username, email, full_name, avatar, bio, created_at FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($user_id, $full_name, $bio = null, $avatar = null) {
        try {
            $query = "UPDATE pengguna SET full_name = ?, bio = ?";
            $params = [$full_name, $bio];

            if ($avatar) {
                $query .= ", avatar = ?";
                $params[] = $avatar;
            }

            $query .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Profil berhasil diperbarui.'];
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui profil.'];
        }
    }

    /**
     * Change password
     */
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            $query = "SELECT password FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'User tidak ditemukan.'];
            }

            $user = $stmt->fetch();

            if (!Security::verifyPassword($old_password, $user['password'])) {
                return ['success' => false, 'message' => 'Password lama salah.'];
            }

            $hashed_password = Security::hashPassword($new_password);
            $query = "UPDATE pengguna SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$hashed_password, $user_id]);

            return ['success' => true, 'message' => 'Password berhasil diubah.'];
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengubah password.'];
        }
    }

    /**
     * Mengambil daftar seluruh pengguna (Dengan Pagination untuk Admin)
     */
    public function getAllUsers($page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            $query = "SELECT id, username, email, full_name, role, created_at 
                     FROM pengguna 
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Hitung total user untuk pagination
     */
    public function getTotalUsersCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM pengguna");
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Update role pengguna
     */
    public function updateUserRole($user_id, $role) {
        try {
            $stmt = $this->db->prepare("UPDATE pengguna SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
            }
            return ['success' => true, 'message' => 'Role pengguna berhasil diperbarui.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal memperbarui role pengguna.'];
        }
    }

    /**
     * Hapus pengguna
     */
    public function deleteUser($user_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM pengguna WHERE id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
            }
            return ['success' => true, 'message' => 'Pengguna berhasil dihapus.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal menghapus pengguna.'];
        }
    }
}
