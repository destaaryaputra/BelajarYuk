<?php
/**
 * Environment Configuration
 * Security constants dan settings utama aplikasi
 */

// Load .env file if exists
$baseDir = dirname(dirname(__DIR__));
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';

// Fallback path check
if (!file_exists($envFile)) {
    $envFile = dirname(__DIR__, 2) . '/.env';
}

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    $content = str_replace("\xEF\xBB\xBF", "", $content); // Remove BOM
    
    // Split lines more robustly
    $lines = preg_split('/\r\n|\r|\n/', $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Simpler key=value split
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            $value = preg_replace('/^["\'](.*)["\']$/', '$1', $value);
            
            // Remove inline comments
            if (strpos($value, '#') !== false) {
                $value = trim(explode('#', $value)[0]);
            }
            
            // HANYA isi jika belum ada di environment (PENTING untuk Vercel)
            if (getenv($name) === false || getenv($name) === '') {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
} else {
    // Di Vercel ini normal, karena env variables diset di dashboard
    // error_log("INFO: .env file not found. Using system environment variables.");
}

// Environment
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
define('ENV', $appEnv); // development, staging, production
define('DEBUG', ENV === 'development');

// Security
$jwtSecret = trim((string)($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: ''));
if ($jwtSecret === '') {
    if ($appEnv === 'production') {
        error_log('FATAL: JWT_SECRET is missing in production environment.');
        http_response_code(500);
        exit('Server misconfiguration.');
    }

    // Ephemeral secret for local/dev to avoid hardcoded insecure fallback.
    $jwtSecret = bin2hex(random_bytes(32));
    error_log('WARNING: JWT_SECRET is not set. Using ephemeral runtime secret for non-production.');
}
define('JWT_SECRET', $jwtSecret);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// AI (Groq) - Deteksi lebih cerdas: cari yang tidak kosong
$groqApiKey = '';
$potentialKeys = [
    $_ENV['GROQ_API_KEY'] ?? null,
    getenv('GROQ_API_KEY'),
    $_SERVER['GROQ_API_KEY'] ?? null
];

foreach ($potentialKeys as $key) {
    if ($key && trim($key) !== '') {
        $groqApiKey = trim($key);
        break;
    }
}

define('GROQ_API_KEY', $groqApiKey);

// Error Handling (Cegah kebocoran error ke JSON response)
if (ENV === 'development') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');

// Timezone
date_default_timezone_set('Asia/Jakarta');

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

// CORS untuk API (strict allowlist; no origin reflection)
$corsAllowedRaw = (string)($_ENV['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: '');
$corsAllowedOrigins = array_values(array_filter(array_map('trim', explode(',', $corsAllowedRaw))));

if (count($corsAllowedOrigins) === 0) {
    $defaultOrigins = ['http://localhost', 'http://127.0.0.1', 'http://localhost:3000', 'http://127.0.0.1:3000'];
    $appUrlHost = parse_url((string) APP_URL, PHP_URL_HOST);
    $appUrlScheme = parse_url((string) APP_URL, PHP_URL_SCHEME) ?: 'http';
    $appUrlPort = parse_url((string) APP_URL, PHP_URL_PORT);

    if ($appUrlHost) {
        $origin = $appUrlScheme . '://' . $appUrlHost;
        if ($appUrlPort) {
            $origin .= ':' . $appUrlPort;
        }
        $defaultOrigins[] = $origin;
    }

    $corsAllowedOrigins = array_values(array_unique($defaultOrigins));
}

$httpOrigin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($httpOrigin !== '' && in_array($httpOrigin, $corsAllowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $httpOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
