<?php
namespace App\Services;

use App\Utils\Security;
use Exception;

class UploadService {
    private static function getSupabaseConfig(): array {
        return [
            'url' => getenv('SUPABASE_URL'),
            'key' => getenv('SUPABASE_KEY'),
            'bucket' => getenv('SUPABASE_STORAGE_BUCKET') ?: 'belajaryuk'
        ];
    }

    public static function uploadThumbnail(array $file): array {
        try {
            // Pastikan semua atribut file diperlukan ada
            if (!isset($file['name'], $file['tmp_name'], $file['size'])) {
                return ['success' => false, 'message' => 'File tidak lengkap.'];
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                return ['success' => false, 'message' => 'Waduh, file fotonya terlalu besar! Maksimal 2MB ya.'];
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                return ['success' => false, 'message' => 'Maaf, format file tidak didukung. Gunakan JPG, PNG, atau WEBP ya!'];
            }

            $mimeType = self::detectMimeType($file['tmp_name'] ?? '');
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                return ['success' => false, 'message' => 'Hayo, file yang kamu unggah bukan gambar asli ya?'];
            }

            $filename = 'thumbnails/thumb_' . bin2hex(random_bytes(8)) . '.' . $extension;
            return self::uploadToSupabase($file['tmp_name'], $filename, $mimeType);

        } catch (Exception $e) {
            error_log('Thumbnail upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memproses gambar.'];
        }
    }

    public static function uploadAvatar(array $file): array {
        try {
            // Validasi keberadaan kunci file
            if (!isset($file['name'], $file['tmp_name'], $file['size'])) {
                return ['success' => false, 'message' => 'File tidak lengkap.'];
            }
            if ($file['size'] > 1 * 1024 * 1024) {
                return ['success' => false, 'message' => 'Foto profil terlalu besar! Maksimal 1MB ya.'];
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.'];
            }

            $mimeType = self::detectMimeType($file['tmp_name'] ?? '');
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                return ['success' => false, 'message' => 'File bukan gambar asli.'];
            }

            $filename = 'avatars/av_' . bin2hex(random_bytes(8)) . '.' . $extension;
            return self::uploadToSupabase($file['tmp_name'], $filename, $mimeType);

        } catch (Exception $e) {
            error_log('Avatar upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memproses foto profil.'];
        }
    }

    public static function uploadPdf(array $file): array {
        try {
            // Validasi keberadaan kunci file
            if (!isset($file['name'], $file['tmp_name'])) {
                return ['success' => false, 'message' => 'File tidak lengkap.'];
            }
            $mimeType = self::detectMimeType($file['tmp_name'] ?? '');
            if ($mimeType !== 'application/pdf') {
                return ['success' => false, 'message' => 'File yang diunggah harus berupa dokumen PDF!'];
            }

            $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            $filename = 'documents/' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
            
            return self::uploadToSupabase($file['tmp_name'], $filename, $mimeType);
        } catch (Exception $e) {
            error_log('PDF upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memproses dokumen.'];
        }
    }

    private static function uploadToSupabase(string $filePath, string $storagePath, string $mimeType): array {
        $config = self::getSupabaseConfig();
        
        if (!$config['url'] || !$config['key']) {
            if (!defined('ENV') || ENV !== 'production') {
                return self::uploadToLocal($filePath, $storagePath);
            }

            error_log('CRITICAL: Supabase Storage configuration missing!');
            return ['success' => false, 'message' => 'Konfigurasi penyimpanan cloud belum siap.'];
        }

        $url = rtrim($config['url'], '/') . "/storage/v1/object/" . $config['bucket'] . "/" . $storagePath;
        $fileData = file_get_contents($filePath);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $config['key'],
            "apikey: " . $config['key'],
            "Content-Type: " . $mimeType
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            // Kita kembalikan full URL atau path yang bisa diakses publik
            $publicUrl = rtrim($config['url'], '/') . "/storage/v1/object/public/" . $config['bucket'] . "/" . $storagePath;
            return ['success' => true, 'filename' => $publicUrl];
        }

        error_log("Supabase Storage Error ($httpCode): " . $response);
        return ['success' => false, 'message' => 'Gagal mengunggah file ke cloud storage.'];
    }

    private static function uploadToLocal(string $filePath, string $storagePath): array {
        $relativePath = str_replace('\\', '/', ltrim($storagePath, '/'));

        if ($relativePath === '' || strpos($relativePath, '..') !== false) {
            return ['success' => false, 'message' => 'Path penyimpanan tidak valid.'];
        }

        $uploadRoot = defined('UPLOADS_PATH')
            ? UPLOADS_PATH
            : dirname(__DIR__, 2) . '/public/uploads';

        $targetPath = rtrim($uploadRoot, '/\\') . '/' . $relativePath;
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            error_log('Local upload error: failed to create directory ' . $targetDir);
            return ['success' => false, 'message' => 'Folder penyimpanan lokal belum siap.'];
        }

        $stored = is_uploaded_file($filePath)
            ? move_uploaded_file($filePath, $targetPath)
            : copy($filePath, $targetPath);

        if (!$stored) {
            error_log('Local upload error: failed to store file at ' . $targetPath);
            return ['success' => false, 'message' => 'Gagal menyimpan file secara lokal.'];
        }

        return ['success' => true, 'filename' => $relativePath];
    }

    private static function detectMimeType(string $path): string {
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }

        $mimeType = finfo_file($finfo, $path) ?: '';
        finfo_close($finfo);

        return $mimeType;
    }
}
