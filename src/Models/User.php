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
     * Update user daily streak
     */
    public function updateStreak(int $user_id): void {
        try {
            // Ensure columns exist (Safeguard)
            $this->db->exec("ALTER TABLE pengguna ADD COLUMN IF NOT EXISTS streak_count INT DEFAULT 0");
            $this->db->exec("ALTER TABLE pengguna ADD COLUMN IF NOT EXISTS last_active_date DATE");

            $query = "SELECT streak_count, last_active_date FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return;

            $today = date('Y-m-d');
            $last_date = $user['last_active_date'];
            $streak = (int) $user['streak_count'];

            if ($last_date === $today) {
                // Already active today, no change
                return;
            }

            $yesterday = date('Y-m-d', strtotime('-1 day'));

            if ($last_date === $yesterday) {
                // Consecutive day!
                $streak++;
            } else {
                // Broke the streak, reset to 1
                $streak = 1;
            }

            $update = "UPDATE pengguna SET streak_count = ?, last_active_date = ? WHERE id = ?";
            $stmt = $this->db->prepare($update);
            $stmt->execute([$streak, $today, $user_id]);

        } catch (Exception $e) {
            error_log("Update streak error: " . $e->getMessage());
        }
    }

    /**
     * Create user baru (registrasi)
     */
    public function register(string $username, string $email, string $password, string $full_name): array {
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
    public function login(string $username, string $password): array {
        try {
            $query = "SELECT id, username, email, full_name, password, role FROM pengguna WHERE username = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Username atau password salah.'];
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!Security::verifyPassword($password, $user['password'])) {
                return ['success' => false, 'message' => 'Username atau password salah.'];
            }

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
     * Update user avatar
     */
    public function updateAvatar(int $user_id, string $avatar_filename): array {
        try {
            $query = "UPDATE pengguna SET avatar = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$avatar_filename, $user_id]);
            return ['success' => true, 'message' => 'Foto profil berhasil diperbarui!'];
        } catch (Exception $e) {
            error_log("Update avatar error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui foto profil.'];
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?array {
        try {
            $query = "SELECT id, username, email, full_name, avatar, bio, created_at, streak_count FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user profile with safety checks
     */
    public function updateProfile(int $user_id, array $data): array {
        try {
            $full_name = $data['full_name'] ?? null;
            $username = $data['username'] ?? null;
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            $bio = $data['bio'] ?? null;

            // Check uniqueness if username or email changes
            if ($username || $email) {
                $checkQuery = "SELECT id FROM pengguna WHERE (username = ? OR email = ?) AND id != ?";
                $stmt = $this->db->prepare($checkQuery);
                $stmt->execute([$username, $email, $user_id]);
                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Username atau email sudah digunakan pengguna lain.'];
                }
            }

            $fields = [];
            $params = [];

            if ($full_name) { $fields[] = "full_name = ?"; $params[] = $full_name; }
            if ($username) { $fields[] = "username = ?"; $params[] = $username; }
            if ($email) { $fields[] = "email = ?"; $params[] = $email; }
            if ($bio !== null) { $fields[] = "bio = ?"; $params[] = $bio; }
            
            if ($password && !empty($password)) {
                $fields[] = "password = ?";
                $params[] = Security::hashPassword($password);
            }

            if (empty($fields)) {
                return ['success' => true, 'message' => 'Tidak ada perubahan.'];
            }

            $params[] = $user_id;
            $query = "UPDATE pengguna SET " . implode(", ", $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Profil berhasil diperbarui!'];
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui profil.'];
        }
    }

    /**
     * Change password
     */
    public function changePassword(int $user_id, string $old_password, string $new_password): array {
        try {
            $query = "SELECT password FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'User tidak ditemukan.'];
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
    public function getAllUsers(int $page = 1, int $limit = 50): array {
        try {
            $offset = ($page - 1) * $limit;
            $query = "SELECT id, username, email, full_name, role, created_at 
                     FROM pengguna 
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
    public function getTotalUsersCount(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM pengguna");
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Get total users count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update role pengguna
     */
    public function updateUserRole(int $user_id, string $role): array {
        try {
            $stmt = $this->db->prepare("UPDATE pengguna SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
            }
            return ['success' => true, 'message' => 'Role pengguna berhasil diperbarui.'];
        } catch (Exception $e) {
            error_log("Update user role error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui role pengguna.'];
        }
    }

    /**
     * Hapus pengguna
     */
    public function deleteUser(int $user_id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM pengguna WHERE id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
            }
            return ['success' => true, 'message' => 'Pengguna berhasil dihapus.'];
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus pengguna.'];
        }
    }
}
