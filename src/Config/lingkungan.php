<?php
/**
 * Environment Configuration
 * Security constants dan settings utama aplikasi
 */

// Load .env file if exists
$baseDir = dirname(dirname(__DIR__));
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Overwrite existing env vars
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Environment
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
define('ENV', $appEnv); // development, staging, production
define('DEBUG', ENV === 'development');

// Security
$jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
if (!$jwtSecret) {
    error_log('WARNING: JWT_SECRET is not set. Using insecure fallback.');
    $jwtSecret = 'fallback-insecure-secret-change-me';
}
define('JWT_SECRET', $jwtSecret);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// AI (Groq)
define('GROQ_API_KEY', $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: '');

// Error Handling (Cegah kebocoran error ke JSON response)
if (ENV === 'development') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');

// Paths
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEWS_PATH', BASE_PATH . '/views');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// App Settings
define('APP_NAME', 'Belajaryuk');
define('APP_URL', $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/belajaryuk');

// Session cookie hardening (must be set before session_start)
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    
    // Perbaikan kompatibilitas versi PHP agar tidak Fatal Error di XAMPP
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/', '', $secureCookie, true);
    }
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS untuk API
$allowedOrigin = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/belajaryuk';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
