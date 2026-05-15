<?php
namespace App\Services;

use App\Utils\Security;
use Exception;

class UploadService {
    public static function uploadThumbnail(array $file): array {
        try {
            if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
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

            $uploadDir = UPLOADS_PATH . '/thumbnails/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'thumb_' . bin2hex(random_bytes(8)) . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                return ['success' => false, 'message' => 'Gagal menyimpan file gambar ke server.'];
            }

            return ['success' => true, 'filename' => $filename];
        } catch (Exception $e) {
            error_log('Thumbnail upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memproses gambar.'];
        }
    }

    public static function uploadPdf(array $file): array {
        try {
            $mimeType = self::detectMimeType($file['tmp_name'] ?? '');
            if ($mimeType !== 'application/pdf') {
                return ['success' => false, 'message' => 'File yang diunggah harus berupa dokumen PDF!'];
            }

            $uploadDir = PUBLIC_PATH . '/assets/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = Security::sanitizeFilename(time() . '_' . ($file['name'] ?? 'document.pdf'));
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                return ['success' => false, 'message' => 'Gagal menyimpan dokumen PDF ke server.'];
            }

            return ['success' => true, 'filename' => $filename];
        } catch (Exception $e) {
            error_log('PDF upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memproses dokumen.'];
        }
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
