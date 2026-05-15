<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Database Configuration
 * Secure database connection setup dengan error handling
 */

class Database {
    private static ?PDO $instance = null;
    private ?string $host;
    private ?string $db;
    private ?string $user;
    private ?string $password;
    private ?string $port;

    public function __construct() {
        if (!defined('BASE_PATH')) {
            $env_file = __DIR__ . '/lingkungan.php';
            if (file_exists($env_file)) require_once $env_file;
        }

        $this->host = $this->getEnvVar('DB_HOST');
        $this->db = $this->getEnvVar('DB_NAME');
        $this->user = $this->getEnvVar('DB_USER');
        $this->password = $this->getEnvVar('DB_PASS');
        $this->port = $this->getEnvVar('DB_PORT', '5432');

        if (!$this->host || !$this->user || !$this->password) {
            error_log("CRITICAL: Database credentials missing in environment configuration!");
            http_response_code(500);
            header('Content-Type: application/json');
            exit(json_encode(['success' => false, 'message' => 'Konfigurasi database belum lengkap. Periksa file .env.']));
        }
    }

    /**
     * Helper to get environment variables from multiple sources
     */
    private function getEnvVar(string $key, $default = null) {
        $value = getenv($key);
        if ($value !== false) return $value;
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];
        return $default;
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $db = new self();
            self::$instance = $db->connect();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db}";
            return new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            http_response_code(500);
            exit(json_encode(['success' => false, 'message' => 'Gagal terhubung ke database. Silakan coba beberapa saat lagi.']));
        }
    }

    // Keep for backward compatibility while refactoring models
    public function getConnection() {
        return self::getInstance();
    }
}

