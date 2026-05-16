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

        if (!$this->host || !$this->user || !$this->password || !$this->db) {
            $missing = [];
            if (!$this->host) $missing[] = 'DB_HOST';
            if (!$this->user) $missing[] = 'DB_USER';
            if (!$this->password) $missing[] = 'DB_PASS';
            if (!$this->db) $missing[] = 'DB_NAME';
            
            error_log("CRITICAL: Database credentials missing: " . implode(', ', $missing));
            http_response_code(500);
            header('Content-Type: application/json');
            exit(json_encode([
                'success' => false, 
                'message' => 'Konfigurasi database (env) tidak lengkap.',
                'missing_vars' => $missing,
                'version' => 'FIX-IDENTIFIER-V1'
            ]));
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
            // Supabase Pooler memerlukan sslmode=require untuk identifikasi tenant (SNI)
            // Pastikan tidak ada spasi di sekitar DB Name
            $dbName = trim($this->db);
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$dbName};sslmode=require";
            
            return new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            
            // SUPER DEBUG: Tampilkan semua status variabel
            exit(json_encode([
                'success' => false, 
                'message' => 'Gagal terhubung ke database Supabase.',
                'debug_message' => $e->getMessage(),
                'version' => 'SUPER-DIAGNOSTIC-V2',
                'env_status' => [
                    'DB_HOST' => $this->host ? 'LOADED' : 'MISSING',
                    'DB_NAME' => $this->db ? "LOADED ('{$this->db}')" : 'MISSING',
                    'DB_USER' => $this->user ? 'LOADED' : 'MISSING',
                    'DB_PORT' => $this->port,
                    'SSL_MODE' => 'require'
                ],
                'dsn_generated' => "pgsql:host={$this->host};port={$this->port};dbname={$this->db};sslmode=require",
                'php_version' => PHP_VERSION,
                'pdo_drivers' => PDO::getAvailableDrivers()
            ]));
        }
    }

    // Keep for backward compatibility while refactoring models
    public function getConnection() {
        return self::getInstance();
    }
}

